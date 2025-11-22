<?php
header('Content-Type: application/json');
include_once(__DIR__ . "/../csdl/db.php");
include_once(__DIR__ . "/func.php");
session_start();

// ==== Kiểm tra quyền Admin ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    echo json_encode(['error' => 'Bạn không có quyền truy cập']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'add') {
        $hoVaTen   = $conn->real_escape_string($data['hoVaTen']);

        if (strlen($hoVaTen) > 255) {
            echo json_encode(["error" => "Họ và tên không được vượt quá 255 ký tự!"]);
            exit();
        }

        $email     = $conn->real_escape_string($data['email']);
        $sdt       = $conn->real_escape_string($data['sdt']);
        $gioiTinh  = $conn->real_escape_string($data['gioiTinh']);
        $lopHoc    = $conn->real_escape_string($data['lopHocPhuTrach']);
        $chucVu    = $conn->real_escape_string($data['chucVu']);
        $namHoc    = $conn->real_escape_string($data['namHoc']);
        // Validate namHoc format YYYY-YYYY and ensure end year > start year
        if (!preg_match('/^\s*(\d{4})\s*-\s*(\d{4})\s*$/', $namHoc, $m)) {
            echo json_encode(['error' => 'Định dạng Năm học phải là YYYY-YYYY (ví dụ: 2022-2023)']);
            exit();
        }
        if ((int)$m[2] <= (int)$m[1]) {
            echo json_encode(['error' => 'Năm kết thúc phải lớn hơn năm bắt đầu']);
            exit();
        }
        $hocKy     = $conn->real_escape_string($data['hocKy']);
        $trangThai = $conn->real_escape_string($data['trangThai']);
        $matKhau   = password_hash('12345678', PASSWORD_DEFAULT);

        // === 1. Thêm vào bảng user ===
        $sqlUser = "INSERT INTO user (hoVaTen, email, sdt, gioiTinh, vaiTro, matKhau)
                    VALUES ('$hoVaTen', '$email', '$sdt', '$gioiTinh', 'HocSinh', '$matKhau')";
        if ($conn->query($sqlUser)) {
            $userId = $conn->insert_id;

            // === 2. Thêm hoặc cập nhật record trong bảng hocsinh ===
            $sqlHS = "INSERT INTO hocsinh (maHS, lopHocPhuTrach, chucVu, namHoc, hocKy, trangThai)
                      VALUES ($userId, '$lopHoc', '$chucVu', '$namHoc', '$hocKy', '$trangThai')
                      ON DUPLICATE KEY UPDATE
                        lopHocPhuTrach='$lopHoc',
                        chucVu='$chucVu',
                        namHoc='$namHoc',
                        hocKy='$hocKy',
                        trangThai='$trangThai'";
            $conn->query($sqlHS);

            echo json_encode(['message' => 'Thêm học sinh thành công']);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
    } elseif ($action === 'update') {
        $userId    = (int)$data['userId'];
        $hoVaTen   = $conn->real_escape_string($data['hoVaTen']);

        if (strlen($hoVaTen) > 255) {
            echo json_encode(["error" => "Họ và tên không được vượt quá 255 ký tự!"]);
            exit();
        }

        $email     = $conn->real_escape_string($data['email']);
        $sdt       = $conn->real_escape_string($data['sdt']);
        $gioiTinh  = $conn->real_escape_string($data['gioiTinh']);
        $lopHoc    = $conn->real_escape_string($data['lopHocPhuTrach']);
        $chucVu    = $conn->real_escape_string($data['chucVu']);
        $namHoc    = $conn->real_escape_string($data['namHoc']);
        // Validate namHoc format YYYY-YYYY and ensure end year > start year
        if (!preg_match('/^\s*(\d{4})\s*-\s*(\d{4})\s*$/', $namHoc, $m)) {
            echo json_encode(['error' => 'Định dạng Năm học phải là YYYY-YYYY (ví dụ: 2022-2023)']);
            exit();
        }
        if ((int)$m[2] <= (int)$m[1]) {
            echo json_encode(['error' => 'Năm kết thúc phải lớn hơn năm bắt đầu']);
            exit();
        }
        $hocKy     = $conn->real_escape_string($data['hocKy']);
        $trangThai = $conn->real_escape_string($data['trangThai']);

        $ok1 = $conn->query("UPDATE user 
                             SET hoVaTen='$hoVaTen', email='$email', sdt='$sdt', gioiTinh='$gioiTinh' 
                             WHERE userID=$userId");
        $ok2 = $conn->query("UPDATE hocsinh 
                             SET lopHocPhuTrach='$lopHoc', chucVu='$chucVu', namHoc='$namHoc', hocKy='$hocKy', trangThai='$trangThai' 
                             WHERE maHS=$userId");

        if ($ok1 && $ok2) {
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
    } elseif ($action === 'deleteMany') {
        $ids = $data['userIds'] ?? [];
        if (empty($ids) || !is_array($ids)) {
            echo json_encode(['error' => 'Không có học sinh nào được chọn']);
            exit();
        }

        // Chuyển mảng ID thành chuỗi số nguyên an toàn
        $idList = implode(",", array_map("intval", $ids));

        $conn->begin_transaction();
        try {
            // Xóa trong bảng hocsinh
            $conn->query("DELETE FROM hocsinh WHERE maHS IN ($idList)");
            // Xóa trong bảng user
            $conn->query("DELETE FROM user WHERE userID IN ($idList) AND vaiTro='HocSinh'");
            $conn->commit();
            echo json_encode(['message' => 'Đã xóa ' . count($ids) . ' học sinh thành công!']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => 'Lỗi khi xóa: ' . $e->getMessage()]);
        }
        exit();
    } else {
        echo json_encode(['error' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
