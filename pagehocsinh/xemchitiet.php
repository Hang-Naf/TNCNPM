<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "HocSinh") {
    header("Location: ../dangnhap.php");
    exit();
}

$userID = $_SESSION["userID"];
$maMonHoc = $_GET['maMonHoc'] ?? '';

if ($maMonHoc == '') {
    echo "Thiếu tham số môn học!";
    exit();
}

// === Lấy thông tin học sinh ===
$sqlHS = "SELECT hoVaTen FROM user WHERE userID = ?";
$stmtHS = $conn->prepare($sqlHS);
$stmtHS->bind_param("i", $userID);
$stmtHS->execute();
$hs = $stmtHS->get_result()->fetch_assoc();

// === Lấy chi tiết điểm theo môn ===
$sql = "SELECT d.loaiDiem, d.diem, m.tenMonHoc
        FROM diemso d
        JOIN monhoc m ON d.maMonHoc = m.maMonHoc
        WHERE d.maHS = ? AND d.maMonHoc = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userID, $maMonHoc);
$stmt->execute();
$result = $stmt->get_result();

$diem = [
    'hk1_mieng' => null,
    'hk1_1tiet' => null,
    'hk1_thiGK' => null,
    'hk1_thiCK' => null,
    'hk2_mieng' => null,
    'hk2_1tiet' => null,
    'hk2_thiGK' => null,
    'hk2_thiCK' => null,
    'tbHK1' => null,
    'tbHK2' => null,
    'tb' => null
];

while ($r = $result->fetch_assoc()) {
    $loai = strtolower($r['loaiDiem']);
    $val  = is_numeric($r['diem']) ? (float)$r['diem'] : null;

    if (strpos($loai, 'hk1_mieng') !== false) $diem['hk1_mieng'] = $val;
    elseif (strpos($loai, 'hk1_1tiet') !== false) $diem['hk1_1tiet'] = $val;
    elseif (strpos($loai, 'hk1_thigk') !== false) $diem['hk1_thiGK'] = $val;
    elseif (strpos($loai, 'hk1_thick') !== false) $diem['hk1_thiCK'] = $val;
    elseif (strpos($loai, 'hk2_mieng') !== false) $diem['hk2_mieng'] = $val;
    elseif (strpos($loai, 'hk2_1tiet') !== false) $diem['hk2_1tiet'] = $val;
    elseif (strpos($loai, 'hk2_thigk') !== false) $diem['hk2_thiGK'] = $val;
    elseif (strpos($loai, 'hk2_thick') !== false) $diem['hk2_thiCK'] = $val;

    $tenMonHoc = $r['tenMonHoc'];
}

// Tính trung bình HK1
$sum = 0;
$count = 0;
if (is_numeric($diem['hk1_mieng'])) {
    $sum += $diem['hk1_mieng'] * 1;
    $count += 1;
}
if (is_numeric($diem['hk1_1tiet'])) {
    $sum += $diem['hk1_1tiet'] * 2;
    $count += 2;
}
if (is_numeric($diem['hk1_thiGK'])) {
    $sum += $diem['hk1_thiGK'] * 2;
    $count += 2;
}
if (is_numeric($diem['hk1_thiCK'])) {
    $sum += $diem['hk1_thiCK'] * 3;
    $count += 3;
}
$diem['tbHK1'] = $count > 0 ? round($sum / $count, 1) : null;

// Tính trung bình HK2
$sum = 0;
$count = 0;
if (is_numeric($diem['hk2_mieng'])) {
    $sum += $diem['hk2_mieng'] * 1;
    $count += 1;
}
if (is_numeric($diem['hk2_1tiet'])) {
    $sum += $diem['hk2_1tiet'] * 2;
    $count += 2;
}
if (is_numeric($diem['hk2_thiGK'])) {
    $sum += $diem['hk2_thiGK'] * 2;
    $count += 2;
}
if (is_numeric($diem['hk2_thiCK'])) {
    $sum += $diem['hk2_thiCK'] * 3;
    $count += 3;
}
$diem['tbHK2'] = $count > 0 ? round($sum / $count, 1) : null;

// Trung bình môn
if (is_numeric($diem['tbHK1']) && is_numeric($diem['tbHK2'])) {
    $diem['tb'] = round(($diem['tbHK1'] + $diem['tbHK2']) / 2, 1);
} elseif (is_numeric($diem['tbHK1'])) {
    $diem['tb'] = $diem['tbHK1'];
} elseif (is_numeric($diem['tbHK2'])) {
    $diem['tb'] = $diem['tbHK2'];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết điểm - <?= htmlspecialchars($monHoc) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f7f9fb;
            margin: 0;
        }

        h1 {
            text-align: center;
            color: #0b1e6b;
            margin-top: 20px;
        }

        table {
            width: 95%;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #eee;
            padding: 10px;
            text-align: center;
        }

        th {
            background: #f1f3f9;
            text-transform: uppercase;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        .back-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 10px;
            background: #0b1e6b;
            color: white;
            text-align: center;
            border-radius: 6px;
            text-decoration: none;
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
                    <li onclick="window.location.href='../pagehocsinh/tlhoctap.php'"><i class="fa-solid fa-book"></i> Tài liệu học tập</li>
                    <li class="active" onclick="window.location.href='../pagehocsinh/kqhoctap.php'"><i class="fa-solid fa-file-lines"></i> Kết quả học tập</li>
                </ul>
            </div>
        </nav>
    </aside>
    <div class="main-content">
        <header class="header">
            <div class="left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchScores" placeholder="Tìm kiếm môn học...">
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
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="window.location.href='../pagehocsinh/ttcanhan.php'"><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container">
            <h1>Chi tiết điểm môn <?= htmlspecialchars($tenMonHoc ?? '') ?></h1>

            <?php if (empty(array_filter($diem))): ?>
                <p style="text-align:center;color:gray;">Chưa có dữ liệu điểm cho môn này.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>HK1 - Miệng</th>
                            <th>HK1 - 1 Tiết</th>
                            <th>HK1 - GK</th>
                            <th>HK1 - CK</th>
                            <th>TB HK1</th>
                            <th>HK2 - Miệng</th>
                            <th>HK2 - 1 Tiết</th>
                            <th>HK2 - GK</th>
                            <th>HK2 - CK</th>
                            <th>TB HK2</th>
                            <th>Trung bình môn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php
                            $fields = [
                                'hk1_mieng',
                                'hk1_1tiet',
                                'hk1_thiGK',
                                'hk1_thiCK',
                                'tbHK1',
                                'hk2_mieng',
                                'hk2_1tiet',
                                'hk2_thiGK',
                                'hk2_thiCK',
                                'tbHK2',
                                'tb'
                            ];
                            foreach ($fields as $f): ?>
                                <td><?= $diem[$f] ?? '-' ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>

            <a href="kqhoctap.php" class="back-btn">⬅ Quay lại bảng điểm</a>
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

        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }

        // === CHỨC NĂNG TÌM KIẾM BẢNG ĐIỂM ===
        const searchInput = document.getElementById("searchScores");
        const scoreRows = document.querySelectorAll(".score-row");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                let foundCount = 0;

                scoreRows.forEach(row => {
                    const subject = row.getAttribute("data-subject").toLowerCase();

                    if (subject.includes(keyword)) {
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