<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// Kiểm tra quyền
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "GiaoVien") {
    header("Location: ../dangnhap.php");
    exit();
}

if (isset($_GET["maHS"])) {
    $maHS = intval($_GET["maHS"]);

    try {
        // Xóa theo quan hệ khóa ngoại (ON DELETE CASCADE sẽ lo phần liên quan)
        $sql = "DELETE FROM user WHERE userID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $maHS);
        if ($stmt->execute()) {
            header("Location: hocsinh.php?deleted=1");
            exit();
        } else {
            echo "❌ Lỗi khi xóa học sinh: " . $stmt->error;
        }
    } catch (Exception $e) {
        echo "❌ Lỗi: " . $e->getMessage();
    }
} else {
    header("Location: hocsinh.php");
    exit();
}
?>
