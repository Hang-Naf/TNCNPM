<?php
include_once('../csdl/db.php');

$lop = $_GET['lop'] ?? '';
$mon = $_GET['mon'] ?? '';

$sql = "
SELECT DISTINCT u.userID
FROM hocsinh h
JOIN user u ON h.maHS = u.userID
LEFT JOIN diemso d ON d.maHS = h.maHS
LEFT JOIN monhoc m ON d.maMonHoc = m.maMonHoc
WHERE 1=1
";

if ($lop !== '') {
    $sql .= " AND h.lopHocPhuTrach = '" . $conn->real_escape_string($lop) . "'";
}
if ($mon !== '') {
    $sql .= " AND m.tenMonHoc = '" . $conn->real_escape_string($mon) . "'";
}

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row['userID'];
}

echo json_encode($data);
exit;
