<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// Kiểm tra đăng nhập và vai trò
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "HocSinh") {
    header("Location: ../dangnhap.php");
    exit();
}

$userID = $_SESSION["userID"];

// Lấy thông tin học sinh
$sql = "SELECT hoVaTen FROM user WHERE userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$res = $stmt->get_result();
$hs = $res->fetch_assoc() ?: ['hoVaTen' => 'Học sinh'];
$stmt->close();

// Lấy thông báo theo ID
$maTB = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($maTB <= 0) {
    die("Thông báo không hợp lệ.");
}

$sql_tb = "SELECT tb.maThongBao, tb.tieuDe, tb.noiDung, tb.tepDinhKem,
                  u.hoVaTen AS nguoiGui, tb.ngayGui
           FROM thongbao tb
           LEFT JOIN user u ON tb.nguoiGui = u.userID
           WHERE tb.maThongBao = ? LIMIT 1";
$stmt = $conn->prepare($sql_tb);
$stmt->bind_param("i", $maTB);
$stmt->execute();
$result_tb = $stmt->get_result();
$tb = $result_tb->fetch_assoc();
$stmt->close();

if (!$tb) {
    die("Thông báo không tồn tại.");
}

// Đánh dấu đã đọc
$sql_update = "UPDATE thongbaouser SET trangThai='Đã đọc' WHERE userID=? AND maThongBao=?";
$stmt = $conn->prepare($sql_update);
$stmt->bind_param("ii", $userID, $maTB);
$stmt->execute();
$stmt->close();

// Xác định người nhận
$sql_role = "SELECT DISTINCT u.vaiTro
             FROM thongbaouser tu
             JOIN user u ON tu.userID = u.userID
             WHERE tu.maThongBao=?";
$stmt = $conn->prepare($sql_role);
$stmt->bind_param("i", $maTB);
$stmt->execute();
$res_roles = $stmt->get_result();
$roles = [];
while ($r = $res_roles->fetch_assoc()) $roles[] = $r['vaiTro'];
$stmt->close();

if (in_array('GiaoVien', $roles) && in_array('HocSinh', $roles)) $nguoiNhan = 'Toàn hệ thống';
elseif (in_array('GiaoVien', $roles)) $nguoiNhan = 'Giáo viên';
elseif (in_array('HocSinh', $roles)) $nguoiNhan = 'Học sinh';
else $nguoiNhan = 'Không xác định';

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết thông báo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f6fa;
        }

        .container {
            padding: 50px;
        }

        abel {
            font-weight: 600;
            color: #0b3364;
        }

        .content {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            background: #f9f9f9;
            min-height: 120px;
            white-space: pre-wrap;
            margin-bottom: 10px;
        }

        .radio-group {
            margin: 10px 0;
        }

        .file-link {
            color: #0b3364;
            text-decoration: underline;
        }

        .back-btn {
            display: block;
            width: fit-content;
            margin: 20px auto 0;
            background: #0b3364;
            color: #fff;
            padding: 8px 18px;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
        }

        .thongbao-detail {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .thongbao-detail h2 {
            color: #0b3364;
            margin-bottom: 10px;
        }

        .thongbao-detail .date {
            color: #777;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .thongbao-detail p {
            font-size: 16px;
            line-height: 1.6;
        }

        .back-btn {
            margin-top: 15px;
            display: inline-block;
            padding: 8px 15px;
            background: #0b3364;
            color: #fff;
            border-radius: 6px;
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
                    <li class="active" onclick="window.location.href='../pagehocsinh/thongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Tra cứu thông tin</div>
                <ul>
                    <li onclick="window.location.href='../pagehocsinh/tlhoctap.php'"><i class="fa-solid fa-book"></i> Tài liệu học tập</li>
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
        <div class="container">
            <h2><?= htmlspecialchars($tb['tieuDe']) ?></h2>

            <p><label>Mã thông báo:</label> <?= htmlspecialchars($tb['maThongBao']) ?></p>
            <p><label>Tiêu đề:</label> <?= htmlspecialchars($tb['tieuDe']) ?></p>

            <label>Nội dung:</label>
            <div class="content"><?= nl2br(htmlspecialchars($tb['noiDung'])) ?></div>

            <p><label>Người gửi:</label> <?= htmlspecialchars($tb['nguoiGui'] ?? 'Hệ thống') ?></p>
            <p><label>Ngày gửi:</label> <?= date('d/m/Y H:i', strtotime($tb['ngayGui'])) ?></p>

            <p><label>Người nhận:</label></p>
            <div class="radio-group">
                <label><input type="radio" <?= ($nguoiNhan == 'Toàn hệ thống') ? 'checked' : '' ?> disabled> Toàn hệ thống</label>
                <label><input type="radio" <?= ($nguoiNhan == 'Giáo viên') ? 'checked' : '' ?> disabled> Giáo viên</label>
                <label><input type="radio" <?= ($nguoiNhan == 'Học sinh') ? 'checked' : '' ?> disabled> Học sinh</label>
            </div>

            <?php if (!empty($tb['tepDinhKem'])): ?>
                <p><label>Tệp đính kèm:</label>
                    <br><a class="file-link" href="../uploads/thongbao/<?= htmlspecialchars($tb['tepDinhKem']) ?>" target="_blank"><?= htmlspecialchars($tb['tepDinhKem']) ?></a>
                </p>
            <?php else: ?>
                <i>Không có tệp đính kèm</i>
            <?php endif; ?>
            <br>

            <a class="back-btn" href="thongbao.php"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
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