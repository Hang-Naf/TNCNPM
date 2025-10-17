<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cnpm";  // tên database của bạn

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Kết nối thất bại: " . $conn->connect_error]);
    exit;
}
?>
