<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Giáo viên ====
if ($_SESSION["vaiTro"] !== "GiaoVien") {
    header("Location: ../dangnhap.php");
    exit();
}

$maGV = $_SESSION["userID"];
$today = date('Y-m-d');

// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = '$maGV' LIMIT 1";
$resultGV = $conn->query($sqlGV);
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];

// ==== Lấy danh sách lớp học mà GV được phân công ====
$sqlLop = "SELECT DISTINCT l.maLop, l.tenLop 
           FROM lophoc_monhoc lm
           JOIN lophoc l ON lm.maLop = l.maLop
           WHERE lm.maGV = '$maGV'";
$lopList = $conn->query($sqlLop);

// ==== Xử lý thêm tài liệu ====
if (isset($_POST['add'])) {
    $maLop = intval($_POST['maLop'] ?? 0);
    $tieuDe = trim($_POST['tieuDe'] ?? '');
    $moTa = trim($_POST['moTa'] ?? '');
    $trangThai = $_POST['trangThai'] ?? 'Công khai';
    $ngayTai = $today;
    $fileName = '';

    // Kiểm tra maLop hợp lệ
    if ($maLop <= 0) {
        echo "<script>alert('Vui lòng chọn lớp học!');</script>";
        exit;
    }

    // ==== Lấy mã môn học mà GV dạy lớp này (để lưu đúng môn) ====
    $sqlMonHoc = "SELECT maMonHoc FROM lophoc_monhoc WHERE maGV='$maGV' AND maLop='$maLop' LIMIT 1";
    $resultMon = $conn->query($sqlMonHoc);
    if ($resultMon && $resultMon->num_rows > 0) {
        $maMonHoc = $resultMon->fetch_assoc()['maMonHoc'];
    } else {
        echo "<script>alert('Không tìm thấy môn học bạn phụ trách trong lớp này!');</script>";
        exit;
    }

    // ==== Upload file nếu có ====
    if (!empty($_FILES['fileTaiLieu']['name'])) {

        // ==== Giới hạn dung lượng 15MB ====
        $maxSize = 15 * 1024 * 1024; // 15MB
        if ($_FILES['fileTaiLieu']['size'] > $maxSize) {
            echo "<script>alert('File quá lớn! Giới hạn tối đa là 15MB.');</script>";
            exit;
        }

        $targetDir = "../uploads/tailieu/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["fileTaiLieu"]["name"]);
        $targetFile = $targetDir . $fileName;
        move_uploaded_file($_FILES["fileTaiLieu"]["tmp_name"], $targetFile);
    }

    // ==== Kiểm tra quyền phân công ====
    $kiemtra = $conn->query("SELECT * FROM lophoc_monhoc WHERE maGV='$maGV' AND maLop='$maLop'");
    if ($kiemtra->num_rows == 0) {
        echo "<script>alert('Bạn không được phân công dạy lớp này!');</script>";
    } else {
        // Sử dụng prepared statement để tránh SQL injection và đảm bảo giá trị được lưu đúng
        $stmt = $conn->prepare("INSERT INTO tailieu (maMonHoc, maLop, tieuDe, noiDung, ngayTai, maGV, trangThai, tepDinhKem) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        // Correct type: i(maMonHoc) i(maLop) s(tieuDe) s(noiDung) s(ngayTai) i(maGV) s(trangThai) s(tepDinhKem)
        $stmt->bind_param("iisssiss", $maMonHoc, $maLop, $tieuDe, $moTa, $ngayTai, $maGV, $trangThai, $fileName);
        
        if ($stmt->execute()) {
            echo "<script>alert('Thêm tài liệu thành công nha!'); window.location='tlhoctap.php';</script>";
        } else {
            echo "Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm tài liệu học tập</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .content-area {
            padding: 30px;
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #111;
            margin-bottom: 25px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            margin-bottom: 20px;
        }

        .form-section {
            background: #f8f9fb;
            padding: 20px;
            border-radius: 8px;
        }

        label {
            font-weight: 600;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        input[type=text],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        textarea {
            resize: none;
            height: 80px;
        }

        .status-group {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 8px;
        }

        .file-upload {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #eee;
        }

        .file-upload input[type=file] {
            border: 1px solid #ccc;
            padding: 8px;
            width: 100%;
            border-radius: 6px;
        }

        .buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-primary {
            background: #0b3364;
            color: #fff;
        }

        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }

        .btn-primary:hover {
            background: #124b8a;
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fa-solid fa-graduation-cap logo"></i>
            <h2>Viện đào tạo ABC</h2>
        </div>

        <nav class="menu">
            <div class="menu-section">
                <div class="menu-title">Quản lý chung</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
                    <li onclick="window.location.href='../pagegiaovien/hocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pagegiaovien/lophoc.php'"><i class="fa-solid fa-school"></i> Lớp học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li class="active" onclick="window.location.href='../pagegiaovien/tlhoctap.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/chuyencan.php'"><i class="fa-solid fa-check"></i> Chuyên cần</li>
                    <li onclick="window.location.href='../pagegiaovien/diemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Thông báo</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/thongbao.php'"><i class="fa-solid fa-bell"></i> Xem thông báo</li>
                </ul>
            </div>
        </nav>
    </aside>

    <div class="main-content">
        <header class="header">
            <div class="left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Tìm kiếm...">
                </div>
            </div>

            <div class="right">
                <div class="notification-area">
                    <i class="fa-regular fa-bell" id="bellIcon"></i>
                    <span class="noti-badge" id="notiBadge">0</span>
                    <div class="notification-dropdown" id="notificationDropdown">
                        <h4>Thông báo</h4>
                        <ul id="notificationList"></ul>
                        <div class="no-noti" id="noNoti">Không có thông báo mới</div>
                    </div>
                </div>

                <div class="user-info" onclick="toggleUserMenu()">
                    <i class="fa-solid fa-user"></i>
                    <span><?= htmlspecialchars($gv['hoVaTen']) ?></span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="window.location.href='../pagegiaovien/ttcanhan.php'"><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>
        <div class="content-area">
            <h1>THÊM TÀI LIỆU</h1>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div>
                        <label>TIÊU ĐỀ</label>
                        <input type="text" name="tieuDe" required>
                    </div>
                    <div>
                        <label>LỚP HỌC</label>
                        <select name="maLop" required>
                            <option value="">-- Chọn lớp được phân công --</option>
                            <?php while ($lop = $lopList->fetch_assoc()): ?>
                                <option value="<?= $lop['maLop'] ?>"><?= htmlspecialchars($lop['tenLop']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label>MÔ TẢ</label>
                        <input type="text" name="moTa" required>
                    </div>
                    <div>
                        <label>TRẠNG THÁI</label>
                        <div class="status-group">
                            <label><input type="radio" name="trangThai" value="Công khai" checked> Công khai</label>
                            <label><input type="radio" name="trangThai" value="Riêng tư"> Riêng tư</label>
                        </div>
                    </div>
                </div>

                <div class="file-upload">
                    <label>FILE TÀI LIỆU</label>
                    <input type="file" name="fileTaiLieu" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar">
                </div>

                <div class="buttons">
                    <button type="button" class="btn btn-secondary" onclick="window.location='tlhoctap.php'">Quay lại</button>
                    <button type="submit" name="add" class="btn btn-primary">Thêm mới</button>
                </div>
            </form>
        </div>
    </div>
    <script>
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
    </script>
</body>

</html>