<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    header("Location: ../dangnhap.php");
    exit;
}

$maThongBao = $_GET['maThongBao'] ?? '';
if ($maThongBao === '' || !is_numeric($maThongBao)) {
    die("Mã thông báo không hợp lệ!");
}

// ==== Lấy thông tin thông báo ====
$stmt = $conn->prepare("SELECT * FROM thongbao WHERE maThongBao = ?");
$stmt->bind_param("i", $maThongBao);
$stmt->execute();
$result = $stmt->get_result();
$tb = $result->fetch_assoc();
$stmt->close();

if (!$tb) die("Không tìm thấy thông báo!");

// ==== Xác định nhóm người nhận (từ bảng thongbaouser + user) ====
$nguoiNhan = 'toan'; // mặc định

$sql = "
    SELECT DISTINCT u.vaiTro 
    FROM thongbaouser tu
    JOIN user u ON tu.userID = u.userID
    WHERE tu.maThongBao = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $maThongBao);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $roles = [];
    while ($row = $res->fetch_assoc()) {
        $roles[] = strtolower($row['vaiTro']);
    }

    if (count($roles) === 1) {
        if (strpos($roles[0], 'giaovien') !== false) {
            $nguoiNhan = 'giaovien';
        } elseif (strpos($roles[0], 'hocsinh') !== false) {
            $nguoiNhan = 'hocsinh';
        }
    } else {
        $nguoiNhan = 'toan';
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết thông báo</title>
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8f9fb;
            margin: 40px;
        }

        .container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            max-width: 700px;
            margin: auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #0b1e6b;
            text-align: center;
            margin-bottom: 25px;
        }

        p {
            margin: 10px 0;
            font-size: 16px;
        }

        label {
            font-weight: bold;
            color: #333;
        }

        .content-box {
            background: #f1f3f9;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 10px;
            min-height: 80px;
            white-space: pre-wrap;
        }

        a.file-link {
            display: inline-block;
            margin-top: 5px;
            color: #0b1e6b;
            text-decoration: none;
        }

        a.file-link:hover {
            text-decoration: underline;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-back {
            background: #0b1e6b;
            color: #fff;
            margin-top: 20px;
            float: right;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>CHI TIẾT THÔNG BÁO</h2>

        <p><label>Mã thông báo:</label> <?= htmlspecialchars($tb['maThongBao']) ?></p>
        <p><label>Tiêu đề:</label> <?= htmlspecialchars($tb['tieuDe']) ?></p>

        <p><label>Nội dung:</label></p>
        <div class="content-box"><?= nl2br(htmlspecialchars($tb['noiDung'])) ?></div>

        <p><label>Người gửi:</label> <?= htmlspecialchars($tb['nguoiGui']) ?></p>
        <p><label>Ngày gửi:</label> <?= htmlspecialchars($tb['ngayGui']) ?></p>
        <p><label>Người nhận:</label>
        <div class="radio-group">
            <label>
                <input type="radio" name="nguoiNhan" value="toan"
                    <?= ($nguoiNhan == 'toan') ? 'checked' : '' ?> disabled>
                Toàn hệ thống
            </label>
            <label>
                <input type="radio" name="nguoiNhan" value="giaovien"
                    <?= ($nguoiNhan == 'giaovien') ? 'checked' : '' ?> disabled>
                Giáo viên
            </label>
            <label>
                <input type="radio" name="nguoiNhan" value="hocsinh"
                    <?= ($nguoiNhan == 'hocsinh') ? 'checked' : '' ?> disabled>
                Học sinh
            </label>
        </div>
        </p>

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

        <button class="btn btn-back" onclick="window.location.href='qlthongbao.php'">Quay lại</button>
    </div>
</body>

</html>