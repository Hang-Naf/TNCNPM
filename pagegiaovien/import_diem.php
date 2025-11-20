<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__ . '/../vendor/autoload.php';

// ==== Kiểm tra quyền Admin ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "GiaoVien") {
    die("Không có quyền thực hiện thao tác này!");
}

if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $fileTmp = $_FILES['file']['tmp_name'];
    $fileName = $_FILES['file']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if ($ext === 'xlsx' || $ext === 'xls') {
        try {
            $spreadsheet = IOFactory::load($fileTmp);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Bỏ qua dòng tiêu đề
            array_shift($rows);

            $countInsert = 0;
            $countUpdate = 0;
            $countInvalid = 0;

            foreach ($rows as $row) {
                $maHS     = trim((string)$row[0]);
                $maMonHoc = trim((string)$row[1]);
                $loaiDiem = trim((string)$row[2]);
                $diem     = trim((string)$row[3]);

                if ($maHS !== '' && $maMonHoc !== '' && $loaiDiem !== '' && $diem !== '') {
                    $maHS     = $conn->real_escape_string($maHS);
                    $maMonHoc = $conn->real_escape_string($maMonHoc);
                    $loaiDiem = $conn->real_escape_string($loaiDiem);

                    // ==== Kiểm tra điểm hợp lệ ====
                    if (!is_numeric($diem)) {
                        $countInvalid++;
                        continue;
                    }
                    $diem = floatval($diem);
                    if ($diem < 0 || $diem > 10) {
                        $countInvalid++;
                        continue;
                    }

                    // Nếu bảng diemso có UNIQUE KEY (maHS, maMonHoc, loaiDiem)
                    $sql = "INSERT INTO diemso (maHS, maMonHoc, loaiDiem, diem)
                            VALUES ('$maHS','$maMonHoc','$loaiDiem','$diem')
                            ON DUPLICATE KEY UPDATE diem = VALUES(diem)";

                    if ($conn->query($sql)) {
                        if ($conn->affected_rows == 1) {
                            $countInsert++;
                        } elseif ($conn->affected_rows == 2) {
                            $countUpdate++;
                        }
                    } else {
                        echo "❌ Lỗi SQL: " . $conn->error . " | Dữ liệu: $maHS, $maMonHoc, $loaiDiem, $diem<br>";
                    }
                }
            }

            echo "<script>
                alert('Import Excel hoàn tất! Thêm mới $countInsert bản ghi, cập nhật $countUpdate bản ghi. Bỏ qua $countInvalid điểm không hợp lệ.');
                window.location.href='diemso.php';
            </script>";
        } catch (Exception $e) {
            echo "<script>alert('Lỗi đọc file Excel: " . $e->getMessage() . "'); window.location.href='diemso.php';</script>";
        }
    } else {
        echo "<script>alert('Chỉ hỗ trợ file Excel (.xlsx, .xls)!'); window.location.href='diemso.php';</script>";
    }
} else {
    header("Location: diemso.php");
    exit();
}
?>