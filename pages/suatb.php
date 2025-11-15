<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    header("Location: ../dangnhap.php");
    exit;
}

$maThongBao = $_GET['maThongBao'] ?? '';
if ($maThongBao === '' || !is_numeric($maThongBao)) {
    die("Mã thông báo không hợp lệ!");
}

// ==== Lấy thông tin thông báo ====
$stmt = $conn->prepare("SELECT * FROM thongbao WHERE maThongBao = ?");
$stmt->bind_param("i", $maThongBao);
$stmt->execute();
$result = $stmt->get_result();
$tb = $result->fetch_assoc();
$stmt->close();

if (!$tb) die("Không tìm thấy thông báo!");

// ==== Xác định người nhận từ thongbaouser + user ====
$nguoiNhan = 'toan'; // mặc định

$sql = "
    SELECT DISTINCT u.vaiTro 
    FROM thongbaouser tu
    JOIN user u ON tu.userID = u.userID
    WHERE tu.maThongBao = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $maThongBao);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $roles = [];
    while ($row = $res->fetch_assoc()) {
        $roles[] = strtolower($row['vaiTro']);
    }

    if (count($roles) === 1) {
        if (strpos($roles[0], 'giaovien') !== false) {
            $nguoiNhan = 'giaovien';
        } elseif (strpos($roles[0], 'hocsinh') !== false) {
            $nguoiNhan = 'hocsinh';
        }
    } else {
        $nguoiNhan = 'toan';
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa thông báo</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8f9fb;
            margin: 40px;
        }

        .container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            max-width: 700px;
            margin: auto;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #0b1e6b;
        }

        input,
        textarea {
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-save {
            background: #0b1e6b;
            color: white;
        }

        .btn-cancel {
            background: #ccc;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        label {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>CHỈNH SỬA THÔNG BÁO</h2>
        <form method="POST" action="../src/thongbao.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="maThongBao" value="<?= htmlspecialchars($tb['maThongBao']) ?>">

            <label>Tiêu đề:</label>
            <input type="text" name="tieuDe" value="<?= htmlspecialchars($tb['tieuDe']) ?>" required>

            <label>Nội dung:</label>
            <textarea name="noiDung" rows="6" required><?= htmlspecialchars($tb['noiDung']) ?></textarea>

            <label>Thời gian gửi thông báo:</label>
            <input type="date" name="thoiGianGui"
                value="<?= htmlspecialchars(substr($tb['ngayGui'], 0, 10)) ?>" required>

            <label>Tệp đính kèm hiện tại:</label>
            <div class="file-info">
                <?php if (!empty($tb['tepDinhKem'])): ?>
                    <a href="../uploads/thongbao/<?= htmlspecialchars($tb['tepDinhKem']) ?>" target="_blank">
                        <?= htmlspecialchars($tb['tepDinhKem']) ?>
                    </a>
                <?php else: ?>
                    <i>Không có tệp đính kèm</i>
                <?php endif; ?>
            </div>
            <input type="hidden" name="oldFile" value="<?= htmlspecialchars($tb['tepDinhKem'] ?? '') ?>">

            <label>Thay tệp đính kèm (nếu muốn):</label>
            <input type="file" name="tepDinhKem" accept=".pdf,.doc,.docx,.jpg,.png,.zip,.rar">

            <label>Người nhận:</label>
            <div class="radio-group">
                <label><input type="radio" name="nguoiNhan" value="toan" <?= ($nguoiNhan == 'toan') ? 'checked' : '' ?>> Toàn hệ thống</label>
                <label><input type="radio" name="nguoiNhan" value="giaovien" <?= ($nguoiNhan == 'giaovien') ? 'checked' : '' ?>> Giáo viên</label>
                <label><input type="radio" name="nguoiNhan" value="hocsinh" <?= ($nguoiNhan == 'hocsinh') ? 'checked' : '' ?>> Học sinh</label>
            </div>

            <div style="margin-top:20px;">
                <button type="button" class="btn btn-cancel" onclick="window.location.href='qlthongbao.php'">Hủy</button>
                <button type="submit" class="btn btn-save">Lưu</button>
            </div>
        </form>
    </div>
    <!-- <script>
        document.getElementById('addForm').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const res = await fetch('../src/thongbao.php', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            alert(json.message);
            if (!json.error) window.location.href = "qlthongbao.php";
        };
        document.getElementById("bellIcon").addEventListener("click", function() {
            const dropdown = document.getElementById("notificationDropdown");
            // Hiện/ẩn menu
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";

            // Gọi AJAX lấy thông báo
            fetch("../get_thongbao.php")
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById("notificationList");
                    const noNoti = document.getElementById("noNoti");
                    const badge = document.getElementById("notiBadge");
                    list.innerHTML = "";

                    let unreadCount = 0;

                    if (data.length > 0) {
                        noNoti.style.display = "none";
                        data.forEach(tb => {
                            const li = document.createElement("li");
                            li.style.padding = "10px 8px";
                            li.style.borderBottom = "1px solid #eee";
                            li.style.cursor = "pointer";

                            if (tb.trangThai === "Chưa đọc") {
                                unreadCount++;
                                li.style.background = "#f0f8ff";
                                li.innerHTML = `
                        <strong style="color:#0b3364;">${tb.tieuDe} 🔵</strong><br>
                        <span>${tb.noiDung}</span><br>
                        <small>${tb.ngayGui}</small>
                    `;
                            } else {
                                li.style.opacity = "0.7";
                                li.innerHTML = `
                        <strong>${tb.tieuDe}</strong><br>
                        <span>${tb.noiDung}</span><br>
                        <small>${tb.ngayGui}</small>
                    `;
                            }

                            li.addEventListener("click", () => markAsRead(tb.maThongBao, li));
                            list.appendChild(li);
                        });
                    } else {
                        noNoti.style.display = "block";
                    }

                    // Cập nhật badge
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount;
                        badge.style.display = "block";
                    } else {
                        badge.style.display = "none";
                    }
                })
                .catch(err => console.error("Lỗi tải thông báo:", err));


            function markAsRead(maThongBao, element) {
                fetch("../update_trangthai.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: "maThongBao=" + encodeURIComponent(maThongBao)
                    })
                    .then(res => res.text())
                    .then(response => {
                        if (response === "OK") {
                            element.style.background = "transparent";
                            element.style.opacity = "0.7";
                            element.querySelector("strong").innerHTML = element.querySelector("strong").innerText;

                            // Giảm số badge đi 1
                            const badge = document.getElementById("notiBadge");
                            let current = parseInt(badge.textContent || "0");
                            if (current > 1) badge.textContent = current - 1;
                            else badge.style.display = "none";
                        }
                    });
            }

        });

        // Ẩn dropdown khi click ra ngoài
        document.addEventListener("click", function(e) {
            const dropdown = document.getElementById("notificationDropdown");
            const bell = document.getElementById("bellIcon");
            if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });

        function toggleUserMenu() {
            const menu = document.getElementById("userMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        // Đóng menu nếu click ra ngoài
        document.addEventListener("click", function(e) {
            const menu = document.getElementById("userMenu");
            const userInfo = document.querySelector(".user-info");
            if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = "none";
            }
        });

        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script> -->
</body>

</html>