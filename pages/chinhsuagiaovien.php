<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Lấy mã giáo viên từ URL ====
if (!isset($_GET['maGV'])) {
    die("Thiếu mã giáo viên!");
}
$maGV = $_GET['maGV'];

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

// ==== Lấy danh sách giáo viên ====
$sql = "
    SELECT 
        g.maGV, u.hoVaTen, u.gioiTinh, u.email, u.sdt,
        g.boMon, g.trinhDo, g.phongBan, g.namHoc, g.hocKy, g.trangThai
    FROM giaovien g
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien'
";
$result = $conn->query($sql);

// ==== Lấy danh sách môn học ====
$monhoc_rs = $conn->query("SELECT maMonHoc, tenMonHoc FROM monhoc");
$monhoc_list = [];
while ($mh = $monhoc_rs->fetch_assoc()) {
    $monhoc_list[] = $mh;
}

// ==== Lấy thông tin giáo viên từ CSDL ====
if (!isset($_GET['maGV'])) {
    die("Thiếu mã giáo viên!");
}
$maGV = $_GET['maGV'];

$sql = "
    SELECT 
        gv.maGV,
        u.hoVaTen,
        u.gioiTinh,
        u.email,
        u.sdt,
        gv.boMon,
        gv.trinhDo,
        gv.phongBan,
        gv.namHoc,
        gv.hocKy,
        gv.trangThai
    FROM giaovien gv
    JOIN user u ON gv.maGV = u.userID
    WHERE gv.maGV = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $maGV);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Không tìm thấy giáo viên có mã $maGV");
}
$gv = $result->fetch_assoc();
?>

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
                    <li class="active" onclick="window.location.href='qlgiaovien.php'"><i
                            class="fa-solid fa-chalkboard-user"></i> Giáo
                        viên</li>
                    <li onclick="window.location.href='qlhocsinh.php'"><i class="fa-solid fa-user-graduate"></i>
                        Học sinh</li>
                    <li onclick="window.location.href='qllophoc.php'"><i class="fa-solid fa-school"></i> Lớp học
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
                <div class="them-hocsinh">
                    <h2 id="title-h2">CHỈNH SỬA GIÁO VIÊN</h2>
                    <form id="addForm" class="form">
                        <input type="hidden" name="action" value="update" id="formAction">
                        <input type="hidden" name="userId" value="<?= htmlspecialchars($gv['maGV']) ?>">

                        <div class="row">
                            <div class="form-group">
                                <label for="hoVaTen">Họ và tên:</label>
                                <input type="text" id="hoVaTen" name="hoVaTen"
                                    value="<?= htmlspecialchars($gv['hoVaTen']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email:</label>
                                <input type="email" id="email" name="email"
                                    value="<?= htmlspecialchars($gv['email']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="sdt">Số điện thoại:</label>
                                <input type="text" id="sdt" name="sdt" value="<?= htmlspecialchars($gv['sdt']) ?>"
                                    required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label for="gioiTinh">Giới tính:</label>
                                <select id="gioiTinh" name="gioiTinh" required>
                                    <option value="Nam" <?= $gv['gioiTinh'] === 'Nam' ? 'selected' : '' ?>>Nam</option>
                                    <option value="Nữ" <?= $gv['gioiTinh'] === 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="boMon">Bộ môn:</label>
                                <select id="boMon" name="boMon" required>
                                    <?php foreach ($monhoc_list as $mh): ?>
                                        <option value="<?= htmlspecialchars($mh['tenMonHoc']) ?>"
                                            <?= ($gv['boMon'] === $mh['tenMonHoc']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mh['tenMonHoc']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="trinhDo">Trình độ:</label>
                                <input type="text" id="trinhDo" name="trinhDo"
                                    value="<?= htmlspecialchars($gv['trinhDo']) ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label for="phongBan">Phòng ban:</label>
                                <input type="text" id="phongBan" name="phongBan"
                                    value="<?= htmlspecialchars($gv['phongBan']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="namHoc">Năm học:</label>
                                <input type="text" id="namHoc" name="namHoc"
                                    value="<?= htmlspecialchars($gv['namHoc']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="hocKy">Học kỳ:</label>
                                <select id="hocKy" name="hocKy" required>
                                    <option value="HK1" <?= $gv['hocKy'] === 'HK1' ? 'selected' : '' ?>>HK1</option>
                                    <option value="HK2" <?= $gv['hocKy'] === 'HK2' ? 'selected' : '' ?>>HK2</option>
                                    <option value="Hè" <?= $gv['hocKy'] === 'Hè' ? 'selected' : '' ?>>Hè</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <label>
                                    <input type="radio" name="trangThai" value="active" <?= $gv['trangThai'] === 'active' ? 'checked' : '' ?>>
                                    Đang hoạt động
                                </label>
                                <label>
                                    <input type="radio" name="trangThai" value="inactive"
                                        <?= $gv['trangThai'] === 'inactive' ? 'checked' : '' ?>>
                                    Tạm dừng
                                </label>
                            </div>
                            <div class="form-group">
                                <button type="submit" name="update" class="btn btn-primary">Lưu thay đổi</button>
                                <a href="qlgiaovien.php" class="btn btn-secondary" onclick="window.location.href='qlgiaovien.php'">Hủy</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // === Xử lý thêm giáo viên qua AJAX ===
        document.getElementById("addForm").addEventListener("submit", async function (e) {
            e.preventDefault(); // ✅ Ngăn trình duyệt reload trang

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch("../src/giaovien.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert(result.error);
                } else {
                    alert(result.message);
                    window.location.href = "qlgiaovien.php";
                }
            } catch (error) {
                console.error("Lỗi khi thêm giáo viên:", error);
                alert("Lỗi khi thêm giáo viên. Vui lòng thử lại!");
            }
        });

        document.getElementById("bellIcon").addEventListener("click", function () {
            const dropdown = document.getElementById("notificationDropdown");
            // Hiện/ẩn menu
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";

            // Gọi AJAX lấy thông báo
            fetch("get_thongbao.php")
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
                fetch("update_trangthai.php", {
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
                window.location.href = "dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>