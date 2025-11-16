<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Học Sinh ====
if ($_SESSION["vaiTro"] !== "HocSinh") {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Lấy maTL từ GET ====
$maTL = isset($_GET['maTL']) ? intval($_GET['maTL']) : 0;

if ($maTL <= 0) {
    die("Tài liệu không hợp lệ.");
}

// ==== Lấy thông tin học sinh ====
$maHS = $_SESSION["userID"];
$sqlHS = "SELECT hoVaTen FROM user WHERE userID = ?";
$stmtHS = $conn->prepare($sqlHS);
$stmtHS->bind_param("i", $maHS);
$stmtHS->execute();
$hs = $stmtHS->get_result()->fetch_assoc();
$stmtHS->close();

// ==== Lấy chi tiết tài liệu ====
$sqlTL = "SELECT t.maTL, t.tieuDe, t.noiDung, t.tepDinhKem, m.tenMonHoc, u.hoVaTen AS nguoiTao
          FROM tailieu t
          LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
          LEFT JOIN user u ON t.maGV = u.userID
          WHERE t.maTL = ?";
$stmtTL = $conn->prepare($sqlTL);
$stmtTL->bind_param("i", $maTL);
$stmtTL->execute();
$tailieu = $stmtTL->get_result()->fetch_assoc();
$stmtTL->close();

if (!$tailieu) {
    die("Tài liệu không tồn tại.");
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết tài liệu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .content-area {
            padding: 30px;
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #111;
        }

        .detail {
            margin-bottom: 15px;
        }

        .detail strong {
            display: inline-block;
            width: 120px;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 15px;
            background: #0b3364;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
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
                <div class="menu-title">Trang cá nhân</div>
                <ul>
                    <li onclick="window.location.href='../pagehocsinh/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
                    <li onclick="window.location.href='../pagehocsinh/thongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Tra cứu thông tin</div>
                <ul>
                    <li class="active" onclick="window.location.href='../pagehocsinh/tlhoctap.php'"><i class="fa-solid fa-book"></i> Tài liệu học tập</li>
                    <li onclick="window.location.href='../pagehocsinh/kqhoctap.php'"><i class="fa-solid fa-file-lines"></i> Kết quả học tập</li>
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
                    <span><?= htmlspecialchars($hs['hoVaTen']) ?></span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="window.location.href='../pagehocsinh/ttcanhan.php'"><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>
        <div class="content-area">
            <h1><?= htmlspecialchars($tailieu['tieuDe']) ?></h1>

            <div class="detail"><strong>Môn học:</strong> <?= htmlspecialchars($tailieu['tenMonHoc']) ?></div>
            <div class="detail"><strong>Giáo viên gửi:</strong> <?= htmlspecialchars($tailieu['nguoiTao']) ?></div>
            <div class="detail"><strong>Nội dung:</strong></div>
            <div style="padding: 10px; border: 1px solid #eee; border-radius: 5px; background: #fafafa;">
                <?= nl2br(htmlspecialchars($tailieu['noiDung'])) ?>
            </div>
            <?php if (!empty($tailieu['tepDinhKem'])): ?>
                <p><strong>Tệp đính kèm:</strong><br>
                    <a class="file-link" href="../uploads/tailieu/<?= htmlspecialchars($tailieu['tepDinhKem']) ?>" target="_blank">
                        <?= htmlspecialchars($tailieu['tepDinhKem']) ?>
                    </a>
                </p>
            <?php else: ?>
                <i>Không có tệp đính kèm</i>
            <?php endif; ?>

            <a class="back-btn" href="tlhoctap.php"><i class="fa-solid fa-arrow-left"></i> Quay lại danh sách</a>
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