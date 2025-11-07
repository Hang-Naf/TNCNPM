<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Học sinh ====
if ($_SESSION["vaiTro"] !== "HocSinh") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$userID = $_SESSION["userID"];

// === Lấy thông tin học sinh ===
$sqlHS = "SELECT hoVaTen FROM user WHERE userID = ?";
$stmtHS = $conn->prepare($sqlHS);
$stmtHS->bind_param("i", $userID);
$stmtHS->execute();
$resultHS = $stmtHS->get_result();
$hs = $resultHS->fetch_assoc();

// === Lấy điểm theo môn ===
$sql = "SELECT d.maMonHoc, m.tenMonHoc, d.loaiDiem, d.diem
        FROM diemso d
        LEFT JOIN monhoc m ON d.maMonHoc = m.maMonHoc
        WHERE d.maHS = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$bangDiem = [];
while ($r = $result->fetch_assoc()) {
    $mon = $r['tenMonHoc'];
    $loai = strtolower($r['loaiDiem']); // ví dụ: 'miệng', '1 tiết', 'thi hk i'
    $diem = is_numeric($r['diem']) ? (float)$r['diem'] : null;

    if (!isset($bangDiem[$mon])) {
        $bangDiem[$mon] = [
            'mieng' => null,
            '1tiet' => null,
            'thi1' => null,
            'thi2' => null,
            'tb' => null
        ];
    }

    if (strpos($loai, 'miệng') !== false) $bangDiem[$mon]['mieng'] = $diem;
    elseif (strpos($loai, '1') !== false) $bangDiem[$mon]['1tiet'] = $diem;
    elseif (strpos($loai, 'hk i') !== false) $bangDiem[$mon]['thi1'] = $diem;
    elseif (strpos($loai, 'hk ii') !== false || strpos($loai, 'hk2') !== false) $bangDiem[$mon]['thi2'] = $diem;

    // Tính trung bình sau khi có đủ dữ liệu
    $arr = $bangDiem[$mon];
    $sum = 0;
    $count = 0;
    foreach (['mieng', '1tiet', 'thi1', 'thi2'] as $key) {
        if (is_numeric($arr[$key])) {
            $sum += $arr[$key];
            $count++;
        }
    }
    $bangDiem[$mon]['tb'] = $count > 0 ? round($sum / $count, 1) : null;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Bảng điểm</title>
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
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 20px;
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

        td:first-child {
            width: 60px;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
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
                    <input type="text" placeholder="Tìm kiếm...">
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
            <h1>BẢNG ĐIỂM</h1>

            <?php if (empty($bangDiem)): ?>
                <div class="no-data">Chưa có dữ liệu điểm.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Môn học</th>
                            <th>Điểm miệng</th>
                            <th>Điểm 1 tiết</th>
                            <th>Điểm thi học kì I</th>
                            <th>Điểm thi học kì II</th>
                            <th>Trung bình môn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1;
                        foreach ($bangDiem as $mon => $d): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td style="text-align:left;"><?= htmlspecialchars($mon) ?></td>
                                <td><?= $d['mieng'] ?? '-' ?></td>
                                <td><?= $d['1tiet'] ?? '-' ?></td>
                                <td><?= $d['thi1'] ?? '-' ?></td>
                                <td><?= $d['thi2'] ?? '-' ?></td>
                                <td><strong><?= $d['tb'] ?? '-' ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
    </script>
</body>

</html>