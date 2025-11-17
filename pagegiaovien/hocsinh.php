<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép GiaoVien ====
if ($_SESSION["vaiTro"] !== "GiaoVien") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}
$userID = $_SESSION["userID"];
$maGV = $_SESSION["userID"];

// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = '$maGV' 
           LIMIT 1";
$resultGV = $conn->query($sqlGV);
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];

// Lấy danh sách lớp mà giáo viên phụ trách
$sql_lop = "SELECT DISTINCT l.maLop, l.tenLop
            FROM lophoc l
            JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
            WHERE lm.maGV = ?";
$stmt = $conn->prepare($sql_lop);
$stmt->bind_param("i", $userID);
$stmt->execute();
$lops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lấy mã lớp được chọn (nếu có)
$maLopChon = $_GET['lop'] ?? 'all';

// PHÂN TRANG: limit / page
$limit = 10; // số hàng mỗi trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Lấy danh sách học sinh cho trang hiện tại và tổng số để tính số trang
$hocsinh = [];
$total = 0;
if ($maLopChon === 'all') {
    // Tổng số học sinh (distinct vì join có thể gây trùng)
    $count_sql = "SELECT COUNT(DISTINCT hs.maHS) AS total
                  FROM hocsinh hs
                  JOIN hocsinh_lophoc hl ON hs.maHS = hl.maHS
                  JOIN lophoc l ON hl.maLop = l.maLop
                  JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
                  WHERE lm.maGV = ?";
    $stmt_count = $conn->prepare($count_sql);
    $stmt_count->bind_param("i", $userID);
    $stmt_count->execute();
    $res_count = $stmt_count->get_result()->fetch_assoc();
    $total = intval($res_count['total'] ?? 0);

    $sql_hs = "SELECT hs.maHS, u.hoVaTen, l.tenLop, hs.chucVu, hs.trangThai
               FROM hocsinh hs
               JOIN user u ON hs.maHS = u.userID
               JOIN hocsinh_lophoc hl ON hs.maHS = hl.maHS
               JOIN lophoc l ON hl.maLop = l.maLop
               JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
               WHERE lm.maGV = ?
               ORDER BY l.tenLop, u.hoVaTen
               LIMIT ?, ?";
    $stmt = $conn->prepare($sql_hs);
    $stmt->bind_param("iii", $userID, $offset, $limit);
} else {
    // Tổng số học sinh trong lớp cụ thể
    $count_sql = "SELECT COUNT(*) AS total
                  FROM hocsinh hs
                  JOIN hocsinh_lophoc hl ON hs.maHS = hl.maHS
                  JOIN lophoc l ON hl.maLop = l.maLop
                  WHERE l.maLop = ?";
    $stmt_count = $conn->prepare($count_sql);
    $stmt_count->bind_param("i", $maLopChon);
    $stmt_count->execute();
    $res_count = $stmt_count->get_result()->fetch_assoc();
    $total = intval($res_count['total'] ?? 0);

    $sql_hs = "SELECT hs.maHS, u.hoVaTen, l.tenLop, hs.chucVu, hs.trangThai
               FROM hocsinh hs
               JOIN user u ON hs.maHS = u.userID
               JOIN hocsinh_lophoc hl ON hs.maHS = hl.maHS
               JOIN lophoc l ON hl.maLop = l.maLop
               WHERE l.maLop = ?
               ORDER BY u.hoVaTen
               LIMIT ?, ?";
    $stmt = $conn->prepare($sql_hs);
    $stmt->bind_param("iii", $maLopChon, $offset, $limit);
}

$stmt->execute();
$hocsinh = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$totalPages = ($limit > 0) ? (int)ceil($total / $limit) : 1;

// Aliases to match other pages' naming
$itemsPerPage = $limit;
$totalItems = $total;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý học sinh</title>
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
            padding: 0px 25px;
        }

        .container {
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

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #f1f3f8;
        }

        .status.active {
            color: green;
            font-weight: 500;
        }

        .status.inactive {
            color: gray;
        }

        .action i {
            cursor: pointer;
            margin: 0 6px;
        }

        select {
            padding: 6px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            margin-top: 14px;
        }

        .pagination a {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            color: #0b1e6b;
            border: 1px solid #e0e6f0;
            background: #fff;
        }

        .pagination a.active {
            background: #0b1e6b;
            color: #fff;
            border-color: #0b1e6b;
        }

        .pagination a:hover { background: #f1f3f8; }
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
                    <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
                    <li class="active" onclick="window.location.href='../pagegiaovien/hocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pagegiaovien/lophoc.php'"><i class="fa-solid fa-school"></i> Lớp học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/tlhoctap.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/chuyencan.php'"><i class="fa-solid fa-check"></i> Chuyên cần</li>
                    <li onclick="window.location.href='../pagegiaovien/diemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Thông báo</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/thongbao.php'"><i class="fa-solid fa-bell"></i> Xem thông báo</li>
                </ul>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <header class="header">
            <div class="left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchStudents" placeholder="Tìm kiếm học sinh...">
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
                    <span><?= htmlspecialchars($gv['hoVaTen']) ?></span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container">
            <h1>QUẢN LÝ HỌC SINH</h1>
            <div class="top-bar">
                <div>
                    <label for="lop">Lớp: </label>
                    <select id="lop" onchange="changeClass(this.value)">
                        <option value="all" <?= ($maLopChon === 'all') ? 'selected' : '' ?>>Tất cả các lớp</option>
                        <?php foreach ($lops as $lop): ?>
                            <option value="<?= $lop['maLop'] ?>" <?= ($lop['maLop'] == $maLopChon) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lop['tenLop']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- <button type="button" class="add-btn" onclick="window.location='themhs.php'">
                    + Thêm Học Sinh
                </button> -->

            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>MÃ HỌC SINH</th>
                        <th>HỌ TÊN</th>
                        <th>LỚP</th>
                        <th>CHỨC VỤ</th>
                        <th>TRẠNG THÁI</th>
                        <th>TÁC VỤ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($hocsinh) > 0): $stt = ($offset ?? 0) + 1; ?>
                        <?php foreach ($hocsinh as $hs): ?>
                            <tr class="student-row" data-name="<?= htmlspecialchars($hs['hoVaTen']) ?>" data-id="<?= htmlspecialchars($hs['maHS']) ?>">
                                <td><?= $stt++ ?></td>
                                <td><?= htmlspecialchars($hs['maHS']) ?></td>
                                <td class="student-name"><?= htmlspecialchars($hs['hoVaTen']) ?></td>
                                <td><?= htmlspecialchars($hs['tenLop']) ?></td>
                                <td><?= htmlspecialchars($hs['chucVu'] ?? '') ?></td>
                                <td>
                                    <span class="status <?= strtolower($hs['trangThai']) ?>">
                                        <?= ucfirst($hs['trangThai']) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="xemhs.php?maHS=<?= urlencode($hs['maHS']) ?>" title="Xem chi tiết">
                                        <i class="fa-solid fa-eye" style="color:black; cursor:pointer;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">Không có học sinh nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Thanh phân trang -->
            <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:10px;">
                <span style="font-size:14px; color:#333;">Trang <?= $page ?>/<?= max(1, $totalPages) ?> (Tổng: <?= $totalItems ?> học sinh)</span>
                <div style="display:flex; gap:8px; align-items:center;">
                    <?php if ($page > 1): ?>
                        <a href="?lop=<?= urlencode($maLopChon) ?>&page=1" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">⏮ Đầu</a>
                        <a href="?lop=<?= urlencode($maLopChon) ?>&page=<?= $page - 1 ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">◀ Trước</a>
                    <?php else: ?>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">⏮ Đầu</button>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">◀ Trước</button>
                    <?php endif; ?>

                    <span style="font-weight:600; font-size:14px; min-width:30px; text-align:center;"><?= $page ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?lop=<?= urlencode($maLopChon) ?>&page=<?= $page + 1 ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Sau ▶</a>
                        <a href="?lop=<?= urlencode($maLopChon) ?>&page=<?= $totalPages ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Cuối ⏭</a>
                    <?php else: ?>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Sau ▶</button>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Cuối ⏭</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changeClass(maLop) {
            window.location.href = "?lop=" + maLop;
        }
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

        // === CHỨC NĂNG TÌM KIẾM HỌC SINH ===
        const searchInput = document.getElementById("searchStudents");
        const studentRows = document.querySelectorAll(".student-row");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                let foundCount = 0;

                studentRows.forEach(row => {
                    const name = row.getAttribute("data-name").toLowerCase();
                    const id = row.getAttribute("data-id").toLowerCase();
                    
                    if (name.includes(keyword) || id.includes(keyword)) {
                        row.style.display = "";
                        foundCount++;
                    } else {
                        row.style.display = "none";
                    }
                });
            });
        }
    </script>
</body>

</html>