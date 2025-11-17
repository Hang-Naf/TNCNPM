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

$maGV = $_SESSION["userID"];
// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = '$maGV' 
           LIMIT 1";
$resultGV = $conn->query($sqlGV);
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];


// ==== Lấy mã lớp từ URL ====
if (!isset($_GET['maLop'])) {
    die("Chưa chọn lớp");
}
$maLop = $_GET['maLop'];

// ==== Lấy thông tin lớp (chỉ khi lớp thuộc giáo viên này) ====
$sqlLop = "SELECT l.maLop, l.tenLop, l.namHoc, l.trangThai,
                  u.hoVaTen AS giaoVien,
                  COUNT(hl.maHS) AS siSo
           FROM lophoc l
           LEFT JOIN giaovien g ON l.maGV = g.maGV
           LEFT JOIN user u ON g.maGV = u.userID
           LEFT JOIN hocsinh_lophoc hl ON l.maLop = hl.maLop
           WHERE l.maLop = ? AND l.maGV = ?
           GROUP BY l.maLop, l.tenLop, l.namHoc, l.trangThai, u.hoVaTen";
$stmtLop = $conn->prepare($sqlLop);
if (!$stmtLop) {
    die("Lỗi prepare SQL: " . $conn->error);
}
$stmtLop->bind_param("ss", $maLop, $maGV);
$stmtLop->execute();
$resultLop = $stmtLop->get_result();
if (!$resultLop || $resultLop->num_rows === 0) {
    die("Lớp không tồn tại hoặc bạn không được phân công");
}
$lop = $resultLop->fetch_assoc();

// ==== Tách khối từ tên lớp ====
$khoi = preg_match('/^\d+/', $lop['tenLop'], $matches) ? $matches[0] : '';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết lớp <?= htmlspecialchars($lop['tenLop']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .container {
            padding: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #f1f3f8;
        }

        .status.active {
            color: green;
            font-weight: 500;
        }

        .status.inactive {
            color: gray;
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
                    <li class="active"onclick="window.location.href='../pagegiaovien/lophoc.php'"><i class="fa-solid fa-school"></i> Lớp học</li>
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
                    <li  onclick="window.location.href='../pagegiaovien/chuyencan.php'"><i class="fa-solid fa-check"></i> Chuyên cần</li>
                    <li onclick="window.location.href='../pagegiaovien/diemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
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
                    <input type="text" id="searchAttendance" placeholder="Tìm kiếm học sinh...">
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
        <h1>Chi tiết lớp: <?= htmlspecialchars($lop['tenLop']) ?></h1>

        <table class="table">
            <thead>
                <tr>
                    <th>Năm học</th>
                    <th>Khối</th>
                    <th>Mã lớp</th>
                    <th>Tên lớp</th>
                    <th>Giáo viên chủ nhiệm</th>
                    <th>Sĩ số</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($lop['namHoc']) ?></td>
                    <td><?= htmlspecialchars($khoi) ?></td>
                    <td><?= htmlspecialchars($lop['maLop']) ?></td>
                    <td><?= htmlspecialchars($lop['tenLop']) ?></td>
                    <td><?= htmlspecialchars($lop['giaoVien'] ?? 'Chưa phân công') ?></td>
                    <td><?= $lop['siSo'] ?></td>
                    <td class="status <?= ($lop['trangThai'] == 'Đang học') ? 'active' : 'inactive' ?>">
                        <?= ($lop['trangThai'] == 'Đang học') ? 'Đang học' : 'Tạm dừng' ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <br>
        <button onclick="window.history.back()">Quay lại</button>
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

        // Xử lý tìm kiếm học sinh
        const searchInput = document.getElementById("searchAttendance");
        const attendanceRows = document.querySelectorAll(".attendance-row");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                attendanceRows.forEach(row => {
                    const name = row.getAttribute("data-name").toLowerCase();
                    row.style.display = name.includes(keyword) ? "" : "none";
                });
            });
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