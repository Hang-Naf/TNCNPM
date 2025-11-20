<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép GiaoVien ====
if ($_SESSION["vaiTro"] !== "GiaoVien") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Lấy danh sách học sinh và môn ====
$dsHS = [];
$monChon = null;

if (isset($_GET['maHS']) && isset($_GET['mon'])) {
    // Export một hàng (một học sinh + một môn)
    $dsHS = [$_GET['maHS']];
    $monChon = $_GET['mon'];
} elseif (isset($_POST['selectedHS']) && $_POST['selectedHS'] !== '') {
    // Export nhiều học sinh (tất cả môn)
    $dsHS = explode(",", $_POST['selectedHS']);
}

// Nếu không có học sinh nào
if (empty($dsHS)) {
    die("Không có học sinh nào được chọn để export.");
}

// ==== Tạo file Excel ====
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Tiêu đề
$sheet->setCellValue('A1', 'Mã HS');
$sheet->setCellValue('B1', 'Họ và tên');
$sheet->setCellValue('C1', 'Lớp');
$sheet->setCellValue('D1', 'Môn học');
$sheet->setCellValue('E1', 'Loại điểm');
$sheet->setCellValue('F1', 'Điểm');

$rowNum = 2;

// ==== Lặp qua từng học sinh ====
foreach ($dsHS as $maHS) {
    $maHS = $conn->real_escape_string($maHS);

    // Lấy chi tiết từng điểm
    $sql = "
    SELECT 
        u.userID AS maHS,
        u.hoVaTen,
        h.lopHocPhuTrach,
        m.tenMonHoc,
        d.loaiDiem,
        d.diem
    FROM hocsinh h
    JOIN user u ON h.maHS = u.userID
    LEFT JOIN diemso d ON d.maHS = h.maHS
    LEFT JOIN monhoc m ON d.maMonHoc = m.maMonHoc
    WHERE u.userID = '$maHS'
    ";
    if ($monChon) {
        $sql .= " AND m.tenMonHoc = '" . $conn->real_escape_string($monChon) . "'";
    }
    $sql .= " ORDER BY m.tenMonHoc, d.loaiDiem";

    $result = $conn->query($sql);
    while ($r = $result->fetch_assoc()) {
        $sheet->setCellValue("A$rowNum", $r['maHS']);
        $sheet->setCellValue("B$rowNum", $r['hoVaTen']);
        $sheet->setCellValue("C$rowNum", $r['lopHocPhuTrach']);
        $sheet->setCellValue("D$rowNum", $r['tenMonHoc']);
        $sheet->setCellValue("E$rowNum", $r['loaiDiem']);
        $sheet->setCellValue("F$rowNum", $r['diem']);
        $rowNum++;
    }

    // ==== Thêm dòng tổng kết trung bình ====
    $sqlTB = "
    SELECT 
        ROUND(
            SUM(CASE 
                WHEN d.loaiDiem LIKE 'hk1_%' THEN 
                    CASE d.loaiDiem
                        WHEN 'hk1_mieng' THEN d.diem*1
                        WHEN 'hk1_1tiet' THEN d.diem*2
                        WHEN 'hk1_thiGK' THEN d.diem*2
                        WHEN 'hk1_thiCK' THEN d.diem*3
                    END
                ELSE 0 END) /
            NULLIF(SUM(CASE 
                WHEN d.loaiDiem='hk1_mieng' THEN 1
                WHEN d.loaiDiem='hk1_1tiet' THEN 2
                WHEN d.loaiDiem='hk1_thiGK' THEN 2
                WHEN d.loaiDiem='hk1_thiCK' THEN 3
                ELSE 0 END),0),1
        ) AS diemHK1,
        ROUND(
            SUM(CASE 
                WHEN d.loaiDiem LIKE 'hk2_%' THEN 
                    CASE d.loaiDiem
                        WHEN 'hk2_mieng' THEN d.diem*1
                        WHEN 'hk2_1tiet' THEN d.diem*2
                        WHEN 'hk2_thiGK' THEN d.diem*2
                        WHEN 'hk2_thiCK' THEN d.diem*3
                    END
                ELSE 0 END) /
            NULLIF(SUM(CASE 
                WHEN d.loaiDiem='hk2_mieng' THEN 1
                WHEN d.loaiDiem='hk2_1tiet' THEN 2
                WHEN d.loaiDiem='hk2_thiGK' THEN 2
                WHEN d.loaiDiem='hk2_thiCK' THEN 3
                ELSE 0 END),0),1
        ) AS diemHK2
    FROM diemso d
    JOIN monhoc m ON d.maMonHoc = m.maMonHoc
    WHERE d.maHS = '$maHS'
    ";
    if ($monChon) {
        $sqlTB .= " AND m.tenMonHoc = '" . $conn->real_escape_string($monChon) . "'";
    }

    $resTB = $conn->query($sqlTB);
    if ($resTB && $tbRow = $resTB->fetch_assoc()) {
        $diemHK1 = $tbRow['diemHK1'];
        $diemHK2 = $tbRow['diemHK2'];
        $tb = "-";
        $tong = 0;
        $dem = 0;
        foreach (['diemHK1', 'diemHK2'] as $c) {
            if (is_numeric($tbRow[$c])) {
                $tong += $tbRow[$c];
                $dem++;
            }
        }
        if ($dem) $tb = round($tong / $dem, 1);

        $sheet->setCellValue("E$rowNum", "Điểm TB HK1");
        $sheet->setCellValue("F$rowNum", $diemHK1);
        $rowNum++;
        $sheet->setCellValue("E$rowNum", "Điểm TB HK2");
        $sheet->setCellValue("F$rowNum", $diemHK2);
        $rowNum++;
        $sheet->setCellValue("E$rowNum", "Trung bình môn");
        $sheet->setCellValue("F$rowNum", $tb);
        $rowNum++;
    }
}

// ==== Xuất file Excel ====
$filename = "BangDiemChiTiet_" . date("Ymd_His") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>