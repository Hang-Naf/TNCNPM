<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "GiaoVien") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$maGV = $_SESSION["userID"];

// Lấy thông tin giáo viên
$sqlGV = "SELECT u.hoVaTen FROM user u JOIN giaovien g ON u.userID = g.maGV WHERE g.maGV = ?";
$stmt = $conn->prepare($sqlGV);
$stmt->bind_param("i", $maGV);
$stmt->execute();
$gv = $stmt->get_result()->fetch_assoc() ?? ['hoVaTen' => 'Giáo viên'];

// Lấy danh sách lớp của giáo viên
$sqlLop = "SELECT DISTINCT l.maLop, l.tenLop
           FROM lophoc l
           JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
           WHERE lm.maGV = ?";
$stmt = $conn->prepare($sqlLop);
$stmt->bind_param("i", $maGV);
$stmt->execute();
$lopList = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Lọc lớp
$maLopChon = $_GET['maLop'] ?? 'all';

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Tạo điều kiện SQL
$cond = "WHERE t.maGV = ?";
$params = [$maGV];
$types = "i";

if ($maLopChon !== 'all') {
    $cond .= " AND t.maLop = ?";
    $params[] = $maLopChon;
    $types .= "i";
}

// Đếm tổng tài liệu
$sqlCount = "SELECT COUNT(*) AS total FROM tailieu t $cond";
$stmt = $conn->prepare($sqlCount);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalItems = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$totalPages = max(1, ceil($totalItems / $limit));

// Lấy dữ liệu trang hiện tại
$sql = "SELECT t.maTL, t.tieuDe, t.noiDung, t.trangThai,
               l.tenLop, u.hoVaTen AS nguoiTao
        FROM tailieu t
        LEFT JOIN lophoc l ON t.maLop = l.maLop
        LEFT JOIN user u ON t.maGV = u.userID
        $cond
        ORDER BY t.maTL DESC
        LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$ds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tài liệu học tập</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .content-area {
            padding: 30px;
        }

        h1 {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #111;
        }

        .filter-bar {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #fff;
        }

        .btn-add {
            background: #0b3364;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-left: auto;
        }

        .btn-add:hover {
            background: #124b8a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #fafafa;
        }

        .actions i {
            cursor: pointer;
            margin: 0 5px;
        }

        .actions i.fa-pen {
            color: #0b3364;
        }

        .actions i.fa-trash {
            color: #d9534f;
        }

        .pagination .page-btn {
            padding: 6px 12px;
            margin: 0 3px;
            border-radius: 4px;
            text-decoration: none;
            background: #eee;
            color: #333;
        }

        .pagination .page-btn.active {
            background: #0b3364;
            color: #fff;
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
                    <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
                    <li onclick="window.location.href='../pagegiaovien/hocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pagegiaovien/lophoc.php'"><i class="fa-solid fa-school"></i> Lớp học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li class="active" onclick="window.location.href='../pagegiaovien/tlhoctap.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
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
                    <input type="text" id="searchDocuments" placeholder="Tìm kiếm tài liệu...">
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
        <div class="content-area">
            <h1>DANH SÁCH TÀI LIỆU</h1>

            <form method="GET" class="filter-bar">
                <div>
                    <label>Lớp:</label><br>
                    <select name="maLop" onchange="this.form.submit()">
                        <option value="">Tất cả lớp</option>
                        <?php foreach ($lopList as $lop): ?>
                            <option value="<?= $lop['maLop'] ?>" <?= ($lop['maLop'] == $maLopChon) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lop['tenLop']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="button" class="btn-add" onclick="window.location='themtlht.php'">
                    + Thêm tài liệu
                </button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>TIÊU ĐỀ</th>
                        <th>MÔ TẢ</th>
                        <th>LỚP</th>
                        <th>NGƯỜI TẠO</th>
                        <th>TRẠNG THÁI</th>
                        <th>TÁC VỤ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ds): $stt = $offset + 1;
                        foreach ($ds as $r): ?>
                            <tr class="document-row" data-title="<?= htmlspecialchars($r['tieuDe']) ?>" data-description="<?= htmlspecialchars($r['noiDung']) ?>">
                                <td><?= $stt ?></td>
                                <td><?= htmlspecialchars($r['tieuDe']) ?></td>
                                <td><?= htmlspecialchars($r['noiDung']) ?></td>
                                <td><?= htmlspecialchars($r['tenLop']) ?></td>
                                <td><?= htmlspecialchars($r['nguoiTao']) ?></td>
                                <td><?= htmlspecialchars($r['trangThai']) ?></td>
                                <td class="actions">
                                    <i class="fa-solid fa-pen"
                                        title="Sửa"
                                        onclick="window.location.href='suatlht.php?maTL=<?= urlencode($r['maTL']) ?>'"></i>
                                    <i class="fa-solid fa-trash"
                                        title="Xóa"
                                        onclick="if(confirm('Bạn có chắc muốn xóa tài liệu này không?')) window.location.href='xoatlht.php?maTL=<?= urlencode($r['maTL']) ?>'"></i>
                                </td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">Không có tài liệu nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:10px;">
                <span>Trang <?= $page ?>/<?= $totalPages ?> (Tổng: <?= $totalItems ?> tài liệu)</span>
                <div style="display:flex; gap:8px;">
                    <?php if ($page > 1): ?>
                        <a href="?maLop=<?= urlencode($maLopChon) ?>&maMonHoc=<?= urlencode($maMonHocChon) ?>&page=1">⏮ Đầu</a>
                        <a href="?maLop=<?= urlencode($maLopChon) ?>&maMonHoc=<?= urlencode($maMonHocChon) ?>&page=<?= $page - 1 ?>">◀ Trước</a>
                    <?php else: ?>
                        <span style="opacity:0.5;">⏮ Đầu</span>
                        <span style="opacity:0.5;">◀ Trước</span>
                    <?php endif; ?>

                    <span style="font-weight:600;"><?= $page ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?maLop=<?= urlencode($maLopChon) ?>&maMonHoc=<?= urlencode($maMonHocChon) ?>&page=<?= $page + 1 ?>">Sau ▶</a>
                        <a href="?maLop=<?= urlencode($maLopChon) ?>&maMonHoc=<?= urlencode($maMonHocChon) ?>&page=<?= $totalPages ?>">Cuối ⏭</a>
                    <?php else: ?>
                        <span style="opacity:0.5;">Sau ▶</span>
                        <span style="opacity:0.5;">Cuối ⏭</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function chonMon() {
            const maMon = document.getElementById("maMonHoc").value;
            if (maMon) {
                window.location = "tlhoctap.php?maMonHoc=" + maMon;
            }
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

        // Xử lý tìm kiếm tài liệu
        const searchInput = document.getElementById("searchDocuments");
        const documentRows = document.querySelectorAll(".document-row");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                documentRows.forEach(row => {
                    const title = row.getAttribute("data-title").toLowerCase();
                    const description = row.getAttribute("data-description").toLowerCase();
                    row.style.display = (title.includes(keyword) || description.includes(keyword)) ? "" : "none";
                });
            });
        }

        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>