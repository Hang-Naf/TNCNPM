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
    <title>Thông báo </title>
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

        .pg {
            border: none;
            background: #eee;
            border-radius: 4px;
            padding: 5px 10px;
            text-decoration: none;
            color: #333;
            font-weight: 600;
        }

        .pg.disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
                    <li onclick="window.location.href='../pagegiaovien/lophoc.php'"><i class="fa-solid fa-school"></i> Lớp học</li>
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
                    <li class="active" onclick="window.location.href='../pagegiaovien/thongbao.php'"><i class="fa-solid fa-bell"></i> Xem thông báo</li>
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
            <h1>THÔNG BÁO</h1>
            <div class="thongbao-box" style="background:#fff; border-radius:10px; box-shadow:0 2px 5px rgba(0,0,0,0.1); padding:15px; margin-top:10px;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#0b3364; color:#fff;">
                            <th style="padding:10px; text-align:left;">Tiêu đề</th>
                            <th style="padding:10px; text-align:right; width:150px;">Ngày gửi</th>
                        </tr>
                    </thead>
                    <tbody id="notificationTableBody">

                    </tbody>
                </table>
                <div class="pg-container" style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:10px;">

                </div>
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

        // Xử lý tìm kiếm thông báo
        const searchInput = document.getElementById("searchNotifications");
        const notificationRows = document.querySelectorAll(".notification-row");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                notificationRows.forEach(row => {
                    const title = row.getAttribute("data-title").toLowerCase();
                    row.style.display = title.includes(keyword) ? "" : "none";
                });
            });
        }

        function loadNotifications(page = 1, itemsPerPage = 10) {
            fetch("../get_thongbao.php")
                .then(res => res.json())
                .then(data => {
                    const tbody = document.getElementById("notificationTableBody");
                    tbody.innerHTML = "";

                    if (data.length > 0) {
                        // Cắt dữ liệu theo phân trang
                        const totalItems = data.length;
                        const totalPages = Math.ceil(totalItems / itemsPerPage);
                        const start = (page - 1) * itemsPerPage;
                        const end = start + itemsPerPage;
                        const pageItems = data.slice(start, end);

                        pageItems.forEach(tb => {
                            const isUnread = tb.trangThai === "Chưa đọc";
                            const tr = document.createElement("tr");
                            tr.className = "notification-row";
                            tr.style.cursor = "pointer";
                            if (isUnread) tr.style.background = "#f0f8ff";
                            tr.onclick = () => window.location.href = "chitiet_thongbao.php?id=" + tb.maThongBao;
                            tr.innerHTML = `
                        <td style="padding:10px; color:#0b1e6b; font-weight:500;">
                            ${tb.tieuDe} ${isUnread ? "🔵" : ""}
                        </td>
                        <td style="padding:10px; text-align:right; color:#333;">
                            ${tb.ngayGui}
                        </td>
                    `;
                            tbody.appendChild(tr);
                        });

                        // Render phân trang
                        const pg = document.querySelector(".pg-container");
                        pg.innerHTML = `
                    <span style="font-size:14px; color:#333;">
                        Trang ${page}/${totalPages} (Tổng: ${totalItems} thông báo)
                    </span>
                    <div style="display:flex; gap:8px; align-items:center;">
                        ${page > 1 ? `<a href="#" class="pg" onclick="loadNotifications(1)">⏮ Đầu</a>
                                      <a href="#" class="pg" onclick="loadNotifications(${page-1})">◀ Trước</a>`
                                    : `<span class="pg disabled">⏮ Đầu</span><span class="pg disabled">◀ Trước</span>`}
                        <span style="font-weight:600; font-size:14px; min-width:30px; text-align:center;">${page}</span>
                        ${page < totalPages ? `<a href="#" class="pg" onclick="loadNotifications(${page+1})">Sau ▶</a>
                                               <a href="#" class="pg" onclick="loadNotifications(${totalPages})">Cuối ⏭</a>`
                                             : `<span class="pg disabled">Sau ▶</span><span class="pg disabled">Cuối ⏭</span>`}
                    </div>
                `;
                    } else {
                        tbody.innerHTML = `<tr><td colspan="2" style="text-align:center;color:#777;">Không có thông báo nào.</td></tr>`;
                        document.querySelector(".pg-container").innerHTML = "";
                    }
                });
        }

        // Load trang đầu tiên khi mở
        document.addEventListener("DOMContentLoaded", () => loadNotifications(1));

        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>