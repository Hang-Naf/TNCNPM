<?php
include_once(__DIR__ . "/../csdl/db.php"); // Kết nối CSDL

// ================== XỬ LÝ THÊM ==================
if (isset($_POST['add'])) {
    $hoVaTen = $_POST['hoVaTen'];
    $email = $_POST['email'];
    $sdt = $_POST['sdt'];
    $matKhau = $_POST['matKhau'];
    $vaiTro = $_POST['vaiTro'];
    $gioiTinh = $_POST['gioiTinh'];
    $ngaySinh = $_POST['ngaySinh'];

    // Kiểm tra xem có cột anhDaiDien không
    $sql_check = "SHOW COLUMNS FROM user LIKE 'anhDaiDien'";
    $hasAvatar = $conn->query($sql_check)->num_rows > 0;

    if ($hasAvatar) {
        $sql = "INSERT INTO user (hoVaTen, matKhau, sdt, ngaySinh, gioiTinh, email, vaiTro, anhDaiDien)
                VALUES ('$hoVaTen', '$matKhau', '$sdt', '$ngaySinh', '$gioiTinh', '$email', '$vaiTro', '')";
    } else {
        $sql = "INSERT INTO user (hoVaTen, matKhau, sdt, ngaySinh, gioiTinh, email, vaiTro)
                VALUES ('$hoVaTen', '$matKhau', '$sdt', '$ngaySinh', '$gioiTinh', '$email', '$vaiTro')";
    }

    if ($conn->query($sql)) {
        echo "<script>alert('Thêm người dùng thành công!'); window.location='qlphanquyen.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== XỬ LÝ CẬP NHẬT VAI TRÒ ==================
if (isset($_POST['updateRole'])) {
    $userID = $_POST['userID'];
    $vaiTro = $_POST['vaiTro'];

    $sql = "UPDATE user SET vaiTro = '$vaiTro' WHERE userID = '$userID'";
    if ($conn->query($sql)) {
        echo "<script>alert('Cập nhật vai trò thành công!'); window.location='qlphanquyen.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== XỬ LÝ XÓA ==================
if (isset($_GET['delete'])) {
    $userID = $_GET['delete'];
    $sql = "DELETE FROM user WHERE userID = $userID";
    if ($conn->query($sql)) {
        echo "<script>alert('Xóa người dùng thành công!'); window.location='qlphanquyen.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== LẤY DANH SÁCH NGƯỜI DÙNG ==================
$sql = "SELECT userID, hoVaTen, email, sdt, vaiTro, gioiTinh, ngaySinh FROM user ORDER BY vaiTro, hoVaTen ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý phân quyền</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            margin-bottom: 20px;
        }

        input,
        select {
            padding: 5px;
            margin: 5px 0;
        }

        button {
            padding: 6px 12px;
            cursor: pointer;
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
                    <li onclick="window.location.href='../pages/qltailieu.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
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
                    <li><i class="fa-solid fa-bell"></i> Thông báo</li>
                    <li><i class="fa-solid fa-calendar-days"></i> Sự kiện</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý tài khoản</div>
                <ul>
                    <li onclick="window.location.href='../pages/phanconggiangday.php'"><i class="fa-solid fa-users"></i> Phân công giảng dạy</li>
                    <li class="active" onclick="window.location.href='../pages/qlphanquyen.php'"><i class="fa-solid fa-user-shield"></i> Phân quyền</li>
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
                        <li><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <h1>⚙️ Quản lý phân quyền người dùng</h1>

        <h3>Thêm người dùng mới</h3>
        <form method="POST">
            <label>Họ và tên:</label>
            <input type="text" name="hoVaTen" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Số điện thoại:</label>
            <input type="text" name="sdt" required>

            <label>Ngày sinh:</label>
            <input type="date" name="ngaySinh">

            <label>Giới tính:</label>
            <select name="gioiTinh" required>
                <option value="Nam">Nam</option>
                <option value="Nữ">Nữ</option>
            </select>

            <label>Mật khẩu:</label>
            <input type="password" name="matKhau" required>

            <label>Vai trò:</label>
            <select name="vaiTro" required>
                <option value="HocSinh">Học sinh</option>
                <option value="GiaoVien">Giáo viên</option>
                <option value="Admin">Admin</option>
            </select>

            <button type="submit" name="add">Thêm người dùng</button>
        </form>

        <h3>Danh sách người dùng</h3>
        <table>
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Giới tính</th>
                    <th>Ngày sinh</th>
                    <th>Vai trò</th>
                    <th>Thay đổi vai trò</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $row['userID'] ?></td>
                        <td><?= htmlspecialchars($row['hoVaTen']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['sdt']) ?></td>
                        <td><?= htmlspecialchars($row['gioiTinh']) ?></td>
                        <td><?= $row['ngaySinh'] ?></td>
                        <td><?= $row['vaiTro'] ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="userID" value="<?= $row['userID'] ?>">
                                <select name="vaiTro">
                                    <option value="Admin" <?= $row['vaiTro'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="GiaoVien" <?= $row['vaiTro'] == 'GiaoVien' ? 'selected' : '' ?>>Giáo viên</option>
                                    <option value="HocSinh" <?= $row['vaiTro'] == 'HocSinh' ? 'selected' : '' ?>>Học sinh</option>
                                </select>
                                <button type="submit" name="updateRole">Lưu</button>
                            </form>
                        </td>
                        <td>
                            <a href="?delete=<?= $row['userID'] ?>" onclick="return confirm('Xóa người dùng này?')">Xóa</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script>
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
                window.location.href = "dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>