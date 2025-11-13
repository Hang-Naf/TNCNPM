<?php
session_start();

// Kết nối database
include("csdl/db.php");
include("src/func.php");

$message = "";

// Khi người dùng nhấn nút Đăng nhập
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["username"]); // đổi biến cho dễ hiểu
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $message = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        // Kiểm tra tài khoản trong bảng user theo email
        $sql = "SELECT * FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Kiểm tra mật khẩu
            if (password_verify($password, $user["matKhau"]) || $password === $user["matKhau"]) {
                // Lưu thông tin vào session
                $_SESSION["userID"] = $user["userID"];
                $_SESSION["hoVaTen"] = $user["hoVaTen"];
                $_SESSION["vaiTro"] = $user["vaiTro"];

                // Chuyển hướng theo vai trò
                switch ($user["vaiTro"]) {
                    case "Admin":
                        header("Location: index.php");
                        exit();
                    case "GiaoVien":
                        header("Location: pagegiaovien/ttcanhan.php");
                        exit();
                    case "HocSinh":
                        header("Location: ../pagehocsinh/ttcanhan.php");
                        exit();
                    default:
                        $message = "Vai trò không hợp lệ!";
                }
            } else {
                $message = "Mật khẩu không đúng!";
            }
        } else {
            $message = "Không tìm thấy tài khoản với email này!";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <title>Đăng Nhập</title>
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
            overflow-x: hidden;
            transition: transform 0.6s ease-in-out;
        }

        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 1s ease forwards;
        }

        .slide-in-left {
            opacity: 0;
            transform: translateX(100px);
            animation: slideInLeft 1s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(100px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .container {
            display: flex;
            width: 100%;
            height: 100%;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            background: #fff;
        }

        /* Bên trái */
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

        /* Bên phải */
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

        .forgot {
            display: block;
            margin: 10px 0 20px;
            font-size: 14px;
            color: #003f91;
            text-decoration: none;
        }

        .forgot:hover {
            text-decoration: underline;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="left">
            <h2 style="font-size: 48px;">Hello, Welcome!</h2>
        </div>
        <div class="right">
            <div class="right-container">
                <h2>Đăng Nhập</h2>
                <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>
                <form method="POST">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="">
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Mật khẩu">
                    </div>
                    <a href="quenmatkhau.php" class="forgot">Quên mật khẩu</a>
                    <button type="submit" class="btn btn-primary">Đăng nhập</button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>
