<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "GiaoVien") {
    http_response_code(403);
    echo json_encode(["error" => "Không có quyền"]);
    exit();
}

$maGV = $_SESSION["userID"];
$maLop = intval($_GET["lop"] ?? 0);

if ($maLop <= 0) {
    echo json_encode([]);
    exit();
}

$sql = "SELECT m.maMonHoc, m.tenMonHoc
        FROM monhoc m
        JOIN lophoc_monhoc lm ON lm.maMonHoc = m.maMonHoc
        WHERE lm.maGV = ? AND lm.maLop = ?
        ORDER BY m.tenMonHoc ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $maGV, $maLop);
$stmt->execute();
$result = $stmt->get_result();

$ds = [];
while ($row = $result->fetch_assoc()) {
    $ds[] = $row;
}
echo json_encode($ds);
