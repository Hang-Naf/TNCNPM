<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "GiaoVien") {
    header("Location: ../dangnhap.php");
    exit();
}

$maGV = $_SESSION["userID"];
$maTL = $_GET['maTL'] ?? 0;

// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = '$maGV' LIMIT 1";
$resultGV = $conn->query($sqlGV);
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];

// ==== Lấy thông tin tài liệu ====
$sql = "SELECT * FROM tailieu WHERE maTL='$maTL' AND maGV='$maGV' LIMIT 1";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    echo "<script>alert('Không tìm thấy tài liệu hoặc bạn không có quyền chỉnh sửa.'); window.location='tlhoctap.php';</script>";
    exit();
}
$tl = $result->fetch_assoc();

// ==== Lấy danh sách lớp GV dạy ====
$sqlLop = "SELECT DISTINCT l.maLop, l.tenLop
           FROM lophoc_monhoc lm
           JOIN lophoc l ON lm.maLop = l.maLop
           WHERE lm.maGV = '$maGV'";
$lopList = $conn->query($sqlLop);

// ==== Xử lý cập nhật ====
if (isset($_POST['update'])) {
    $tieuDe = trim($_POST['tieuDe']);
    $noiDung = trim($_POST['moTa']);
    $maLop = $_POST['maLop'];
    $trangThai = $_POST['trangThai'];
    $fileName = $tl['tepDinhKem'];

    // Nếu có file mới
    if (!empty($_FILES['fileTaiLieu']['name'])) {
        $targetDir = "../uploads/tailieu/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["fileTaiLieu"]["name"]);
        $targetFile = $targetDir . $fileName;
        move_uploaded_file($_FILES["fileTaiLieu"]["tmp_name"], $targetFile);
    }

    $sqlUpdate = "UPDATE tailieu 
                  SET tieuDe='$tieuDe', noiDung='$noiDung', maLop='$maLop', trangThai='$trangThai', tepDinhKem='$fileName'
                  WHERE maTL='$maTL' AND maGV='$maGV'";
    if ($conn->query($sqlUpdate)) {
        echo "<script>alert('Cập nhật thành công!'); window.location='tlhoctap.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// ==== Xóa file đính kèm ====
if (isset($_GET['deleteFile']) && $_GET['deleteFile'] == 1) {
    $filePath = "../uploads/tailieu/" . $tl['tepDinhKem'];
    if (file_exists($filePath)) unlink($filePath);
    $conn->query("UPDATE tailieu SET tepDinhKem=NULL WHERE maTL='$maTL'");
    echo "<script>alert('Đã xóa file!'); window.location='suatlht.php?maTL=$maTL';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa tài liệu</title>
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

        .file-upload {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #eee;
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

        .file-name {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .file-name a {
            color: #0b3364;
            text-decoration: none;
            font-weight: 500;
        }

        .delete-btn {
            background: #d9534f;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
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
            <h1>CHỈNH SỬA TÀI LIỆU</h1>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div>
                        <label>TIÊU ĐỀ</label>
                        <input type="text" name="tieuDe" value="<?= htmlspecialchars($tl['tieuDe']) ?>" required>
                    </div>
                    <div>
                        <label>LỚP HỌC</label>
                        <select name="maLop" required>
                            <?php while ($lop = $lopList->fetch_assoc()): ?>
                                <option value="<?= $lop['maLop'] ?>" <?= ($lop['maLop'] == $tl['maLop']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($lop['tenLop']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label>MÔ TẢ</label>
                        <input type="text" name="moTa" value="<?= htmlspecialchars($tl['noiDung']) ?>" required>
                    </div>
                    <div>
                        <label>TRẠNG THÁI</label>
                        <div class="status-group">
                            <label><input type="radio" name="trangThai" value="Công khai" <?= ($tl['trangThai'] == 'Công khai') ? 'checked' : '' ?>> Công khai</label>
                            <label><input type="radio" name="trangThai" value="Riêng tư" <?= ($tl['trangThai'] == 'Riêng tư') ? 'checked' : '' ?>> Riêng tư</label>
                        </div>
                    </div>
                </div>

                <div class="file-upload">
                    <label>FILE TÀI LIỆU</label>
                    <?php if (!empty($tl['tepDinhKem'])): ?>
                        <div class="file-name">
                            <i class="fa-solid fa-folder"></i>
                            <a href="../uploads/tailieu/<?= htmlspecialchars($tl['tepDinhKem']) ?>" target="_blank"><?= htmlspecialchars($tl['tepDinhKem']) ?></a>
                            <button type="button" class="delete-btn" onclick="if(confirm('Xóa file này?')) window.location='suatlht.php?maTL=<?= $maTL ?>&deleteFile=1'">Xóa file</button>
                        </div>
                    <?php else: ?>
                        <input type="file" name="fileTaiLieu" accept=".pdf,.doc,.docx,.ppt,.pptx,.zip,.rar">
                    <?php endif; ?>
                </div>

                <div class="buttons">
                    <button type="button" class="btn btn-secondary" onclick="window.location='tlhoctap.php'">Quay lại</button>
                    <button type="submit" name="update" class="btn btn-primary">Lưu</button>
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
