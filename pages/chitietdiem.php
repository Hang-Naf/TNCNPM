<?php
session_start();
include_once(__DIR__ . '/../csdl/db.php');
include_once(__DIR__ . '/../src/func.php');

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Admin hoặc Giáo viên ====
if (!in_array($_SESSION["vaiTro"], ["Admin", "GiaoVien"])) {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

// Lấy tham số GET
$maHS = $_GET['maHS'] ?? '';
$mon = $_GET['mon'] ?? '';

if (empty($maHS) || empty($mon)) {
    die("Thiếu thông tin học sinh hoặc môn học.");
}

// Lấy thông tin học sinh
$stmt = $conn->prepare("
    SELECT u.hoVaTen, h.lopHocPhuTrach
    FROM user u
    JOIN hocsinh h ON u.userID = h.maHS
    WHERE u.userID = ?
");
$stmt->bind_param("i", $maHS);
$stmt->execute();
$result = $stmt->get_result();
$hs = $result->fetch_assoc();
$stmt->close();

if (!$hs) {
    die("Học sinh không tồn tại.");
}

// Lấy danh sách điểm theo môn
$stmt = $conn->prepare("
    SELECT d.loaiDiem, d.diem, d.ngayNhap
    FROM diemso d
    JOIN monhoc m ON d.maMonHoc = m.maMonHoc
    WHERE d.maHS = ? AND m.tenMonHoc = ?
    ORDER BY d.loaiDiem, d.ngayNhap
");
$stmt->bind_param("is", $maHS, $mon);
$stmt->execute();
$result = $stmt->get_result();
$diemList = [];
while ($row = $result->fetch_assoc()) {
    $diemList[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết điểm - <?= htmlspecialchars($hs['hoVaTen']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h2 { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: left; }
        th { background: #003f91; color: #fff; }
        .back-btn { display: inline-block; margin-bottom: 15px; padding: 8px 15px; background: #003f91; color: #fff; border-radius: 5px; text-decoration: none; }
    </style>
</head>
<body>

<a href="qldiemso.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Quay lại bảng điểm</a>

<h2>Chi tiết điểm của <?= htmlspecialchars($hs['hoVaTen']) ?> - Lớp <?= htmlspecialchars($hs['lopHocPhuTrach']) ?> - Môn <?= htmlspecialchars($mon) ?></h2>

<?php if (!empty($diemList)): ?>
    <table>
        <thead>
            <tr>
                <th>Loại điểm</th>
                <th>Điểm</th>
                <th>Ngày nhập</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($diemList as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['loaiDiem']) ?></td>
                    <td><?= htmlspecialchars($d['diem']) ?></td>
                    <td><?= htmlspecialchars($d['ngayNhap']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Chưa có điểm cho môn này.</p>
<?php endif; ?>

</body>
</html>
