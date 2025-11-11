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
    $maHS = $_POST['maHS'];
    $maMonHoc = $_POST['maMonHoc'];
    $ngayHoc = $_POST['ngayHoc'];
    $trangThai = $_POST['trangThai'];
    $ghiChu = $_POST['ghiChu'];

    $sql = "INSERT INTO chuyencan (maHS, maMonHoc, ngayHoc, trangThai, ghiChu)
            VALUES ('$maHS', '$maMonHoc', '$ngayHoc', '$trangThai', '$ghiChu')";
    if ($conn->query($sql)) {
        echo "<script>alert('Thêm điểm danh thành công!'); window.location='qlchuyencan.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== XỬ LÝ XÓA ==================
if (isset($_GET['delete'])) {
    $maDiemDanh = $_GET['delete'];
    $sql = "DELETE FROM chuyencan WHERE maDiemDanh = $maDiemDanh";
    if ($conn->query($sql)) {
        echo "<script>alert('Xóa thành công!'); window.location='qlchuyencan.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== XỬ LÝ CẬP NHẬT ==================
if (isset($_POST['update'])) {
    $maDiemDanh = $_POST['maDiemDanh'];
    $maHS = $_POST['maHS'];
    $maMonHoc = $_POST['maMonHoc'];
    $ngayHoc = $_POST['ngayHoc'];
    $trangThai = $_POST['trangThai'];
    $ghiChu = $_POST['ghiChu'];

    $sql = "UPDATE chuyencan 
            SET maHS='$maHS', maMonHoc='$maMonHoc', ngayHoc='$ngayHoc', 
                trangThai='$trangThai', ghiChu='$ghiChu'
            WHERE maDiemDanh='$maDiemDanh'";
    if ($conn->query($sql)) {
        echo "<script>alert('Cập nhật thành công!'); window.location='qlchuyencan.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== LẤY DỮ LIỆU ==================
$hocsinh = $conn->query("SELECT h.maHS, u.hoVaTen FROM hocsinh h 
                         JOIN user u ON h.maHS = u.userID");

$monhoc = $conn->query("SELECT * FROM monhoc ORDER BY tenMonHoc ASC");

$sql = "SELECT c.maDiemDanh, c.ngayHoc, c.trangThai, c.ghiChu,
               u.hoVaTen AS tenHS, m.tenMonHoc
        FROM chuyencan c
        LEFT JOIN hocsinh h ON c.maHS = h.maHS
        LEFT JOIN user u ON h.maHS = u.userID
        LEFT JOIN monhoc m ON c.maMonHoc = m.maMonHoc
        ORDER BY c.ngayHoc DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý chuyên cần</title>
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
            width: 150px;
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
                    <li class="active" onclick="window.location.href='../pages/qlchuyencan.php'"><i
                            class="fa-solid fa-check"></i> Chuyên cần</li>
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
                    <li onclick="window.location.href='../pages/qlphanquyen.php'"><i
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
        <!-- <h1>📋 Quản lý chuyên cần học sinh</h1>

        <h3>Thêm điểm danh mới</h3>
        <form method="POST">
            <label>Học sinh:</label>
            <select name="maHS" required>
                <option value="">-- Chọn học sinh --</option>
                <?php
                $hs = $conn->query("SELECT h.maHS, u.hoVaTen FROM hocsinh h JOIN user u ON h.maHS=u.userID");
                while ($r = $hs->fetch_assoc()) { ?>
                    <option value="<?= $r['maHS'] ?>"><?= htmlspecialchars($r['hoVaTen']) ?></option>
                <?php } ?>
            </select>

            <label>Môn học:</label>
            <select name="maMonHoc" required>
                <option value="">-- Chọn môn học --</option>
                <?php
                $mh = $conn->query("SELECT * FROM monhoc");
                while ($r = $mh->fetch_assoc()) { ?>
                    <option value="<?= $r['maMonHoc'] ?>"><?= htmlspecialchars($r['tenMonHoc']) ?></option>
                <?php } ?>
            </select>

            <label>Ngày học:</label>
            <input type="date" name="ngayHoc" required>

            <label>Trạng thái:</label>
            <select name="trangThai" required>
                <option value="Có mặt">Có mặt</option>
                <option value="Vắng có phép">Vắng có phép</option>
                <option value="Vắng không phép">Vắng không phép</option>
            </select>

            <label>Ghi chú:</label>
            <textarea name="ghiChu" rows="2"></textarea>

            <button type="submit" name="add">Thêm</button>
        </form> -->
        <div id="main-container">
            <h1>ĐIỂM DANH HỌC SINH</h1>
            <!-- <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm học sinh</button> -->
            <div class="row">
                <div class="form-group">
                    <label>Ngày:</label>
                    <input type="date">
                </div>
                <div class="form-group">
                    <label>Lớp:</label>
                    <select>
                        <option>Tất cả các lớp</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Môn:</label>
                    <select>
                        <option>Tất cả các lớp</option>
                    </select>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>Mã</th>
                        <th>Họ và tên</th>
                        <th>Môn học</th>
                        <th>Ngày học</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><?= $row['maDiemDanh'] ?></td>
                            <td><?= htmlspecialchars($row['tenHS']) ?></td>
                            <td><?= htmlspecialchars($row['tenMonHoc']) ?></td>
                            <td><?= $row['ngayHoc'] ?></td>
                            <td><?= $row['trangThai'] ?></td>
                            <td><?= htmlspecialchars($row['ghiChu']) ?></td>
                            <td>
                                <a href="?edit=<?= $row['maDiemDanh'] ?>">Sửa</a> |
                                <a href="?delete=<?= $row['maDiemDanh'] ?>"
                                    onclick="return confirm('Bạn có chắc muốn xóa không?')">Xóa</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
    // ============ FORM SỬA ============
    if (isset($_GET['edit'])) {
        $id = $_GET['edit'];
        $edit = $conn->query("SELECT * FROM chuyencan WHERE maDiemDanh = $id")->fetch_assoc();
        ?>
        <h3>Chỉnh sửa điểm danh</h3>
        <form method="POST">
            <input type="hidden" name="maDiemDanh" value="<?= $edit['maDiemDanh'] ?>">

            <label>Học sinh:</label>
            <select name="maHS" required>
                <?php
                $hs2 = $conn->query("SELECT h.maHS, u.hoVaTen FROM hocsinh h JOIN user u ON h.maHS=u.userID");
                while ($r = $hs2->fetch_assoc()) {
                    $sel = $r['maHS'] == $edit['maHS'] ? "selected" : "";
                    echo "<option value='{$r['maHS']}' $sel>{$r['hoVaTen']}</option>";
                } ?>
            </select>

            <label>Môn học:</label>
            <select name="maMonHoc" required>
                <?php
                $mh2 = $conn->query("SELECT * FROM monhoc");
                while ($r = $mh2->fetch_assoc()) {
                    $sel = $r['maMonHoc'] == $edit['maMonHoc'] ? "selected" : "";
                    echo "<option value='{$r['maMonHoc']}' $sel>{$r['tenMonHoc']}</option>";
                } ?>
            </select>

            <label>Ngày học:</label>
            <input type="date" name="ngayHoc" value="<?= $edit['ngayHoc'] ?>" required>

            <label>Trạng thái:</label>
            <select name="trangThai">
                <option <?= $edit['trangThai'] == 'Có mặt' ? 'selected' : '' ?>>Có mặt</option>
                <option <?= $edit['trangThai'] == 'Vắng có phép' ? 'selected' : '' ?>>Vắng có phép</option>
                <option <?= $edit['trangThai'] == 'Vắng không phép' ? 'selected' : '' ?>>Vắng không phép</option>
            </select>

            <label>Ghi chú:</label>
            <textarea name="ghiChu" rows="2"><?= htmlspecialchars($edit['ghiChu']) ?></textarea>

            <button type="submit" name="update">Cập nhật</button>
        </form>
    <?php } ?>
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

        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>