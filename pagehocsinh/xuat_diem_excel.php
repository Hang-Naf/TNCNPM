<?php
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "HocSinh") {
    header("Location: ../dangnhap.php");
    exit();
}

$userID = $_SESSION["userID"];

// ==== Xác định môn cần xuất ====
$mons = [];
if (isset($_GET['mon'])) {
    $mons[] = $_GET['mon'];
} elseif (isset($_POST['selectedMon'])) {
    $mons = explode(",", $_POST['selectedMon']);
}

if (empty($mons)) {
    die("Không có môn nào được chọn để export.");
}

// ==== Tạo file Excel ====
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Chi tiết điểm");

// ==== Header ====
$sheet->setCellValue('A1', 'STT');
$sheet->setCellValue('B1', 'Môn học');
$sheet->setCellValue('C1', 'HK1 - Miệng');
$sheet->setCellValue('D1', 'HK1 - 1 Tiết');
$sheet->setCellValue('E1', 'HK1 - Thi GK');
$sheet->setCellValue('F1', 'HK1 - Thi CK');
$sheet->setCellValue('G1', 'TB HK1');
$sheet->setCellValue('H1', 'HK2 - Miệng');
$sheet->setCellValue('I1', 'HK2 - 1 Tiết');
$sheet->setCellValue('J1', 'HK2 - Thi GK');
$sheet->setCellValue('K1', 'HK2 - Thi CK');
$sheet->setCellValue('L1', 'TB HK2');
$sheet->setCellValue('M1', 'Trung bình môn');

// ==== Lấy dữ liệu điểm từ DB ====
$sql = "SELECT m.tenMonHoc, d.loaiDiem, d.diem
        FROM diemso d
        LEFT JOIN monhoc m ON d.maMonHoc = m.maMonHoc
        WHERE d.maHS = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$bangDiem = [];
while ($r = $result->fetch_assoc()) {
    $mon = $r['tenMonHoc'];
    if (!in_array($mon, $mons)) continue;

    $loai = strtolower($r['loaiDiem']);
    $diem = is_numeric($r['diem']) ? (float)$r['diem'] : null;

    if (!isset($bangDiem[$mon])) {
        $bangDiem[$mon] = [
            'hk1_mieng' => null,
            'hk1_1tiet' => null,
            'hk1_thiGK' => null,
            'hk1_thiCK' => null,
            'hk2_mieng' => null,
            'hk2_1tiet' => null,
            'hk2_thiGK' => null,
            'hk2_thiCK' => null,
            'tbHK1' => null,
            'tbHK2' => null,
            'tb' => null
        ];
    }

    // Gán điểm
    if (strpos($loai, 'hk1_mieng') !== false) $bangDiem[$mon]['hk1_mieng'] = $diem;
    elseif (strpos($loai, 'hk1_1tiet') !== false) $bangDiem[$mon]['hk1_1tiet'] = $diem;
    elseif (strpos($loai, 'hk1_thigk') !== false) $bangDiem[$mon]['hk1_thiGK'] = $diem;
    elseif (strpos($loai, 'hk1_thick') !== false) $bangDiem[$mon]['hk1_thiCK'] = $diem;
    elseif (strpos($loai, 'hk2_mieng') !== false) $bangDiem[$mon]['hk2_mieng'] = $diem;
    elseif (strpos($loai, 'hk2_1tiet') !== false) $bangDiem[$mon]['hk2_1tiet'] = $diem;
    elseif (strpos($loai, 'hk2_thigk') !== false) $bangDiem[$mon]['hk2_thiGK'] = $diem;
    elseif (strpos($loai, 'hk2_thick') !== false) $bangDiem[$mon]['hk2_thiCK'] = $diem;
}

// ==== Tính trung bình ====
foreach ($bangDiem as $mon => &$d) {
    // HK1
    $sum = 0;
    $count = 0;
    if (is_numeric($d['hk1_mieng'])) {
        $sum += $d['hk1_mieng'] * 1;
        $count += 1;
    }
    if (is_numeric($d['hk1_1tiet'])) {
        $sum += $d['hk1_1tiet'] * 2;
        $count += 2;
    }
    if (is_numeric($d['hk1_thiGK'])) {
        $sum += $d['hk1_thiGK'] * 2;
        $count += 2;
    }
    if (is_numeric($d['hk1_thiCK'])) {
        $sum += $d['hk1_thiCK'] * 3;
        $count += 3;
    }
    $d['tbHK1'] = $count > 0 ? round($sum / $count, 1) : null;

    // HK2
    $sum = 0;
    $count = 0;
    if (is_numeric($d['hk2_mieng'])) {
        $sum += $d['hk2_mieng'] * 1;
        $count += 1;
    }
    if (is_numeric($d['hk2_1tiet'])) {
        $sum += $d['hk2_1tiet'] * 2;
        $count += 2;
    }
    if (is_numeric($d['hk2_thiGK'])) {
        $sum += $d['hk2_thiGK'] * 2;
        $count += 2;
    }
    if (is_numeric($d['hk2_thiCK'])) {
        $sum += $d['hk2_thiCK'] * 3;
        $count += 3;
    }
    $d['tbHK2'] = $count > 0 ? round($sum / $count, 1) : null;

    // Trung bình môn
    if (is_numeric($d['tbHK1']) && is_numeric($d['tbHK2'])) {
        $d['tb'] = round(($d['tbHK1'] + $d['tbHK2']) / 2, 1);
    } elseif (is_numeric($d['tbHK1'])) {
        $d['tb'] = $d['tbHK1'];
    } elseif (is_numeric($d['tbHK2'])) {
        $d['tb'] = $d['tbHK2'];
    }
}

// ==== Ghi dữ liệu vào Excel ====
$row = 2;
$stt = 1;
foreach ($bangDiem as $mon => $d) {
    $sheet->setCellValue("A$row", $stt++);
    $sheet->setCellValue("B$row", $mon);
    $sheet->setCellValue("C$row", $d['hk1_mieng'] ?? '-');
    $sheet->setCellValue("D$row", $d['hk1_1tiet'] ?? '-');
    $sheet->setCellValue("E$row", $d['hk1_thiGK'] ?? '-');
    $sheet->setCellValue("F$row", $d['hk1_thiCK'] ?? '-');
    $sheet->setCellValue("G$row", $d['tbHK1'] ?? '-');
    $sheet->setCellValue("H$row", $d['hk2_mieng'] ?? '-');
    $sheet->setCellValue("I$row", $d['hk2_1tiet'] ?? '-');
    $sheet->setCellValue("J$row", $d['hk2_thiGK'] ?? '-');
    $sheet->setCellValue("K$row", $d['hk2_thiCK'] ?? '-');
    $sheet->setCellValue("L$row", $d['tbHK2'] ?? '-');
    $sheet->setCellValue("M$row", $d['tb'] ?? '-');
    $row++;
}

// ==== Xuất file Excel ====
$filename = "ChiTietDiem_" . date("Ymd_His") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment;filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>