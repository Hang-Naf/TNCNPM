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

// ==== Lấy thông tin giáo viên đăng nhập ====
$sql = "SELECT hoVaTen FROM user WHERE userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$res = $stmt->get_result();
$gv = $res->fetch_assoc() ?: ['hoVaTen' => 'Giáo viên'];
$stmt->close();

// ==== Lấy mã thông báo từ URL ====
$maTB = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($maTB <= 0) {
    die("<p style='color:red; text-align:center;'>Không tìm thấy thông báo (ID không hợp lệ).</p>");
}

// ==== Lấy thông tin thông báo chính ====
$sql_tb = "
    SELECT tb.maThongBao, tb.tieuDe, tb.noiDung, tb.tepDinhKem, 
           u.hoVaTen AS tenNguoiGui, tb.ngayGui
    FROM thongbao tb
    LEFT JOIN user u ON tb.nguoiGui = u.userID
    WHERE tb.maThongBao = ? LIMIT 1
";
$stmt = $conn->prepare($sql_tb);
$stmt->bind_param("i", $maTB);
$stmt->execute();
$result_tb = $stmt->get_result();
$tb = $result_tb->fetch_assoc();
$stmt->close();

if (!$tb) {
    die("<p style='color:red; text-align:center;'>Thông báo không tồn tại.</p>");
}

$maThongBao = $tb['maThongBao'];
$tieuDe = $tb['tieuDe'] ?? '';
$noiDung = $tb['noiDung'] ?? '';
$nguoiGui = $tb['tenNguoiGui'] ?? 'Hệ thống';
$ngayGui = $tb['ngayGui'] ? date('d/m/Y H:i', strtotime($tb['ngayGui'])) : '';

// ==== Lấy nhóm người nhận ====
$sql_role = "
    SELECT DISTINCT u.vaiTro 
    FROM thongbaouser tu
    JOIN user u ON tu.userID = u.userID
    WHERE tu.maThongBao = ?
";
$stmt = $conn->prepare($sql_role);
$stmt->bind_param("i", $maTB);
$stmt->execute();
$res_roles = $stmt->get_result();

$roles = [];
while ($r = $res_roles->fetch_assoc()) {
    $roles[] = $r['vaiTro'];
}
$stmt->close();

// Xác định nhóm nhận
if (in_array('GiaoVien', $roles) && in_array('HocSinh', $roles)) {
    $nguoiNhan = 'Toàn hệ thống';
} elseif (in_array('GiaoVien', $roles)) {
    $nguoiNhan = 'Giáo viên';
} elseif (in_array('HocSinh', $roles)) {
    $nguoiNhan = 'Học sinh';
} else {
    $nguoiNhan = 'Không xác định';
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết thông báo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f5f6fa;
            margin: 0;
        }

        .container {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin: 25px;
            width: 550px;
        }

        h2 {
            color: #0b3364;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            color: #0b3364;
        }

        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #f9f9f9;
            height: 120px;
            resize: vertical;
        }

        .radio-group {
            margin: 10px 0;
        }

        .file-link {
            color: #0b3364;
            text-decoration: underline;
        }

        .back-btn {
            display: block;
            width: fit-content;
            margin: 20px auto 0;
            background: #0b3364;
            color: #fff;
            padding: 8px 18px;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-header"><i class="fa-solid fa-graduation-cap logo"></i>
            <h2>Viện đào tạo ABC</h2>
        </div>
        <nav class="menu">
            <div class="menu-section">
                <div class="menu-title">Quản lý chung</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
                    <li onclick="window.location.href='../pagegiaovien/hocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
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
                    <li class="active" onclick="window.location.href='../pagegiaovien/thongbao.php'"><i class="fa-solid fa-bell"></i> Xem thông báo</li>
                </ul>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <header class="header">
            <div class="left">
                <h2 style="color:#0b3364;">Chi tiết thông báo</h2>
            </div>
            <div class="right">
                <div class="user-info" onclick="toggleUserMenu()"><i class="fa-solid fa-user"></i><span><?= htmlspecialchars($gv['hoVaTen']) ?></span><i class="fa-solid fa-angle-down"></i></div>
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container">
            <h2>CHI TIẾT THÔNG BÁO</h2>

            <p><label>Mã thông báo:</label> <?= htmlspecialchars($maThongBao) ?></p>
            <p><label>Tiêu đề:</label> <?= htmlspecialchars($tieuDe) ?></p>

            <label>Nội dung:</label>
            <textarea readonly><?= htmlspecialchars($noiDung) ?></textarea>

            <p><label>Người gửi:</label> <?= htmlspecialchars($nguoiGui) ?></p>
            <p><label>Ngày gửi:</label> <?= htmlspecialchars($ngayGui) ?></p>

            <p><label>Người nhận:</label></p>
            <div class="radio-group">
                <label><input type="radio" <?= (stripos($nguoiNhan, 'Toàn') !== false || stripos($nguoiNhan, 'toan') !== false) ? 'checked' : '' ?> disabled> Toàn hệ thống</label>
                <label><input type="radio" <?= (stripos($nguoiNhan, 'Giáo') !== false) ? 'checked' : '' ?> disabled> Giáo viên</label>
                <label><input type="radio" <?= (stripos($nguoiNhan, 'Học') !== false) ? 'checked' : '' ?> disabled> Học sinh</label>
            </div>

            <p><label>Tệp đính kèm:</label>
                <?php if (!empty($tb['tepDinhKem'])): ?>
                    <br>
                    <a class="file-link" href="../uploads/thongbao/<?= htmlspecialchars($tb['tepDinhKem']) ?>" target="_blank">
                        <?= htmlspecialchars($tb['tepDinhKem']) ?>
                    </a>
                <?php else: ?>
                    <i>Không có tệp đính kèm</i>
                <?php endif; ?>
            </p>

            <a class="back-btn" href="thongbao.php"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById("userMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) window.location.href = "../dangxuat.php";
        }
    </script>
</body>

</html>