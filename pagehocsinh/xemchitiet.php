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
$monHoc = $_GET['mon'] ?? '';

if ($monHoc == '') {
    echo "Thiếu tham số môn học!";
    exit();
}

// === Lấy thông tin học sinh ===
$sqlHS = "SELECT hoVaTen FROM user WHERE userID = ?";
$stmtHS = $conn->prepare($sqlHS);
$stmtHS->bind_param("i", $userID);
$stmtHS->execute();
$resultHS = $stmtHS->get_result();
$hs = $resultHS->fetch_assoc();

// === Lấy chi tiết điểm theo môn ===
$sql = "SELECT d.loaiDiem, d.diem, m.tenMonHoc
        FROM diemso d
        LEFT JOIN monhoc m ON d.maMonHoc = m.maMonHoc
        WHERE d.maHS = ? AND m.tenMonHoc = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $userID, $monHoc);
$stmt->execute();
$result = $stmt->get_result();

$dsDiem = [];
while ($r = $result->fetch_assoc()) {
    $dsDiem[] = $r;
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
            width: 80%;
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
            <h1>Chi tiết điểm môn <?= htmlspecialchars($monHoc) ?></h1>

            <?php if (empty($dsDiem)): ?>
                <p style="text-align:center;color:gray;">Chưa có dữ liệu điểm cho môn này.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Loại điểm</th>
                            <th>Điểm</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1;
                        foreach ($dsDiem as $d): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($d['loaiDiem']) ?></td>
                                <td><?= is_numeric($d['diem']) ? $d['diem'] : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <a href="kqhoctap.php" class="back-btn">⬅ Quay lại bảng điểm</a>
        </div>
    </div>
</body>

</html>