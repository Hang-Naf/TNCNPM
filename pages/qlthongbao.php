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

$currentUserId = $_SESSION["userID"];

// ==== Lấy danh sách thông báo ====
$sql = "
    SELECT 
        t.maThongBao,
        t.tieuDe,
        t.noiDung,
        t.ngayGui,
        COALESCE(u.hoVaTen, 'Hệ thống') AS nguoiGui,
        COUNT(tu.userID) AS tongNguoiNhan,
        SUM(CASE WHEN tu.trangThai = 'Đã đọc' THEN 1 ELSE 0 END) AS soDaDoc
    FROM thongbao t
    LEFT JOIN user u ON t.nguoiGui = u.userID
    LEFT JOIN thongbaouser tu ON t.maThongBao = tu.maThongBao
    GROUP BY t.maThongBao, t.tieuDe, t.noiDung, t.ngayGui, u.hoVaTen
    ORDER BY t.ngayGui DESC
";
$result = $conn->query($sql);
if (!$result) {
    die("<pre>SQL Error: " . $conn->error . "</pre>");
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý thông báo</title>
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
                    <li class="active" onclick="window.location.href='../pages/qlthongbao.php'"><i
                            class="fa-solid fa-bell"></i> Thông báo</li>
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
        <div id="main-container">


            <h1>QUẢN LÝ THÔNG BÁO</h1>

            <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm Thông Báo</button>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>MÃ TB</th>
                        <th>TIÊU ĐỀ</th>
                        <th>NGƯỜI GỬI</th>
                        <th>NGÀY GỬI</th>
                        <th>TỔNG NGƯỜI NHẬN</th>
                        <th>ĐÃ ĐỌC</th>
                        <th>TÁC VỤ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0):
                        $stt = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $stt++ ?></td>
                                <td><?= htmlspecialchars($row['maThongBao']) ?></td>
                                <td><?= htmlspecialchars($row['tieuDe']) ?></td>
                                <td><?= htmlspecialchars($row['nguoiGui']) ?></td>
                                <td><?= htmlspecialchars($row['ngayGui']) ?></td>
                                <td><?= htmlspecialchars($row['tongNguoiNhan']) ?></td>
                                <td><?= htmlspecialchars($row['soDaDoc']) ?></td>
                                <td class="actions">
                                    <i class="fa-solid fa-eye" onclick="showDetail(
                                    '<?= htmlspecialchars(addslashes($row['maThongBao'])) ?>',
                                    '<?= htmlspecialchars(addslashes($row['tieuDe'])) ?>',
                                    '<?= htmlspecialchars(addslashes($row['noiDung'])) ?>',
                                    '<?= htmlspecialchars(addslashes($row['nguoiGui'])) ?>',
                                    '<?= htmlspecialchars(addslashes($row['ngayGui'])) ?>'
                                )"></i>
                                    <i class="fa-solid fa-pen-to-square" onclick="showEditPopup(
                                    <?= $row['maThongBao'] ?>,
                                    '<?= htmlspecialchars(addslashes($row['tieuDe'])) ?>',
                                    '<?= htmlspecialchars(addslashes($row['noiDung'])) ?>'
                                )"></i>
                                    <i class="fa-solid fa-trash" onclick="xoaThongBao(<?= $row['maThongBao'] ?>)"></i>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">Không có thông báo nào</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="popup-bg" id="addPopup">
            <div class="popup">
                <h2 id="title-h2">THÊM THÔNG BÁO</h2>
                <div class="them-hocsinh">
                    <form id="addForm" class="student-form">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="form-group-horizontal">
                                <label>Tiêu đề:</label>
                                <input type="text" name="tieuDe" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group-horizontal">
                                <label>Nội dung:</label>
                                <textarea name="noiDung" required></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group-horizontal">
                                <label>Người nhận:</label>
                                <input type="radio" name="nguoiNhan" value="Toàn hệ thống"> Toàn hệ thống
                                <input type="radio" name="nguoiNhan" value="Giáo viên"> Giáo viên
                                <input type="radio" name="nguoiNhan" value="Học sinh"> Học sinh
                            </div>
                        </div>
                        <div class="row-right">
                            <div class="popup-buttons">
                                <button type="button" class="btn-secondary" onclick="window.location.href='qlthongbao.php'">Hủy</button>
                                <button type="submit" class="btn-primary" id="submitButton">Thêm mới</button>
                            </div>
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

        function showAddPopup() {
            document.getElementById('addPopup').style.display = 'block';
            document.getElementById('main-container').style.display = 'none';
        }

        function closePopup(id) {
            document.getElementById(id).style.display = 'none';
        }

        function showDetail(ma, td, nd, ng, ngay) {
            document.getElementById('dMaTB').innerText = ma;
            document.getElementById('dTieuDe').innerText = td;
            document.getElementById('dNoiDung').innerText = nd;
            document.getElementById('dNguoiGui').innerText = ng;
            document.getElementById('dNgayGui').innerText = ngay;
            document.getElementById('detailPopup').style.display = 'flex';
        }

        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const res = await fetch('src/thongbao.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            alert(json.message);
            if (!json.error) location.reload();
        };

        function showEditPopup(ma, td, nd) {
            document.getElementById('editMaTB').value = ma;
            document.getElementById('editTieuDe').value = td;
            document.getElementById('editNoiDung').value = nd;
            document.getElementById('editPopup').style.display = 'flex';
        }

        document.getElementById('editForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const res = await fetch('src/thongbao.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            alert(json.message);
            if (!json.error) location.reload();
        };


        async function xoaThongBao(id) {
            if (!confirm("Bạn có chắc muốn xóa thông báo này?")) return;
            const res = await fetch('src/thongbao.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'delete',
                    maThongBao: id
                })
            });
            const json = await res.json();
            alert(json.message);
            if (!json.error) location.reload();
        }

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