<?php
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$dsHS = [];
$monChon = null;

// ==== 1 học sinh + 1 môn (icon export) ====
if (isset($_GET['maHS']) && isset($_GET['mon'])) {
    $dsHS = [$_GET['maHS']];
    $monChon = $_GET['mon'];
}
// ==== nhiều học sinh ====
elseif (isset($_POST['selectedHS']) && $_POST['selectedHS'] !== '') {
    $dsHS = explode(",", $_POST['selectedHS']);
}

if (empty($dsHS)) die("Không có học sinh nào để xuất Excel.");

// ========================================
// Hàm tính trung bình
// ========================================
function TB($a) {
    $sum = 0; 
    $w = 0;

    if ($a["mieng"] !== "") { $sum += $a["mieng"] * 1; $w+=1; }
    if ($a["1tiet"] !== "") { $sum += $a["1tiet"] * 2; $w+=2; }
    if ($a["gk"] !== "")    { $sum += $a["gk"] * 2;     $w+=2; }
    if ($a["ck"] !== "")    { $sum += $a["ck"] * 3;     $w+=3; }

    return $w ? round($sum / $w, 1) : "";
}

// ========================================
// LẤY DỮ LIỆU CHO TẤT CẢ HỌC SINH
// ========================================
$hsList = implode(",", array_map('intval', $dsHS));

$sql = "
SELECT d.maHS, h.lopHocPhuTrach, u.hoVaTen, m.tenMonHoc, d.loaiDiem, d.diem
FROM diemso d
JOIN hocsinh h ON h.maHS = d.maHS
JOIN user u ON u.userID = h.maHS
JOIN monhoc m ON m.maMonHoc = d.maMonHoc
WHERE d.maHS IN ($hsList)
";

if ($monChon) {
    $sql .= " AND m.tenMonHoc = '" . $conn->real_escape_string($monChon) . "'";
}

$sql .= " ORDER BY d.maHS, m.tenMonHoc";

$result = $conn->query($sql);

// Gom dữ liệu vào mảng
$data = [];
while ($row = $result->fetch_assoc()) {
    $maHS = $row['maHS'];
    $tenMon = $row['tenMonHoc'];
    $loai = strtolower($row['loaiDiem']);
    $diem = $row['diem'];

    if (!isset($data[$maHS])) {
        $data[$maHS] = [
            'tenHS' => $row['hoVaTen'],
            'lop' => $row['lopHocPhuTrach'],
            'mon' => []
        ];
    }

    if (!isset($data[$maHS]['mon'][$tenMon])) {
        $data[$maHS]['mon'][$tenMon] = [
            'hk1' => ['mieng'=>"",'1tiet'=>"",'gk'=>"",'ck'=>""],
            'hk2' => ['mieng'=>"",'1tiet'=>"",'gk'=>"",'ck'=>""]
        ];
    }

    // Gán điểm vào đúng học kỳ và loại
    if (str_contains($loai, "hk1_mieng")) $data[$maHS]['mon'][$tenMon]['hk1']['mieng'] = $diem;
    if (str_contains($loai, "hk1_1tiet")) $data[$maHS]['mon'][$tenMon]['hk1']['1tiet'] = $diem;
    if (str_contains($loai, "hk1_thigk")) $data[$maHS]['mon'][$tenMon]['hk1']['gk'] = $diem;
    if (str_contains($loai, "hk1_thick")) $data[$maHS]['mon'][$tenMon]['hk1']['ck'] = $diem;

    if (str_contains($loai, "hk2_mieng")) $data[$maHS]['mon'][$tenMon]['hk2']['mieng'] = $diem;
    if (str_contains($loai, "hk2_1tiet")) $data[$maHS]['mon'][$tenMon]['hk2']['1tiet'] = $diem;
    if (str_contains($loai, "hk2_thigk")) $data[$maHS]['mon'][$tenMon]['hk2']['gk'] = $diem;
    if (str_contains($loai, "hk2_thick")) $data[$maHS]['mon'][$tenMon]['hk2']['ck'] = $diem;
}

// ========================================
// TẠO FILE EXCEL
// ========================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Bang Diem");

// Header
$headers = [
    "A1" => "Mã HS", "B1" => "Họ và tên", "C1" => "Lớp", "D1" => "Môn học",
    "E1" => "HK1 - Miệng", "F1" => "HK1 - 1 tiết", "G1" => "HK1 - GK", "H1" => "HK1 - CK", "I1" => "TB HK1",
    "J1" => "HK2 - Miệng", "K1" => "HK2 - 1 tiết", "L1" => "HK2 - GK", "M1" => "HK2 - CK", "N1" => "TB HK2", "O1" => "TB Môn",
];
foreach ($headers as $cell=>$text) $sheet->setCellValue($cell,$text);

// Style header
$sheet->getStyle("A1:O1")->applyFromArray([
    "font"=>["bold"=>true,"color"=>["rgb"=>"FFFFFF"]],
    "alignment"=>["horizontal"=>Alignment::HORIZONTAL_CENTER],
    "fill"=>["fillType"=>Fill::FILL_SOLID,"startColor"=>["rgb"=>"4F81BD"]]
]);

$sheet->getColumnDimension("A")->setWidth(10);
$sheet->getColumnDimension("B")->setWidth(22);
$sheet->getColumnDimension("C")->setWidth(12);
$sheet->getColumnDimension("D")->setWidth(18);
for ($col="E"; $col<="O"; $col++) $sheet->getColumnDimension($col)->setWidth(12);

$rowNum = 2;

// ========================================
// GHI DỮ LIỆU VÀO EXCEL
// ========================================
foreach ($data as $maHS=>$info) {
    foreach ($info['mon'] as $tenMon=>$diemMon) {
        $tb1 = TB($diemMon['hk1']);
        $tb2 = TB($diemMon['hk2']);
        $tbm = ($tb1!=="" && $tb2!=="") ? round(($tb1+$tb2)/2,1) : ($tb1!==""?$tb1:$tb2);

        $sheet->fromArray([
            $maHS, $info['tenHS'], $info['lop'], $tenMon,
            $diemMon['hk1']['mieng'], $diemMon['hk1']['1tiet'], $diemMon['hk1']['gk'], $diemMon['hk1']['ck'], $tb1,
            $diemMon['hk2']['mieng'], $diemMon['hk2']['1tiet'], $diemMon['hk2']['gk'], $diemMon['hk2']['ck'], $tb2, $tbm
        ], NULL, "A$rowNum");

        $sheet->getStyle("A$rowNum:O$rowNum")->applyFromArray([
            "borders"=>["allBorders"=>["borderStyle"=>Border::BORDER_THIN]],
            "alignment"=>["horizontal"=>Alignment::HORIZONTAL_CENTER]
        ]);

        $sheet->getStyle("B$rowNum")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("D$rowNum")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $rowNum++;
    }
}

// ========================================
// XUẤT FILE
// ========================================
$filename = "BangDiem_" . date("Ymd_His") . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit();
?>
