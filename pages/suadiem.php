<?php
include_once(__DIR__ . '/../csdl/db.php');

// ==== LẤY DỮ LIỆU ====
$maHS = $_GET['maHS'] ?? '';
$tenMon = $_GET['mon'] ?? '';

if ($maHS === '' || $tenMon === '') {
    die("Thiếu thông tin học sinh hoặc môn học.");
}

// ==== CẬP NHẬT (KHI SUBMIT) ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hocKy = ['hk1', 'hk2'];
    $loai = ['mieng', '1tiet', 'thiGK', 'thiCK'];

    // Lấy mã môn học
    $getMon = $conn->prepare("SELECT maMonHoc FROM monhoc WHERE tenMonHoc = ?");
    $getMon->bind_param("s", $tenMon);
    $getMon->execute();
    $getMon->bind_result($maMonHoc);
    $getMon->fetch();
    $getMon->close();

    if (!$maMonHoc)
        die("Không tìm thấy môn học trong CSDL!");

    foreach ($hocKy as $hk) {
        foreach ($loai as $l) {
            $field = $hk . '_' . $l;
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $diem = floatval($_POST[$field]);

                // Kiểm tra xem điểm đã có chưa
                $check = $conn->prepare("SELECT maDiem FROM diemso WHERE maHS=? AND maMonHoc=? AND loaiDiem=?");
                $check->bind_param("iis", $maHS, $maMonHoc, $field);
                $check->execute();
                $check->store_result();

                if ($check->num_rows > 0) {
                    // Cập nhật
                    $sql = "UPDATE diemso SET diem=?, ngayCapNhat=NOW() WHERE maHS=? AND maMonHoc=? AND loaiDiem=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("diis", $diem, $maHS, $maMonHoc, $field);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    // Thêm mới nếu chưa có
                    $sql = "INSERT INTO diemso(maHS, maMonHoc, loaiDiem, diem, ngayCapNhat)
                            VALUES(?, ?, ?, ?, NOW())";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iisd", $maHS, $maMonHoc, $field, $diem);
                    $stmt->execute();
                    $stmt->close();
                }
                $check->close();
            }
        }
    }

    echo "<script>alert('Cập nhật điểm thành công!');window.location.href='qldiemso.php';</script>";
    exit();
}

// ==== LẤY THÔNG TIN HỌC SINH + ĐIỂM ====
$sql = "SELECT u.hoVaTen, d.loaiDiem, d.diem
        FROM diemso d
        JOIN monhoc m ON d.maMonHoc = m.maMonHoc
        JOIN hocsinh h ON d.maHS = h.maHS
        JOIN user u ON h.maHS = u.userID
        WHERE d.maHS = ? AND m.tenMonHoc = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $maHS, $tenMon);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
$tenHS = "";
while ($r = $res->fetch_assoc()) {
    $data[$r['loaiDiem']] = $r['diem'];
    $tenHS = $r['hoVaTen'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Cập nhật điểm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .form-box {
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .add-btn {
            background: #0b1e6b;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
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

        .status.active {
            background-color: rgba(32, 164, 99, 0.2);
            color: #20a463;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .status.inactive {
            background-color: rgba(128, 128, 128, 0.2);
            color: gray;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .actions i {
            cursor: pointer;
            margin-right: 10px;
        }

        td:not(.no-center) {
            text-align: center;
        }

        .info {
            font-size: 24px;
            justify-content: center;
            display: flex;
            gap: 20px;
        }

        .form {
            width: 100%;
            /* border: 1px solid black; */
            display: flex;
            justify-content: center;
        }

        .section-title {
            font-size: 24px;
            padding: 5px 10px;
            border-radius: 4px;
            display: inline-block;
            font-weight: bold;
            color: white;
            background-color: #152259;
        }

        .name {
            font-size: 24px;
            display: inline-block;
            width: 180px;
            font-weight: bold;
        }

        .row {
            margin: 10px;
        }

        .row label {
            margin-right: 40px;
        }

        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .buttons button {
            padding: 10px 30px;
            border: none;
            border-radius: 4px;
        }

        .buttons button:hover {
            filter: brightness(90%);
            cursor: pointer;
        }

        .buttons .save {
            background-color: #18BD5B;
            color: white;
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
                    <li onclick="window.location.href='../index.php'"><i class="fa-solid fa-house"></i> Dashboard</li>
                    <li onclick="window.location.href='../pages/qlgiaovien.php'"><i
                            class="fa-solid fa-chalkboard-user"></i> Giáo viên</li>
                    <li onclick="window.location.href='../pages/qlhocsinh.php'"><i
                            class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pages/qllophoc.php'"><i class="fa-solid fa-school"></i> Lớp
                        học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học
                    </li>
                    <li onclick="window.location.href='../pages/qltailieu.php'"><i class="fa-solid fa-file-lines"></i>
                        Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlchuyencan.php'"><i class="fa-solid fa-check"></i>
                        Chuyên cần</li>
                    <li class="active" onclick="window.location.href='../pages/qldiemso.php'"><i
                            class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý thông tin</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông
                        báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý tài khoản</div>
                <ul>
                    <li onclick="window.location.href='../pages/phanconggiangday.php'"><i class="fa-solid fa-users"></i>
                        Phân công giảng dạy</li>
                    <li onclick="window.location.href='../pages/qlphanquyen.php'"><i
                            class="fa-solid fa-user-shield"></i> Phân quyền</li>
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
                    <span>Quản trị viên</span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>
        <div class="form-box">
            <h1>CẬP NHẬT ĐIỂM</h1>
            <div class="info">
                <div><b>HỌ TÊN HỌC SINH:</b> <?= htmlspecialchars($tenHS) ?></div>
                <div><b>MÃ HỌC SINH:</b> K<?= str_pad($maHS, 7, '0', STR_PAD_LEFT) ?></div>
            </div>
            <br>
            <div class="form">
                <form method="POST">
                    <div class="section">
                        <div class="section-title">HỌC KỲ I</div>
                        <div class="row">
                            <label><span class="name">ĐIỂM MIỆNG:</span>
                                <input type="number" name="hk1_mieng" step="0.1"
                                    value="<?= $diemArr['hk1_mieng'] ?? '' ?>">
                            </label>
                            <label><span class="name">ĐIỂM THI GK:</span>
                                <input type="number" name="hk1_thiGK" step="0.1"
                                    value="<?= $diemArr['hk1_thiGK'] ?? '' ?>">
                            </label>
                        </div>
                        <div class="row">
                            <label><span class="name">ĐIỂM 1 TIẾT:</span>
                                <input type="number" name="hk1_1tiet" step="0.1"
                                    value="<?= $diemArr['hk1_1tiet'] ?? '' ?>">
                            </label>
                            <label><span class="name">ĐIỂM THI CK:</span>
                                <input type="number" name="hk1_thiCK" step="0.1"
                                    value="<?= $diemArr['hk1_thiCK'] ?? '' ?>">
                            </label>
                        </div>
                    </div>

                    <div class="section" style="margin-top:25px;">
                        <div class="section-title">HỌC KỲ II</div>
                        <div class="row">
                            <label><span class="name">ĐIỂM MIỆNG:</span>
                                <input type="number" name="hk2_mieng" step="0.1"
                                    value="<?= $diemArr['hk2_mieng'] ?? '' ?>">
                            </label>
                            <label><span class="name">ĐIỂM THI GK:</span>
                                <input type="number" name="hk2_thiGK" step="0.1"
                                    value="<?= $diemArr['hk2_thiGK'] ?? '' ?>">
                            </label>
                        </div>
                        <div class="row">
                            <label><span class="name">ĐIỂM 1 TIẾT:</span>
                                <input type="number" name="hk2_1tiet" step="0.1"
                                    value="<?= $diemArr['hk2_1tiet'] ?? '' ?>">
                            </label>
                            <label><span class="name">ĐIỂM THI CK:</span>
                                <input type="number" name="hk2_thiCK" step="0.1"
                                    value="<?= $diemArr['hk2_thiCK'] ?? '' ?>">
                            </label>
                        </div>
                    </div>

                    <div class="buttons">
                        <button type="button" class="cancel" onclick="window.location.href='qldiemso.php'">HỦY</button>
                        <button type="submit" class="save">CẬP NHẬT</button>
                    </div>
                </form>
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

        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>