<?php
session_start();
include_once(__DIR__ . "/csdl/db.php");

if (!isset($_SESSION["userID"])) exit("Chưa đăng nhập");

$userID = $_SESSION["userID"];
$maThongBao = $_POST["maThongBao"] ?? "";

if ($maThongBao === "") exit("Thiếu mã thông báo");

$sql = "UPDATE thongbaouser 
        SET trangThai = 'Đã đọc', thoiGianDoc = NOW()
        WHERE userID = ? AND maThongBao = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $userID, $maThongBao);
$stmt->execute();

echo "OK";
?>
