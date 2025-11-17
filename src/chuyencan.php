<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION['userID']) || $_SESSION['vaiTro'] !== 'Admin') {
    http_response_code(403);
    exit('FORBIDDEN');
}

$maHS = intval($_POST['maHS']);
$maMonHoc = isset($_POST['maMonHoc']) && $_POST['maMonHoc'] !== ""
    ? intval($_POST['maMonHoc'])
    : null;
$ngayHoc = $_POST['ngayHoc'];
$trangThai = $_POST['trangThai'];

if (!$maHS || !$ngayHoc || !$trangThai) {
    http_response_code(400);
    exit('Thiếu dữ liệu');
}

# ==== CHẶN NGÀY TƯƠNG LAI ====
if ($ngayHoc > date('Y-m-d')) {
    http_response_code(400);
    exit("Không được điểm danh ngày tương lai");
}

# ==== CHẶN CHỦ NHẬT ====
if (date('w', strtotime($ngayHoc)) == 0) {
    http_response_code(400);
    exit("Không được điểm danh Chủ nhật");
}

# ==== Điều kiện môn học ====
$condMon = $maMonHoc === null ? "IS NULL" : "= $maMonHoc";

# ==== Kiểm tra bản ghi tồn tại ====
$sql_check = "
    SELECT 1 
    FROM chuyencan 
    WHERE maHS = $maHS 
      AND ngayHoc = '$ngayHoc'
      AND (maMonHoc $condMon)
";

$check = $conn->query($sql_check);

if ($check && $check->num_rows > 0) {
    # ==== Update ====
    $sql_update = "
        UPDATE chuyencan 
        SET trangThai = '$trangThai'
        WHERE maHS = $maHS 
          AND ngayHoc = '$ngayHoc'
          AND (maMonHoc $condMon)
    ";
    $conn->query($sql_update);
} else {
    # ==== Insert ====
    $monValue = $maMonHoc === null ? "NULL" : $maMonHoc;

    $sql_insert = "
        INSERT INTO chuyencan(maHS, maMonHoc, ngayHoc, trangThai)
        VALUES ($maHS, $monValue, '$ngayHoc', '$trangThai')
    ";
    $conn->query($sql_insert);
}

echo "OK";
