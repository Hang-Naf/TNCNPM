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
    <link rel="stylesheet" href="../form.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        #main-container {
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .add-btn {
            background: #0b1e6b;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f1f3f9;
        }

        .status.active {
            background-color: rgba(32, 164, 99, 0.2);
            color: #20a463;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .status.inactive {
            background-color: rgba(128, 128, 128, 0.2);
            color: gray;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .actions i {
            cursor: pointer;
            margin-right: 10px;
        }

        td:not(.no-center) {
            text-align: center;
        }

        #addPopup {
            display: none;
        }

        .hide-column {
            display: none;
        }

        .selection {
            display: flex;
            gap: 120px;
        }

        .selection h2 {
            color: gray;
        }

        .selection h2:hover {
            cursor: pointer;
            color: black;
            text-decoration: underline;
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
                    <li onclick="window.location.href='../pages/qlgiaovien.php'"><i
                            class="fa-solid fa-chalkboard-user"></i> Giáo viên</li>
                    <li onclick="window.location.href='../pages/qlhocsinh.php'"><i
                            class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pages/qllophoc.php'"><i class="fa-solid fa-school"></i> Lớp
                        học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học
                    </li>
                    <li onclick="window.location.href='../pages/qltailieu.php'"><i class="fa-solid fa-file-lines"></i>
                        Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlchuyencan.php'"><i class="fa-solid fa-check"></i>
                        Chuyên cần</li>
                    <li onclick="window.location.href='../pages/qldiemso.php'"><i
                            class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý thông tin</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông
                        báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý tài khoản</div>
                <ul>
                    <li onclick="window.location.href='../pages/phanconggiangday.php'"><i class="fa-solid fa-users"></i>
                        Phân công giảng dạy</li>
                    <li class="active" onclick="window.location.href='../pages/qlphanquyen.php'"><i
                            class="fa-solid fa-user-shield"></i> Phân quyền</li>
                </ul>
            </div>
        </nav>
    </aside>
    <div class="main-content">
        <header class="header">
            <div class="left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Tìm kiếm..." class="searchb">
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
        <div id="main-container">
            <h1>DANH SÁCH TÀI KHOẢN</h1>
            <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm mới</button>
            <div class="selection">
                <h2>Tất cả</h2>
                <h2>Giáo viên</h2>
                <h2>Học sinh</h2>
                <h2>Admin</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>Mã</th>
                        <th>Họ và tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Giới tính</th>
                        <th class="hide-column">Ngày sinh</th>
                        <th>Vai trò</th>
                        <th class="hide-column">Thay đổi vai trò</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><?= $row['userID'] ?></td>
                            <td><?= htmlspecialchars($row['hoVaTen']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['sdt']) ?></td>
                            <td><?= htmlspecialchars($row['gioiTinh']) ?></td>
                            <td class="hide-column"><?= $row['ngaySinh'] ?></td>
                            <td><?= $row['vaiTro'] ?></td>
                            <td class="hide-column">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="userID" value="<?= $row['userID'] ?>">
                                    <select name="vaiTro">
                                        <option value="Admin" <?= $row['vaiTro'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="GiaoVien" <?= $row['vaiTro'] == 'GiaoVien' ? 'selected' : '' ?>>Giáo
                                            viên
                                        </option>
                                        <option value="HocSinh" <?= $row['vaiTro'] == 'HocSinh' ? 'selected' : '' ?>>Học sinh
                                        </option>
                                    </select>
                                    <button type="submit" name="updateRole">Lưu</button>
                                </form>
                            </td>
                            <td>
                                <a href="?delete=<?= $row['userID'] ?>"
                                    onclick="return confirm('Xóa người dùng này?')">Xóa</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>
        <div class="popup-bg" id="addPopup">
            <div class="popup">
                <div class="them-hocsinh">
                    <h2 id="title-h2">THÊM NGƯỜI DÙNG MỚI</h2>
                    <form method="POST">
                        <div class="row">
                            <div class="form-group">
                                <label>Họ và tên:</label>
                                <input type="text" name="hoVaTen" required>
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Số điện thoại:</label>
                                <input type="text" name="sdt" required>
                            </div>
                            <div class="form-group">
                                <label>Ngày sinh:</label>
                                <input type="date" name="ngaySinh">
                            </div>
                        </div>
                        <h2 id="title-h2">PHÂN QUYỀN</h2>
                        <table style="width: 200px; background: none;">
                            <tr>
                                <td style="text-align: left;">Admin:</td>
                                <td><input type="radio" name="phanQuyen"></td>
                            </tr>
                            <tr>
                                <td style="text-align: left;">Giáo viên:</td>
                                <td><input type="radio" name="phanQuyen"></td>
                            </tr>
                            <tr>
                                <td style="text-align: left;">Học sinh:</td>
                                <td><input type="radio" name="phanQuyen"></td>
                            </tr>
                        </table>
                        <div class="buttons">
                            <button type="button" class="btn-secondary"
                                onclick="window.location.href='qlphanquyen.php'">Hủy</button>
                            <button type="submit" class="btn-primary" id="submitBtn">
                                <i class="fa-solid fa-plus"></i> Thêm mới
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        <script>
            document.getElementById("bellIcon").addEventListener("click", function () {
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
            document.addEventListener("click", function (e) {
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
            document.addEventListener("click", function (e) {
                const menu = document.getElementById("userMenu");
                const userInfo = document.querySelector(".user-info");
                if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
                    menu.style.display = "none";
                }
            });

            function showAddPopup() {
                document.getElementById('addPopup').style.display = 'block';
                document.getElementById('main-container').style.display = 'none';
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