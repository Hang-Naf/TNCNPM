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

// ==== Lấy danh sách học sinh ====
$sql = "
    SELECT 
        h.maHS, u.hoVaTen, u.gioiTinh, u.email, u.sdt,
        h.lopHocPhuTrach, h.namHoc, h.hocKy, h.trangThai
    FROM hocsinh h
    JOIN user u ON h.maHS = u.userID
    WHERE u.vaiTro = 'HocSinh'
";
$result = $conn->query($sql);

// ==== Lấy danh sách lớp học ====
$lophoc_rs = $conn->query("SELECT maLop, tenLop FROM lophoc");
$lophoc_list = [];
while ($lh = $lophoc_rs->fetch_assoc()) {
    $lophoc_list[] = $lh;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý học sinh</title>
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
                    <li class="active" onclick="window.location.href='../pages/qlhocsinh.php'"><i
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
                    <li><i class="fa-solid fa-bell"></i> Thông báo</li>
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
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1>QUẢN LÝ HỌC SINH</h1>
                <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm Học Sinh</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>STT</th>
                        <th>MÃ HỌC SINH</th>
                        <th>HỌ TÊN</th>
                        <th class="hide-column">GIỚI TÍNH</th>
                        <th class="hide-column">EMAIL</th>
                        <th class="hide-column">SDT</th>
                        <th>LỚP</th>
                        <th>KHÓA HỌC</th>
                        <th>CHỨC VỤ</th>
                        <th>TRẠNG THÁI</th>
                        <th>TÁC VỤ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0):
                        $stt = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['maHS'] ?>">
                                <td><input type="checkbox"></td>
                                <td><?= $stt++ ?></td>
                                <td><?= $row['maHS'] ?></td>
                                <td class="no-center"><?= htmlspecialchars($row['hoVaTen']) ?></td>
                                <td class="hide-column"><?= htmlspecialchars($row['gioiTinh']) ?></td>
                                <td class="hide-column"><?= htmlspecialchars($row['email']) ?></td>
                                <td class="hide-column"><?= htmlspecialchars($row['sdt']) ?></td>
                                <td><?= htmlspecialchars($row['lopHocPhuTrach']) ?></td>
                                <td><?= htmlspecialchars($row['namHoc']) ?></td>
                                <td></td>
                                <td>
                                    <span class="status <?= $row['trangThai'] === 'active' ? 'active' : 'inactive' ?>">
                                        <?= $row['trangThai'] === 'active' ? '● Active' : '● Inactive' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <i class="fa-solid fa-pen edit-btn"></i>
                                    <i class="fa-solid fa-trash delete-btn"></i>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="12" style="text-align:center;">Không có dữ liệu</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="popup-bg" id="addPopup">
            <div class="popup">
                <h1 id="title-h2">THÊM HỌC SINH</h1>
                <br>
                <div class="them-hocsinh">
                    <form id="addForm" class="student-form">
                        <input type="hidden" name="action" value="add" id="formAction">
                        <div class="row">
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Năm học:</label>
                                <input type="text" name="namHoc" id="addNamHoc" placeholder="VD: 2022-2025" required
                                    readonly>
                            </div>
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Học kỳ:</label>
                                <select name="hocKy" id="addHocKy" readonly>
                                    <option value="">-- Học kỳ tự động --</option>
                                </select>
                            </div>
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Mã học sinh:</label>
                                <input type="text">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Họ và Tên:</label>
                                <input type="text" name="hoVaTen" id="HoTen" required>
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" name="email" id="Email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Số Điện Thoại:</label>
                                <input type="text" name="sdt" id="Sdt">
                            </div>
                            <div class="form-group">
                                <label>Giới tính:</label>
                                <select name="gioiTinh" id="GioiTinh">
                                    <option hidden></option>
                                    <option value="Nam">Nam</option>
                                    <option value="Nữ">Nữ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Chức vụ:</label>
                                <select>
                                    <option value=""></option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Lớp học:</label>
                                <select name="lopHocPhuTrach" required>
                                    <option hidden></option>
                                    <?php foreach ($lophoc_list as $lh): ?>
                                        <option value="<?= htmlspecialchars($lh['tenLop']) ?>">
                                            <?= htmlspecialchars($lh['tenLop']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <div class="form-group-horizontal">
                                    <label><input type="radio" name="trangThai" value="active" checked> Đang học</label>
                                    <label><input type="radio" name="trangThai" value="inactive"> Đã nghỉ</label>
                                </div>

                            </div>
                        </div>
                        <div class="buttons">
                            <button type="button" class="btn-secondary"
                                onclick="window.location.href='qlhocsinh.php'">Hủy</button>
                            <button type="submit" class="btn-primary" id="submitButton">
                                <i class="fa-solid fa-plus"></i> Thêm mới
                            </button>
                        </div>
                    </form>
                </div>
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

        // Đóng menu nếu click ra ngoài
        document.addEventListener("click", function (e) {
            const menu = document.getElementById("userMenu");
            const userInfo = document.querySelector(".user-info");
            if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = "none";
            }
        });
        const apiHocSinh = "../src/hocsinh.php";

        // === Xử lý thêm học sinh qua AJAX ===
        document.getElementById("addForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch("../src/hocsinh.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert(result.error);
                } else {
                    alert(result.message);
                    location.reload();
                }


            } catch (error) {
                console.error("Lỗi khi thêm học sinh:", error);
                alert("Lỗi khi thêm học sinh. Vui lòng thử lại!");
            }
        });

        // === Mở popup thêm ===
        function showAddPopup() {
            document.getElementById('addPopup').style.display = 'block';
            document.getElementById('main-container').style.display = 'none';
        }

        // === Mở popup sửa học sinh ===
        document.addEventListener("click", e => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");

                // Lấy dữ liệu từ dòng
                const userId = tr.dataset.id;
                const hoVaTen = tr.children[3].innerText.trim();
                const gioiTinh = tr.children[4].innerText.trim();
                const email = tr.children[5].innerText.trim();
                const sdt = tr.children[6].innerText.trim();
                const lopHoc = tr.children[7].innerText.trim();
                const namHoc = tr.children[8].innerText.trim();
                const hocKy = tr.children[9].innerText.trim();
                const trangThaiText = tr.children[10].innerText.trim();
                const trangThai = trangThaiText === "Hoạt động" ? "active" : "inactive";

                // Hiển thị popup và đổi giao diện nút
                showAddPopup();
                document.getElementById("title-h2").innerText = "CHỈNH SỬA HỌC SINH";
                document.querySelector("input[name='action']").value = "update";
                document.getElementById("submitButton").innerHTML = '<i class="fa-solid fa-pen"></i> Cập nhật';

                // Gán dữ liệu vào form theo id / name
                const form = document.getElementById("addForm");
                form.querySelector("input[name='userId']")?.remove(); // xóa nếu đã tồn tại
                const hiddenInput = document.createElement("input");
                hiddenInput.type = "hidden";
                hiddenInput.name = "userId";
                hiddenInput.value = userId;
                form.appendChild(hiddenInput);

                form.querySelector("#HoTen").value = hoVaTen;
                form.querySelector("#Email").value = email;
                form.querySelector("#Sdt").value = sdt;
                form.querySelector("#GioiTinh").value = gioiTinh;

                // Chọn lớp học
                const selectLop = form.querySelector("select[name='lopHocPhuTrach']");
                for (let opt of selectLop.options) {
                    if (opt.value === lopHoc) {
                        opt.selected = true;
                        break;
                    }
                }

                // Năm học và học kỳ
                form.querySelector("#addNamHoc").value = namHoc;
                form.querySelector("#addHocKy").value = hocKy;

                // Trạng thái
                const radioEls = form.querySelectorAll("input[name='trangThai']");
                radioEls[0].checked = trangThai === "active"; // Đang học
                radioEls[1].checked = trangThai === "inactive"; // Đã nghỉ
            }
        });

        // Xóa học sinh
        document.addEventListener("click", async (e) => {
            if (e.target.classList.contains("delete-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;
                if (confirm("Bạn có chắc muốn xóa học sinh này?")) {
                    const res = await fetch(apiHocSinh, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: "delete",
                            userId: id
                        })
                    });
                    const json = await res.json();
                    alert(json.message || json.error);
                    if (json.message) location.reload();
                }
            }
        });

        // === Menu người dùng ===
        function toggleUserMenu() {
            const menu = document.getElementById("userMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        document.addEventListener("click", function (e) {
            const menu = document.getElementById("userMenu");
            const userInfo = document.querySelector(".user-info");
            if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = "none";
            }
        });

        // === Đăng xuất ===
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "dangnhap.php";
            }
        }
    </script>

</body>

</html>