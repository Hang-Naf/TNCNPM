<?php
// ini_set('display_errors', 0); // tắt báo lỗi HTML
// ini_set('display_startup_errors', 0);
// error_reporting(0);
header('Content-Type: application/json');
include_once(__DIR__ . "/../csdl/db.php");
include_once(__DIR__ . "/func.php");
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    echo json_encode(['error' => 'Bạn không có quyền truy cập']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'add') {
        $hoVaTen = $conn->real_escape_string($data['hoVaTen']);
        $email = $conn->real_escape_string($data['email']);
        $sdt = $conn->real_escape_string($data['sdt']);
        $gioiTinh = $conn->real_escape_string($data['gioiTinh']);
        $lopHoc = $conn->real_escape_string($data['lopHocPhuTrach']);
        $namHoc = $conn->real_escape_string($data['namHoc']);
        $hocKy = $conn->real_escape_string($data['hocKy']);
        $trangThai = $conn->real_escape_string($data['trangThai']);
        $matKhau = password_hash('123456', PASSWORD_DEFAULT);

        // Thêm user
        if ($conn->query("INSERT INTO user (hoVaTen,email,sdt,gioiTinh,vaiTro,matKhau)
                          VALUES ('$hoVaTen','$email','$sdt','$gioiTinh','HocSinh','$matKhau')")) {
            $userId = $conn->insert_id;

            // Chờ trigger tạo record hocsinh, sau đó UPDATE
            $conn->query("UPDATE hocsinh SET lopHocPhuTrach='$lopHoc', namHoc='$namHoc', hocKy='$hocKy', trangThai='$trangThai' WHERE maHS=$userId");

            echo json_encode(['message' => 'Thêm học sinh thành công']);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
    } elseif ($action === 'update') {
        $userId = (int)$data['userId'];
        $hoVaTen = $conn->real_escape_string($data['hoVaTen']);
        $email = $conn->real_escape_string($data['email']);
        $sdt = $conn->real_escape_string($data['sdt']);
        $gioiTinh = $conn->real_escape_string($data['gioiTinh']);
        $lopHoc = $conn->real_escape_string($data['lopHocPhuTrach']);
        $namHoc = $conn->real_escape_string($data['namHoc']);
        $hocKy = $conn->real_escape_string($data['hocKy']);
        $trangThai = $conn->real_escape_string($data['trangThai']);

        if (
            $conn->query("UPDATE user SET hoVaTen='$hoVaTen', email='$email', sdt='$sdt', gioiTinh='$gioiTinh' WHERE userID=$userId")
            && $conn->query("UPDATE hocsinh SET lopHocPhuTrach='$lopHoc', namHoc='$namHoc', hocKy='$hocKy', trangThai='$trangThai' WHERE maHS=$userId")
        ) {
            echo json_encode(['message' => 'Cập nhật học sinh thành công']);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
    } elseif ($action === 'delete') {
        $userId = (int)$data['userId'];
        if ($conn->query("DELETE FROM user WHERE userID=$userId")) {
            echo json_encode(['message' => 'Xóa học sinh thành công']);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
    } else {
        echo json_encode(['error' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
