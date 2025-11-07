<?php
include_once(__DIR__ . "/../csdl/db.php");
include_once(__DIR__ . "/func.php");
session_start();

header('Content-Type: application/json; charset=utf-8');

// ==== Kiểm tra quyền Admin ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    echo json_encode(['error' => 'Không có quyền truy cập']);
    exit();
}

// ==== Nhận dữ liệu JSON ====
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['action'])) {
    echo json_encode(['error' => 'Dữ liệu không hợp lệ']);
    exit();
}

$action = $data['action'];

switch ($action) {
    // ===== THÊM LỚP HỌC =====
    case 'add':
        $tenLop = $conn->real_escape_string($data['tenLop'] ?? '');
        $siSo = intval($data['siSo'] ?? 0);
        $maGV = isset($data['maGV']) && $data['maGV'] !== '' ? intval($data['maGV']) : 'NULL';
        $namHoc = $conn->real_escape_string($data['namHoc'] ?? '');
        $trangThai = $conn->real_escape_string($data['trangThai'] ?? '');

        $sql = "INSERT INTO lophoc (tenLop, siSo, maGV, namHoc, trangThai)
                VALUES ('$tenLop', $siSo, $maGV, '$namHoc', '$trangThai')";
        if ($conn->query($sql)) {
            $maLopMoi = $conn->insert_id;

            // === TỰ ĐỘNG GÁN CÁC MÔN HỌC MẶC ĐỊNH CHO LỚP MỚI ===
            // Lấy danh sách môn học mặc định
            $sqlMon = "SELECT maMonHoc FROM monhoc";
            $rsMon = $conn->query($sqlMon);
            if ($rsMon && $rsMon->num_rows > 0) {
                while ($mon = $rsMon->fetch_assoc()) {
                    $maMon = $mon['maMonHoc'];
                    $conn->query("INSERT INTO lophoc_monhoc (maLop, maMonHoc) VALUES ($maLopMoi, $maMon)");
                }
            }

            echo json_encode(['message' => 'Thêm lớp học thành công và đã gán các môn học mặc định']);
        } else {
            echo json_encode(['error' => 'Lỗi: ' . $conn->error]);
        }
        break;

    // ===== CẬP NHẬT LỚP HỌC =====
    case 'update':
        $maLop = intval($data['maLop'] ?? 0);
        $tenLop = $conn->real_escape_string($data['tenLop'] ?? '');
        $siSo = intval($data['siSo'] ?? 0);
        $maGV = isset($data['maGV']) && $data['maGV'] !== '' ? intval($data['maGV']) : 'NULL';
        $namHoc = $conn->real_escape_string($data['namHoc'] ?? '');
        $trangThai = $conn->real_escape_string($data['trangThai'] ?? '');

        $sql = "UPDATE lophoc SET
                    tenLop='$tenLop',
                    siSo=$siSo,
                    maGV=$maGV,
                    namHoc='$namHoc',
                    trangThai='$trangThai'
                WHERE maLop=$maLop";
        if ($conn->query($sql)) {
            echo json_encode(['message' => 'Cập nhật lớp học thành công']);
        } else {
            echo json_encode(['error' => 'Lỗi: ' . $conn->error]);
        }
        break;

    // ===== XÓA LỚP HỌC =====
    case 'delete':
        $maLop = intval($data['maLop'] ?? 0);
        $sql = "DELETE FROM lophoc WHERE maLop=$maLop";
        if ($conn->query($sql)) {
            echo json_encode(['message' => 'Xóa lớp học thành công']);
        } else {
            echo json_encode(['error' => 'Lỗi: ' . $conn->error]);
        }
        break;

    default:
        echo json_encode(['error' => 'Hành động không hợp lệ']);
        break;
}
