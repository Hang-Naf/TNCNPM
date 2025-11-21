<?php
require __DIR__ . "/../vendor/autoload.php";
include_once(__DIR__ . "/../csdl/db.php");
session_start();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// Kiểm tra đăng nhập 
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

if ($_SESSION["vaiTro"] !== "HocSinh") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$userID = $_SESSION["userID"];

function styleHeader($sheet, $cells)
{
    $sheet->getStyle($cells)->applyFromArray([
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);
}

// XUẤT 1 MÔN — HIỂN THỊ ĐẦY ĐỦ TẤT CẢ LOẠI ĐIỂM
if (isset($_GET["mon"])) {

    $tenMon = $_GET["mon"];

    $sql = "SELECT d.loaiDiem, d.diem, m.tenMonHoc
            FROM diemso d
            JOIN monhoc m ON d.maMonHoc = m.maMonHoc
            WHERE d.maHS = ? AND m.tenMonHoc = ?";
    $stm = $conn->prepare($sql);
    $stm->bind_param("is", $userID, $tenMon);
    $stm->execute();
    $rs = $stm->get_result();

    // Mặc định rỗng
    $hk1 = ["mieng" => "", "1tiet" => "", "gk" => "", "ck" => ""];
    $hk2 = ["mieng" => "", "1tiet" => "", "gk" => "", "ck" => ""];

    while ($d = $rs->fetch_assoc()) {
        $loai = strtolower($d["loaiDiem"]);
        $diem = $d["diem"];

        if (strpos($loai, "hk1_mieng") !== false) $hk1["mieng"] = $diem;
        if (strpos($loai, "hk1_1tiet") !== false) $hk1["1tiet"] = $diem;
        if (strpos($loai, "hk1_thigk") !== false) $hk1["gk"] = $diem;
        if (strpos($loai, "hk1_thick") !== false) $hk1["ck"] = $diem;

        if (strpos($loai, "hk2_mieng") !== false) $hk2["mieng"] = $diem;
        if (strpos($loai, "hk2_1tiet") !== false) $hk2["1tiet"] = $diem;
        if (strpos($loai, "hk2_thigk") !== false) $hk2["gk"] = $diem;
        if (strpos($loai, "hk2_thick") !== false) $hk2["ck"] = $diem;
    }

    // Tính TB chung (không coi điểm trống = 0)
    function tinhTB($arr) {
        $sum = 0; $w = 0;

        if ($arr["mieng"] !== "") { $sum += $arr["mieng"] * 1; $w += 1; }
        if ($arr["1tiet"] !== "") { $sum += $arr["1tiet"] * 2; $w += 2; }
        if ($arr["gk"] !== "")    { $sum += $arr["gk"] * 2;    $w += 2; }
        if ($arr["ck"] !== "")    { $sum += $arr["ck"] * 3;    $w += 3; }

        return $w > 0 ? round($sum / $w, 1) : "";
    }

    $tbHK1 = tinhTB($hk1);
    $tbHK2 = tinhTB($hk2);

    if ($tbHK1 !== "" && $tbHK2 !== "")
        $tbMon = round(($tbHK1 + $tbHK2) / 2, 1);
    else
        $tbMon = ($tbHK1 !== "" ? $tbHK1 : $tbHK2);

    // Tạo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Tiêu đề lớn
    $sheet->setCellValue("A1", "BẢNG ĐIỂM MÔN: $tenMon");
    $sheet->mergeCells("A1:M1");
    $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Header 1 dòng
    $header = [
        "Môn học",
        "HK1 Miệng", "HK1 1 tiết", "HK1 GK", "HK1 CK", "TB HK1",
        "HK2 Miệng", "HK2 1 tiết", "HK2 GK", "HK2 CK", "TB HK2",
        "TB Môn"
    ];
    $sheet->fromArray([$header], NULL, "A3");
    styleHeader($sheet, "A3:M3");

    // Dữ liệu 1 dòng
    $data = [
        $tenMon,
        $hk1["mieng"], $hk1["1tiet"], $hk1["gk"], $hk1["ck"], $tbHK1,
        $hk2["mieng"], $hk2["1tiet"], $hk2["gk"], $hk2["ck"], $tbHK2,
        $tbMon
    ];

    $sheet->fromArray([$data], NULL, "A4");

    // Borders
    $sheet->getStyle("A4:M4")->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ]);

    // Xuất file
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"diem_$tenMon.xlsx\"");

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit();
}


// XUẤT NHIỀU MÔN — CHI TIẾT TẤT CẢ ĐIỂM CỦA HAI HỌC KỲ
if (isset($_POST["selectedHS"])) {

    $selected = explode(",", $_POST["selectedHS"]);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle("Bang diem chi tiet");

    // Tiêu đề
    $sheet->setCellValue("A1", "BẢNG ĐIỂM CHI TIẾT TẤT CẢ MÔN");
    $sheet->mergeCells("A1:L1");
    $sheet->getStyle("A1")->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle("A1")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Header bảng
    $sheet->fromArray([
        [
            "Môn học",
            "HK1 Miệng",
            "HK1 1 tiết",
            "HK1 GK",
            "HK1 CK",
            "TB HK1",
            "HK2 Miệng",
            "HK2 1 tiết",
            "HK2 GK",
            "HK2 CK",
            "TB HK2",
            "TB Môn"
        ]
    ], NULL, "A3");

    styleHeader($sheet, "A3:L3");

    $row = 4;

    foreach ($selected as $maMon) {

        $sql = "SELECT d.loaiDiem, d.diem, m.tenMonHoc
                FROM diemso d
                JOIN monhoc m ON d.maMonHoc = m.maMonHoc
                WHERE d.maHS = ? AND d.maMonHoc = ?";
        $stm = $conn->prepare($sql);
        $stm->bind_param("ii", $userID, $maMon);
        $stm->execute();
        $rs = $stm->get_result();

        $tenMon = "";
        $hk1 = ["mieng" => null, "1tiet" => null, "gk" => null, "ck" => null];
        $hk2 = ["mieng" => null, "1tiet" => null, "gk" => null, "ck" => null];

        while ($d = $rs->fetch_assoc()) {
            $tenMon = $d["tenMonHoc"];
            $loai = strtolower($d["loaiDiem"]);
            $diem = $d["diem"];

            if (strpos($loai, "hk1_mieng") !== false) $hk1["mieng"] = $diem;
            if (strpos($loai, "hk1_1tiet") !== false) $hk1["1tiet"] = $diem;
            if (strpos($loai, "hk1_thigk") !== false) $hk1["gk"] = $diem;
            if (strpos($loai, "hk1_thick") !== false) $hk1["ck"] = $diem;

            if (strpos($loai, "hk2_mieng") !== false) $hk2["mieng"] = $diem;
            if (strpos($loai, "hk2_1tiet") !== false) $hk2["1tiet"] = $diem;
            if (strpos($loai, "hk2_thigk") !== false) $hk2["gk"] = $diem;
            if (strpos($loai, "hk2_thick") !== false) $hk2["ck"] = $diem;
        }

        // Tính TB
        // TÍNH TB HK1
        $sum = 0;
        $weight = 0;

        if ($hk1["mieng"] !== null) {
            $sum += $hk1["mieng"] * 1;
            $weight += 1;
        }
        if ($hk1["1tiet"] !== null) {
            $sum += $hk1["1tiet"] * 2;
            $weight += 2;
        }
        if ($hk1["gk"] !== null) {
            $sum += $hk1["gk"] * 2;
            $weight += 2;
        }
        if ($hk1["ck"] !== null) {
            $sum += $hk1["ck"] * 3;
            $weight += 3;
        }

        $tbHK1 = $weight > 0 ? round($sum / $weight, 1) : null;

        // TÍNH TB HK2
        $sum = 0;
        $weight = 0;

        if ($hk2["mieng"] !== null) {
            $sum += $hk2["mieng"] * 1;
            $weight += 1;
        }
        if ($hk2["1tiet"] !== null) {
            $sum += $hk2["1tiet"] * 2;
            $weight += 2;
        }
        if ($hk2["gk"] !== null) {
            $sum += $hk2["gk"] * 2;
            $weight += 2;
        }
        if ($hk2["ck"] !== null) {
            $sum += $hk2["ck"] * 3;
            $weight += 3;
        }

        $tbHK2 = $weight > 0 ? round($sum / $weight, 1) : null;

        if ($tbHK1 !== null && $tbHK2 !== null)
            $tbMon = round(($tbHK1 + $tbHK2) / 2, 1);
        else
            $tbMon = ($tbHK1 !== null ? $tbHK1 : $tbHK2);


        // Ghi từng dòng
        $sheet->fromArray([
            [
                $tenMon,
                $hk1["mieng"],
                $hk1["1tiet"],
                $hk1["gk"],
                $hk1["ck"],
                $tbHK1,
                $hk2["mieng"],
                $hk2["1tiet"],
                $hk2["gk"],
                $hk2["ck"],
                $tbHK2,
                $tbMon
            ]
        ], NULL, "A$row");

        // Style border
        $sheet->getStyle("A$row:L$row")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        $row++;
    }

    // Xuất file
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=\"bang_diem_chi_tiet.xlsx\"");

    $writer = new Xlsx($spreadsheet);
    $writer->save("php://output");
    exit();
}

echo "Không có dữ liệu.";
?>