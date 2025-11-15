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


// ======================== THÊM GIÁO VIÊN ========================
if ($action === "add") {
    $hoVaTen   = trim($data["hoVaTen"] ?? '');

    if (strlen($hoVaTen) > 255) {
        echo json_encode(["error" => "Họ và tên không được vượt quá 255 ký tự!"]);
        exit();
    }

    $email     = trim($data["email"] ?? '');
    $sdt       = trim($data["sdt"] ?? '');
    $gioiTinh  = $data["gioiTinh"] ?? 'Nam';
    $boMon     = $data["boMon"] ?? 'Chưa xác định';
    $trinhDo   = $data["trinhDo"] ?? 'Chưa cập nhật';
    $phongBan  = $data["phongBan"] ?? '';
    $namHoc    = $data["namHoc"] ?? 'Chưa cập nhật';
    $hocKy     = $data["hocKy"] ?? 'Chưa cập nhật';
    $trangThai = $data["trangThai"] ?? 'active';
    $matKhau   = password_hash("12345678", PASSWORD_DEFAULT);

    // ---- Kiểm tra email trùng ----
    $check = $conn->prepare("SELECT userID FROM user WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(["error" => "Email đã tồn tại!"]);
        exit();
    }

    // ---- Thêm vào bảng user ----
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

    usleep(200000); // tránh trễ trigger

    // ---- Kiểm tra trigger đã tạo giáo viên chưa ----
    $checkGV = $conn->prepare("SELECT maGV FROM giaovien WHERE maGV=?");
    $checkGV->bind_param("i", $newUserId);
    $checkGV->execute();
    $rs = $checkGV->get_result();

    if ($rs->num_rows === 0) {
        // Nếu trigger không tạo, tự chèn
        $stmt2 = $conn->prepare("
            INSERT INTO giaovien (maGV, boMon, trinhDo, phongBan, namHoc, hocKy, trangThai)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt2->bind_param("issssss", $newUserId, $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai);
        $stmt2->execute();
    } else {
        // Nếu trigger tạo rồi → cập nhật lại
        $stmt2 = $conn->prepare("
            UPDATE giaovien 
            SET boMon=?, trinhDo=?, phongBan=?, namHoc=?, hocKy=?, trangThai=? 
            WHERE maGV=?
        ");
        $stmt2->bind_param("ssssssi", $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai, $newUserId);
        $stmt2->execute();
    }

    // ---- Cập nhật bảng giaovien_monhoc ----
    $stmt3 = $conn->prepare("
        INSERT INTO giaovien_monhoc (maGV, maMonHoc)
        SELECT ?, maMonHoc 
        FROM monhoc 
        WHERE tenMonHoc = ?
    ");
    $stmt3->bind_param("is", $newUserId, $boMon);
    $stmt3->execute();

    echo json_encode(["message" => "Thêm giáo viên thành công!"]);
    exit();
}



// ======================== CẬP NHẬT GIÁO VIÊN ========================
if ($action === "update") {
    $userId    = (int)($data["userId"] ?? 0);
    $hoVaTen   = $data["hoVaTen"] ?? '';
    $email     = $data["email"] ?? '';
    $sdt       = $data["sdt"] ?? '';
    $gioiTinh  = $data["gioiTinh"] ?? 'Nam';
    $boMon     = $data["boMon"] ?? 'Chưa xác định';
    $trinhDo   = $data["trinhDo"] ?? 'Chưa cập nhật';
    $phongBan  = $data["phongBan"] ?? '';
    $namHoc    = $data["namHoc"] ?? 'Chưa cập nhật';
    $hocKy     = $data["hocKy"] ?? 'Chưa cập nhật';
    $trangThai = $data["trangThai"] ?? 'active';

    // ---- Cập nhật user ----
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

    // ---- Cập nhật bảng giaovien ----
    $stmt2 = $conn->prepare("
        UPDATE giaovien 
        SET boMon=?, trinhDo=?, phongBan=?, namHoc=?, hocKy=?, trangThai=? 
        WHERE maGV=?
    ");
    $stmt2->bind_param("ssssssi", $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai, $userId);
    $stmt2->execute();

    // ---- Xóa phân công môn cũ ----
    $conn->query("DELETE FROM giaovien_monhoc WHERE maGV = $userId");

    // ---- Thêm phân công môn mới ----
    $stmt3 = $conn->prepare("
        INSERT INTO giaovien_monhoc (maGV, maMonHoc)
        SELECT ?, maMonHoc 
        FROM monhoc 
        WHERE tenMonHoc = ?
    ");
    $stmt3->bind_param("is", $userId, $boMon);
    $stmt3->execute();

    echo json_encode(["message" => "Cập nhật giáo viên thành công!"]);
    exit();
}



// ======================== XÓA GIÁO VIÊN ========================
if ($action === "delete") {
    $userId = (int)($data["userId"] ?? 0);

    $stmt = $conn->prepare("DELETE FROM user WHERE userID=? AND vaiTro='GiaoVien'");
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        echo json_encode(["message" => "Xóa giáo viên thành công!"]);
    } else {
        echo json_encode(["error" => "Không thể xóa giáo viên: " . $conn->error]);
    }
    exit();
}

echo json_encode(["error" => "Hành động không hợp lệ!"]);
