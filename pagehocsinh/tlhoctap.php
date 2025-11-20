<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập và vai trò học sinh ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "HocSinh") {
    header("Location: ../dangnhap.php");
    exit();
}

$maHS = $_SESSION["userID"];

// ==== Lấy thông tin học sinh và các lớp học sinh thuộc về ====
$sqlHS = "SELECT u.hoVaTen FROM user u WHERE u.userID = ?";
$stmtHS = $conn->prepare($sqlHS);
$stmtHS->bind_param("i", $maHS);
$stmtHS->execute();
$hs = $stmtHS->get_result()->fetch_assoc();
$stmtHS->close();

// Lấy tất cả maLop mà học sinh thuộc về
$sqlLop = "SELECT maLop FROM hocsinh_lophoc WHERE maHS = ?";
$stmtLop = $conn->prepare($sqlLop);
$stmtLop->bind_param("i", $maHS);
$stmtLop->execute();
$lopResult = $stmtLop->get_result();
$maLopArr = [];
while ($row = $lopResult->fetch_assoc()) {
    $maLopArr[] = intval($row['maLop']);
}
$stmtLop->close();

if (count($maLopArr) === 0) {
    echo '<h2 style="color:red;text-align:center;margin-top:40px;">Bạn chưa được phân vào lớp nào. Vui lòng liên hệ giáo viên hoặc quản trị viên.</h2>';
    exit();
}

// ==== Lấy danh sách môn học của các lớp học sinh thuộc về ====
$inLop = implode(',', array_fill(0, count($maLopArr), '?'));
$sqlMon = "SELECT DISTINCT m.maMonHoc, m.tenMonHoc FROM lophoc_monhoc lm JOIN monhoc m ON lm.maMonHoc = m.maMonHoc WHERE lm.maLop IN ($inLop)";
$stmtMon = $conn->prepare($sqlMon);
if (count($maLopArr) > 0) {
    $stmtMon->bind_param(str_repeat('i', count($maLopArr)), ...$maLopArr);
}
$stmtMon->execute();
$monhocList = $stmtMon->get_result();
$stmtMon->close();

// ==== Lọc theo môn học nếu có ====
$params = $maLopArr;
$types = str_repeat('i', count($maLopArr));
$cond = "t.maLop IN ($inLop) AND t.trangThai = 'Công khai'";

if (!empty($_GET['maMonHoc'])) {
    $maMonHoc = intval($_GET['maMonHoc']);
    $cond .= " AND t.maMonHoc = ?";
    $params[] = $maMonHoc;
    $types .= "i";
}

// === PHÂN TRANG ===
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// ==== Đếm tổng số tài liệu ====
$countSql = "SELECT COUNT(*) as total FROM tailieu t WHERE $cond";
$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($types, ...$params);
$countStmt->execute();
$countResult = $countStmt->get_result();
$countRow = $countResult->fetch_assoc();
$totalItems = $countRow['total'];
$totalPages = max(1, ceil($totalItems / $itemsPerPage));
$countStmt->close();

// ==== Lấy danh sách tài liệu ====
$sql = "SELECT t.maTL, t.tieuDe, t.noiDung, t.tepDinhKem, m.tenMonHoc, u.hoVaTen AS nguoiTao
    FROM tailieu t
    LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
    LEFT JOIN user u ON t.maGV = u.userID
    WHERE $cond
    ORDER BY t.maTL DESC
    LIMIT ?, ?";
$params[] = $offset;
$params[] = $itemsPerPage;
$types .= "ii";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$ds = $stmt->get_result();
$stmt->close();
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
        }

        .header {
            padding: 10px 25px;
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

        .hide-col {
            display: none;
        }

        .actions i {
            cursor: pointer;
            margin: 0 5px;
            color: #0b3364;
        }

        .download-link {
            color: #0b3364;
            text-decoration: underline;
            font-weight: 600;
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
                <div class="menu-title">Trang cá nhân</div>
                <ul>
                    <li onclick="window.location.href='../pagehocsinh/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
                    <li onclick="window.location.href='../pagehocsinh/thongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Tra cứu thông tin</div>
                <ul>
                    <li class="active" onclick="window.location.href='../pagehocsinh/tlhoctap.php'"><i class="fa-solid fa-book"></i> Tài liệu học tập</li>
                    <li onclick="window.location.href='../pagehocsinh/kqhoctap.php'"><i class="fa-solid fa-file-lines"></i> Kết quả học tập</li>
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
                    <span><?= htmlspecialchars($hs['hoVaTen']) ?></span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu" style="display:none;">
                    <ul>
                        <li onclick="window.location.href='../pagehocsinh/ttcanhan.php'"><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>
        <div class="content-area">
            <h1>DANH SÁCH TÀI LIỆU</h1>
            <form method="GET" class="filter-bar">
                <div>
                    <label>Môn học:</label><br>
                    <select name="maMonHoc" onchange="this.form.submit()">
                        <option value="">Tất cả môn</option>
                        <?php while ($mon = $monhocList->fetch_assoc()): ?>
                            <option value="<?= $mon['maMonHoc'] ?>" <?= (isset($_GET['maMonHoc']) && $_GET['maMonHoc'] == $mon['maMonHoc']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mon['tenMonHoc']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tiêu đề</th>
                        <th>Mô tả</th>
                        <th>Môn học</th>
                        <th>Giáo viên gửi</th>
                        <th class="hide-col">Tệp đính kèm</th>
                        <th>Tác vụ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($ds && $ds->num_rows > 0):
                        $stt = $offset + 1;
                        while ($r = $ds->fetch_assoc()):
                    ?>
                            <tr class="document-row" data-title="<?= htmlspecialchars($r['tieuDe']) ?>" data-subject="<?= htmlspecialchars($r['tenMonHoc']) ?>">
                                <td><?= $stt ?></td>
                                <td class="doc-title"><?= htmlspecialchars($r['tieuDe']) ?></td>
                                <td><?= htmlspecialchars($r['noiDung']) ?></td>
                                <td><?= htmlspecialchars($r['tenMonHoc']) ?></td>
                                <td><?= htmlspecialchars($r['nguoiTao']) ?></td>
                                <td class="hide-col">
                                    <?php if (!empty($r['tepDinhKem'])): ?>
                                        <a class="download-link" href="../uploads/tailieu/<?= urlencode($r['tepDinhKem']) ?>" target="_blank">Tải về</a>
                                    <?php else: ?>
                                        <span style="color:#888;">Không có</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <i class="fa-solid fa-eye" title="Xem chi tiết" onclick="window.location.href='chitiet_tailieu.php?maTL=<?= $r['maTL'] ?>'"></i>
                                </td>
                            </tr>
                        <?php
                            $stt++;
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="7" style="text-align:center;padding:20px;">Không có tài liệu nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- ========= THANH PHÂN TRANG ========= -->
            <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:10px;">
                <span style="font-size:14px; color:#333;">Trang <?= $page ?>/<?= max(1, $totalPages) ?> (Tổng: <?= $totalItems ?> tài liệu)</span>
                <div style="display:flex; gap:8px; align-items:center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?= !empty($_GET['maMonHoc']) ? '&maMonHoc=' . $_GET['maMonHoc'] : '' ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">⏮ Đầu</a>
                        <a href="?page=<?= $page - 1 ?><?= !empty($_GET['maMonHoc']) ? '&maMonHoc=' . $_GET['maMonHoc'] : '' ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">◀ Trước</a>
                    <?php else: ?>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">⏮ Đầu</button>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">◀ Trước</button>
                    <?php endif; ?>
                    <span style="font-weight:600; font-size:14px; min-width:30px; text-align:center;"><?= $page ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= !empty($_GET['maMonHoc']) ? '&maMonHoc=' . $_GET['maMonHoc'] : '' ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Sau ▶</a>
                        <a href="?page=<?= $totalPages ?><?= !empty($_GET['maMonHoc']) ? '&maMonHoc=' . $_GET['maMonHoc'] : '' ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Cuối ⏭</a>
                    <?php else: ?>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Sau ▶</button>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Cuối ⏭</button>
                    <?php endif; ?>
                </div>
            </div>
            <!-- ========= HẾT THANH PHÂN TRANG ========= -->
        </div>
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
        // Tìm kiếm tài liệu
        const searchInput = document.getElementById("searchDocuments");
        const documentRows = document.querySelectorAll(".document-row");
        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                documentRows.forEach(row => {
                    const title = row.getAttribute("data-title").toLowerCase();
                    const subject = row.getAttribute("data-subject").toLowerCase();
                    row.style.display = (title.includes(keyword) || subject.includes(keyword)) ? "" : "none";
                });
            });
        }

        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php";
            }
        }
    </script>
</body>

</html>