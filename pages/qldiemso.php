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
    $loaiDiem = $_POST['loaiDiem'];
    $diem = $_POST['diem'];
    $nhanXet = $_POST['nhanXet'];
    $ngayCapNhat = date('Y-m-d');

    $sql = "INSERT INTO diemso (maHS, maMonHoc, loaiDiem, diem, ngayCapNhat, nhanXet)
            VALUES ('$maHS', '$maMonHoc', '$loaiDiem', '$diem', '$ngayCapNhat', '$nhanXet')";
    if ($conn->query($sql)) {
        echo "<script>alert('Thêm điểm thành công!'); window.location='qldiemso.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== XỬ LÝ XÓA ==================
if (isset($_GET['delete'])) {
    $maDiem = $_GET['delete'];
    $sql = "DELETE FROM diemso WHERE maDiem = $maDiem";
    if ($conn->query($sql)) {
        echo "<script>alert('Xóa thành công!'); window.location='qldiemso.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== XỬ LÝ CẬP NHẬT ==================
if (isset($_POST['update'])) {
    $maDiem = $_POST['maDiem'];
    $maHS = $_POST['maHS'];
    $maMonHoc = $_POST['maMonHoc'];
    $loaiDiem = $_POST['loaiDiem'];
    $diem = $_POST['diem'];
    $nhanXet = $_POST['nhanXet'];

    $sql = "UPDATE diemso 
            SET maHS='$maHS', maMonHoc='$maMonHoc', loaiDiem='$loaiDiem', 
                diem='$diem', nhanXet='$nhanXet'
            WHERE maDiem='$maDiem'";
    if ($conn->query($sql)) {
        echo "<script>alert('Cập nhật thành công!'); window.location='qldiemso.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ================== LẤY DỮ LIỆU ==================
$hocsinh = $conn->query("SELECT h.maHS, u.hoVaTen FROM hocsinh h JOIN user u ON h.maHS = u.userID");
$monhoc = $conn->query("SELECT * FROM monhoc ORDER BY tenMonHoc ASC");

$sql = "SELECT d.maDiem, d.loaiDiem, d.diem, d.ngayCapNhat, d.nhanXet,
               u.hoVaTen AS tenHS, m.tenMonHoc
        FROM diemso d
        LEFT JOIN hocsinh h ON d.maHS = h.maHS
        LEFT JOIN user u ON h.maHS = u.userID
        LEFT JOIN monhoc m ON d.maMonHoc = m.maMonHoc
        ORDER BY d.ngayCapNhat DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý điểm số</title>
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

        #addPopup {
            display: none;
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
                    <li class="active" onclick="window.location.href='../pages/qldiemso.php'"><i
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

        <!-- <h3>Thêm điểm mới</h3>
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

            <label>Loại điểm:</label>
            <select name="loaiDiem" required>
                <option value="Miệng">Miệng</option>
                <option value="15 phút">15 phút</option>
                <option value="1 tiết">1 tiết</option>
                <option value="Giữa kỳ">Giữa kỳ</option>
                <option value="Cuối kỳ">Cuối kỳ</option>
            </select>

            <label>Điểm:</label>
            <input type="number" name="diem" step="0.1" min="0" max="10" required>

            <label>Nhận xét:</label>
            <textarea name="nhanXet" rows="2"></textarea>

            <button type="submit" name="add">Thêm</button>
        </form> -->
        <div id="main-container">
            <h1>BẢNG ĐIỂM</h1>
            <div class="row">
                <div class="form-group">
                    <label>Năm học:</label>
                    <select></select>
                </div>
                <div class="form-group">
                    <label>Lớp:</label>
                    <select></select>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Học sinh</th>
                        <th>Môn học</th>
                        <th>Loại điểm</th>
                        <th>Điểm</th>
                        <th>Ngày cập nhật</th>
                        <th>Nhận xét</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td><?= $row['maDiem'] ?></td>
                            <td><?= htmlspecialchars($row['tenHS']) ?></td>
                            <td><?= htmlspecialchars($row['tenMonHoc']) ?></td>
                            <td><?= htmlspecialchars($row['loaiDiem']) ?></td>
                            <td><?= $row['diem'] ?></td>
                            <td><?= $row['ngayCapNhat'] ?></td>
                            <td><?= htmlspecialchars($row['nhanXet']) ?></td>
                            <td>
                                <a href="?edit=<?= $row['maDiem'] ?>">Sửa</a> |
                                <a href="?delete=<?= $row['maDiem'] ?>"
                                    onclick="return confirm('Bạn có chắc muốn xóa không?')">Xóa</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>

        <?php
        // ================== FORM SỬA ==================
        if (isset($_GET['edit'])) {
            $id = $_GET['edit'];
            $edit = $conn->query("SELECT * FROM diemso WHERE maDiem = $id")->fetch_assoc();
            ?>
            <h3>Chỉnh sửa điểm</h3>
            <form method="POST">
                <input type="hidden" name="maDiem" value="<?= $edit['maDiem'] ?>">

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

                <label>Loại điểm:</label>
                <select name="loaiDiem">
                    <option <?= $edit['loaiDiem'] == 'Miệng' ? 'selected' : '' ?>>Miệng</option>
                    <option <?= $edit['loaiDiem'] == '15 phút' ? 'selected' : '' ?>>15 phút</option>
                    <option <?= $edit['loaiDiem'] == '1 tiết' ? 'selected' : '' ?>>1 tiết</option>
                    <option <?= $edit['loaiDiem'] == 'Giữa kỳ' ? 'selected' : '' ?>>Giữa kỳ</option>
                    <option <?= $edit['loaiDiem'] == 'Cuối kỳ' ? 'selected' : '' ?>>Cuối kỳ</option>
                </select>

                <label>Điểm:</label>
                <input type="number" name="diem" step="0.1" min="0" max="10" value="<?= $edit['diem'] ?>" required>

                <label>Nhận xét:</label>
                <textarea name="nhanXet" rows="2"><?= htmlspecialchars($edit['nhanXet']) ?></textarea>

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