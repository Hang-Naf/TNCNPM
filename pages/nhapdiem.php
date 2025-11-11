<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Admin ====
if ($_SESSION["vaiTro"] !== "Admin") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

// === Lấy danh sách học sinh và môn học ===
$dsHS = $conn->query("SELECT u.userID AS maHS, u.hoVaTen, h.lopHocPhuTrach 
                      FROM hocsinh h 
                      JOIN user u ON h.maHS = u.userID
                      ORDER BY h.lopHocPhuTrach, u.hoVaTen ASC");
$dsMon = $conn->query("SELECT * FROM monhoc ORDER BY tenMonHoc ASC");

// === Khi submit form ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $maHS = $_POST["maHS"];
    $maMon = $_POST["maMonHoc"];
    $diem = [
        'hk1_mieng' => $_POST['hk1_mieng'] ?? null,
        'hk1_1tiet' => $_POST['hk1_1tiet'] ?? null,
        'hk1_thiGK' => $_POST['hk1_thiGK'] ?? null,
        'hk1_thiCK' => $_POST['hk1_thiCK'] ?? null,
        'hk2_mieng' => $_POST['hk2_mieng'] ?? null,
        'hk2_1tiet' => $_POST['hk2_1tiet'] ?? null,
        'hk2_thiGK' => $_POST['hk2_thiGK'] ?? null,
        'hk2_thiCK' => $_POST['hk2_thiCK'] ?? null,
    ];

    foreach ($diem as $loai => $value) {
        if ($value !== '' && is_numeric($value)) {
            $sql = "INSERT INTO diemso (maHS, maMonHoc, loaiDiem, diem, ngayCapNhat) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisd", $maHS, $maMon, $loai, $value);
            if (!$stmt->execute()) {
                echo "Lỗi SQL: " . $stmt->error;
            }
        }
    }

    echo "<script>
        alert('Thêm điểm thành công!');
        window.location.href = 'qldiemso.php';
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm điểm học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 40px;
        }

        .form-container {
            background: #fff;
            border-radius: 10px;
            padding: 30px 40px;
            max-width: 700px;
            margin: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #0b3364;
            margin-bottom: 20px;
        }

        select,
        input {
            width: 100%;
            padding: 6px 10px;
            margin: 5px 0 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        label {
            font-weight: bold;
        }

        .hk-section {
            background: #f0f4ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .hk-section h3 {
            margin: 0 0 10px;
            background: #0b3364;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
        }

        button {
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            margin-top: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-submit {
            background: #28a745;
            color: white;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #ddd;
            color: black;
            margin-left: 10px;
        }

        .btn-cancel:hover {
            background: #bbb;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>THÊM ĐIỂM HỌC SINH</h2>
        <form method="POST">
            <label for="maHS">Chọn học sinh:</label>
            <select name="maHS" required>
                <option value="">-- Chọn học sinh --</option>
                <?php while ($hs = $dsHS->fetch_assoc()): ?>
                    <option value="<?= $hs['maHS'] ?>">
                        <?= htmlspecialchars($hs['hoVaTen']) ?> (<?= htmlspecialchars($hs['lopHocPhuTrach']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="maMonHoc">Chọn môn học:</label>
            <select name="maMonHoc" required>
                <option value="">-- Chọn môn học --</option>
                <?php while ($m = $dsMon->fetch_assoc()): ?>
                    <option value="<?= $m['maMonHoc'] ?>"><?= htmlspecialchars($m['tenMonHoc']) ?></option>
                <?php endwhile; ?>
            </select>

            <div class="hk-section">
                <h3>HỌC KỲ I</h3>
                <div class="grid">
                    <div>
                        <label>Điểm miệng:</label>
                        <input type="number" step="0.1" name="hk1_mieng">
                    </div>
                    <div>
                        <label>Điểm 1 tiết:</label>
                        <input type="number" step="0.1" name="hk1_1tiet">
                    </div>
                    <div>
                        <label>Điểm thi GK:</label>
                        <input type="number" step="0.1" name="hk1_thiGK">
                    </div>
                    <div>
                        <label>Điểm thi CK:</label>
                        <input type="number" step="0.1" name="hk1_thiCK">
                    </div>
                </div>
            </div>

            <div class="hk-section">
                <h3>HỌC KỲ II</h3>
                <div class="grid">
                    <div>
                        <label>Điểm miệng:</label>
                        <input type="number" step="0.1" name="hk2_mieng">
                    </div>
                    <div>
                        <label>Điểm 1 tiết:</label>
                        <input type="number" step="0.1" name="hk2_1tiet">
                    </div>
                    <div>
                        <label>Điểm thi GK:</label>
                        <input type="number" step="0.1" name="hk2_thiGK">
                    </div>
                    <div>
                        <label>Điểm thi CK:</label>
                        <input type="number" step="0.1" name="hk2_thiCK">
                    </div>
                </div>
            </div>

            <div style="text-align:center;">
                <button type="submit" class="btn-submit">➕ Thêm điểm</button>
                <button type="button" class="btn-cancel" onclick="window.location.href='qldiemso.php'">Hủy</button>
            </div>
        </form>
    </div>
</body>

</html>