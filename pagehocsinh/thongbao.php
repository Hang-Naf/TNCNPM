<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép HọcSinh ====
if ($_SESSION["vaiTro"] !== "HocSinh") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$userID = $_SESSION["userID"];

// === PHÂN TRANG ===
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Truy vấn thông tin cá nhân học sinh
$sql = "SELECT h.maHS, u.hoVaTen, u.email, u.sdt, u.ngaySinh, u.gioiTinh, 
               h.lopHocPhuTrach, h.chucVu, h.anhDaiDien, h.namHoc, h.hocKy, h.trangThai
        FROM user u
        JOIN hocsinh h ON u.userID = h.maHS
        WHERE u.userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$hs = $result->fetch_assoc();

// === Lấy danh sách thông báo chung ===
// === ĐẾM TỔNG SỐ THÔNG BÁO ===
$count_sql = "SELECT COUNT(*) as total
           FROM thongbao tb
           JOIN thongbaouser tbu ON tb.maThongBao = tbu.maThongBao
           WHERE tbu.userID = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $userID);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$countRow = $count_result->fetch_assoc();
$totalItems = $countRow['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

$sql_tb = "SELECT tb.maThongBao, tb.tieuDe, tb.noiDung, tb.ngayGui, tbu.trangThai
           FROM thongbao tb
           JOIN thongbaouser tbu ON tb.maThongBao = tbu.maThongBao
           WHERE tbu.userID = ?
           ORDER BY tb.ngayGui DESC
           LIMIT $offset, $itemsPerPage";
$stmt_tb = $conn->prepare($sql_tb);
$stmt_tb->bind_param("i", $userID);
$stmt_tb->execute();
$result_tb = $stmt_tb->get_result();

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

        .header {
            padding: 12px 25px;
        }

        h1 {
            margin: 20px 0px 15px 30px;
        }

        .filter-box {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 15px 20px 15px 30px;
        }

        
        .filter-box form{
            width: 100%;
        }

        label {
            font-weight: 750;
            color: #333;
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
                    <input type="text" id="searchNotifications" placeholder="Tìm kiếm thông báo...">
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
            <h1>THÔNG BÁO</h1>
            <div class="thongbao-box" style="background:#fff; border-radius:10px; box-shadow:0 2px 5px rgba(0,0,0,0.1); padding:15px; margin-top:10px;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#0b3364; color:#fff;">
                            <th style="padding:10px; text-align:left;">Tiêu đề</th>
                            <th style="padding:10px; text-align:right; width:150px;">Ngày gửi</th>
                        </tr>
                    </thead>
                    <tbody id="notificationTable">
                        <?php if ($result_tb && $result_tb->num_rows > 0): ?>
                            <?php while ($row = $result_tb->fetch_assoc()): ?>
                                <tr class="notification-row" style="border-bottom:1px solid #eee; cursor:pointer; <?= $row['trangThai'] == 'Chưa đọc' ? 'background:#f0f8ff' : '' ?>"
                                    onclick="window.location.href='chitiet_thongbao.php?id=<?= $row['maThongBao'] ?>'">
                                    <td style="padding:10px; color:#0b1e6b; font-weight:500;" class="notification-title">
                                        <?= htmlspecialchars($row['tieuDe']) ?> <?= $row['trangThai'] == 'Chưa đọc' ? '🔵' : '' ?>
                                    </td>
                                    <td style="padding:10px; text-align:right; color:#333;" class="notification-date">
                                        <?= date('d/m/Y', strtotime($row['ngayGui'])) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="padding:15px; text-align:center; color:#777;">Không có thông báo nào.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div id="noResults" style="display:none; padding:15px; text-align:center; color:#777;">
                    Không tìm thấy thông báo phù hợp.
                </div>

                <!-- ========= THANH PHÂN TRANG ========= -->
                <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:10px;">
                    <span style="font-size:14px; color:#333;">Trang <?= $page ?>/<?= max(1, $totalPages) ?> (Tổng: <?= $totalItems ?> thông báo)</span>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <?php if ($page > 1): ?>
                            <a href="?page=1" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">⏮ Đầu</a>
                            <a href="?page=<?= $page - 1 ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">◀ Trước</a>
                        <?php else: ?>
                            <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">⏮ Đầu</button>
                            <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">◀ Trước</button>
                        <?php endif; ?>
                        
                        <span style="font-weight:600; font-size:14px; min-width:30px; text-align:center;"><?= $page ?></span>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Sau ▶</a>
                            <a href="?page=<?= $totalPages ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Cuối ⏭</a>
                        <?php else: ?>
                            <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Sau ▶</button>
                            <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Cuối ⏭</button>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- ========= HẾT THANH PHÂN TRANG ========= -->
            </div>
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

        // === CHỨC NĂNG TÌM KIẾM THÔNG BÁO ===
        const searchInput = document.getElementById("searchNotifications");
        const notificationRows = document.querySelectorAll(".notification-row");
        const noResults = document.getElementById("noResults");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                let foundCount = 0;

                notificationRows.forEach(row => {
                    const title = row.querySelector(".notification-title").innerText.toLowerCase();
                    
                    if (title.includes(keyword)) {
                        row.style.display = "";
                        foundCount++;
                    } else {
                        row.style.display = "none";
                    }
                });

                // Hiển thị/ẩn thông báo "Không tìm thấy"
                if (foundCount === 0 && keyword !== "") {
                    noResults.style.display = "block";
                } else {
                    noResults.style.display = "none";
                }
            });
        }
    </script>
</body>

</html>