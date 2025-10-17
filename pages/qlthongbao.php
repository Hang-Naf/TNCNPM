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
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .header {
            padding: 10px 25px;
        }

        h1 {
            margin: 20px 0;
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
            width: 180px;
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
            text-align: left;
        }

        th {
            background: #f1f3f9;
        }

        .actions i {
            cursor: pointer;
            margin-right: 10px;
            color: #333;
        }

        .actions i:hover {
            color: #0b1e6b;
        }

        .popup-bg {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .popup {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 500px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .popup input,
        .popup textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .popup-buttons {
            text-align: right;
            margin-top: 10px;
        }

        .send-btn,
        .save-btn {
            background: #0b1e6b;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
        }

        .cancel-btn {
            background: #ccc;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
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
                    <li class="active" onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                    <li onclick="window.location.href='../pages/qlsukien.php'"><i class="fa-solid fa-calendar-days"></i> Sự kiện</li>
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

    <!-- Popup: Thêm -->
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <h3>Thêm thông báo</h3>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <label>Tiêu đề:</label>
                <input type="text" name="tieuDe" required>
                <label>Nội dung:</label>
                <textarea name="noiDung" rows="5" required></textarea>
                <div class="popup-buttons">
                    <button type="button" class="cancel-btn" onclick="closePopup('addPopup')">Hủy</button>
                    <button type="submit" class="send-btn">Gửi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup: Sửa -->
    <div class="popup-bg" id="editPopup">
        <div class="popup">
            <h3>Sửa thông báo</h3>
            <form id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="maThongBao" id="editMaTB">
                <label>Tiêu đề:</label>
                <input type="text" name="tieuDe" id="editTieuDe" required>
                <label>Nội dung:</label>
                <textarea name="noiDung" id="editNoiDung" rows="5" required></textarea>
                <div class="popup-buttons">
                    <button type="button" class="cancel-btn" onclick="closePopup('editPopup')">Hủy</button>
                    <button type="submit" class="save-btn">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup: Xem chi tiết -->
    <div class="popup-bg" id="detailPopup">
        <div class="popup">
            <h3>Chi tiết thông báo</h3>
            <p><strong>Mã TB:</strong> <span id="dMaTB"></span></p>
            <p><strong>Tiêu đề:</strong> <span id="dTieuDe"></span></p>
            <p><strong>Nội dung:</strong></p>
            <p id="dNoiDung" style="white-space: pre-wrap; background:#f1f3f9; padding:10px; border-radius:6px;"></p>
            <p><strong>Người gửi:</strong> <span id="dNguoiGui"></span></p>
            <p><strong>Ngày gửi:</strong> <span id="dNgayGui"></span></p>
            <div class="popup-buttons">
                <button type="button" class="cancel-btn" onclick="closePopup('detailPopup')">Đóng</button>
            </div>
        </div>
    </div>

    <script>
        function showAddPopup() {
            document.getElementById('addPopup').style.display = 'flex';
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
                window.location.href = "dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>

</body>

</html>