<?php
include_once(__DIR__ . '/../csdl/db.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data["action"])) {
    echo json_encode(["error" => "Dữ liệu không hợp lệ!"]);
    exit();
}

$action = $data["action"];
date_default_timezone_set("Asia/Ho_Chi_Minh");

// ======= THÊM GIÁO VIÊN =======
if ($action === "add") {
    $hoVaTen = trim($data["hoVaTen"] ?? '');
    $email = trim($data["email"] ?? '');
    $sdt = trim($data["sdt"] ?? '');
    $gioiTinh = $data["gioiTinh"] ?? 'Nam';
    $boMon = $data["boMon"] ?? 'Chưa xác định';
    $trinhDo = $data["trinhDo"] ?? 'Chưa cập nhật';
    $phongBan = $data["phongBan"] ?? '';
    $namHoc = $data["namHoc"] ?? 'Chưa cập nhật';
    $hocKy = $data["hocKy"] ?? 'Chưa cập nhật';
    $trangThai = $data["trangThai"] ?? 'active';
    $matKhau = password_hash("123456", PASSWORD_DEFAULT);

    // Kiểm tra email trùng
    $check = $conn->prepare("SELECT userID FROM user WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(["error" => "Email đã tồn tại!"]);
        exit();
    }

    //  Thêm user mới (trigger sẽ tự ghi log và tạo thông báo)
    $stmt = $conn->prepare("
        INSERT INTO user (hoVaTen, matKhau, sdt, gioiTinh, email, vaiTro) 
        VALUES (?, ?, ?, ?, ?, 'GiaoVien')
    ");
    $stmt->bind_param("sssss", $hoVaTen, $matKhau, $sdt, $gioiTinh, $email);

    if (!$stmt->execute()) {
        echo json_encode(["error" => "Không thể thêm vào bảng user: " . $conn->error]);
        exit();
    }

    $newUserId = $conn->insert_id;

    // Chờ 1 chút để trigger xử lý (phòng trễ)
    usleep(200000);

    // Nếu trigger chưa tạo dòng trong giaovien thì tự chèn
    $checkGV = $conn->prepare("SELECT maGV FROM giaovien WHERE maGV=?");
    $checkGV->bind_param("i", $newUserId);
    $checkGV->execute();
    $rs = $checkGV->get_result();

    if ($rs->num_rows === 0) {
        $stmt2 = $conn->prepare("
            INSERT INTO giaovien (maGV, boMon, trinhDo, anhDaiDien, phongBan, namHoc, hocKy, trangThai)
            VALUES (?, ?, ?, '', ?, ?, ?, ?)
        ");
        $stmt2->bind_param("issssss", $newUserId, $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai);
        $stmt2->execute();
    } else {
        $stmt2 = $conn->prepare("
            UPDATE giaovien 
            SET boMon=?, trinhDo=?, phongBan=?, namHoc=?, hocKy=?, trangThai=? 
            WHERE maGV=?
        ");
        $stmt2->bind_param("ssssssi", $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai, $newUserId);
        $stmt2->execute();
    }

    echo json_encode(["message" => " Thêm giáo viên thành công!"]);
    exit();
}

// ======= CẬP NHẬT GIÁO VIÊN =======
if ($action === "update") {
    $userId = (int) ($data["userId"] ?? 0);
    $hoVaTen = $data["hoVaTen"] ?? '';
    $email = $data["email"] ?? '';
    $sdt = $data["sdt"] ?? '';
    $gioiTinh = $data["gioiTinh"] ?? 'Nam';
    $boMon = $data["boMon"] ?? 'Chưa xác định';
    $trinhDo = $data["trinhDo"] ?? 'Chưa cập nhật';
    $phongBan = $data["phongBan"] ?? '';
    $namHoc = $data["namHoc"] ?? 'Chưa cập nhật';
    $hocKy = $data["hocKy"] ?? 'Chưa cập nhật';
    $trangThai = $data["trangThai"] ?? 'active';

    // ✅ Kiểm tra trùng email (bỏ qua chính user đang cập nhật)
    $check = $conn->prepare("SELECT userID FROM user WHERE email=? AND userID != ?");
    $check->bind_param("si", $email, $userId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode(["error" => "Email đã tồn tại!"]);
        exit();
    }

    // ✅ Cập nhật bảng user (trigger tự ghi log và tạo thông báo)
    $stmt = $conn->prepare("
    UPDATE user 
    SET hoVaTen=?, email=?, sdt=?, gioiTinh=? 
    WHERE userID=? AND vaiTro='GiaoVien'
");
    $stmt->bind_param("ssssi", $hoVaTen, $email, $sdt, $gioiTinh, $userId);

    if (!$stmt->execute()) {
        echo json_encode(["error" => "Lỗi cập nhật user: " . $conn->error]);
        exit();
    }


    // Cập nhật thông tin phụ
    $stmt2 = $conn->prepare("
        UPDATE giaovien 
        SET boMon=?, trinhDo=?, phongBan=?, namHoc=?, hocKy=?, trangThai=? 
        WHERE maGV=?
    ");
    $stmt2->bind_param("ssssssi", $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai, $userId);
    $stmt2->execute();

    echo json_encode(["message" => " Cập nhật giáo viên thành công!"]);
    exit();
}

// ======= XÓA GIÁO VIÊN =======
if ($action === "delete") {
    $userId = (int) ($data["userId"] ?? 0);

    //  Xóa từ bảng user (trigger tự xử lý log & thông báo)
    $stmt = $conn->prepare("DELETE FROM user WHERE userID=? AND vaiTro='GiaoVien'");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(["message" => " Xóa giáo viên thành công!"]);
    } else {
        echo json_encode(["error" => "Không thể xóa giáo viên: " . $conn->error]);
    }
    exit();
}

echo json_encode(["error" => "Hành động không hợp lệ!"]);
