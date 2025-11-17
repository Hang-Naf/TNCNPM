<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

$maHS = $_GET['maHS'] ?? '';
$mon = $_GET['mon'] ?? ''; // Có thể là tên môn hoặc mã môn

if ($maHS === '' || $mon === '') {
    die("Thiếu thông tin để xóa.");
}

$maHS = intval($maHS);

// Xác định xem $mon là mã hay tên môn
if (is_numeric($mon)) {
    // $mon là mã môn học
    $maMon = intval($mon);
} else {
    // $mon là tên môn, cần lấy mã
    $sqlMon = "SELECT maMonHoc FROM monhoc WHERE tenMonHoc = ? LIMIT 1";
    $stmtMon = $conn->prepare($sqlMon);
    $stmtMon->bind_param("s", $mon);
    $stmtMon->execute();
    $resultMon = $stmtMon->get_result()->fetch_assoc();
    
    if (!$resultMon) {
        die("Không tìm thấy môn học: $mon");
    }
    $maMon = $resultMon['maMonHoc'];
    $stmtMon->close();
}

// === XÓA ĐIỂM ===
$sql = "DELETE FROM diemso WHERE maHS = ? AND maMonHoc = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Lỗi SQL: " . $conn->error);
}

$stmt->bind_param("ii", $maHS, $maMon);

if ($stmt->execute()) {
    echo "<script>alert('Đã xóa toàn bộ điểm của học sinh!');window.location.href='diemso.php';</script>";
} else {
    echo "<script>alert('Lỗi khi xóa điểm: " . $stmt->error . "');window.history.back();</script>";
}

$stmt->close();
?>
