<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Giáo viên ====
if ($_SESSION["vaiTro"] !== "GiaoVien") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$maGV = $_SESSION["userID"];
$lopChon = $_GET['lop'] ?? '';
// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = '$maGV' 
           LIMIT 1";
$resultGV = $conn->query($sqlGV);
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];


// ====== Lấy môn học mà giáo viên phụ trách ======
$sqlMon = "SELECT m.maMonHoc, m.tenMonHoc
           FROM monhoc m
           JOIN lophoc_monhoc lm ON lm.maMonHoc = m.maMonHoc
           WHERE lm.maGV = ?";
$stmt = $conn->prepare($sqlMon);
$stmt->bind_param("i", $maGV);
$stmt->execute();
$monRes = $stmt->get_result();
$mon = $monRes->fetch_assoc();

if ($mon) {
    $maMonHoc = $mon['maMonHoc'];
    $tenMonHoc = $mon['tenMonHoc'];
} else {
    // Giáo viên chưa được phân công môn nào
    $maMonHoc = 0; // dùng giá trị không tồn tại
    $tenMonHoc = "(Chưa được phân công)";
}

// ====== Lấy danh sách lớp mà giáo viên này dạy ======
$sqlLop = "SELECT DISTINCT l.maLop, l.tenLop
           FROM lophoc l
           JOIN lophoc_monhoc lm ON lm.maLop = l.maLop
           WHERE lm.maGV = ?";
$stmt2 = $conn->prepare($sqlLop);
$stmt2->bind_param("i", $maGV);
$stmt2->execute();
$dsLop = $stmt2->get_result();

// ====== Truy vấn danh sách học sinh và điểm ======
$sql = "
SELECT 
    u.userID AS maHS,
    u.hoVaTen,
    l.tenLop,

    ROUND(SUM(CASE 
        WHEN d.loaiDiem LIKE 'hk1_%' THEN 
            CASE 
                WHEN d.loaiDiem = 'hk1_mieng' THEN d.diem * 1
                WHEN d.loaiDiem = 'hk1_1tiet' THEN d.diem * 2
                WHEN d.loaiDiem = 'hk1_thiGK' THEN d.diem * 2
                WHEN d.loaiDiem = 'hk1_thiCK' THEN d.diem * 3
                ELSE 0 END
        ELSE 0 END) /
    NULLIF(SUM(CASE 
        WHEN d.loaiDiem LIKE 'hk1_%' THEN 
            CASE 
                WHEN d.loaiDiem = 'hk1_mieng' THEN 1
                WHEN d.loaiDiem = 'hk1_1tiet' THEN 2
                WHEN d.loaiDiem = 'hk1_thiGK' THEN 2
                WHEN d.loaiDiem = 'hk1_thiCK' THEN 3
                ELSE 0 END
        ELSE 0 END),0),1) AS diemHK1,

    ROUND(SUM(CASE 
        WHEN d.loaiDiem LIKE 'hk2_%' THEN 
            CASE 
                WHEN d.loaiDiem = 'hk2_mieng' THEN d.diem * 1
                WHEN d.loaiDiem = 'hk2_1tiet' THEN d.diem * 2
                WHEN d.loaiDiem = 'hk2_thiGK' THEN d.diem * 2
                WHEN d.loaiDiem = 'hk2_thiCK' THEN d.diem * 3
                ELSE 0 END
        ELSE 0 END) /
    NULLIF(SUM(CASE 
        WHEN d.loaiDiem LIKE 'hk2_%' THEN 
            CASE 
                WHEN d.loaiDiem = 'hk2_mieng' THEN 1
                WHEN d.loaiDiem = 'hk2_1tiet' THEN 2
                WHEN d.loaiDiem = 'hk2_thiGK' THEN 2
                WHEN d.loaiDiem = 'hk2_thiCK' THEN 3
                ELSE 0 END
        ELSE 0 END),0),1) AS diemHK2

FROM hocsinh_lophoc hl
JOIN user u ON hl.maHS = u.userID
JOIN lophoc l ON hl.maLop = l.maLop
LEFT JOIN diemso d ON d.maHS = hl.maHS AND d.maMonHoc = ?
WHERE l.maLop IN (
    SELECT maLop FROM lophoc_monhoc WHERE maGV = ?
)
";

if ($lopChon != '') {
    $sql .= " AND l.maLop = " . intval($lopChon);
}

// === PHÂN TRANG ===
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Đếm tổng học sinh (sử dụng cùng filter)
$count_sql = "SELECT COUNT(DISTINCT u.userID) as total
FROM hocsinh_lophoc hl
JOIN user u ON hl.maHS = u.userID
JOIN lophoc l ON hl.maLop = l.maLop
JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
LEFT JOIN diemso d ON d.maHS = hl.maHS AND d.maMonHoc = ?
WHERE lm.maGV = ?";
if ($lopChon != '') {
    $count_sql .= " AND l.maLop = " . intval($lopChon);
}
$stmtCount = $conn->prepare($count_sql);
$stmtCount->bind_param("ii", $maMonHoc, $maGV);
$stmtCount->execute();
$countRes = $stmtCount->get_result()->fetch_assoc();
$totalItems = intval($countRes['total'] ?? 0);
$totalPages = ($itemsPerPage > 0) ? (int)ceil($totalItems / $itemsPerPage) : 1;
$stmtCount->close();

$sql .= " GROUP BY u.userID ORDER BY l.tenLop, u.hoVaTen ASC LIMIT $offset, $itemsPerPage";
$stmt3 = $conn->prepare($sql);
$stmt3->bind_param("ii", $maMonHoc, $maGV);
$stmt3->execute();
$result = $stmt3->get_result();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý chuyên cần</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f5f6fa;
        }

        .header {
            padding: 12px 25px;
        }

        h1 {
            margin: 20px 0px 15px 30px;
        }

        .button-container {
            text-align: right;
            margin-right: 50px;
            display: flex;
            justify-self: flex-end;
        }

        .filter-box {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin: 15px 20px 15px 30px;
        }


        .filter-box form {
            width: 100%;
        }

        label {
            font-weight: 750;
            color: #333;
        }

        select,
        input[type=date] {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-right: 50px;
            margin-left: 10px;
            width: 200%;
        }

        .btn {
            padding: 7px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: #fff;
            background: #0b3364;
        }

        table {
            width: 95%;
            height: auto;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            margin: 20px 20px 0px 20px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        tr {
            text-align: center;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #0b3364;
            color: #fff;
        }

        tr:hover {
            background: #f9f9f9;
        }

        /* .status-btns {
            display: flex;
            gap: 5px;
            justify-content: center;
        } */

        .status-btn {
            padding: 5px 8px;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-size: 13px;
            transition: 0.2s;
        }

        .present {
            background: #27ae60;
            color: #fff;
        }

        .late {
            background: #f39c12;
            color: #fff;
        }

        .absent {
            background: #e74c3c;
            color: #fff;
        }

        .status-btn.active {
            outline: 3px solid #222;
        }

        .summary-box {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 300px;
            float: right;
        }

        .summary-box h3 {
            margin-bottom: 10px;
            color: #0b3364;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
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
                    <li onclick="window.location.href='../pagegiaovien/tlhoctap.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/chuyencan.php'"><i class="fa-solid fa-check"></i> Chuyên cần</li>
                    <li class="active" onclick="window.location.href='../pagegiaovien/diemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
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
                    <input type="text" id="searchScores" placeholder="Tìm kiếm học sinh...">
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
            <h1>BẢNG ĐIỂM</h1>
            <div style="margin-bottom:10px;" class="button-container">
                <button class="btn" onclick="window.location.href='nhapdiem.php'">
                    <i class="fa-solid fa-keyboard"></i> Nhập điểm mới
                </button>
            </div>
            <form method="GET" class="filter-box">
                <div>
                    <label for="lop"><strong>Lớp:</strong></label><br>
                    <select name="lop" id="lop" onchange="this.form.submit()">
                        <option value="">Tất cả lớp</option>
                        <?php while ($l = $dsLop->fetch_assoc()) {
                            $sel = ($l['maLop'] == $lopChon) ? "selected" : "";
                            echo "<option value='{$l['maLop']}' $sel>{$l['tenLop']}</option>";
                        } ?>
                    </select>
                </div>
            </form>

            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="checkAll"></th>
                        <th>STT</th>
                        <th>MÃ HS</th>
                        <th>HỌ TÊN</th>
                        <th>LỚP</th>
                        <th>ĐIỂM HK I</th>
                        <th>ĐIỂM HK II</th>
                        <th>TRUNG BÌNH</th>
                        <th>TÁC VỤ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        $stt = 1;
                        while ($r = $result->fetch_assoc()) {
                            $tb = "-";
                            if (is_numeric($r['diemHK1']) || is_numeric($r['diemHK2'])) {
                                $tong = 0;
                                $dem = 0;
                                foreach (['diemHK1', 'diemHK2'] as $c) {
                                    if (is_numeric($r[$c])) {
                                        $tong += $r[$c];
                                        $dem++;
                                    }
                                }
                                $tb = $dem ? round($tong / $dem, 1) : "-";
                            }
                            $hrefSua = "suadiem.php?maHS={$r['maHS']}&mon={$maMonHoc}";
                            $hrefXuat = "../pagegiaovien/export_diem_excel.php?maHS=" . urlencode($r['maHS']) . "&mon=" . urlencode($tenMonHoc);
                            $hrefCT   = "chitietdiem.php?maHS={$r['maHS']}&mon={$maMonHoc}";
                            echo "
                                <tr class='score-row' 
                                    data-name='" . htmlspecialchars($r['hoVaTen']) . "' 
                                    data-mahs='" . htmlspecialchars($r['maHS']) . "'>
                                    <td><input type='checkbox' class='rowCheckbox'></td>
                                    <td>{$stt}</td>
                                    <td>" . htmlspecialchars($r['maHS']) . "</td>
                                    <td>" . htmlspecialchars($r['hoVaTen']) . "</td>
                                    <td>" . htmlspecialchars($r['tenLop']) . "</td>
                                    <td>" . ($r['diemHK1'] ?? '-') . "</td>
                                    <td>" . ($r['diemHK2'] ?? '-') . "</td>
                                    <td><strong>{$tb}</strong></td>
                                    <td>
                                        <a href='{$hrefCT}'><i class='fa-solid fa-eye' style='color:green'></i></a>
                                        &nbsp;
                                        <a href='{$hrefSua}'><i class='fa-solid fa-pen-to-square' style='color:black'></i></a>
                                        &nbsp;
                                        <a href='{$hrefXuat}' onclick=\"return confirm('Bạn có chắc muốn xuất toàn bộ điểm của học sinh này trong môn " . htmlspecialchars($tenMonHoc) . " không?');\" title='Xuất điểm'>
                                            <i class='fa-solid fa-file-export' style='color:black;'></i>
                                        </a>
                                    </td>
                                </tr>";
                            $stt++;
                        }
                    } else {
                        echo "<tr><td colspan='8'>Không có dữ liệu phù hợp.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <!-- Thanh phân trang -->
            <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:10px;">
                <span style="font-size:14px; color:#333;">Trang <?= $page ?>/<?= max(1, $totalPages) ?> (Tổng: <?= $totalItems ?> học sinh)</span>
                <div style="display:flex; gap:8px; align-items:center;">
                    <?php $baseParams = '';
                    if ($lopChon !== '') $baseParams .= '&lop=' . urlencode($lopChon);
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?= $baseParams ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">⏮ Đầu</a>
                        <a href="?page=<?= $page - 1 ?><?= $baseParams ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">◀ Trước</a>
                    <?php else: ?>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">⏮ Đầu</button>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">◀ Trước</button>
                    <?php endif; ?>

                    <span style="font-weight:600; font-size:14px; min-width:30px; text-align:center;"><?= $page ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $baseParams ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Sau ▶</a>
                        <a href="?page=<?= $totalPages ?><?= $baseParams ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Cuối ⏭</a>
                    <?php else: ?>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Sau ▶</button>
                        <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Cuối ⏭</button>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Nút Import & Export-->
            <div class="button-container">
                <a href="import_diem_excel.php"
                    style="margin:20px 30px; padding:10px 16px; background:#0b1e6b; color:white; border:none; border-radius:6px; cursor:pointer; text-decoration:none; display:inline-block; width:180px; text-align:center;">
                    Import bảng điểm
                </a>
                <form method="POST" action="export_diem_excel.php" id="exportForm">
                    <input type="hidden" name="selectedHS" id="selectedHS">
                    <button type="submit" style="margin:20px 0; padding:10px 16px; background:green; color:white; border:none; border-radius:6px; cursor:pointer; width:200px; height: 41px;">
                        Export bảng điểm
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Checkbox "chọn tất cả"
        const checkAll = document.getElementById("checkAll");
        const rowCheckboxes = document.querySelectorAll(".rowCheckbox");

        // Khi tick vào checkbox đầu tiên
        checkAll.addEventListener("change", function() {
            rowCheckboxes.forEach(cb => cb.checked = checkAll.checked);
        });

        document.getElementById("exportForm").addEventListener("submit", function(e) {
            const selected = [];
            document.querySelectorAll("tbody tr").forEach(row => {
                const checkbox = row.querySelector(".rowCheckbox");
                if (checkbox && checkbox.checked) {
                    // cột MÃ HS nằm ở vị trí thứ 2 (index 2)
                    const maHS = row.children[2]?.innerText.replace("K", "");
                    selected.push(maHS);
                }
            });
            if (selected.length === 0) {
                alert("Vui lòng chọn ít nhất một học sinh để export!");
                e.preventDefault();
                return;
            }
            document.getElementById("selectedHS").value = selected.join(",");
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

        // === Cập nhật chuyên cần tức thì (AJAX) ===
        function setStatus(maHS, status, button) {
            // Bỏ active các nút cùng hàng
            const row = button.closest('.status-btns');
            row.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
            button.classList.add('active');

            // Cập nhật input ẩn
            document.getElementById('status' + maHS).value = status;

            // Lấy dữ liệu cần gửi
            const formData = new FormData();
            formData.append('maHS', maHS);
            formData.append('maMonHoc', document.querySelector('input[name="maMonHoc"]').value);
            formData.append('ngayHoc', document.querySelector('input[name="ngayHoc"]').value);
            formData.append('trangThai', status);

            // Gửi AJAX cập nhật vào CSDL
            formData.append('ajax', '1'); // đánh dấu là request AJAX
            fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(text => {
                    if (text.trim() === 'OK') {
                        console.log(' Cập nhật thành công:', maHS, status);
                    } else {
                        console.error(' Lỗi:', text);
                        alert('Không thể cập nhật trạng thái!');
                    }
                })
                .catch(err => console.error('Lỗi kết nối:', err));
        }

        // Xử lý tìm kiếm học sinh
        const searchInput = document.getElementById("searchScores");
        const scoreRows = document.querySelectorAll(".score-row");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                scoreRows.forEach(row => {
                    const name = row.getAttribute("data-name").toLowerCase();
                    const mahs = row.getAttribute("data-mahs").toLowerCase();

                    if (name.includes(keyword) || mahs.includes(keyword)) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
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