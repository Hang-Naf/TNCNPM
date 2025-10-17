<?php
header("Content-Type: application/json; charset=utf-8");
include_once(__DIR__ . "/../csdl/db.php");
include_once(__DIR__ . "/func.php");
session_start();

// ==== Kiểm tra quyền truy cập ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    echo json_encode(["error" => "Không có quyền truy cập"]);
    exit;
}

// ==== Nhận dữ liệu JSON từ fetch ====
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input["action"])) {
    echo json_encode(["error" => "Dữ liệu gửi không hợp lệ"]);
    exit;
}

$action = $input["action"];

// ==================== XỬ LÝ HÀNH ĐỘNG ====================

switch ($action) {
    case "add":
        $maLop = $input["maLop"] ?? "";
        $maMonHoc = $input["maMonHoc"] ?? "";
        $maGV = $input["maGV"] ?? "";

        if ($maLop === "" || $maMonHoc === "") {
            echo json_encode(["error" => "Vui lòng chọn lớp và môn học"]);
            exit;
        }

        // Kiểm tra trùng (một lớp - môn chỉ có 1 phân công)
        $check = $conn->prepare("SELECT id FROM lophoc_monhoc WHERE maLop=? AND maMonHoc=?");
        $check->bind_param("ss", $maLop, $maMonHoc);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(["error" => "Phân công này đã tồn tại!"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO lophoc_monhoc(maLop, maMonHoc, maGV) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $maLop, $maMonHoc, $maGV);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Thêm phân công thành công!"]);
        } else {
            echo json_encode(["error" => "Lỗi khi thêm: " . $conn->error]);
        }
        break;

    case "update":
        $id = $input["id"] ?? "";
        $maLop = $input["maLop"] ?? "";
        $maMonHoc = $input["maMonHoc"] ?? "";
        $maGV = $input["maGV"] ?? "";

        if ($id === "" || $maLop === "" || $maMonHoc === "") {
            echo json_encode(["error" => "Thiếu thông tin cần thiết"]);
            exit;
        }

        // Kiểm tra phân công trùng (ngoại trừ chính nó)
        $check = $conn->prepare("SELECT id FROM lophoc_monhoc WHERE maLop=? AND maMonHoc=? AND id<>?");
        $check->bind_param("ssi", $maLop, $maMonHoc, $id);
        $check->execute();
        $result = $check->get_result();
        if ($result->num_rows > 0) {
            echo json_encode(["error" => "Phân công này đã tồn tại!"]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE lophoc_monhoc SET maLop=?, maMonHoc=?, maGV=? WHERE id=?");
        $stmt->bind_param("sssi", $maLop, $maMonHoc, $maGV, $id);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Cập nhật phân công thành công!"]);
        } else {
            echo json_encode(["error" => "Lỗi khi cập nhật: " . $conn->error]);
        }
        break;

    case "delete":
        $id = $input["id"] ?? "";
        if ($id === "") {
            echo json_encode(["error" => "Thiếu ID cần xóa"]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM lophoc_monhoc WHERE id=?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(["message" => "Xóa phân công thành công!"]);
        } else {
            echo json_encode(["error" => "Lỗi khi xóa: " . $conn->error]);
        }
        break;

    default:
        echo json_encode(["error" => "Hành động không hợp lệ"]);
        break;
}
