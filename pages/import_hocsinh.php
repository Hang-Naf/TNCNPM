<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    header("Location: ../dangnhap.php");
    exit();
}

require '../vendor/autoload.php';

$errorList = [];
$count = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excelFile"])) {
    $fileTmp = $_FILES["excelFile"]["tmp_name"];

    try {
        $spreadsheet = IOFactory::load($fileTmp);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
    } catch (Exception $e) {
        $errorList[] = "File Excel không hợp lệ!";
        $rows = [];
    }

    if (empty($rows)) {
        echo "<script>alert('File Excel trống!'); window.location.href='qlhocsinh.php';</script>";
        exit();
    }

    array_shift($rows); // bỏ dòng tiêu đề
    if (count($rows) > 100) $rows = array_slice($rows, 0, 100);

    foreach ($rows as $index => $r) {
        $hoVaTen = isset($r[0]) ? trim($r[0]) : '';
        $gioiTinh = isset($r[1]) ? trim($r[1]) : '';
        $email = isset($r[2]) ? strtolower(trim($r[2])) : '';
        $sdt = isset($r[3]) ? trim($r[3]) : '';
        $matKhau = isset($r[4]) ? trim($r[4]) : '';
        $lopHocPhuTrach = isset($r[5]) ? trim($r[5]) : '';
        $chucVu = isset($r[6]) ? trim($r[6]) : '';
        $namHoc = isset($r[7]) ? trim($r[7]) : '';
        $hocKy = isset($r[8]) ? trim($r[8]) : '';
        $trangThai = 'active';

        if (!$hoVaTen || !$email || !$matKhau) {
            $errorList[] = "Dòng " . ($index + 2) . ": Thiếu dữ liệu bắt buộc (Họ tên, Email, Mật khẩu)";
            continue;
        }

        // Check email tồn tại
        $chkStmt = $conn->prepare("SELECT userID FROM user WHERE email = ?");
        $chkStmt->bind_param("s", $email);
        $chkStmt->execute();
        $chkResult = $chkStmt->get_result();
        if ($chkResult->num_rows > 0) {
            $errorList[] = "Dòng " . ($index + 2) . ": Email '$email' đã tồn tại, bỏ qua";
            $chkStmt->close();
            continue;
        }
        $chkStmt->close();

        $hash = password_hash($matKhau, PASSWORD_BCRYPT);

        // Thêm user
        $stmtUser = $conn->prepare("INSERT INTO user (hoVaTen, gioiTinh, email, sdt, matKhau, vaiTro) VALUES (?, ?, ?, ?, ?, 'HocSinh')");
        $stmtUser->bind_param("sssss", $hoVaTen, $gioiTinh, $email, $sdt, $hash);
        $stmtUser->execute();
        $maHS = $stmtUser->insert_id;
        $stmtUser->close();

        // Thêm / cập nhật hocsinh
        $checkHSStmt = $conn->prepare("SELECT maHS FROM hocsinh WHERE maHS = ?");
        $checkHSStmt->bind_param("i", $maHS);
        $checkHSStmt->execute();
        $checkHSResult = $checkHSStmt->get_result();
        $hsExists = ($checkHSResult->num_rows > 0);
        $checkHSStmt->close();

        if ($hsExists) {
            $stmtHS = $conn->prepare("UPDATE hocsinh SET lopHocPhuTrach=?, chucVu=?, namHoc=?, hocKy=?, trangThai=? WHERE maHS=?");
            $stmtHS->bind_param("sssssi", $lopHocPhuTrach, $chucVu, $namHoc, $hocKy, $trangThai, $maHS);
            $stmtHS->execute();
            $stmtHS->close();
        } else {
            $stmtHS = $conn->prepare("INSERT INTO hocsinh (maHS, lopHocPhuTrach, chucVu, namHoc, hocKy, anhDaiDien, trangThai) VALUES (?, ?, ?, ?, ?, 'Chưa cập nhật', ?)");
            $stmtHS->bind_param("isssss", $maHS, $lopHocPhuTrach, $chucVu, $namHoc, $hocKy, $trangThai);
            $stmtHS->execute();
            $stmtHS->close();
        }

        write_log($conn, $_SESSION["userID"], "Import", "Thêm học sinh: $hoVaTen", "Info");
        $count++;
    }

    // Hiển thị kết quả
    $errStr = !empty($errorList) ? implode("\\n", $errorList) : '';
    if ($count > 0) {
        if (!empty($errorList)) {
            echo "<script>alert('Import thành công $count học sinh nhưng có lỗi:\\n$errStr'); window.location.href='qlhocsinh.php';</script>";
        } else {
            echo "<script>alert('Import thành công $count học sinh!'); window.location.href='qlhocsinh.php';</script>";
        }
        exit();
    } elseif (!empty($errorList)) {
        echo "<script>alert('Không import được học sinh nào:\\n$errStr'); window.location.href='qlhocsinh.php';</script>";
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Import Học Sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
        }

        .header {
            padding: 10px 25px;
        }

        .container {
            padding: 20px;
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="file"] {
            padding: 10px;
            border: 2px dashed #0b3364;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
        }

        .info-box {
            background: #f0f8ff;
            border-left: 4px solid #0b3364;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }

        .info-box h4 {
            margin: 0 0 8px 0;
            color: #0b3364;
        }

        .info-box ul {
            margin: 0;
            padding-left: 20px;
        }

        .info-box li {
            margin: 4px 0;
        }

        .btn-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-import {
            background: #0b3364;
            color: white;
            width: 150px;
            height: 48px;
        }

        .btn-import:hover {
            background: #0a2a50;
        }

        .btn-back {
            background: #ccc;
            color: #333;
            width: 150px;
        }

        .btn-back:hover {
            background: #bbb;
        }

        .btn-sample {
            background: #28a745;
            color: white;
            width: auto;
            padding: 10px 20px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-sample:hover {
            background: #218838;
        }

        .sample-area {
            text-align: center;
            margin-bottom: 20px;
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
                    <li class="active" onclick="window.location.href='../pages/qlhocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
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
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
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
                    <input type="text" placeholder="Tìm kiếm...">
                </div>
            </div>

            <div class="right">
                <div class="user-info" onclick="toggleUserMenu()">
                    <i class="fa-solid fa-user"></i>
                    <span>Quản trị viên</span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu" style="display:none;">
                    <ul>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container">
            <h1>IMPORT HỌC SINH TỪ FILE EXCEL</h1>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="excelFile">Chọn file Excel (.xlsx, .xls):</label>
                    <input type="file" name="excelFile" id="excelFile" accept=".xlsx,.xls" required>
                </div>

                <div class="sample-area">
                    <a href="mau_import_hocsinh.xlsx" class="btn-sample" style="border-radius: 6px; width: 150px; height: 26px; margin-right: 20px;" download>📥 Tải file mẫu</a>
                    <button type="submit" class="btn-import">📤 Import</button>
                </div>

            </form>
        </div>
        <div class="btn-container">

            <a href="qlhocsinh.php" class="btn-back" style="text-decoration:none; display:flex; align-items:center; border-radius: 7px; justify-content:center; width: 150px; height: 50px;">⬅ Quay lại</a>
        </div>
    </div>

    <script>
        function toggleUserMenu() {
            const menu = document.getElementById("userMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        document.addEventListener("click", function(e) {
            const menu = document.getElementById("userMenu");
            const userInfo = document.querySelector(".user-info");
            if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = "none";
            }
        });

        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php";
            }
        }
    </script>
</body>

</html>