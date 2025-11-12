<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Giáo viên ====
if ($_SESSION["vaiTro"] !== "GiaoVien") {
    header("Location: ../dangnhap.php");
    exit();
}

$maGV = $_SESSION["userID"];

// ==== Kiểm tra mã tài liệu ====
if (!isset($_GET["maTL"])) {
    echo "<script>alert('Thiếu thông tin tài liệu!'); window.location='tlhoctap.php';</script>";
    exit();
}

$maTL = intval($_GET["maTL"]);

// ==== Kiểm tra quyền sở hữu tài liệu ====
$sqlCheck = "SELECT * FROM tailieu WHERE maTL = '$maTL' AND maGV = '$maGV'";
$result = $conn->query($sqlCheck);

if ($result->num_rows === 0) {
    echo "<script>alert('Bạn không có quyền xóa tài liệu này!'); window.location='tlhoctap.php';</script>";
    exit();
}

// ==== Lấy tên file đính kèm (nếu có) để xóa ====
$row = $result->fetch_assoc();
if (!empty($row["tepDinhKem"])) {
    $filePath = __DIR__ . "/../uploads/tailieu/" . $row["tepDinhKem"];
    if (file_exists($filePath)) {
        unlink($filePath); // Xóa file vật lý
    }
}

// ==== Thực hiện xóa tài liệu ====
$sqlDelete = "DELETE FROM tailieu WHERE maTL = '$maTL' AND maGV = '$maGV'";
if ($conn->query($sqlDelete)) {
    echo "<script>alert('Đã xóa tài liệu thành công!'); window.location='tlhoctap.php';</script>";
} else {
    echo "<script>alert('Lỗi khi xóa tài liệu: " . addslashes($conn->error) . "'); window.location='tlhoctap.php';</script>";
}

$conn->close();
?>
