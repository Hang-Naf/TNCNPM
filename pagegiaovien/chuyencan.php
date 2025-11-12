<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();
// === XỬ LÝ AJAX CẬP NHẬT CHUYÊN CẦN ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    include_once(__DIR__ . '/../csdl/db.php');

    $maHS = intval($_POST['maHS'] ?? 0);
    $maMonHoc = intval($_POST['maMonHoc'] ?? 0);
    $ngayHoc = $_POST['ngayHoc'] ?? '';
    $trangThai = $_POST['trangThai'] ?? '';

    if ($maHS > 0 && $maMonHoc > 0 && !empty($ngayHoc)) {
        $stmt = $conn->prepare("SELECT maDiemDanh FROM chuyencan WHERE maHS=? AND maMonHoc=? AND ngayHoc=?");
        if (!$stmt) {
            die("❌ Lỗi prepare SQL: " . $conn->error);
        }

        $stmt->bind_param("iis", $maHS, $maMonHoc, $ngayHoc);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $update = $conn->prepare("UPDATE chuyencan SET trangThai=? WHERE maHS=? AND maMonHoc=? AND ngayHoc=?");
            $update->bind_param("siis", $trangThai, $maHS, $maMonHoc, $ngayHoc);
            if ($update->execute())
                echo "OK";
            else
                echo "Lỗi UPDATE: " . $conn->error;
        } else {
            $insert = $conn->prepare("INSERT INTO chuyencan(maHS, maMonHoc, ngayHoc, trangThai) VALUES (?, ?, ?, ?)");
            $insert->bind_param("iiss", $maHS, $maMonHoc, $ngayHoc, $trangThai);
            if ($insert->execute())
                echo "OK";
            else
                echo "Lỗi INSERT: " . $conn->error;
        }
    } else {
        echo "Thiếu dữ liệu POST";
    }
    exit(); // Dừng xử lý HTML bên dưới
}


// ==== Kiểm tra đăng nhập & quyền ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}
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
           WHERE g.maGV = '$maGV' 
           LIMIT 1";
$resultGV = $conn->query($sqlGV);
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];


// ==== Lấy danh sách lớp được phân công ====
$sql_lop = "SELECT DISTINCT l.maLop, l.tenLop
             FROM lophoc l
             JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
             WHERE lm.maGV = ?";
$stmt = $conn->prepare($sql_lop);
$stmt->bind_param("i", $maGV);
$stmt->execute();
$lophoc = $stmt->get_result();

// ==== Lấy MÔN HỌC duy nhất hoặc đầu tiên theo giáo viên ====
$sql_mon = "SELECT DISTINCT m.maMonHoc, m.tenMonHoc
             FROM monhoc m
             JOIN lophoc_monhoc lm ON m.maMonHoc = lm.maMonHoc
             WHERE lm.maGV = ?
             LIMIT 1";
$stmt2 = $conn->prepare($sql_mon);
$stmt2->bind_param("i", $maGV);
$stmt2->execute();
$result_mon = $stmt2->get_result();
$monhoc = $result_mon->fetch_assoc();

if (!$monhoc) {
    die("<h3 style='color:red;text-align:center;'>⚠️ Giáo viên chưa được phân công môn học nào!</h3>");
}

$maMonHoc = $monhoc['maMonHoc']; // dùng để lọc và lưu điểm danh


// ==== Xử lý lưu điểm danh ====
if (isset($_POST['save'])) {
    $ngayHoc = $_POST['ngayHoc'];
    $maMonHoc = $_POST['maMonHoc'];
    $maHS = $_POST['maHS'];
    $trangThai = $_POST['trangThai'];

    foreach ($maHS as $hs => $val) {
        $tt = $trangThai[$hs] ?? '';
        $check = $conn->prepare("SELECT maDiemDanh FROM chuyencan WHERE maHS=? AND maMonHoc=? AND ngayHoc=?");
        $check->bind_param("iis", $hs, $maMonHoc, $ngayHoc);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $update = $conn->prepare("UPDATE chuyencan SET trangThai=? WHERE maHS=? AND maMonHoc=? AND ngayHoc=?");
            $update->bind_param("siis", $tt, $hs, $maMonHoc, $ngayHoc);
            $update->execute();
        } else {
            $insert = $conn->prepare("INSERT INTO chuyencan(maHS, maMonHoc, ngayHoc, trangThai) VALUES (?, ?, ?, ?)");
            $insert->bind_param("iiss", $hs, $maMonHoc, $ngayHoc, $tt);
            $insert->execute();
        }
    }

    echo "<script>alert('✅ Lưu điểm danh thành công!'); window.location='chuyencan.php';</script>";
    exit();
}

// ==== Lấy dữ liệu lọc ====
$loc_ngay = $_GET['ngayHoc'] ?? date('Y-m-d');
$loc_lop = $_GET['maLop'] ?? '';
$loc_mon = $_GET['maMonHoc'] ?? '';

$filter = "";
if ($loc_lop)
    $filter .= " AND hl.maLop = " . intval($loc_lop);

// ==== Lấy danh sách học sinh trong lớp (theo giáo viên) ====
$sql_hs = "
SELECT h.maHS, u.hoVaTen, l.tenLop
FROM hocsinh h
JOIN user u ON h.maHS = u.userID
LEFT JOIN hocsinh_lophoc hl ON h.maHS = hl.maHS
LEFT JOIN lophoc l ON hl.maLop = l.maLop
JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
WHERE lm.maGV = ? $filter
ORDER BY l.tenLop, u.hoVaTen ASC";
$stmt3 = $conn->prepare($sql_hs);
$stmt3->bind_param("i", $maGV);
$stmt3->execute();
$danhsach = $stmt3->get_result();

// ==== Lấy trạng thái chuyên cần ====
$chuyencan = [];
$cc = $conn->query("SELECT * FROM chuyencan WHERE ngayHoc='$loc_ngay' AND maMonHoc=$maMonHoc");
if ($cc) {
    while ($r = $cc->fetch_assoc()) {
        $chuyencan[$r['maHS']] = $r['trangThai'];
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý chuyên cần</title>
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

        #main-container {
            padding: 20px;
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
            background: white;
            margin-top: 20px;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f1f3f9;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .status-btns {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .status-btn {
            padding: 5px 8px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-size: 13px;
            transition: 0.2s;
        }

        .present {
            background: #27ae60;
            color: #fff;
        }

        .late {
            background: #f39c12;
            color: #fff;
        }

        .absent {
            background: #e74c3c;
            color: #fff;
        }

        .status-btn.active {
            outline: 3px solid #222;
        }

        .summary-box {
            flex-shrink: 0;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 300px;
            float: right;
        }

        .summary-box h3 {
            margin-bottom: 10px;
            color: #0b3364;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }

        .tables {
            gap: 20px;
            display: flex;
            align-items: flex-start;
            width: 100%;
        }

        .tables table {
            flex: 1;
            width: 100%;
        }

        #frmDiemDanh {
            flex: 1;
            min-width: 0;
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
                    <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-house"></i>
                        Thông tin cá nhân</li>
                    <li onclick="window.location.href='../pagegiaovien/hocsinh.php'"><i
                            class="fa-solid fa-user-graduate"></i> Học sinh</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/tlhoctap.php'"><i
                            class="fa-solid fa-file-lines"></i> Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li class="active" onclick="window.location.href='../pagegiaovien/chuyencan.php'"><i
                            class="fa-solid fa-check"></i> Chuyên cần</li>
                    <li onclick="window.location.href='../pagegiaovien/diemso.php'"><i
                            class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Thông báo</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/thongbao.php'"><i class="fa-solid fa-bell"></i>
                        Xem thông báo</li>
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
                        <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i
                                class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>
        <div id="main-container">
            <h1>ĐIỂM DANH HỌC SINH</h1>
            <div class="filter-box">
                <form method="GET">
                    <label>Ngày:</label>
                    <input type="date" name="ngayHoc" value="<?= htmlspecialchars($loc_ngay) ?>">
                    <label>Lớp:</label>
                    <select name="maLop">
                        <option value="">-- Tất cả lớp --</option>
                        <?php while ($r = $lophoc->fetch_assoc()) { ?>
                            <option value="<?= $r['maLop'] ?>" <?= ($loc_lop == $r['maLop']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['tenLop']) ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="btn">Lọc</button>
                </form>
            </div>
            <div class="tables">
                <?php if ($danhsach): ?>
                    <form method="POST" id="frmDiemDanh">
                        <input type="hidden" name="ngayHoc" value="<?= $loc_ngay ?>">
                        <input type="hidden" name="maMonHoc" value="<?= $maMonHoc ?>">

                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Học sinh</th>
                                    <th>Lớp</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stt = 1;
                                $tong = 0;
                                $present = 0;
                                $late = 0;
                                $absent = 0;
                                while ($hs = $danhsach->fetch_assoc()) {
                                    $maHS = $hs['maHS'];
                                    $status = $chuyencan[$maHS] ?? '';
                                    if ($status == 'Có mặt')
                                        $present++;
                                    elseif ($status == 'Đến muộn')
                                        $late++;
                                    elseif ($status == 'Vắng mặt')
                                        $absent++;
                                    $tong++;
                                    ?>
                                    <tr>
                                        <td><?= $stt++ ?></td>
                                        <td><?= htmlspecialchars($hs['hoVaTen']) ?></td>
                                        <td><?= htmlspecialchars($hs['tenLop'] ?? '-') ?></td>
                                        <td class="status-btns">
                                            <input type="hidden" name="maHS[<?= $maHS ?>]" value="<?= $maHS ?>">
                                            <input type="hidden" name="trangThai[<?= $maHS ?>]" id="status<?= $maHS ?>"
                                                value="<?= $status ?>">
                                            <button type="button"
                                                class="status-btn present <?= ($status == 'Có mặt') ? 'active' : '' ?>"
                                                onclick="setStatus(<?= $maHS ?>,'Có mặt',this)">Có mặt</button>
                                            <button type="button"
                                                class="status-btn late <?= ($status == 'Đến muộn') ? 'active' : '' ?>"
                                                onclick="setStatus(<?= $maHS ?>,'Đến muộn',this)">Đến muộn</button>
                                            <button type="button"
                                                class="status-btn absent <?= ($status == 'Vắng mặt') ? 'active' : '' ?>"
                                                onclick="setStatus(<?= $maHS ?>,'Vắng mặt',this)">Vắng mặt</button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <br>
                        <button type="submit" name="save" class="btn">💾 Lưu điểm danh</button>
                    </form>

                    <div class="summary-box">
                        <h3>TỔNG QUAN ĐIỂM DANH</h3>
                        <div class="summary-item"><span>Có mặt:</span><strong><?= $present ?></strong></div>
                        <div class="summary-item"><span>Đến muộn:</span><strong><?= $late ?></strong></div>
                        <div class="summary-item"><span>Vắng mặt:</span><strong><?= $absent ?></strong></div>
                        <hr>
                        <div class="summary-item">
                            <span>Tỷ lệ đi học:</span>
                            <strong><?= $tong > 0 ? round($present / $tong * 100, 1) : 0 ?>%</strong>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        document.getElementById("bellIcon").addEventListener("click", function () {
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
        document.addEventListener("click", function (e) {
            const dropdown = document.getElementById("notificationDropdown");
            const bell = document.getElementById("bellIcon");
            if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });

        function toggleUserMenu() {
            const menu = document.getElementById("userMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        // Đóng menu nếu click ra ngoài
        document.addEventListener("click", function (e) {
            const menu = document.getElementById("userMenu");
            const userInfo = document.querySelector(".user-info");
            if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = "none";
            }
        });

        // === Cập nhật chuyên cần tức thì (AJAX) ===
        function setStatus(maHS, status, button) {
            // Bỏ active các nút cùng hàng
            const row = button.closest('.status-btns');
            row.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
            button.classList.add('active');

            // Cập nhật input ẩn
            document.getElementById('status' + maHS).value = status;

            // Lấy dữ liệu cần gửi
            const formData = new FormData();
            formData.append('maHS', maHS);
            formData.append('maMonHoc', document.querySelector('input[name="maMonHoc"]').value);
            formData.append('ngayHoc', document.querySelector('input[name="ngayHoc"]').value);
            formData.append('trangThai', status);

            // Gửi AJAX cập nhật vào CSDL
            formData.append('ajax', '1'); // đánh dấu là request AJAX
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
                .then(res => res.text())
                .then(text => {
                    if (text.trim() === 'OK') {
                        console.log(' Cập nhật thành công:', maHS, status);
                    } else {
                        console.error(' Lỗi:', text);
                        alert('Không thể cập nhật trạng thái!');
                    }
                })
                .catch(err => console.error('Lỗi kết nối:', err));
        }

        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>