<?php
include_once(__DIR__ . "/../csdl/db.php"); // Đảm bảo có file kết nối CSDL

// Xử lý thêm mới
if (isset($_POST['add'])) {
    $maMonHoc = $_POST['maMonHoc'];
    $tieuDe = $_POST['tieuDe'];
    $noiDung = $_POST['noiDung'];
    $ngayTai = date('Y-m-d');
    $maGV = $_POST['maGV'];
    $trangThai = $_POST['trangThai'];

    $sql = "INSERT INTO tailieu (maMonHoc, tieuDe, noiDung, ngayTai, maGV, trangThai)
            VALUES ('$maMonHoc', '$tieuDe', '$noiDung', '$ngayTai', '$maGV', '$trangThai')";
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

    $sql = "UPDATE tailieu 
            SET maMonHoc='$maMonHoc', tieuDe='$tieuDe', noiDung='$noiDung', trangThai='$trangThai'
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
</head>
<style>
    .popup-bg {
        padding: 20px;
    }

    .popup {
        position: relative;
        font-family: 'Segoe UI', sans-serif;
    }

    .form {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .form-group {
        flex: 1;
    }

    .row {
        width: 100%;
        display: flex;
        gap: 20px;
    }

    label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
    }

    input,
    select {
        width: 100%;
        padding: 10px;
        border-radius: 6px;
        box-sizing: border-box;
    }

    .radio-group input[type="radio"] {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }

    .buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-primary {
        background: #0b1e6b;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-primary:hover {
        background: #0d2591;
    }

    .btn-secondary {
        background: #ccc;
        color: #333;
        border: none;
        padding: 10px 18px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
    }

    .btn-secondary:hover {
        background: #bbb;
    }

    textarea {
        width: 99%;
        height: 200%;
    }
</style>

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
                    <li onclick="window.location.href='index.php'"><i class="fa-solid fa-house"></i>
                        Dashboard</li>
                    <li onclick="window.location.href='qlgiaovien.php'"><i class="fa-solid fa-chalkboard-user"></i> Giáo
                        viên</li>
                    <li onclick="window.location.href='qlhocsinh.php'"><i class="fa-solid fa-user-graduate"></i>
                        Học sinh</li>
                    <li onclick="window.location.href='qllophoc.php'"><i class="fa-solid fa-school"></i>
                        Lớp học
                    </li>
                </ul>
            </div>
            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học
                    </li>
                    <li class="active" onclick="window.location.href='qltailieu.php'"><i
                            class="fa-solid fa-file-lines"></i> Tài
                        liệu</li>
                </ul>
            </div>
            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='qlchuyencan.php'"><i class="fa-solid fa-check"></i> Chuyên
                        cần</li>
                    <li onclick="window.location.href='qldiemso.php'"><i class="fa-solid fa-clipboard-list"></i>
                        Điểm số</li>
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
                    <li onclick="window.location.href='phanconggiangday.php'"><i class="fa-solid fa-users"></i>
                        Phân công giảng dạy</li>
                    <li onclick="window.location.href='qlphanquyen.php'"><i class="fa-solid fa-user-shield"></i>
                        Phân quyền</li>
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
                        <li><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>
        <div class="popup-bg" id="addPopup">
            <div class="popup">
                <h2 id="title-h2">THÊM TÀI LIỆU</h2>
                <div class="them-hocsinh">
                    <form method="post" action="" id="addForm" class="student-form">
                        <input type="hidden" name="action" value="add" id="formAction">
                        <input type="hidden" name="userId" id="userId">
                        <div class="row">
                            <div class="form-group">
                                <label>Môn học:</label>
                                <select name="maMonHoc" required>
                                    <option value="">-- Chọn môn học --</option>
                                    <?php while ($row = $monhoc->fetch_assoc()) { ?>
                                        <option value="<?= $row['maMonHoc'] ?>"><?= htmlspecialchars($row['tenMonHoc']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tiêu đề:</label>
                                <input type="text" name="tieuDe" required>
                            </div>
                            <div class="form-group">
                                <label>Giáo viên tải lên:</label>
                                <select name="maGV" required>
                                    <option hidden></option>
                                    <?php while ($row = $giaovien->fetch_assoc()) { ?>
                                        <option value="<?= $row['maGV'] ?>"><?= htmlspecialchars($row['hoVaTen']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Nội dung:</label>
                                <textarea name="noiDung" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <select name="trangThai" required>
                                    <option value="Công khai">Công khai</option>
                                    <option value="Riêng tư">Riêng tư</option>
                                </select>
                            </div>
                        </div>
                        <div class="buttons">
                            <button type="submit" class="btn-primary" name="add">
                                <i class="fa-solid fa-plus"></i> Thêm mới
                            </button>
                            <button type="button" class="btn-secondary" onclick="window.location.href='qltailieu.php'">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="../header.js"></script>
</body>

</html>