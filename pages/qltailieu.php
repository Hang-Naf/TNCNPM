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
                    <li  onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                    <li  onclick="window.location.href='../pages/qltsukien.php'"><i class="fa-solid fa-calendar-days"></i> Sự kiện</li>
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
                        <li><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>
        <h1>📚 Quản lý tài liệu</h1>

    <h3>Thêm tài liệu mới</h3>
    <form method="POST">
        <label>Môn học:</label>
        <select name="maMonHoc" required>
            <option value="">-- Chọn môn học --</option>
            <?php while ($row = $monhoc->fetch_assoc()) { ?>
                <option value="<?= $row['maMonHoc'] ?>"><?= htmlspecialchars($row['tenMonHoc']) ?></option>
            <?php } ?>
        </select>

        <label>Tiêu đề:</label>
        <input type="text" name="tieuDe" required>

        <label>Nội dung:</label>
        <textarea name="noiDung" rows="3" required></textarea>

        <label>Giáo viên tải lên:</label>
        <select name="maGV" required>
            <option value="">-- Chọn giáo viên --</option>
            <?php while ($row = $giaovien->fetch_assoc()) { ?>
                <option value="<?= $row['maGV'] ?>"><?= htmlspecialchars($row['hoVaTen']) ?></option>
            <?php } ?>
        </select>

        <label>Trạng thái:</label>
        <select name="trangThai" required>
            <option value="Công khai">Công khai</option>
            <option value="Riêng tư">Riêng tư</option>
        </select>

        <button type="submit" name="add">Thêm</button>
    </form>

    <h3>Danh sách tài liệu</h3>
    <table>
        <thead>
            <tr>
                <th>Mã TL</th>
                <th>Tiêu đề</th>
                <th>Môn học</th>
                <th>Giáo viên</th>
                <th>Ngày tải</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['maTL'] ?></td>
                    <td><?= htmlspecialchars($row['tieuDe']) ?></td>
                    <td><?= htmlspecialchars($row['tenMonHoc']) ?></td>
                    <td><?= htmlspecialchars($row['tenGV']) ?></td>
                    <td><?= $row['ngayTai'] ?></td>
                    <td><?= $row['trangThai'] ?></td>
                    <td>
                        <a href="qltailieu.php?edit=<?= $row['maTL'] ?>">Sửa</a> |
                        <a href="qltailieu.php?delete=<?= $row['maTL'] ?>" onclick="return confirm('Xóa tài liệu này?')">Xóa</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    </div>
    
    <?php
    // Hiển thị form sửa
    if (isset($_GET['edit'])) {
        $id = $_GET['edit'];
        $edit = $conn->query("SELECT * FROM tailieu WHERE maTL = $id")->fetch_assoc();
    ?>
        <h3>Chỉnh sửa tài liệu</h3>
        <form method="POST">
            <input type="hidden" name="maTL" value="<?= $edit['maTL'] ?>">

            <label>Môn học:</label>
            <select name="maMonHoc" required>
                <?php
                $monhoc2 = $conn->query("SELECT * FROM monhoc");
                while ($m = $monhoc2->fetch_assoc()) {
                    $sel = $edit['maMonHoc'] == $m['maMonHoc'] ? "selected" : "";
                    echo "<option value='{$m['maMonHoc']}' $sel>{$m['tenMonHoc']}</option>";
                }
                ?>
            </select>

            <label>Tiêu đề:</label>
            <input type="text" name="tieuDe" value="<?= htmlspecialchars($edit['tieuDe']) ?>" required>

            <label>Nội dung:</label>
            <textarea name="noiDung" rows="3"><?= htmlspecialchars($edit['noiDung']) ?></textarea>

            <label>Trạng thái:</label>
            <select name="trangThai">
                <option <?= $edit['trangThai'] == 'Công khai' ? 'selected' : '' ?>>Công khai</option>
                <option <?= $edit['trangThai'] == 'Riêng tư' ? 'selected' : '' ?>>Riêng tư</option>
            </select>

            <button type="submit" name="update">Cập nhật</button>
        </form>
    <?php } ?>
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