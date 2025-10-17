<?php
include_once(__DIR__ . '/../csdl/db.php');
header("Content-Type: application/json; charset=UTF-8");

// === Nhận dữ liệu JSON từ client ===
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(["error" => "Dữ liệu không hợp lệ!"]);
    exit;
}

$action = $data["action"];

try {
    // ===== THÊM MÔN HỌC =====
    if ($action === "add") {
        $tenMonHoc = trim($data["tenMonHoc"] ?? "");
        $moTa = trim($data["moTa"] ?? "");
        $hocKy = trim($data["hocKy"] ?? "HK1");
        $trongSo = floatval($data["trongSo"] ?? 1.0);
        $namHoc = trim($data["namHoc"] ?? "");
        $trangThai = trim($data["trangThai"] ?? "Hoạt động");
        $truongBoMon = intval($data["truongBoMon"] ?? 0);

        if ($tenMonHoc === "" || $namHoc === "") {
            echo json_encode(["error" => "Vui lòng nhập đầy đủ thông tin bắt buộc!"]);
            exit;
        }

        $stmt = $conn->prepare("
            INSERT INTO monhoc (tenMonHoc, moTa, hocKy, trongSo, trangThai, namHoc)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssiss", $tenMonHoc, $moTa, $hocKy, $trongSo, $trangThai, $namHoc);
        $stmt->execute();

        $maMonHoc = $conn->insert_id;

        // Nếu chọn trưởng bộ môn → lưu vào bảng giaovien_monhoc
        if ($truongBoMon > 0) {
            $stmt2 = $conn->prepare("
                INSERT INTO giaovien_monhoc (maGV, maMonHoc)
                VALUES (?, ?)
            ");
            $stmt2->bind_param("ii", $truongBoMon, $maMonHoc);
            $stmt2->execute();
        }

        echo json_encode(["message" => "Thêm môn học thành công!"]);
    }

    // ===== CẬP NHẬT MÔN HỌC =====
    elseif ($action === "update") {
        $maMonHoc = intval($data["maMonHoc"] ?? 0);
        $tenMonHoc = trim($data["tenMonHoc"] ?? "");
        $moTa = trim($data["moTa"] ?? "");
        $hocKy = trim($data["hocKy"] ?? "HK1");
        $trongSo = floatval($data["trongSo"] ?? 1.0);
        $namHoc = trim($data["namHoc"] ?? "");
        $trangThai = trim($data["trangThai"] ?? "Hoạt động");
        $truongBoMon = intval($data["truongBoMon"] ?? 0);

        if ($maMonHoc <= 0) {
            echo json_encode(["error" => "Thiếu mã môn học!"]);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE monhoc
            SET tenMonHoc = ?, moTa = ?, hocKy = ?, trongSo = ?, trangThai = ?, namHoc = ?
            WHERE maMonHoc = ?
        ");
        $stmt->bind_param("sssissi", $tenMonHoc, $moTa, $hocKy, $trongSo, $trangThai, $namHoc, $maMonHoc);
        $stmt->execute();

        // Cập nhật trưởng bộ môn (nếu có)
        if ($truongBoMon > 0) {
            // Kiểm tra xem đã có bản ghi trong giaovien_monhoc chưa
            $check = $conn->prepare("SELECT id FROM giaovien_monhoc WHERE maMonHoc = ?");
            $check->bind_param("i", $maMonHoc);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $stmt2 = $conn->prepare("UPDATE giaovien_monhoc SET maGV = ? WHERE maMonHoc = ?");
                $stmt2->bind_param("ii", $truongBoMon, $maMonHoc);
            } else {
                $stmt2 = $conn->prepare("INSERT INTO giaovien_monhoc (maGV, maMonHoc) VALUES (?, ?)");
                $stmt2->bind_param("ii", $truongBoMon, $maMonHoc);
            }
            $stmt2->execute();
        }

        echo json_encode(["message" => "Cập nhật môn học thành công!"]);
    }

    // ===== XÓA MÔN HỌC =====
    elseif ($action === "delete") {
        $maMonHoc = intval($data["maMonHoc"] ?? 0);
        if ($maMonHoc <= 0) {
            echo json_encode(["error" => "Thiếu mã môn học!"]);
            exit;
        }

        // Xóa liên kết với giáo viên (nếu có)
        $conn->query("DELETE FROM giaovien_monhoc WHERE maMonHoc = $maMonHoc");

        // Xóa môn học
        $conn->query("DELETE FROM monhoc WHERE maMonHoc = $maMonHoc");

        echo json_encode(["message" => "Xóa môn học thành công!"]);
    } else {
        echo json_encode(["error" => "Hành động không hợp lệ!"]);
    }
} catch (Exception $e) {
    echo json_encode(["error" => "Lỗi: " . $e->getMessage()]);
}

$conn->close();
