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

$maGV = $_SESSION["userID"];

// ==== Lấy mã lớp từ URL ====
if (!isset($_GET['maLop'])) {
    die("Chưa chọn lớp");
}
$maLop = $_GET['maLop'];

// ==== Lấy thông tin lớp (chỉ khi lớp thuộc giáo viên này) ====
$sqlLop = "SELECT l.maLop, l.tenLop, l.namHoc, l.trangThai,
                  u.hoVaTen AS giaoVien,
                  COUNT(hl.maHS) AS siSo
           FROM lophoc l
           LEFT JOIN giaovien g ON l.maGV = g.maGV
           LEFT JOIN user u ON g.maGV = u.userID
           LEFT JOIN hocsinh_lophoc hl ON l.maLop = hl.maLop
           WHERE l.maLop = ? AND l.maGV = ?
           GROUP BY l.maLop, l.tenLop, l.namHoc, l.trangThai, u.hoVaTen";
$stmtLop = $conn->prepare($sqlLop);
if (!$stmtLop) {
    die("Lỗi prepare SQL: " . $conn->error);
}
$stmtLop->bind_param("ss", $maLop, $maGV);
$stmtLop->execute();
$resultLop = $stmtLop->get_result();
if (!$resultLop || $resultLop->num_rows === 0) {
    die("Lớp không tồn tại hoặc bạn không được phân công");
}
$lop = $resultLop->fetch_assoc();

// ==== Tách khối từ tên lớp ====
$khoi = preg_match('/^\d+/', $lop['tenLop'], $matches) ? $matches[0] : '';
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết lớp <?= htmlspecialchars($lop['tenLop']) ?></title>
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
            padding: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
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
    </style>
</head>

<body>
    <div class="container">
        <h1>Chi tiết lớp: <?= htmlspecialchars($lop['tenLop']) ?></h1>

        <table class="table">
            <thead>
                <tr>
                    <th>Năm học</th>
                    <th>Khối</th>
                    <th>Mã lớp</th>
                    <th>Tên lớp</th>
                    <th>Giáo viên chủ nhiệm</th>
                    <th>Sĩ số</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= htmlspecialchars($lop['namHoc']) ?></td>
                    <td><?= htmlspecialchars($khoi) ?></td>
                    <td><?= htmlspecialchars($lop['maLop']) ?></td>
                    <td><?= htmlspecialchars($lop['tenLop']) ?></td>
                    <td><?= htmlspecialchars($lop['giaoVien'] ?? 'Chưa phân công') ?></td>
                    <td><?= $lop['siSo'] ?></td>
                    <td class="status <?= ($lop['trangThai'] == 'Đang học') ? 'active' : 'inactive' ?>">
                        <?= ($lop['trangThai'] == 'Đang học') ? 'Đang học' : 'Tạm dừng' ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <br>
        <button onclick="window.history.back()">Quay lại</button>
    </div>
</body>

</html>