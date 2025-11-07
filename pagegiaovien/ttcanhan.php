<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép GiaoVien ====
if ($_SESSION["vaiTro"] !== "GiaoVien") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}
$userID = $_SESSION["userID"];

// Truy vấn thông tin cá nhân giáo viên
$sql = "SELECT g.maGV AS maGV, u.hoVaTen, u.email, u.sdt, u.ngaySinh, u.gioiTinh, 
               g.boMon, g.trinhDo, g.phongBan, g.namHoc, g.hocKy, g.trangThai, g.anhDaiDien
        FROM user u
        JOIN giaovien g ON u.userID = g.maGV
        WHERE u.userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$gv = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .header {
            padding: 0px 25px;
        }

        .container {
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
            width: 150px;
        }

        .profile-card {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 20px auto;
        }

        .profile-left {
            flex: 0 0 200px;
            text-align: center;
        }

        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ddd;
        }

        .avatar-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #eaeaea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: #999;
        }

        .profile-right {
            flex: 1;
            padding-left: 40px;
        }

        .profile-right p {
            margin: 6px 0;
            font-size: 16px;
            color: #333;
        }

        .profile-right strong {
            color: #555;
            width: 140px;
            display: inline-block;
        }

        .contact-info {
            margin-top: 20px;
            display: flex;
            gap: 30px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            background: #f5f7fb;
            padding: 10px 16px;
            border-radius: 8px;
            color: #0b1e6b;
            font-weight: 500;
        }

        .contact-item i {
            margin-right: 8px;
            font-size: 18px;
        }

        .status.active {
            color: green;
            font-weight: 500;
        }

        .status.inactive {
            color: gray;
        }

        .actions i {
            cursor: pointer;
            margin-right: 10px;
        }

        .popup-bg {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .popup {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 450px;
        }

        .popup input,
        .popup select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .popup-buttons {
            text-align: right;
        }

        .save-btn {
            background: #0b1e6b;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
        }

        .cancel-btn {
            background: #ccc;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
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
                    <li class="active" onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
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
                    <li onclick="window.location.href='../pagegiaovien/diemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Thông báo</div>
                <ul>
                    <li  onclick="window.location.href='../pagegiaovien/thongbao.php'"><i class="fa-solid fa-bell"></i> Xem thông báo</li>
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

        <div class="container">
            <h1>Thông tin cá nhân</h1>
            <?php if ($gv): ?>
                <div class="profile-card">
                    <div class="profile-left">
                            <div class="avatar-placeholder">
                                <i class="fa-solid fa-user"></i>
                            </div>
                    </div>

                    <div class="profile-right">
                        <p><strong>Mã giáo viên:</strong> <?= htmlspecialchars($gv['maGV']) ?></p>
                        <p><strong>Họ và tên:</strong> <?= htmlspecialchars($gv['hoVaTen']) ?></p>
                        <p><strong>Bộ môn:</strong> <?= htmlspecialchars($gv['boMon']) ?></p>
                        <p><strong>Phòng ban:</strong> <?= htmlspecialchars($gv['phongBan']) ?></p>
                        <p><strong>Trình độ:</strong> <?= htmlspecialchars($gv['trinhDo']) ?></p>
                        <p><strong>Giới tính:</strong> <?= htmlspecialchars($gv['gioiTinh'] ?: 'Chưa cập nhật') ?></p>

                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fa-solid fa-phone"></i>
                                <span><?= htmlspecialchars($gv['sdt'] ?: 'Chưa có số') ?></span>
                            </div>
                            <div class="contact-item">
                                <i class="fa-solid fa-envelope"></i>
                                <span><?= htmlspecialchars($gv['email'] ?: 'Chưa có email') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <p>Không tìm thấy thông tin cá nhân.</p>
            <?php endif; ?>
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

        function toggleUserMenu() {
            const menu = document.getElementById("userMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        // Đóng menu nếu click ra ngoài
        document.addEventListener("click", function(e) {
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