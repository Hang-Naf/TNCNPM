<?php
header("Content-Type: application/json; charset=utf-8");
include_once(__DIR__ . "/../csdl/db.php");
include_once(__DIR__ . "/../src/func.php");
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    echo json_encode(["error" => true, "message" => "Vui lòng đăng nhập trước."]);
    exit;
}

// ==== Chỉ cho phép Admin ====
if ($_SESSION["vaiTro"] !== "Admin") {
    echo json_encode(["error" => true, "message" => "Bạn không có quyền thực hiện thao tác này."]);
    exit;
}

$action = $_POST["action"] ?? "";

switch ($action) {
    // ======= THÊM THÔNG BÁO =======
    case "add":
        $tieuDe = trim($_POST["tieuDe"] ?? "");
        $noiDung = trim($_POST["noiDung"] ?? "");
        $nguoiGui = $_SESSION["userID"];

        if ($tieuDe === "" || $noiDung === "") {
            echo json_encode(["error" => true, "message" => "Vui lòng nhập đầy đủ tiêu đề và nội dung."]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO thongbao (tieuDe, noiDung, nguoiGui) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $tieuDe, $noiDung, $nguoiGui);
        $ok = $stmt->execute();

        if ($ok) {
            $maThongBao = $stmt->insert_id;

            // Gửi thông báo này đến toàn bộ người dùng (trừ Admin gửi)
            $resUser = $conn->query("SELECT userID FROM user WHERE userID != {$nguoiGui}");
            if ($resUser && $resUser->num_rows > 0) {
                $insertStmt = $conn->prepare("INSERT INTO thongbaouser (userID, maThongBao, trangThai) VALUES (?, ?, 'Chưa đọc')");
                while ($u = $resUser->fetch_assoc()) {
                    $uid = $u['userID'];
                    $insertStmt->bind_param("ii", $uid, $maThongBao);
                    $insertStmt->execute();
                }
            }

            // Ghi log hệ thống
            // ghilog($conn, $nguoiGui, "Thêm thông báo mới: {$tieuDe}");

            echo json_encode(["error" => false, "message" => "Gửi thông báo thành công!"]);
        } else {
            echo json_encode(["error" => true, "message" => "Không thể thêm thông báo."]);
        }
        break;

    case "update":
        $maThongBao = intval($_POST["maThongBao"] ?? 0);
        $tieuDe = trim($_POST["tieuDe"] ?? "");
        $noiDung = trim($_POST["noiDung"] ?? "");

        if ($maThongBao <= 0 || $tieuDe === "" || $noiDung === "") {
            echo json_encode(["error" => true, "message" => "Dữ liệu không hợp lệ."]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE thongbao SET tieuDe = ?, noiDung = ? WHERE maThongBao = ?");
        $stmt->bind_param("ssi", $tieuDe, $noiDung, $maThongBao);
        $ok = $stmt->execute();

        if ($ok) {
            // ghilog($conn, $_SESSION["userID"], "Cập nhật thông báo #$maThongBao");
            echo json_encode(["error" => false, "message" => "Cập nhật thông báo thành công!"]);
        } else {
            echo json_encode(["error" => true, "message" => "Không thể cập nhật thông báo."]);
        }
        break;

    // ======= XOÁ THÔNG BÁO =======
    case "delete":
        $maThongBao = intval($_POST["maThongBao"] ?? 0);
        if ($maThongBao <= 0) {
            echo json_encode(["error" => true, "message" => "Mã thông báo không hợp lệ."]);
            exit;
        }

        // Xóa khỏi bảng thongbaouser trước (do ràng buộc khóa ngoại)
        $conn->query("DELETE FROM thongbaouser WHERE maThongBao = $maThongBao");
        $stmt = $conn->prepare("DELETE FROM thongbao WHERE maThongBao = ?");
        $stmt->bind_param("i", $maThongBao);
        $ok = $stmt->execute();

        if ($ok) {
            // ghilog($conn, $_SESSION["userID"], "Xóa thông báo #$maThongBao");
            echo json_encode(["error" => false, "message" => "Đã xóa thông báo thành công."]);
        } else {
            echo json_encode(["error" => true, "message" => "Không thể xóa thông báo."]);
        }
        break;

    default:
        echo json_encode(["error" => true, "message" => "Hành động không hợp lệ."]);
}
?>
