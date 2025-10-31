<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Admin ====
if ($_SESSION["vaiTro"] !== "Admin") {
    session_destroy();
    header("Location: dangnhap.php");
    exit();
}

// ==== Lấy danh sách môn học và trưởng bộ môn ====
$sql = "
    SELECT 
        m.maMonHoc,
        m.tenMonHoc,
        m.moTa,
        m.hocKy,
        m.trongSo,
        m.trangThai,
        m.namHoc,
        u.hoVaTen AS truongBoMon
    FROM monhoc m
    LEFT JOIN (
        SELECT gm.maMonHoc, gm.maGV
        FROM giaovien_monhoc gm
        GROUP BY gm.maMonHoc
    ) AS gvmh ON m.maMonHoc = gvmh.maMonHoc
    LEFT JOIN user u ON u.userID = gvmh.maGV
";
$result = $conn->query($sql);

// ==== Lấy danh sách giáo viên cho select ====
$gv_rs = $conn->query("
    SELECT g.maGV, u.hoVaTen, g.boMon 
    FROM giaovien g 
    JOIN user u ON g.maGV = u.userID
");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý môn học</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <link rel="stylesheet" href="popup.css">
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
                    <li class="active" onclick="window.location.href='qlmonhoc.php'"><i class="fa-solid fa-book"></i>
                        Môn học
                    </li>
                    <li onclick="window.location.href='qltailieu.php'"><i class="fa-solid fa-file-lines"></i> Tài
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
                <h2 id="title-h2">THÊM MÔN HỌC</h2>
                <div class="them-hocsinh">
                    <form id="addForm" class="student-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="maMonHoc">

                        <div class="row">
                            <div class="form-group">
                                <label>Tên môn:</label>
                                <input type="text" name="tenMonHoc" required>
                            </div>
                            <div class="form-group">
                                <label>Trưởng bộ môn:</label>
                                <select name="truongBoMon">
                                    <option value="">--Chọn Trưởng Bộ Môn--</option>
                                    <?php
                                    $gv_rs->data_seek(0);
                                    while ($gv = $gv_rs->fetch_assoc()):
                                        ?>
                                        <option value="<?= $gv['maGV'] ?>"><?= htmlspecialchars($gv['hoVaTen']) ?>
                                            (<?= htmlspecialchars($gv['boMon']) ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label>Năm học:</label>
                                <input type="text" name="namHoc" placeholder="VD: 2024-2025" required>
                            </div>

                            <div class="form-group">
                                <label>Học kỳ:</label>
                                <select name="hocKy">
                                    <option value="HK1">Học kỳ 1</option>
                                    <option value="HK2">Học kỳ 2</option>
                                    <option value="Hè">Học kỳ Hè</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Trọng số:</label>
                                <input type="number" name="trongSo" placeholder="Trọng số" step="0.1" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label>Mô tả:</label>
                                <textarea name="moTa"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="trangThai" value="Hoạt động"> Đang hoạt
                                        động</label>
                                    <label><input type="radio" name="trangThai" value="Ngưng"> Tạm dừng</label>
                                </div>
                            </div>
                        </div>

                        <div class="buttons">
                            <button type="submit" class="btn-primary" id="submitBtn">
                                <i class="fa-solid fa-plus"></i> Thêm mới
                            </button>
                            <button type="button" class="btn-secondary" onclick="closePopup()">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../header.js"></script>
    <script>
        document.getElementById("addForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch("../src/monhoc.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert(result.error);
                } else {
                    alert(result.message);
                    window.location.href = "qlmonhoc.php";
                }
            } catch (error) {
                console.error("Lỗi khi thêm môn học:", error);
                alert("Lỗi khi thêm môn học. Vui lòng thử lại!");
            }
        });
    </script>
</body>

</html>