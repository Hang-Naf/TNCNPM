<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    header("Location: ../dangnhap.php");
    exit;
}

$maThongBao = $_GET['maThongBao'] ?? '';
if ($maThongBao === '') die("Mã thông báo không hợp lệ!");

// Xóa bản ghi
$conn->query("DELETE FROM thongbaouser WHERE maThongBao = $maThongBao");
$conn->query("DELETE FROM thongbao WHERE maThongBao = $maThongBao");

// Quay lại danh sách
header("Location: qlthongbao.php");
exit;
