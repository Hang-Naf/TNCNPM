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
        $tenLop = trim($data['tenLop'] ?? '');
        $siSo = intval($data['siSo'] ?? 0);
        $maGV = isset($data['maGV']) && $data['maGV'] !== '' ? intval($data['maGV']) : 'NULL';
        // Tự động tạo năm học theo năm hiện tại
        $namHienTai = date("Y");
        $namSau = $namHienTai + 1;
        $namHoc = "$namHienTai-$namSau";
        $trangThai = trim($data['trangThai'] ?? '');

        // Kiểm tra dữ liệu trống
        if ($tenLop === '' || $trangThai === '') {
            echo json_encode(['error' => 'Vui lòng nhập đầy đủ thông tin lớp học (Tên lớp, Năm học, Trạng thái).']);
            exit();
        }

        // Kiểm tra độ dài tên lớp
        if (strlen($tenLop) > 50) {
            echo json_encode(['error' => 'Tên lớp không được vượt quá 50 ký tự.']);
            exit();
        }

        if ($siSo <= 0) {
            echo json_encode(['error' => 'Sĩ số phải lớn hơn 0.']);
            exit();
        }

        // Kiểm tra trùng tên lớp trong cùng năm học
        $sqlCheck = "SELECT maLop FROM lophoc WHERE tenLop='$tenLop' AND namHoc='$namHoc'";
        $rsCheck = $conn->query($sqlCheck);
        if ($rsCheck && $rsCheck->num_rows > 0) {
            echo json_encode(['error' => "Tên lớp '$tenLop' đã tồn tại trong năm học $namHoc!"]);
            exit();
        }

        // Thêm lớp nếu hợp lệ
        $sql = "INSERT INTO lophoc (tenLop, siSo, maGV, namHoc, trangThai)
                VALUES ('$tenLop', $siSo, $maGV, '$namHoc', '$trangThai')";
        if ($conn->query($sql)) {
            $maLopMoi = $conn->insert_id;

            // === GÁN CÁC MÔN HỌC MẶC ĐỊNH ===
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
        $tenLop = trim($data['tenLop'] ?? '');
        $siSo = intval($data['siSo'] ?? 0);
        $maGV = isset($data['maGV']) && $data['maGV'] !== '' ? intval($data['maGV']) : 'NULL';
        $namHoc = trim($data['namHoc'] ?? '');
        $trangThai = trim($data['trangThai'] ?? '');

        // Kiểm tra dữ liệu trống
        if ($tenLop === '' || $namHoc === '' || $trangThai === '') {
            echo json_encode(['error' => 'Vui lòng nhập đầy đủ thông tin lớp học (Tên lớp, Năm học, Trạng thái).']);
            exit();
        }

        // Kiểm tra độ dài tên lớp
        if (strlen($tenLop) > 50) {
            echo json_encode(['error' => 'Tên lớp không được vượt quá 50 ký tự.']);
            exit();
        }

        if ($siSo <= 0) {
            echo json_encode(['error' => 'Sĩ số phải lớn hơn 0.']);
            exit();
        }

        // Kiểm tra trùng tên lớp (trừ lớp đang sửa)
        $sqlCheck = "SELECT maLop FROM lophoc 
                     WHERE tenLop='$tenLop' AND namHoc='$namHoc' AND maLop <> $maLop";
        $rsCheck = $conn->query($sqlCheck);
        if ($rsCheck && $rsCheck->num_rows > 0) {
            echo json_encode(['error' => "Tên lớp '$tenLop' đã tồn tại trong năm học $namHoc!"]);
            exit();
        }

        // Cập nhật lớp
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
        if ($maLop <= 0) {
            echo json_encode(['error' => 'Thiếu mã lớp cần xóa.']);
            exit();
        }

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
