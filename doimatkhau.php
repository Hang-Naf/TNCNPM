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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <title>Đổi Mật Khẩu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }

        body {
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            display: flex;
            width: 100%;
            height: 100%;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            background: #fff;
        }

        .left {
            flex: 1;
            background: #003f91;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            border-radius: 0 25% 25% 0;
            padding: 40px;
        }

        .left h2 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .left p {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .btn-outline {
            background: transparent;
            color: #fff;
            border: 2px solid #fff;
            padding: 12px 40px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
        }

        .btn-outline:hover {
            background: #fff;
            color: #003f91;
        }

        .right {
            width: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
        }

        .right-container {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border: 1px solid #eee;
            padding: 40px;
            border-radius: 10px;
        }

        .right-container h2 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
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

        .form-group input {
            width: 100%;
            height: 48px;
            padding: 12px 15px;
            border: none;
            border-radius: 6px;
            background: #eee;
            font-size: 15px;
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

        .new-pass-box {
            margin-top: 20px;
            padding: 15px;
            background: #f1f1f1;
            border-radius: 8px;
            text-align: center;
        }

        .new-pass-box code {
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .copy-btn {
            margin-top: 10px;
            padding: 8px 15px;
            background: #003f91;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Bên trái -->
        <div class="left">
            <h2 style="font-size: 48px;">Hello, Welcome!</h2>
            <!-- <p style="font-size: 24px;">Bạn chưa có tài khoản?</p>
            <button class="btn-outline" onclick="window.location.href='dangky.php'">Đăng Ký</button> -->
        </div>

        <!-- Bên phải -->
        <div class="right">
            <div class="right-container">
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
        </div>
    </div>
    <?php if (!empty($success)) : ?>
        <script>
            alert("Đổi mật khẩu thành công! Nhấn OK để đến trang đăng nhập.");
            window.location.href = "dangnhap.php";
        </script>
    <?php endif; ?>

</body>

</html>