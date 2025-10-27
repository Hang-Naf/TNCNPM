<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý giáo viên</title>
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
                    <li onclick="window.location.href='qlhocsinh.php'"><i
                            class="fa-solid fa-user-graduate"></i>
                        Học sinh</li>
                    <li class="active" onclick="window.location.href='qllophoc.php'"><i class="fa-solid fa-school"></i> Lớp học
                    </li>
                </ul>
            </div>
            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học
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
                <h2 id="title-h2">THÊM LỚP HỌC</h2>
                <div class="them-hocsinh">
                    <form id="addForm" class="student-form">
                        <input type="hidden" name="action" value="add" id="formAction">
                        <div class="row">
                            <div class="form-group">
                                <label>Tên lớp:</label>
                                <input type="text">
                            </div>
                            <div class="form-group">
                                <label>Sĩ số:</label>
                                <input type="text">
                            </div>
                            <div class="form-group">
                                <label>GVCN:</label>
                                <select name="maGV">
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label>Năm học:</label>
                                <input type="text">
                            </div>
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <div class="radio-group">
                                    <label><input type="radio" name="status" checked> Đang hoạt động</label>
                                    <label><input type="radio" name="status"> Tạm dừng</label>
                                </div>
                            </div>
                        </div>

                        <div class="buttons">
                            <button type="submit" class="btn-primary">
                                <i class="fa-solid fa-plus"></i> Thêm mới
                            </button>
                            <button type="button" class="btn-secondary" onclick="window.closePopup()">Hủy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>