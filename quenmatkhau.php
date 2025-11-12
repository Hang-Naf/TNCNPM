<?php
session_start();
// Kết nối CSDL
include("csdl/db.php");
include("src/func.php");

$message = "";
$newPass = "";

// Nếu người dùng submit form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? "");

    if (isEmpty($email)) {
        $message = "⚠️ Vui lòng nhập email!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "⚠️ Email không hợp lệ!";
    } else {
        // Kiểm tra email có tồn tại không
        $user = getUserByEmail($email);

        if ($user) {
            // Tạo mật khẩu mới ngẫu nhiên (8 ký tự)
            $newPass = substr(md5(time()), 0, 8);

            // Mã hóa mật khẩu
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);

            // Cập nhật mật khẩu mới
            $sqlUpdate = "UPDATE user SET matKhau=? WHERE email=?";
            $ok = executeSQL($sqlUpdate, [$hashed, $email], "ss");

            if ($ok) {
                $message = "✅ Mật khẩu mới của bạn đã được tạo thành công!";
                $_SESSION['email'] = $email;
            } else {
                $message = "❌ Có lỗi khi cập nhật mật khẩu.";
            }
        } else {
            $message = "⚠️ Email không tồn tại trong hệ thống!";
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
    <title>Quên Mật Khẩu</title>
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
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        .btn-primary {
            background: #003f91;
            color: #fff;
        }

        .message {
            margin-top: 15px;
            font-size: 15px;
            color: #003f91;
            text-align: center;
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
                <h2>Quên Mật Khẩu</h2>
                <b>Nhập địa chỉ email để đặt lại mật khẩu</b><br><br>
                <form action="" method="POST">
                    <div class="form-group">
                        <?php
                        $emailValue = $_POST['email'] ?? $_GET['email'] ?? $_SESSION['email'] ?? '';
                        ?>
                        <input type="email" name="email" placeholder="Email"
                            value="<?= htmlspecialchars($emailValue) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Gửi</button>
                </form>

                <?php if (!empty($message)) : ?>
                    <div class="message"><?= $message ?></div>
                <?php endif; ?>

                <?php if (!empty($newPass)) : ?>
                    <div class="new-pass-box">
                        <p>Mật khẩu mới của bạn:</p>
                        <code id="newPass"><?= $newPass ?></code>
                        <br>
                        <button class="copy-btn" onclick="copyPass()">📋 Copy & Đăng nhập</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function copyPass() {
            let passText = document.getElementById("newPass").innerText;
            navigator.clipboard.writeText(passText).then(() => {
                alert("✅ Mật khẩu đã được copy. Chuyển đến trang đăng nhập...");
                window.location.href = "dangnhap.php";
            });
        }
    </script>
</body>

</html>