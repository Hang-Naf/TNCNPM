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
    $userID = intval($_POST['userID']);
    $vaiTro = $_POST['vaiTro'];

    $sql = "UPDATE user SET vaiTro = '$vaiTro' WHERE userID = $userID";
    if ($conn->query($sql)) {
        // Đồng bộ dữ liệu theo vai trò mới
        if ($vaiTro === "HocSinh") {
            $conn->query("UPDATE giaovien SET trangThai='inactive' WHERE maGV=$userID");

            $chucVu = "Học sinh";
            $namHoc = date("Y") . "-" . (date("Y") + 1);
            $hocKy = (date("n") >= 8 && date("n") <= 12) ? "HK1" : ((date("n") >= 1 && date("n") <= 5) ? "HK2" : "Hè");

            // KHÔNG ghi đè lopHocPhuTrach
            $conn->query("INSERT INTO hocsinh(maHS, chucVu, namHoc, hocKy, trangThai)
                  VALUES($userID,'$chucVu','$namHoc','$hocKy','active')
                  ON DUPLICATE KEY UPDATE chucVu='$chucVu', namHoc='$namHoc', hocKy='$hocKy', trangThai='active'");
        } elseif ($vaiTro === "GiaoVien") {
            $conn->query("UPDATE hocsinh SET trangThai='inactive' WHERE maHS=$userID");

            // KHÔNG ghi đè boMon nếu đã có
            $conn->query("INSERT INTO giaovien(maGV, boMon, trangThai)
                  VALUES($userID,'Chưa phân công','active')
                  ON DUPLICATE KEY UPDATE trangThai='active'");
        } elseif ($vaiTro === "Admin") {
            // Xóa cả dữ liệu học sinh và giáo viên
            // $conn->query("DELETE FROM hocsinh WHERE maHS = $userID");
            // $conn->query("DELETE FROM giaovien WHERE maGV = $userID");
            $conn->query("UPDATE giaovien SET trangThai='inactive' WHERE maGV=$userID");
            $conn->query("UPDATE hocsinh SET trangThai='inactive' WHERE maHS=$userID");
        }

        // Nếu đổi quyền của chính mình
        if ($userID == $_SESSION["userID"]) {
            session_destroy();
            if ($vaiTro === "GiaoVien") {
                echo "<script>alert('Bạn đã đổi quyền sang Giáo viên. Vui lòng đăng nhập lại!'); window.location='../pagegiaovien/ttcanhan.php';</script>";
            } elseif ($vaiTro === "HocSinh") {
                echo "<script>alert('Bạn đã đổi quyền sang Học sinh. Vui lòng đăng nhập lại!'); window.location='../pagehocsinh/ttcanhan.php';</script>";
            } else {
                echo "<script>alert('Bạn đã đổi quyền. Vui lòng đăng nhập lại!'); window.location='../dangnhap.php';</script>";
            }
        } else {
            echo "<script>alert('Cập nhật vai trò thành công!'); window.location='qlphanquyen.php';</script>";
        }
    } else {
        echo "Lỗi: " . $conn->error;
    }
}


// ================== LẤY DANH SÁCH NGƯỜI DÙNG ==================
// ==== PHÂN TRANG ====
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// ==== TRUY VẤN ĐẾM TỔNG SỐ ITEMS ====
$countSql = "SELECT COUNT(*) AS total FROM user";
$countResult = $conn->query($countSql);
$totalItems = ($countResult && $countResult->num_rows > 0) ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalItems / $itemsPerPage);

$sql = "SELECT userID, hoVaTen, email, sdt, vaiTro, gioiTinh, ngaySinh FROM user ORDER BY vaiTro, hoVaTen ASC LIMIT $offset, $itemsPerPage";
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

        .header {
            padding: 10px 25px;
            margin: -20px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: rgb(243, 243, 243);
            border-radius: 8px;
            padding: 0px 12px;
        }

        h1 {
            margin-top: 40px;
            margin-left: 20px;
        }

        table {
            width: 95%;
            border-collapse: collapse;
            background: white;
            margin: 40px 20px;
        }

        tr {
            text-align: center;
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
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
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
                    <input type="text" id="searchBox" placeholder="Tìm kiếm...">
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
                        <div id="xemChiTietThongBao"
                            style="text-align:center;padding:10px;background:#f0f2f6;cursor:pointer;font-size:13px;font-weight:600;color:#0b3364;border-top:1px solid #ddd;">
                            🔍 Xem chi tiết thông báo
                        </div>
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

        <h1>PHÂN QUYỀN</h1>

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
                </tr>
            </thead>
            <tbody>
                <?php $stt = $offset + 1;
                while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= $stt++ ?></td>
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
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- ========= THANH PHÂN TRANG ========= -->
        <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:-20px;">
            <span style="font-size:14px; color:#333;">Trang <?= $page ?>/<?= max(1, $totalPages) ?> (Tổng: <?= $totalItems ?> người dùng)</span>
            <div style="display:flex; gap:8px; align-items:center;">
                <?php if ($page > 1): ?>
                    <a href="?page=1" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">⏮ Đầu</a>
                    <a href="?page=<?= $page - 1 ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">◀ Trước</a>
                <?php else: ?>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">⏮ Đầu</button>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">◀ Trước</button>
                <?php endif; ?>

                <span style="font-weight:600; font-size:14px; min-width:30px; text-align:center;"><?= $page ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Sau ▶</a>
                    <a href="?page=<?= $totalPages ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Cuối ⏭</a>
                <?php else: ?>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Sau ▶</button>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Cuối ⏭</button>
                <?php endif; ?>
            </div>
        </div>
        <!-- ========= HẾT THANH PHÂN TRANG ========= -->
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

        const searchInput = document.getElementById("searchBox");
        const searchIcon = document.querySelector(".search-box i");
        const tableRows = document.querySelectorAll("tbody tr");

        function thucHienTimKiem() {
            const keyword = searchInput.value.trim().toLowerCase();
            let found = 0;

            tableRows.forEach(row => {
                const hoVaTen = row.children[1]?.innerText.toLowerCase() || "";
                const email = row.children[2]?.innerText.toLowerCase() || "";
                const sdt = row.children[3]?.innerText.toLowerCase() || "";

                if (hoVaTen.includes(keyword) || email.includes(keyword) || sdt.includes(keyword)) {
                    row.style.display = "";
                    found++;
                } else {
                    row.style.display = "none";
                }
            });

            // Nếu không tìm thấy, thêm dòng thông báo
            const oldRow = document.getElementById("noResultRow");
            if (oldRow) oldRow.remove();

            if (found === 0) {
                const tbody = document.querySelector("tbody");
                const tr = document.createElement("tr");
                tr.id = "noResultRow";
                tr.innerHTML = `<td colspan="8" style="text-align:center;color:gray;">Không tìm thấy người dùng phù hợp.</td>`;
                tbody.appendChild(tr);
            }
        }

        // Kích hoạt tìm kiếm khi nhập hoặc nhấn Enter
        searchInput.addEventListener("input", thucHienTimKiem);
        searchInput.addEventListener("keypress", e => {
            if (e.key === "Enter") {
                e.preventDefault();
                thucHienTimKiem();
            }
        });
        searchIcon.addEventListener("click", thucHienTimKiem);

        // Khi click vào "Xem chi tiết thông báo"
        document.getElementById("xemChiTietThongBao").addEventListener("click", function() {
            window.location.href = "../pages/qlthongbao.php";
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