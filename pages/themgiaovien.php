<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}
// ==== Kiểm tra nếu đang sửa ====
$editData = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("
        SELECT 
            g.maGV, u.hoVaTen, u.gioiTinh, u.email, u.sdt,
            g.boMon, g.trinhDo, g.phongBan, g.namHoc, g.hocKy, g.trangThai
        FROM giaovien g
        JOIN user u ON g.maGV = u.userID
        WHERE g.maGV = ?
    ");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
}

// ==== Chỉ cho phép Admin ====
if ($_SESSION["vaiTro"] !== "Admin") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Lấy danh sách giáo viên ====
$sql = "
    SELECT 
        g.maGV, u.hoVaTen, u.gioiTinh, u.email, u.sdt,
        g.boMon, g.trinhDo, g.phongBan, g.namHoc, g.hocKy, g.trangThai
    FROM giaovien g
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien'
";
$result = $conn->query($sql);

// ==== Lấy danh sách môn học ====
$monhoc_rs = $conn->query("SELECT maMonHoc, tenMonHoc FROM monhoc");
$monhoc_list = [];
while ($mh = $monhoc_rs->fetch_assoc()) {
    $monhoc_list[] = $mh;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm giáo viên</title>
    <style>
        .popup {
            position: relative;
            font-family: 'Segoe UI', sans-serif;
        }

        .form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            flex: 1;
        }

        .row {
            width: 100%;
            display: flex;
            gap: 20px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .radio-group input[type="radio"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }

        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-primary {
            background: #0b1e6b;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #0d2591;
        }

        .btn-secondary {
            background: #ccc;
            color: #333;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background: #bbb;
        }
    </style>
</head>

<body>
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <div class="them-hocsinh">
                <h2 id="title-h2">THÊM GIÁO VIÊN</h2>
                <form id="addForm" class="form">
                    <input type="hidden" name="action" value="add" id="formAction">
                    <input type="hidden" name="userId" id="userId">

                    <div class="row">
                        <div class="form-group">
                            <label>Họ và Tên:</label>
                            <input type="text" name="hoVaTen">
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email">
                        </div>
                        <div class="form-group">
                            <label>Số Điện Thoại:</label>
                            <input type="text" name="sdt">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Giới tính:</label>
                            <select name="gioiTinh">
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bộ môn:</label>
                            <select name="boMon" required>
                                <option value="">-- Chọn bộ môn --</option>
                                <?php foreach ($monhoc_list as $mh): ?>
                                    <option value="<?= htmlspecialchars($mh['tenMonHoc']) ?>">
                                        <?= htmlspecialchars($mh['tenMonHoc']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trình độ:</label>
                            <input type="text" name="trinhDo" placeholder="Trình độ (VD: Cử nhân, Thạc sĩ)">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Phòng ban:</label>
                            <input type="text" name="phongBan" placeholder="Phòng ban (VD: Tổ Toán)">

                        </div>
                        <div class="form-group">
                            <label>Năm học:</label>
                            <input type="text" name="namHoc" id="addNamHoc" placeholder="Năm học" readonly>
                        </div>
                        <div class="form-group">
                            <label>Học kỳ:</label>
                            <select name="hocKy" id="addHocKy" readonly>
                                <option value="">-- Học kỳ tự động --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Trạng thái:</label>
                            <div class="radio-group">
                                <label><input type="radio" name="trangThai" value="active"> Đang hoạt động</label>
                                <label><input type="radio" name="trangThai" value="inactive"> Tạm dừng</label>
                            </div>
                        </div>
                        <div class="popup-buttons">
                            <button type="button" class="btn-secondary"
                                onclick="window.location.href='qlgiaovien.php'">Quay
                                lại</button>
                            <button type="submit" class="btn-primary" id="submitButton">Thêm giáo viên</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        const apiGiaoVien = "../src/giaovien.php";
        document.getElementById("addForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            const res = await fetch(apiGiaoVien, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(data)
            });

            const result = await res.json();

            if (result.error) {
                alert(result.error);
            } else {
                alert(result.message);
                window.location.href = result.redirect; // ← chuyển về trang quản lý giáo viên
            }
        });
        // === Nếu có tham số edit trên URL, tự động điền form ===
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has("edit")) {
            document.getElementById("title-h2").innerText = "CHỈNH SỬA GIÁO VIÊN";
            document.getElementById("formAction").value = "update";
            document.getElementById("submitButton").innerText = "Lưu thay đổi";

            // Gán dữ liệu
            const fields = ["maGV", "hoVaTen", "gioiTinh", "email", "sdt", "boMon", "trinhDo", "phongBan", "namHoc", "hocKy", "trangThai"];
            fields.forEach((f) => {
                const value = urlParams.get(f);
                const el = document.querySelector(`[name="${f}"]`);
                if (el) {
                    if (el.tagName === "SELECT" || el.tagName === "INPUT") {
                        if (el.type === "radio") return;
                        el.value = value;
                    }
                }
            });

            // Chọn trạng thái
            const trangThai = urlParams.get("trangThai");
            if (trangThai) {
                document.querySelector(`[name="trangThai"][value="${trangThai}"]`).checked = true;
            }

            // Lưu id giáo viên (nếu có)
            document.getElementById("userId").value = urlParams.get("maGV");
        }

    </script>
</body>

</html>