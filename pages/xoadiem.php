<?php
include_once(__DIR__ . '/../csdl/db.php');

$maHS = $_GET['maHS'] ?? '';
$tenMon = $_GET['mon'] ?? '';

if ($maHS === '' || $tenMon === '') {
    die("Thiếu thông tin để xóa.");
}

$sql = "DELETE FROM diemso 
        WHERE maHS = ? 
        AND maMonHoc = (SELECT maMonHoc FROM monhoc WHERE tenMonHoc = ? LIMIT 1)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $maHS, $tenMon);

if ($stmt->execute()) {
    echo "<script>alert('Đã xóa toàn bộ điểm của học sinh trong môn $tenMon!');window.location.href='qldiemso.php';</script>";
} else {
    echo "<script>alert('Lỗi khi xóa điểm!');window.history.back();</script>";
}
