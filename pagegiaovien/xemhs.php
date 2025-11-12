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
$maGV = $userID;

// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = ? LIMIT 1";
$stmtGV = $conn->prepare($sqlGV);
$stmtGV->bind_param("i", $maGV);
$stmtGV->execute();
$resultGV = $stmtGV->get_result();
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];

// ==== Lấy danh sách lớp ====
$sql_lop = "SELECT DISTINCT l.maLop, l.tenLop
            FROM lophoc l
            JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
            WHERE lm.maGV = ?";
$stmt = $conn->prepare($sql_lop);
$stmt->bind_param("i", $userID);
$stmt->execute();
$lops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$msg = "";
$hs = null;

// ==== Lấy dữ liệu học sinh cần sửa ====
if (!isset($_GET["maHS"])) {
    header("Location: hocsinh.php");
    exit();
}

$maHS = intval($_GET["maHS"]);

$sqlHS = "SELECT u.hoVaTen, u.email, u.sdt, u.gioiTinh,
                 h.chucVu, h.trangThai, h.lopHocPhuTrach, h.namHoc, h.hocKy
          FROM user u 
          JOIN hocsinh h ON u.userID = h.maHS
          WHERE u.userID = ?";
$stmtHS = $conn->prepare($sqlHS);
$stmtHS->bind_param("i", $maHS);
$stmtHS->execute();
$resultHS = $stmtHS->get_result();
if ($resultHS->num_rows === 0) {
    $msg = "❌ Không tìm thấy học sinh.";
} else {
    $hs = $resultHS->fetch_assoc();
}

// ==== Xử lý cập nhật ====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hoTen = trim($_POST["hoTen"]);
    $email = trim($_POST["email"]);
    $sdt = trim($_POST["sdt"]);
    $gioiTinh = $_POST["gioiTinh"];
    $lop = $_POST["lop"];
    $chucVu = trim($_POST["chucVu"]);
    $trangThai = $_POST["trangThai"];
    $namHoc = $_POST["namHoc"];
    $hocKy = $_POST["hocKy"];

    $conn->begin_transaction();
    try {
        // 1️⃣ Cập nhật bảng user
        $sqlUser = "UPDATE user SET hoVaTen=?, email=?, sdt=?, gioiTinh=? WHERE userID=?";
        $stmtU = $conn->prepare($sqlUser);
        $stmtU->bind_param("ssssi", $hoTen, $email, $sdt, $gioiTinh, $maHS);
        if (!$stmtU->execute()) throw new Exception("Lỗi update user: " . $stmtU->error);

        // 2️⃣ Lấy tên lớp từ maLop
        $tenLop = null;
        $q = $conn->prepare("SELECT tenLop FROM lophoc WHERE maLop=?");
        $q->bind_param("i", $lop);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        $tenLop = $r ? $r['tenLop'] : "Chưa cập nhật";
        $q->close();

        // 3️⃣ Cập nhật bảng hocsinh
        $sqlHS2 = "UPDATE hocsinh 
                   SET lopHocPhuTrach=?, chucVu=?, namHoc=?, hocKy=?, trangThai=? 
                   WHERE maHS=?";
        $stmtHS2 = $conn->prepare($sqlHS2);
        $stmtHS2->bind_param("sssssi", $tenLop, $chucVu, $namHoc, $hocKy, $trangThai, $maHS);
        if (!$stmtHS2->execute()) throw new Exception("Lỗi update hocsinh: " . $stmtHS2->error);

        // 4️⃣ Cập nhật hocsinh_lophoc
        $sqlDel = "DELETE FROM hocsinh_lophoc WHERE maHS=?";
        $stmtD = $conn->prepare($sqlDel);
        $stmtD->bind_param("i", $maHS);
        $stmtD->execute();

        $sqlAdd = "INSERT INTO hocsinh_lophoc (maHS, maLop) VALUES (?, ?)";
        $stmtAdd = $conn->prepare($sqlAdd);
        $stmtAdd->bind_param("ii", $maHS, $lop);
        $stmtAdd->execute();

        $conn->commit();
        echo "<script>alert('Cập nhật học sinh thành công!'); window.location='hocsinh.php';</script>";
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "❌ Lỗi khi cập nhật học sinh: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sửa học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .container {
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #0b1e6b;
            margin-bottom: 25px;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        label {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            display: block;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .full {
            grid-column: span 2;
        }

        .radio-group {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .buttons {
            margin-top: 20px;
            text-align: right;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .info-item {
            padding: 8px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #f9f9f9;
        }

        .info-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
            display: block;
        }

        .info-value {
            color: #555;
        }

        .full {
            grid-column: span 2;
        }

        .buttons {
            margin-top: 25px;
            text-align: right;
        }

        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #0b1e6b;
            background: #0b1e6b;
            color: white;
            cursor: pointer;
        }

        .btn:hover {
            background: #1129a6;
        }

        .msg {
            text-align: center;
            margin-bottom: 15px;
            font-weight: 500;
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
                    <li class="active" onclick="window.location.href='../pagegiaovien/hocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
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
            <h1>CHI TIẾT HỌC SINH</h1>
            <?php if (!empty($msg)): ?>
                <div class="msg"><?= htmlspecialchars($msg) ?></div>
            <?php elseif ($hs): ?>
                <div class="info-grid">
                    <div><span class="info-label">Năm học:</span>
                        <div class="info-item"><?= htmlspecialchars($hs['namHoc']) ?></div>
                    </div>
                    <div><span class="info-label">Học kỳ:</span>
                        <div class="info-item"><?= htmlspecialchars($hs['hocKy']) ?></div>
                    </div>
                    <div class="full"><span class="info-label">Họ và tên:</span>
                        <div class="info-item"><?= htmlspecialchars($hs['hoVaTen']) ?></div>
                    </div>
                    <div><span class="info-label">Email:</span>
                        <div class="info-item"><?= htmlspecialchars($hs['email']) ?></div>
                    </div>
                    <div><span class="info-label">Số điện thoại:</span>
                        <div class="info-item"><?= htmlspecialchars($hs['sdt']) ?></div>
                    </div>
                    <div><span class="info-label">Giới tính:</span>
                        <div class="info-item"><?= htmlspecialchars($hs['gioiTinh']) ?></div>
                    </div>
                    <div><span class="info-label">Chức vụ:</span>
                        <div class="info-item"><?= htmlspecialchars($hs['chucVu']) ?></div>
                    </div>
                    <div><span class="info-label">Lớp học:</span>
                        <div class="info-item"><?= htmlspecialchars($hs['lopHocPhuTrach']) ?></div>
                    </div>
                    <div class="full"><span class="info-label">Trạng thái:</span>
                        <div class="info-item"><?= $hs['trangThai'] === 'active' ? 'Đang học' : 'Đã nghỉ' ?></div>
                    </div>
                </div>
                <div class="buttons">
                    <button type="button" class="btn" onclick="window.location='hocsinh.php'">Quay lại</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>