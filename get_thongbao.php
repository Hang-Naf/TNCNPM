<?php
session_start();
header("Content-Type: application/json; charset=utf-8");
include_once(__DIR__ . "/csdl/db.php");

if (!isset($_SESSION["userID"])) {
    echo json_encode(["error" => "Chưa đăng nhập"]);
    exit();
}

$userID = $_SESSION["userID"];

// Lấy tất cả thông báo kèm trạng thái
$sql = "SELECT 
            t.maThongBao,
            t.tieuDe,
            t.noiDung,
            DATE_FORMAT(t.ngayGui, '%d/%m/%Y %H:%i') AS ngayGui,
            tu.trangThai
        FROM thongbaouser tu
        JOIN thongbao t ON tu.maThongBao = t.maThongBao
        WHERE tu.userID = ?
        ORDER BY t.ngayGui DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications, JSON_UNESCAPED_UNICODE);
?>
