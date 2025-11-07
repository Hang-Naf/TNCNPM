<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Admin ====
if ($_SESSION["vaiTro"] !== "Admin") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

// Xử lý thêm mới
if (isset($_POST['add'])) {
    $maMonHoc = $_POST['maMonHoc'];
    $tieuDe = $_POST['tieuDe'];
    $noiDung = $_POST['noiDung'];
    $ngayTai = date('Y-m-d');
    $maGV = $_POST['maGV'];
    $trangThai = $_POST['trangThai'];
    $maLop = $_POST['maLop'];

    $sql = "INSERT INTO tailieu (maMonHoc, maLop, tieuDe, noiDung, ngayTai, maGV, trangThai)
        VALUES ('$maMonHoc', '$maLop', '$tieuDe', '$noiDung', '$ngayTai', '$maGV', '$trangThai')";

    if ($conn->query($sql)) {
        echo "<script>alert('Thêm tài liệu thành công!'); window.location='qltailieu.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Xử lý xóa
if (isset($_GET['delete'])) {
    $maTL = $_GET['delete'];
    $sql = "DELETE FROM tailieu WHERE maTL = $maTL";
    if ($conn->query($sql)) {
        echo "<script>alert('Xóa thành công!'); window.location='qltailieu.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Xử lý cập nhật
if (isset($_POST['update'])) {
    $maTL = $_POST['maTL'];
    $maMonHoc = $_POST['maMonHoc'];
    $tieuDe = $_POST['tieuDe'];
    $noiDung = $_POST['noiDung'];
    $trangThai = $_POST['trangThai'];
    $maLop = $_POST['maLop'];

    $sql = "UPDATE tailieu 
        SET maMonHoc='$maMonHoc', maLop='$maLop', 
            tieuDe='$tieuDe', noiDung='$noiDung', trangThai='$trangThai'
        WHERE maTL='$maTL'";
    if ($conn->query($sql)) {
        echo "<script>alert('Cập nhật thành công!'); window.location='qltailieu.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Lấy danh sách môn học để hiển thị dropdown
$monhoc = $conn->query("SELECT * FROM monhoc");

// Lấy danh sách giáo viên
$giaovien = $conn->query("SELECT g.maGV, u.hoVaTen FROM giaovien g 
                          JOIN user u ON g.maGV = u.userID");

// Lấy danh sách tài liệu
$sql = "SELECT t.maTL, t.tieuDe, t.noiDung, t.ngayTai, t.trangThai, 
               m.tenMonHoc, u.hoVaTen AS tenGV
        FROM tailieu t
        LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
        LEFT JOIN user u ON t.maGV = u.userID";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý tài liệu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }

        form {
            margin: 20px 0;
        }

        .content-area {
            padding: 20px;
            font-family: 'Segoe UI', sans-serif;
            background: #f5f6fa;
            min-height: 100vh;
        }

        .content-area h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #111;
        }

        /* Bộ lọc */
        .filter-bar {
            display: flex;
            gap: 30px;
            align-items: center;
            background: #fafafa;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .filter-bar label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-bar select {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            min-width: 160px;
            background: #fff;
        }

        .filter-bar button {
            background: #0b3364;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            cursor: pointer;
            font-weight: 500;
        }

        .filter-bar button:hover {
            background: #124b8a;
        }

        /* Bảng danh sách */
        .table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        .table-container th,
        .table-container td {
            padding: 12px;
            text-align: left;
        }

        .table-container thead {
            background: #f7f8fa;
            color: #222;
            border-bottom: 1px solid #ddd;
        }

        .table-container tbody tr {
            border-top: 1px solid #eee;
            transition: background 0.2s;
        }

        .table-container tbody tr:hover {
            background: #f9f9f9;
        }

        /* Phân trang */
        .pagination-bar {
            padding: 12px 16px;
            background: #f9f9f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #eee;
        }

        .pagination-bar span {
            font-size: 14px;
            color: #333;
        }

        .pagination-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pagination-controls button {
            border: none;
            background: #eee;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
        }

        .pagination-controls button:hover:not(:disabled) {
            background: #ddd;
        }

        .pagination-controls button:disabled {
            opacity: 0.5;
            cursor: default;
        }

        .pagination-controls span {
            font-weight: 600;
            font-size: 14px;
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
                    <li onclick="window.location.href='../pages/qlgiaovien.php'"><i class="fa-solid fa-chalkboard-user"></i> Giáo viên</li>
                    <li onclick="window.location.href='../pages/qlhocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pages/qllophoc.php'"><i class="fa-solid fa-school"></i> Lớp học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học</li>
                    <li class="active" onclick="window.location.href='../pages/qltailieu.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlchuyencan.php'"><i class="fa-solid fa-check"></i> Chuyên cần</li>
                    <li onclick="window.location.href='../pages/qldiemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý thông tin</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý tài khoản</div>
                <ul>
                    <li onclick="window.location.href='../pages/phanconggiangday.php'"><i class="fa-solid fa-users"></i> Phân công giảng dạy</li>
                    <li onclick="window.location.href='../pages/qlphanquyen.php'"><i class="fa-solid fa-user-shield"></i> Phân quyền</li>
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
        <main class="content-area">
            <h1 style="font-size:24px; font-weight:700; margin-bottom:20px;">DANH SÁCH TÀI LIỆU</h1>

            <!-- Bộ lọc -->
            <form method="GET" class="filter-bar">

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Lớp:</label>
                    <select name="maLop" style="padding:8px 10px; border:1px solid #ccc; border-radius:6px; min-width:160px;">
                        <option value="">Tất cả lớp</option>
                        <?php
                        $lopList = $conn->query("SELECT * FROM lophoc ORDER BY tenLop ASC");
                        while ($lop = $lopList->fetch_assoc()) {
                            $selected = (isset($_GET['maLop']) && $_GET['maLop'] == $lop['maLop']) ? 'selected' : '';
                            echo "<option value='{$lop['maLop']}' $selected>{$lop['tenLop']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Môn:</label>
                    <select name="maMonHoc" style="padding:8px 10px; border:1px solid #ccc; border-radius:6px; min-width:160px;">
                        <option value="">Tất cả môn học</option>
                        <?php
                        $monList = $conn->query("SELECT * FROM monhoc ORDER BY tenMonHoc ASC");
                        while ($mon = $monList->fetch_assoc()) {
                            $selected = (isset($_GET['maMonHoc']) && $_GET['maMonHoc'] == $mon['maMonHoc']) ? 'selected' : '';
                            echo "<option value='{$mon['maMonHoc']}' $selected>{$mon['tenMonHoc']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit">Lọc</button>
            </form>

            <!-- Bảng danh sách -->
            <div class="table-container">

                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>TIÊU ĐỀ</th>
                            <th>MÔ TẢ</th>
                            <th>MÔN HỌC</th>
                            <th>NGƯỜI TẠO</th>
                            <th>TRẠNG THÁI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Xử lý lọc
                        $cond = "WHERE 1=1";
                        if (!empty($_GET['maLop'])) {
                            $maLop = intval($_GET['maLop']);
                            $cond .= " AND t.maLop = $maLop";
                        }
                        if (!empty($_GET['maMonHoc'])) {
                            $maMonHoc = intval($_GET['maMonHoc']);
                            $cond .= " AND t.maMonHoc = $maMonHoc";
                        }

                        $sql = "SELECT t.tieuDe, t.noiDung, t.trangThai, m.tenMonHoc, u.hoVaTen AS nguoiTao
                                FROM tailieu t
                                LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
                                LEFT JOIN user u ON t.maGV = u.userID
                                $cond
                                ORDER BY t.maTL DESC";

                        $ds = $conn->query($sql);
                        $stt = 1;
                        if ($ds->num_rows > 0) {
                            while ($r = $ds->fetch_assoc()) {
                                echo "<tr style='border-top:1px solid #eee;'>
                                    <td style='padding:10px;'>$stt</td>
                                    <td style='padding:10px;'>" . htmlspecialchars($r['tieuDe']) . "</td>
                                    <td style='padding:10px;'>" . htmlspecialchars($r['noiDung']) . "</td>
                                    <td style='padding:10px;'>" . htmlspecialchars($r['tenMonHoc']) . "</td>
                                    <td style='padding:10px;'>" . htmlspecialchars($r['nguoiTao']) . "</td>
                                    <td style='padding:10px;'>" . htmlspecialchars($r['trangThai'] ?? '') . "</td>
                                </tr>";
                                $stt++;
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding:20px;'>Không có tài liệu nào.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- Thanh phân trang giả lập -->
                <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee;">
                    <span>1–<?= min($stt - 1, 4) ?>/<?= $stt - 1 ?> mục</span>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px;">◀</button>
                        <span style="font-weight:600;">1/5</span>
                        <button style="border:none; background:#eee; border-radius:4px; padding:5px 10px;">▶</button>
                    </div>
                </div>
            </div>
        </main>
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