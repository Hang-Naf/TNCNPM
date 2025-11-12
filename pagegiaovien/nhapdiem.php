<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Giáo viên ====
if ($_SESSION["vaiTro"] !== "GiaoVien") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$maGV = $_SESSION["userID"];

// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = ? LIMIT 1";
$stmtGV = $conn->prepare($sqlGV);
$stmtGV->bind_param("i", $maGV);
$stmtGV->execute();
$gv = $stmtGV->get_result()->fetch_assoc() ?: ['hoVaTen' => 'Giáo viên'];

// ==== Lấy lớp và môn dạy của giáo viên ====
$sqlLop = "SELECT DISTINCT l.maLop, l.tenLop
           FROM lophoc l
           JOIN lophoc_monhoc lm ON lm.maLop = l.maLop
           WHERE lm.maGV = ?";
$stmtL = $conn->prepare($sqlLop);
$stmtL->bind_param("i", $maGV);
$stmtL->execute();
$dsLop = $stmtL->get_result();

$sqlMon = "SELECT m.maMonHoc, m.tenMonHoc
           FROM monhoc m
           JOIN lophoc_monhoc lm ON lm.maMonHoc = m.maMonHoc
           WHERE lm.maGV = ?";
$stmtM = $conn->prepare($sqlMon);
$stmtM->bind_param("i", $maGV);
$stmtM->execute();
$dsMon = $stmtM->get_result();

$message = "";

// ==== Khi submit form ====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $maHS = intval($_POST["maHS"] ?? 0);
    $maMon = intval($_POST["maMonHoc"] ?? 0);

    // Danh sách loại điểm
    $cacLoai = [
        'hk1_mieng',
        'hk1_1tiet',
        'hk1_thiGK',
        'hk1_thiCK',
        'hk2_mieng',
        'hk2_1tiet',
        'hk2_thiGK',
        'hk2_thiCK'
    ];

    // Kiểm tra ít nhất 1 điểm được nhập (không phải chuỗi rỗng)
    $hasAny = false;
    foreach ($cacLoai as $loai) {
        if (isset($_POST[$loai]) && trim((string)$_POST[$loai]) !== '') {
            // optional: chỉ coi là hợp lệ khi là số
            if (is_numeric($_POST[$loai])) {
                $hasAny = true;
                break;
            } else {
                // nếu có giá trị nhưng không phải số -> thông báo lỗi ngay
                $message = "Giá trị điểm phải là số (0 - 10). Vui lòng kiểm tra lại.";
                break;
            }
        }
    }

    if ($message === "" && !$hasAny) {
        $message = "Vui lòng nhập ít nhất 1 điểm (không để tất cả trống).";
    }

    // Nếu không có lỗi thì lưu vào CSDL
    if ($message === "") {
        $savedCount = 0;

        // chuẩn bị truy vấn kiểm tra tồn tại
        $checkSql = "SELECT 1 FROM diemso WHERE maHS = ? AND maMonHoc = ? AND loaiDiem = ? LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        if (!$checkStmt) {
            $message = "Lỗi chuẩn bị truy vấn kiểm tra: " . $conn->error;
        } else {
            // chuẩn bị truy vấn insert
            $insertSql = "INSERT INTO diemso (maHS, maMonHoc, loaiDiem, diem, ngayCapNhat)
                      VALUES (?, ?, ?, ?, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            if (!$insertStmt) {
                $message = "Lỗi chuẩn bị truy vấn lưu điểm: " . $conn->error;
            } else {
                foreach ($cacLoai as $loai) {
                    $raw = $_POST[$loai] ?? '';
                    if ($raw !== '' && is_numeric($raw)) {
                        $diem = floatval($raw);
                        if ($diem < 0 || $diem > 10) {
                            $message = "Giá trị điểm phải nằm trong khoảng 0 - 10.";
                            break;
                        }

                        // kiểm tra trùng
                        $checkStmt->bind_param("iis", $maHS, $maMon, $loai);
                        $checkStmt->execute();
                        $res = $checkStmt->get_result();
                        if ($res && $res->num_rows > 0) {
                            $message = "⚠️ Điểm $loai cho học sinh #$maHS môn #$maMon đã tồn tại. Không thể nhập trùng.";
                            break;
                        }

                        // nếu chưa tồn tại thì insert
                        $insertStmt->bind_param("iisd", $maHS, $maMon, $loai, $diem);
                        if ($insertStmt->execute()) {
                            $savedCount++;
                            write_log($conn, $maGV, "Nhập điểm", "GV {$gv['hoVaTen']} nhập $loai = $diem cho HS #$maHS (môn $maMon)");
                        } else {
                            $message = "Lỗi lưu điểm: " . $insertStmt->error;
                            break;
                        }
                    }
                }

                if ($message === "" && $savedCount > 0) {
                    echo "<script>alert('✅ Đã nhập $savedCount giá trị điểm thành công!'); window.location='diemso.php';</script>";
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Nhập điểm học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f6fa;
        }

        h1 {
            margin: 20px 0;
        }

        .form-container {
            background: #fff;
            border-radius: 10px;
            padding: 30px 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        select,
        input {
            width: 100%;
            padding: 8px 10px;
            margin: 5px 0 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        label {
            font-weight: bold;
        }

        .hk-section {
            background: #f0f4ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .hk-section h3 {
            background: #0b3364;
            color: #fff;
            padding: 5px 12px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 25px;
        }

        button {
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            margin-top: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-submit {
            background: #28a745;
            color: white;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #ccc;
            color: black;
            margin-left: 10px;
        }

        .btn-cancel:hover {
            background: #999;
        }

        .message {
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .message.error {
            background: #ffe6e6;
            color: #a00;
            border: 1px solid #f5a1a1;
        }

        .message.ok {
            background: #e6ffed;
            color: #086b2b;
            border: 1px solid #8fe09a;
        }

        .filter-box {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        select,
        input[type=date] {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn {
            padding: 7px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: #fff;
            background: #0b3364;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #0b3364;
            color: #fff;
        }

        tr:hover {
            background: #f9f9f9;
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-graduation-cap logo"></i>
            <h2>Viện đào tạo ABC</h2>
        </div>

        <nav class="menu">
            <div class="menu-section">
                <div class="menu-title">Quản lý chung</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
                    <li onclick="window.location.href='../pagegiaovien/hocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/tlhoctap.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/chuyencan.php'"><i class="fa-solid fa-check"></i> Chuyên cần</li>
                    <li class="active" onclick="window.location.href='../pagegiaovien/diemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Thông báo</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/thongbao.php'"><i class="fa-solid fa-bell"></i> Xem thông báo</li>
                </ul>
            </div>
        </nav>
    </aside>
    <div class="main-content">
        <header class="header">
            <div class="left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Tìm kiếm...">
                </div>
            </div>

            <div class="right">
                <div class="notification-area">
                    <i class="fa-regular fa-bell" id="bellIcon"></i>
                    <span class="noti-badge" id="notiBadge">0</span>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <h4>Thông báo</h4>
                        <ul id="notificationList"></ul>
                        <div class="no-noti" id="noNoti">Không có thông báo mới</div>
                    </div>
                </div>

                <div class="user-info" onclick="toggleUserMenu()">
                    <i class="fa-solid fa-user"></i>
                    <span><?= htmlspecialchars($gv['hoVaTen']) ?></span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="form-container">
            <h2><i class="fa-solid fa-keyboard"></i> NHẬP ĐIỂM HỌC SINH</h2>

            <?php if ($message !== ""): ?>
                <div class="message error"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>Chọn lớp:</label>
                <select id="lop" name="lop" required onchange="loadHocSinh(this.value)">
                    <option value="">-- Chọn lớp --</option>
                    <?php
                    // vì $dsLop đã được sử dụng, nếu muốn reload trang nhiều lần, rewind result
                    // nhưng ở đây chỉ render một lần, dùng trực tiếp
                    while ($lop = $dsLop->fetch_assoc()):
                    ?>
                        <option value="<?= $lop['maLop'] ?>"><?= htmlspecialchars($lop['tenLop']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label>Chọn học sinh:</label>
                <select id="maHS" name="maHS" required>
                    <option value="">-- Chọn học sinh --</option>
                </select>

                <input type="hidden" id="maMonHoc" name="maMonHoc">
                <div id="monhoc-info" style="margin-bottom:15px; font-weight:bold; color:#0b3364;">
                    <i>Vui lòng chọn lớp để hiển thị môn dạy...</i>
                </div>

                <div class="hk-section">
                    <h3>HỌC KỲ I</h3>
                    <div class="grid">
                        <div><label>Điểm miệng:</label><input type="number" step="0.1" name="hk1_mieng" min="0" max="10"></div>
                        <div><label>Điểm 1 tiết:</label><input type="number" step="0.1" name="hk1_1tiet" min="0" max="10"></div>
                        <div><label>Thi GK:</label><input type="number" step="0.1" name="hk1_thiGK" min="0" max="10"></div>
                        <div><label>Thi CK:</label><input type="number" step="0.1" name="hk1_thiCK" min="0" max="10"></div>
                    </div>
                </div>

                <div class="hk-section">
                    <h3>HỌC KỲ II</h3>
                    <div class="grid">
                        <div><label>Điểm miệng:</label><input type="number" step="0.1" name="hk2_mieng" min="0" max="10"></div>
                        <div><label>Điểm 1 tiết:</label><input type="number" step="0.1" name="hk2_1tiet" min="0" max="10"></div>
                        <div><label>Thi GK:</label><input type="number" step="0.1" name="hk2_thiGK" min="0" max="10"></div>
                        <div><label>Thi CK:</label><input type="number" step="0.1" name="hk2_thiCK" min="0" max="10"></div>
                    </div>
                </div>

                <div style="text-align:center;">
                    <button type="submit" class="btn-submit"><i class="fa-solid fa-save"></i> Lưu điểm</button>
                    <button type="button" class="btn-cancel" onclick="window.location.href='diemso.php'">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById("bellIcon").addEventListener("click", function() {
            const dropdown = document.getElementById("notificationDropdown");
            // Hiện/ẩn menu
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";

            // Gọi AJAX lấy thông báo
            fetch("../get_thongbao.php")
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById("notificationList");
                    const noNoti = document.getElementById("noNoti");
                    const badge = document.getElementById("notiBadge");
                    list.innerHTML = "";

                    let unreadCount = 0;

                    if (data.length > 0) {
                        noNoti.style.display = "none";
                        data.forEach(tb => {
                            const li = document.createElement("li");
                            li.style.padding = "10px 8px";
                            li.style.borderBottom = "1px solid #eee";
                            li.style.cursor = "pointer";

                            if (tb.trangThai === "Chưa đọc") {
                                unreadCount++;
                                li.style.background = "#f0f8ff";
                                li.innerHTML = `
                        <strong style="color:#0b3364;">${tb.tieuDe} 🔵</strong><br>
                        <span>${tb.noiDung}</span><br>
                        <small>${tb.ngayGui}</small>
                    `;
                            } else {
                                li.style.opacity = "0.7";
                                li.innerHTML = `
                        <strong>${tb.tieuDe}</strong><br>
                        <span>${tb.noiDung}</span><br>
                        <small>${tb.ngayGui}</small>
                    `;
                            }

                            li.addEventListener("click", () => markAsRead(tb.maThongBao, li));
                            list.appendChild(li);
                        });
                    } else {
                        noNoti.style.display = "block";
                    }

                    // Cập nhật badge
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount;
                        badge.style.display = "block";
                    } else {
                        badge.style.display = "none";
                    }
                })
                .catch(err => console.error("Lỗi tải thông báo:", err));


            function markAsRead(maThongBao, element) {
                fetch("../update_trangthai.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "maThongBao=" + encodeURIComponent(maThongBao)
                    })
                    .then(res => res.text())
                    .then(response => {
                        if (response === "OK") {
                            element.style.background = "transparent";
                            element.style.opacity = "0.7";
                            element.querySelector("strong").innerHTML = element.querySelector("strong").innerText;

                            // Giảm số badge đi 1
                            const badge = document.getElementById("notiBadge");
                            let current = parseInt(badge.textContent || "0");
                            if (current > 1) badge.textContent = current - 1;
                            else badge.style.display = "none";
                        }
                    });
            }

        });

        // Ẩn dropdown khi click ra ngoài
        document.addEventListener("click", function(e) {
            const dropdown = document.getElementById("notificationDropdown");
            const bell = document.getElementById("bellIcon");
            if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });

        function loadHocSinh(maLop) {
            const hsSelect = document.getElementById('maHS');
            hsSelect.innerHTML = "<option value=''>Đang tải...</option>";
            if (!maLop) {
                hsSelect.innerHTML = "<option value=''>-- Chọn học sinh --</option>";
                document.getElementById('monhoc-info').innerHTML = "<i>Vui lòng chọn lớp để hiển thị môn dạy...</i>";
                document.getElementById('maMonHoc').value = '';
                return;
            }

            // Lấy danh sách học sinh
            fetch('../src/ajax_hocsinh.php?lop=' + encodeURIComponent(maLop))
                .then(res => res.json())
                .then(data => {
                    hsSelect.innerHTML = "<option value=''>-- Chọn học sinh --</option>";
                    if (Array.isArray(data)) {
                        data.forEach(hs => {
                            const opt = document.createElement('option');
                            opt.value = hs.userID;
                            opt.textContent = hs.hoVaTen;
                            hsSelect.appendChild(opt);
                        });
                    } else {
                        hsSelect.innerHTML = "<option value=''>Không có học sinh</option>";
                    }
                })
                .catch(err => {
                    console.error(err);
                    hsSelect.innerHTML = "<option value=''>Lỗi tải danh sách</option>";
                });

            // Lấy môn mà GV dạy trong lớp đó
            loadMonHoc(maLop);
        }

        function loadMonHoc(maLop) {
            fetch('../src/ajax_monhoc.php?lop=' + encodeURIComponent(maLop))
                .then(res => res.json())
                .then(data => {
                    const monInfo = document.getElementById('monhoc-info');
                    const inputHidden = document.getElementById('maMonHoc');
                    if (Array.isArray(data) && data.length > 0) {
                        // Nếu GV dạy nhiều môn trong lớp, lấy môn đầu tiên (hoặc hiển thị chọn)
                        if (data.length === 1) {
                            monInfo.textContent = "Môn dạy: " + data[0].tenMonHoc;
                            inputHidden.value = data[0].maMonHoc;
                        } else {
                            let html = "Môn dạy: ";
                            html += data.map(m => m.tenMonHoc).join(", ");
                            monInfo.innerHTML = html + "<br><i>(Tự động chọn môn đầu tiên)</i>";
                            inputHidden.value = data[0].maMonHoc;
                        }
                    } else {
                        monInfo.innerHTML = "<span style='color:red'>Không tìm thấy môn dạy trong lớp này.</span>";
                        inputHidden.value = '';
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('monhoc-info').innerHTML = "<span style='color:red'>Lỗi tải môn học!</span>";
                });
        }

        function toggleUserMenu() {
            const menu = document.getElementById('userMenu');
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
        }

        // Đóng menu nếu click ra ngoài
        document.addEventListener("click", function(e) {
            const menu = document.getElementById("userMenu");
            const userInfo = document.querySelector(".user-info");
            if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = "none";
            }
        });

        function logout() {
            if (confirm('Bạn có chắc muốn đăng xuất không?')) {
                window.location.href = '../dangxuat.php';
            }
        }
    </script>
</body>

</html>