<?php
// session_start();
include_once(__DIR__ . '/../csdl/db.php');

// Hàm lấy dữ liệu SELECT

function getData($sql, $params = [], $types = "")
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        responseJSON(["error" => "Lỗi prepare: " . $conn->error], 500);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}


// Hàm thực thi INSERT, UPDATE, DELETE

function executeSQL($sql, $params = [], $types = "")
{
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        responseJSON(["error" => "Lỗi prepare: " . $conn->error], 500);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// Hàm trả JSON chuẩn

function responseJSON($data, $status = 200)
{
    http_response_code($status);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    return;
}

// Hàm lấy input JSON từ client

function getJSONInput()
{
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        responseJSON(["error" => "Dữ liệu gửi lên không hợp lệ (phải là JSON)"], 400);
    }
    return $input;
}

// Hàm kiểm tra đầu vào rỗng
function isEmpty($value) {
    return (!isset($value) || trim($value) === "");
}

// Hàm lấy thông tin user theo email
function getUserByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT userID, hoVaTen, matKhau AS password, vaiTro, email FROM user WHERE email = ?");
    if (!$stmt) {
        error_log("Lỗi prepare trong getUserByEmail: " . $conn->error);
        return null;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

// Hàm kiểm tra đăng nhập
// function loginUser($email, $password) {
//     $user = getUserByEmail($email);
//     if ($user && password_verify($password, $user['password'])) {
//         $_SESSION['userID']   = $user['userID'];
//         $_SESSION['hoVaTen']  = $user['hoVaTen'];
//         $_SESSION['vaiTro']   = $user['vaiTro'];
//         $_SESSION['email']    = $user['email']; // Lưu email vào session
//         return true;
//     }
//     return false;
// }
if (!function_exists('checkLogin')) {
    function checkLogin($email, $password) {
        $user = getUserByEmail($email);
        if ($user && password_verify($user['password'], $password)) {
            return $user; // trả về dữ liệu user
        }
        return null; // đăng nhập thất bại
    }
}


function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}

// Hàm ghi log dùng chung
function write_log($conn, $userID, $action, $content, $type = 'Info') {
    $stmt = $conn->prepare("INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Lỗi prepare trong write_log: " . $conn->error);
        return false;
    }
    $stmt->bind_param("isss", $userID, $action, $content, $type);
    $stmt->execute();
    $stmt->close();
}

?>

