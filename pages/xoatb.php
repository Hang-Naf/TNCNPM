<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    http_response_code(403);
    echo json_encode(["error" => true, "message" => "Không có quyền thực hiện thao tác này!"]);
    exit();
}

// ==== Trường hợp xóa nhiều (POST JSON) ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $ids = $data['selected'] ?? [];

    if (!is_array($ids) || empty($ids)) {
        echo json_encode(["error" => true, "message" => "Không có thông báo nào được chọn."]);
        exit();
    }

    $idList = implode(",", array_map('intval', $ids));

    // Xóa liên quan từ bảng thongbaouser trước
    $conn->query("DELETE FROM thongbaouser WHERE maThongBao IN ($idList)");
    $conn->query("DELETE FROM thongbao WHERE maThongBao IN ($idList)");

    echo json_encode(["error" => false, "message" => "Đã xóa ".count($ids)." thông báo."]);
    exit();
}

// ==== Trường hợp xóa 1 thông báo qua GET ====
$maThongBao = $_GET['maThongBao'] ?? '';
if ($maThongBao === '') {
    echo "<script>alert('Mã thông báo không hợp lệ!'); window.location.href='qlthongbao.php';</script>";
    exit();
}

$maThongBao = intval($maThongBao);
$conn->query("DELETE FROM thongbaouser WHERE maThongBao = $maThongBao");
$conn->query("DELETE FROM thongbao WHERE maThongBao = $maThongBao");

echo "<script>alert('Đã xóa thông báo.'); window.location.href='qlthongbao.php';</script>";
exit();
