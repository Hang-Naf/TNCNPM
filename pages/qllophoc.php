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

// ==== Lấy danh sách lớp học ====
$sql = "
    SELECT 
        l.maLop, 
        l.tenLop, 
        LEFT(l.tenLop, 2) AS khoi,  -- Tự động lấy khối từ tên lớp
        l.siSo, 
        l.trangThai, 
        l.namHoc,
        g.maGV, 
        u.hoVaTen AS tenGV
    FROM lophoc l
    LEFT JOIN giaovien g ON l.maGV = g.maGV
    LEFT JOIN user u ON g.maGV = u.userID
";
$result = $conn->query($sql);

// ==== Lấy danh sách giáo viên cho select ====
$giaovien_rs = $conn->query("
    SELECT g.maGV, u.hoVaTen 
    FROM giaovien g 
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien'
");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý lớp học</title>
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
            text-align: center;
        }

        th {
            background: #f1f3f9;
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
                    <li onclick="window.location.href='../pages/qlhocsinh.php'"><i
                            class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li class="active" onclick="window.location.href='../pages/qllophoc.php'"><i
                            class="fa-solid fa-school"></i> Lớp học</li>
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
            <h1>QUẢN LÝ LỚP HỌC</h1>
            <button class="add-btn" onclick="showAddPopup()">
                <i class="fa-solid fa-plus"></i> Thêm Lớp Học
            </button>

            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox"></th>
                        <th>STT</th>
                        <th>MÃ LỚP</th>
                        <th>TÊN LỚP</th>
                        <th>SĨ SỐ</th>
                        <th>GIÁO VIÊN PHỤ TRÁCH</th>
                        <th class="hide-column">NĂM HỌC</th>
                        <th>TRẠNG THÁI</th>
                        <th>TÁC VỤ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0):
                        $stt = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['maLop'] ?>">
                                <td><input type="checkbox"></td>
                                <td><?= $stt++ ?></td>
                                <td><?= $row['maLop'] ?></td>
                                <td><?= htmlspecialchars($row['tenLop']) ?></td>
                                <td><?= htmlspecialchars($row['siSo']) ?></td>
                                <td data-gv="<?= $row['maGV'] ?? '' ?>"><?= htmlspecialchars($row['tenGV'] ?? '—') ?></td>
                                <td class="hide-column"><?= htmlspecialchars($row['namHoc']) ?></td>
                                <td><?= htmlspecialchars($row['trangThai']) ?></td>
                                <td class="actions">
                                    <i class="fa-solid fa-pen edit-btn"></i>
                                    <i class="fa-solid fa-trash delete-btn"></i>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="10" style="text-align:center;">Không có dữ liệu</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="popup-bg" id="addPopup">
            <div class="popup">
                <h2 id="title-h2">THÊM LỚP HỌC</h2>
                <div class="them-hocsinh">
                    <form id="addForm" class="student-form">
                        <input type="hidden" name="action" value="add" id="formAction">
                        <div class="row">
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Năm học:</label>
                                <input type="text">
                            </div>
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Tên lớp:</label>
                                <input type="text">
                            </div>
                            
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">GVCN:</label>
                                <select name="maGV">
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Sĩ số:</label>
                                <input type="number">
                            </div>
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <div class="form-group-horizontal">
                                    <label class="label-width-auto"><input type="radio" name="status" checked> Đang hoạt động</label>
                                    <label class="label-width-auto"><input type="radio" name="status"> Tạm dừng</label>
                                </div>
                            </div>
                        </div>

                        <div class="buttons">
                            <button type="button" class="btn-secondary"
                                onclick="window.location.href='qllophoc.php'">Hủy</button>
                            <button type="submit" class="btn-primary">
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
        const api = "../src/lophoc.php";
        let currentId = null;

        function showAddPopup() {
            document.getElementById('addPopup').style.display = 'block';
            document.getElementById('main-container').style.display = 'none';
        }

        function closePopup() {
            document.getElementById("addPopup").style.display = "none";
            document.getElementById("addForm").reset();
        }

        // === Xử lý thêm / cập nhật lớp học qua AJAX ===
        document.getElementById("addForm").addEventListener("submit", async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch("../src/lophoc.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                alert(result.message || result.error);
                if (result.message) window.location.href = "qllophoc.php";
            } catch (error) {
                console.error("Lỗi khi xử lý lớp học:", error);
                alert("Lỗi khi xử lý lớp học. Vui lòng thử lại!");
            }
        });


        // === Xử lý nhấn nút sửa (mở form và điền dữ liệu lớp học) ===
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");

                // Lấy dữ liệu từ dòng
                const maLop = tr.dataset.id;
                const tenLop = tr.children[3].innerText.trim();
                const siSo = tr.children[4].innerText.trim();
                const maGV = tr.children[5].dataset.gv || "";
                const namHoc = tr.children[6].innerText.trim();
                const trangThai = tr.children[7].innerText.trim();

                // Hiển thị popup và đổi giao diện nút
                showAddPopup();
                document.getElementById("title-h2").innerText = "CHỈNH SỬA LỚP HỌC";
                document.querySelector(".btn-primary").innerHTML = `<i class="fa-solid fa-check"></i> Cập nhật`;
                document.getElementById("formAction").value = "update";

                // Gán dữ liệu vào form
                const form = document.getElementById("addForm");
                const inputs = form.querySelectorAll("input[type='text']");
                inputs[0].value = tenLop;
                inputs[1].value = siSo;
                form.querySelector("select[name='maGV']").value = maGV;
                inputs[2].value = namHoc;

                // Gán trạng thái
                const radios = form.querySelectorAll("input[name='status']");
                if (trangThai === "Đang hoạt động") {
                    radios[0].checked = true;
                } else {
                    radios[1].checked = true;
                }

                // Thêm hidden input chứa mã lớp (nếu chưa có)
                let hidden = form.querySelector("input[name='maLop']");
                if (!hidden) {
                    hidden = document.createElement("input");
                    hidden.type = "hidden";
                    hidden.name = "maLop";
                    form.appendChild(hidden);
                }
                hidden.value = maLop;
            }
        });


        // === Xóa lớp học ===
        document.addEventListener("click", async (e) => {
            if (e.target.classList.contains("delete-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;
                if (confirm("Bạn có chắc muốn xóa lớp học này?")) {
                    const res = await fetch(api, {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ action: "delete", maLop: id })
                    });
                    const json = await res.json();
                    alert(json.message || json.error);
                    if (json.message) location.reload();
                }
            }
        });

        // === Xử lý user menu ===
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

        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "dangnhap.php";
            }
        }
    </script>

</body>

</html>