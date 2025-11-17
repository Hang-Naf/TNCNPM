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

// ==== PHÂN TRANG ====
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// ==== Lấy danh sách thông báo ====
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// ==== TRUY VẤN ĐẾM TỔNG SỐ ITEMS ====
if ($filter === 'sent') {
    $countSql = "
        SELECT COUNT(*) AS total FROM thongbao
        WHERE nguoiGui = '$currentUserId'
    ";
} else {
    $countSql = "SELECT COUNT(*) AS total FROM thongbao";
}

$countResult = $conn->query($countSql);
$totalItems = ($countResult && $countResult->num_rows > 0) ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalItems / $itemsPerPage);

if ($filter === 'sent') {
    // Chỉ lấy thông báo do admin hiện tại gửi
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
        WHERE t.nguoiGui = '$currentUserId'
        GROUP BY t.maThongBao, t.tieuDe, t.noiDung, t.ngayGui, u.hoVaTen
        ORDER BY t.ngayGui DESC
        LIMIT $offset, $itemsPerPage
    ";
} else {
    // Tất cả thông báo
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
        LIMIT $offset, $itemsPerPage
    ";
}

$result = $conn->query($sql);
if (!$result) {
    die("<pre>SQL Error: " . $conn->error . "</pre>");
}
// ==== Thống kê số lượng ====
$count_all = $conn->query("SELECT COUNT(*) AS total FROM thongbaouser")->fetch_assoc()['total'];
$count_sent = $conn->query("SELECT COUNT(*) AS total FROM thongbao WHERE nguoiGui = '$currentUserId'")->fetch_assoc()['total'];
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

        .thongbao-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
            padding: 10px 0;
            border-bottom: 2px solid #f0f0f0;
        }

        .tabs {
            display: flex;
            gap: 20px;
        }

        .tab {
            background: none;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            position: relative;
        }

        .tab span {
            color: #666;
            font-weight: normal;
        }

        .tab.active {
            color: #0b1e6b;
        }

        .tab.active::after {
            content: "";
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #0b1e6b;
            border-radius: 2px;
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
        <h1>THÔNG BÁO</h1>

        <div class="thongbao-header">
            <div class="tabs">
                <button class="tab <?= ($filter === 'all') ? 'active' : '' ?>">Tất cả <span>(<?= $count_all ?>)</span></button>
                <button class="tab <?= ($filter === 'sent') ? 'active' : '' ?>">Đã gửi <span>(<?= $count_sent ?>)</span></button>
            </div>
            <button class="add-btn" onclick="window.location.href='themthongbao.php'"><i class="fa-solid fa-plus"></i> Thêm Thông Báo</button>
        </div>
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
                    $stt = $offset + 1;
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
                                <a href="xemtb.php?maThongBao=<?= urlencode($row['maThongBao']) ?>" title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <a href="suatb.php?maThongBao=<?= urlencode($row['maThongBao']) ?>" title="Chỉnh sửa">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="xoatb.php?maThongBao=<?= urlencode($row['maThongBao']) ?>"
                                    onclick="return confirm('Bạn có chắc muốn xóa thông báo này?')"
                                    title="Xóa">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
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

        <!-- ========= THANH PHÂN TRANG ========= -->
        <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:10px;">
            <span style="font-size:14px; color:#333;">Trang <?= $page ?>/<?= max(1, $totalPages) ?> (Tổng: <?= $totalItems ?> thông báo)</span>
            <div style="display:flex; gap:8px; align-items:center;">
                <?php if ($page > 1): ?>
                    <a href="?page=1&filter=<?= $filter ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">⏮ Đầu</a>
                    <a href="?page=<?= $page - 1 ?>&filter=<?= $filter ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">◀ Trước</a>
                <?php else: ?>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">⏮ Đầu</button>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">◀ Trước</button>
                <?php endif; ?>
                
                <span style="font-weight:600; font-size:14px; min-width:30px; text-align:center;"><?= $page ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&filter=<?= $filter ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Sau ▶</a>
                    <a href="?page=<?= $totalPages ?>&filter=<?= $filter ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Cuối ⏭</a>
                <?php else: ?>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Sau ▶</button>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Cuối ⏭</button>
                <?php endif; ?>
            </div>
        </div>
        <!-- ========= HẾT THANH PHÂN TRANG ========= -->
    </div>

    <!-- Popup: Thêm -->
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <h3>Thêm thông báo</h3>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <label>Tiêu đề:</label>
                <input type="text" name="tieuDe" maxlength="255" required>
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
                <input type="text" name="tieuDe" id="editTieuDe" maxlength="255" required>
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
        // Chuyển tab lọc
        document.querySelectorAll(".tab").forEach(tab => {
            tab.addEventListener("click", () => {
                document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
                tab.classList.add("active");

                if (tab.textContent.includes("Đã gửi")) {
                    window.location.href = "qlthongbao.php?filter=sent";
                } else {
                    window.location.href = "qlthongbao.php?filter=all";
                }
            });
        });

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

        const searchInput = document.getElementById("searchBox");
        const searchIcon = document.querySelector(".search-box i");
        const tableRows = document.querySelectorAll("table tbody tr");

        function thucHienTimKiem() {
            const keyword = searchInput.value.trim().toLowerCase();
            let found = 0;

            tableRows.forEach(row => {
                const tieuDe = row.children[2]?.innerText.toLowerCase() || "";
                const nguoiGui = row.children[3]?.innerText.toLowerCase() || "";
                const ngayGui = row.children[4]?.innerText.toLowerCase() || "";

                if (tieuDe.includes(keyword) || nguoiGui.includes(keyword) || ngayGui.includes(keyword)) {
                    row.style.display = "";
                    found++;
                } else {
                    row.style.display = "none";
                }
            });

            // Nếu không tìm thấy, hiển thị dòng thông báo
            const oldRow = document.getElementById("noResultRow");
            if (oldRow) oldRow.remove();

            if (found === 0) {
                const tbody = document.querySelector("table tbody");
                const tr = document.createElement("tr");
                tr.id = "noResultRow";
                tr.innerHTML = `<td colspan="8" style="text-align:center;color:gray;">Không tìm thấy thông báo phù hợp.</td>`;
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