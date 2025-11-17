<?php
include_once(__DIR__ . "/../csdl/db.php");
include_once(__DIR__ . "/../src/func.php");
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Admin ====
if ($_SESSION["vaiTro"] !== "Admin") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$action = $_POST["action"] ?? "";
$nguoiGui = (int)$_SESSION["userID"];

// === Hàm kiểm tra cột tồn tại ===
function columnExists(mysqli $conn, string $table, string $column): bool
{
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($res && $res->num_rows > 0);
}

// === Hàm trả JSON tiện dụng ===
function jsonResponse($arr)
{
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($arr);
}

switch ($action) {

    // ======= THÊM THÔNG BÁO =======
    case "add":
        $tieuDe = trim($_POST["tieuDe"] ?? "");
        $noiDung = trim($_POST["noiDung"] ?? "");
        $thoiGianGui = $_POST["thoiGianGui"] ?? date("Y-m-d");
        $nguoiNhan = $_POST["nguoiNhan"] ?? "toan";

        // === Xử lý upload file ===
        $tepDinhKem = null;
        if (isset($_FILES['tepDinhKem']) && $_FILES['tepDinhKem']['error'] == 0) {
            $uploadDir = __DIR__ . '/../uploads/thongbao/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = time() . '_' . basename($_FILES['tepDinhKem']['name']);
            $targetPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['tepDinhKem']['tmp_name'], $targetPath)) {
                $tepDinhKem = $fileName;
            }
        }

        if ($tieuDe === "" || $noiDung === "") {
            jsonResponse(["error" => true, "message" => "Vui lòng nhập đầy đủ tiêu đề và nội dung."]);
        }

        // Kiểm tra độ dài tiêu đề (tối đa 255 ký tự)
        if (mb_strlen($tieuDe, 'UTF-8') > 255) {
            jsonResponse(["error" => true, "message" => "Tiêu đề không được vượt quá 255 ký tự."]);
        }

        $hasDoiTuong = columnExists($conn, 'thongbao', 'doiTuongNhan');
        if ($hasDoiTuong) {
            $stmt = $conn->prepare("INSERT INTO thongbao (tieuDe, noiDung, tepDinhKem, nguoiGui, ngayGui, doiTuongNhan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiss", $tieuDe, $noiDung, $tepDinhKem, $nguoiGui, $thoiGianGui, $nguoiNhan);
        } else {
            $stmt = $conn->prepare("INSERT INTO thongbao (tieuDe, noiDung, tepDinhKem, nguoiGui, ngayGui) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssis", $tieuDe, $noiDung, $tepDinhKem, $nguoiGui, $thoiGianGui);
        }

        if (!$stmt || !$stmt->execute()) {
            jsonResponse(["error" => true, "message" => "Lỗi thêm thông báo: " . $conn->error]);
        }

        $maThongBao = $stmt->insert_id;
        $stmt->close();

        // Gửi cho người nhận phù hợp
        switch ($nguoiNhan) {
            case "giaovien":
                $resUser = $conn->query("SELECT userID FROM user WHERE LOWER(vaiTro) LIKE '%giaovien%'");
                break;
            case "hocsinh":
                $resUser = $conn->query("SELECT userID FROM user WHERE LOWER(vaiTro) LIKE '%hocsinh%'");
                break;
            default:
                $resUser = $conn->query("SELECT userID FROM user WHERE userID != {$nguoiGui}");
        }

        if ($resUser && $resUser->num_rows > 0) {
            $ins = $conn->prepare("INSERT INTO thongbaouser (userID, maThongBao, trangThai) VALUES (?, ?, 'Chưa đọc')");
            while ($u = $resUser->fetch_assoc()) {
                $uid = (int)$u['userID'];
                $ins->bind_param("ii", $uid, $maThongBao);
                $ins->execute();
            }
            $ins->close();
        }

        jsonResponse(["error" => false, "message" => "Gửi thông báo thành công!", "maThongBao" => $maThongBao]);
        break;

    // ======= CẬP NHẬT =======
    case "update":
        $maThongBao = intval($_POST["maThongBao"] ?? 0);
        $tieuDe = trim($_POST["tieuDe"] ?? "");
        $noiDung = trim($_POST["noiDung"] ?? "");
        $thoiGianGui = $_POST["thoiGianGui"] ?? date("Y-m-d");
        $nguoiNhan = $_POST["nguoiNhan"] ?? "toan";

        if ($maThongBao <= 0 || $tieuDe === "" || $noiDung === "") {
            jsonResponse(["error" => true, "message" => "Dữ liệu không hợp lệ."]);
        }

        // Kiểm tra độ dài tiêu đề (tối đa 255 ký tự)
        if (mb_strlen($tieuDe, 'UTF-8') > 255) {
            jsonResponse(["error" => true, "message" => "Tiêu đề không được vượt quá 255 ký tự."]);
        }

        // === Xử lý upload file (nếu có) ===
        $tepDinhKem = null;
        if (isset($_FILES['tepDinhKem']) && $_FILES['tepDinhKem']['error'] == 0) {
            $uploadDir = __DIR__ . '/../uploads/thongbao/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES['tepDinhKem']['name']);
            $targetPath = $uploadDir . $fileName;
            // Xóa file cũ nếu có file mới
            if ($tepDinhKem && isset($_POST['oldFile']) && $_POST['oldFile'] !== '') {
                $oldPath = __DIR__ . '/../uploads/thongbao/' . basename($_POST['oldFile']);
                if (file_exists($oldPath)) unlink($oldPath);
            }
            if (move_uploaded_file($_FILES['tepDinhKem']['tmp_name'], $targetPath)) {
                $tepDinhKem = $fileName;
            }
        }

        $hasDoiTuong = columnExists($conn, 'thongbao', 'doiTuongNhan');
        if ($tepDinhKem) {
            if ($hasDoiTuong) {
                $stmt = $conn->prepare("UPDATE thongbao SET tieuDe=?, noiDung=?, ngayGui=?, doiTuongNhan=?, tepDinhKem=? WHERE maThongBao=?");
                $stmt->bind_param("sssssi", $tieuDe, $noiDung, $thoiGianGui, $nguoiNhan, $tepDinhKem, $maThongBao);
            } else {
                $stmt = $conn->prepare("UPDATE thongbao SET tieuDe=?, noiDung=?, ngayGui=?, tepDinhKem=? WHERE maThongBao=?");
                $stmt->bind_param("ssssi", $tieuDe, $noiDung, $thoiGianGui, $tepDinhKem, $maThongBao);
            }
        } else {
            if ($hasDoiTuong) {
                $stmt = $conn->prepare("UPDATE thongbao SET tieuDe=?, noiDung=?, ngayGui=?, doiTuongNhan=? WHERE maThongBao=?");
                $stmt->bind_param("ssssi", $tieuDe, $noiDung, $thoiGianGui, $nguoiNhan, $maThongBao);
            } else {
                $stmt = $conn->prepare("UPDATE thongbao SET tieuDe=?, noiDung=?, ngayGui=? WHERE maThongBao=?");
                $stmt->bind_param("sssi", $tieuDe, $noiDung, $thoiGianGui, $maThongBao);
            }
        }

        if ($stmt && $stmt->execute()) {
            // === Cập nhật lại người nhận ===
            $conn->query("DELETE FROM thongbaouser WHERE maThongBao = $maThongBao");

            switch ($nguoiNhan) {
                case "giaovien":
                    $resUser = $conn->query("SELECT userID FROM user WHERE LOWER(vaiTro) LIKE '%giaovien%'");
                    break;
                case "hocsinh":
                    $resUser = $conn->query("SELECT userID FROM user WHERE LOWER(vaiTro) LIKE '%hocsinh%'");
                    break;
                default:
                    $resUser = $conn->query("SELECT userID FROM user WHERE userID != {$nguoiGui}");
            }

            if ($resUser && $resUser->num_rows > 0) {
                $insertStmt = $conn->prepare("INSERT INTO thongbaouser (userID, maThongBao, trangThai) VALUES (?, ?, 'Chưa đọc')");
                while ($u = $resUser->fetch_assoc()) {
                    $uid = (int)$u['userID'];
                    $insertStmt->bind_param("ii", $uid, $maThongBao);
                    $insertStmt->execute();
                }
                $insertStmt->close();
            }

            header("Location: ../pages/qlthongbao.php?msg=success");
            exit;
        } else {
            header("Location: ../pages/qlthongbao.php?msg=error");
            exit;
        }
        break;

    // ======= XOÁ =======
    case "delete":
        $maThongBao = intval($_POST["maThongBao"] ?? 0);
        if ($maThongBao <= 0) {
            jsonResponse(["error" => true, "message" => "Mã thông báo không hợp lệ."]);
        }

        $conn->query("DELETE FROM thongbaouser WHERE maThongBao = $maThongBao");
        $stmt = $conn->prepare("DELETE FROM thongbao WHERE maThongBao=?");
        $stmt->bind_param("i", $maThongBao);
        $ok = $stmt->execute();

        if ($ok) {
            jsonResponse(["error" => false, "message" => "Đã xóa thông báo thành công."]);
        } else {
            jsonResponse(["error" => true, "message" => "Không thể xóa thông báo: " . $stmt->error]);
        }
        break;

    default:
        jsonResponse(["error" => true, "message" => "Hành động không hợp lệ."]);
}
