<?php
session_start();
include("csdl/db.php");
include("src/func.php");

$message = "";

// Nếu người dùng submit form đổi mật khẩu
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? "");
    $tempPass = trim($_POST['tempPass'] ?? "");
    $newPass = trim($_POST['newPass'] ?? "");
    $confirmPass = trim($_POST['confirmPass'] ?? "");

    if (isEmpty($email) || isEmpty($tempPass) || isEmpty($newPass) || isEmpty($confirmPass)) {
        $message = "⚠ Vui lòng nhập đầy đủ thông tin!";
    } elseif ($newPass !== $confirmPass) {
        $message = "⚠ Mật khẩu mới và xác nhận mật khẩu không khớp!";
    } elseif (strlen($newPass) < 6) {
        $message = "⚠ Mật khẩu mới phải ít nhất 6 ký tự!";
    } else {

        // Kiểm tra email có tồn tại không
        $user = getUserByEmail($email);

        // kiểm tra email có tồn tại không
        $user = getUserByEmail($email);

        if (!$user) {
            $message = "❌ Email không tồn tại!";
        } elseif (!isset($user["password"])) {
            $message = "❌ Dữ liệu người dùng không hợp lệ (thiếu cột mật khẩu)!";
        } elseif (!password_verify($tempPass, $user["password"])) {
            $message = "❌ Mật khẩu tạm không chính xác!";
        } else {

            // cập nhật mật khẩu mới
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);

            executeSQL(
                "UPDATE user SET matKhau=? WHERE email=?",
                [$hashed, $email],
                "ss"
            );

            $message = "Đổi mật khẩu thành công!";
            $success = true;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đổi Mật Khẩu</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            font-family: "Quicksand", sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            width: 420px;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
            color: #003f91;
        }

        label {
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            background: #f1f1f1;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: #003f91;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 10px;
        }

        .message {
            margin-top: 10px;
            text-align: center;
            font-weight: bold;
            color: #d00;
        }

        .success {
            color: green !important;
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Đổi Mật Khẩu</h2>

        <form action="" method="POST">

            <label>Email của bạn:</label>
            <input type="email" name="email" placeholder="Nhập email của bạn" required>

            <label>Mật khẩu tạm:</label>
            <input type="text" name="tempPass" placeholder="Nhập mật khẩu tạm" required>

            <label>Mật khẩu mới:</label>
            <input type="password" name="newPass" placeholder="Mật khẩu mới" required>

            <label>Xác nhận mật khẩu mới:</label>
            <input type="password" name="confirmPass" placeholder="Nhập lại mật khẩu" required>

            <button type="submit" class="btn">Xác Nhận Đổi</button>
        </form>

        <?php if (!empty($message)) : ?>
            <div class="message <?= str_contains($message, 'thành công') ? 'success' : '' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

    </div>
<?php if (!empty($success)) : ?>
<script>
    alert("Đổi mật khẩu thành công! Nhấn OK để đến trang đăng nhập.");
    window.location.href = "dangnhap.php";
</script>
<?php endif; ?>

</body>

</html>