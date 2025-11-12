<?php
include_once(__DIR__ . '/../csdl/db.php');
header('Content-Type: application/json; charset=UTF-8');

// ==== Lấy mã lớp từ GET ====
$maLop = $_GET['lop'] ?? '';

if (empty($maLop)) {
    echo json_encode([]);
    exit();
}

// ==== Truy vấn danh sách học sinh thuộc lớp ====
$sql = "
    SELECT u.userID, u.hoVaTen
    FROM hocsinh_lophoc hl
    JOIN user u ON hl.maHS = u.userID
    WHERE hl.maLop = ?
    ORDER BY u.hoVaTen ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "Lỗi prepare: " . $conn->error]);
    exit();
}

$stmt->bind_param("i", $maLop);
$stmt->execute();
$result = $stmt->get_result();

$hocsinh = [];
while ($row = $result->fetch_assoc()) {
    $hocsinh[] = [
        'userID' => $row['userID'],
        'hoVaTen' => $row['hoVaTen']
    ];
}

echo json_encode($hocsinh, JSON_UNESCAPED_UNICODE);
$stmt->close();
$conn->close();
?>
