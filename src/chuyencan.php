<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION['userID']) || $_SESSION['vaiTro'] !== 'Admin') {
    http_response_code(403);
    exit('FORBIDDEN');
}

$maHS = intval($_POST['maHS']);
$maMonHoc = !empty($_POST['maMonHoc']) ? intval($_POST['maMonHoc']) : "NULL";
$ngayHoc = $_POST['ngayHoc'];
$trangThai = $_POST['trangThai'];

if (!$maHS || !$ngayHoc || !$trangThai) {
    http_response_code(400);
    exit('Thiếu dữ liệu');
}

// Kiểm tra bản ghi đã tồn tại chưa
$sql_check = "SELECT * FROM chuyencan WHERE maHS=$maHS AND ngayHoc='$ngayHoc' AND (maMonHoc=$maMonHoc OR (maMonHoc IS NULL AND $maMonHoc IS NULL))";
$check = $conn->query($sql_check);

if ($check && $check->num_rows > 0) {
    $conn->query("UPDATE chuyencan SET trangThai='$trangThai' WHERE maHS=$maHS AND ngayHoc='$ngayHoc' AND (maMonHoc=$maMonHoc OR (maMonHoc IS NULL AND $maMonHoc IS NULL))");
} else {
    $conn->query("INSERT INTO chuyencan(maHS, maMonHoc, ngayHoc, trangThai) VALUES($maHS, $maMonHoc, '$ngayHoc', '$trangThai')");
}

echo "OK";
