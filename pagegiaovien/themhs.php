<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép GiaoVien ====
if ($_SESSION["vaiTro"] !== "GiaoVien") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$userID = $_SESSION["userID"];
$maGV = $userID;

// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = ? LIMIT 1";
$stmtGV = $conn->prepare($sqlGV);
$stmtGV->bind_param("i", $maGV);
$stmtGV->execute();
$resultGV = $stmtGV->get_result();
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];

// ==== Lấy danh sách lớp mà giáo viên phụ trách ====
$sql_lop = "SELECT DISTINCT l.maLop, l.tenLop
            FROM lophoc l
            JOIN lophoc_monhoc lm ON l.maLop = lm.maLop
            WHERE lm.maGV = ?";
$stmt = $conn->prepare($sql_lop);
$stmt->bind_param("i", $userID);
$stmt->execute();
$lops = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ==== Tự động sinh năm học & học kỳ mặc định ====
$currentYear = date("Y");
$nextYear = $currentYear + 1;
$namHocDefault = "$currentYear-$nextYear";
$hocKyDefault = (date("n") <= 6) ? "2" : "1";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hoTen = trim($_POST["hoTen"]);
    $email = trim($_POST["email"]);
    $sdt = trim($_POST["sdt"]);
    $gioiTinh = $_POST["gioiTinh"] ?? 'Nam';
    $lop = $_POST["lop"]; // this should be maLop (int) from your <select>
    $chucVu = trim($_POST["chucVu"]);
    $trangThai = $_POST["trangThai"] ?? 'active';
    $namHoc = $_POST["namHoc"];
    $hocKy = $_POST["hocKy"];

    // Kiểm tra trùng số điện thoại
    $checkSql = "SELECT userID FROM user WHERE sdt = ?";
    $checkStmt = $conn->prepare($checkSql);
    if (!$checkStmt) {
        $msg = "Lỗi prepare kiểm tra SĐT: " . $conn->error;
    } else {
        $checkStmt->bind_param("s", $sdt);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $msg = "⚠️ Số điện thoại này đã tồn tại trong hệ thống!";
        } else {
            $conn->begin_transaction();
            try {
                // 1) Thêm user
                $sqlUser = "INSERT INTO user (hoVaTen, email, sdt, gioiTinh, vaiTro, matKhau)
                            VALUES (?, ?, ?, ?, 'HocSinh', ?)";
                $stmtUser = $conn->prepare($sqlUser);
                if (!$stmtUser) throw new Exception("Lỗi prepare user: " . $conn->error . " | SQL: $sqlUser");

                $defaultPass = password_hash("123456", PASSWORD_DEFAULT);
                $stmtUser->bind_param("sssss", $hoTen, $email, $sdt, $gioiTinh, $defaultPass);
                if (!$stmtUser->execute()) throw new Exception("Lỗi execute user: " . $stmtUser->error);

                $userID_new = $stmtUser->insert_id;
                if (!$userID_new) throw new Exception("Không lấy được insert_id sau khi thêm user.");

                // 2) Thêm vào bảng hocsinh
                // NOTE: theo structure trong sql dump, cột tên là `lopHocPhuTrach` (string), chứ không phải maLop
                // Sắp xếp theo thứ tự dùng trong hocsinh.php: (maHS, lopHocPhuTrach, chucVu, namHoc, hocKy, trangThai)
                $sqlHS = "INSERT INTO hocsinh (maHS, lopHocPhuTrach, chucVu, namHoc, hocKy, trangThai)
                          VALUES (?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE lopHocPhuTrach=VALUES(lopHocPhuTrach), chucVu=VALUES(chucVu), namHoc=VALUES(namHoc), hocKy=VALUES(hocKy), trangThai=VALUES(trangThai)";
                $stmtHS = $conn->prepare($sqlHS);
                if (!$stmtHS) throw new Exception("Lỗi prepare hocsinh: " . $conn->error . " | SQL: $sqlHS");

                // maHS (int), lopHocPhuTrach (string) - we assume $lop is maLop int; convert to tenLop? 
                // In your qlhocsinh.php the popup used lopHocPhuTrach = tenLop; but here select provides maLop.
                // We'll store the class name (tenLop) in lopHocPhuTrach to be consistent with other files.
                // So we need to lookup tenLop from lophoc by maLop.
                $tenLop = null;
                $q = $conn->prepare("SELECT tenLop FROM lophoc WHERE maLop = ?");
                if ($q) {
                    $q->bind_param("i", $lop);
                    $q->execute();
                    $r = $q->get_result()->fetch_assoc();
                    $tenLop = $r ? $r['tenLop'] : "Chưa cập nhật";
                    $q->close();
                } else {
                    // nếu không tìm được tên lớp, vẫn dùng maLop as string
                    $tenLop = (string)$lop;
                }

                $stmtHS->bind_param("isssss", $userID_new, $tenLop, $chucVu, $namHoc, $hocKy, $trangThai);
                if (!$stmtHS->execute()) throw new Exception("Lỗi execute hocsinh: " . $stmtHS->error);

                // 3) Gán vào hocsinh_lophoc (xóa mapping cũ rồi insert mới)
                $sqlDelete = "DELETE FROM hocsinh_lophoc WHERE maHS = ?";
                $stmtDel = $conn->prepare($sqlDelete);
                if (!$stmtDel) throw new Exception("Lỗi prepare xóa hocsinh_lophoc: " . $conn->error . " | SQL: $sqlDelete");
                $stmtDel->bind_param("i", $userID_new);
                if (!$stmtDel->execute()) throw new Exception("Lỗi execute xóa hocsinh_lophoc: " . $stmtDel->error);
                $stmtDel->close();

                $sqlInsertLH = "INSERT INTO hocsinh_lophoc (maHS, maLop) VALUES (?, ?)";
                $stmtLH = $conn->prepare($sqlInsertLH);
                if (!$stmtLH) throw new Exception("Lỗi prepare hocsinh_lophoc: " . $conn->error . " | SQL: $sqlInsertLH");

                // maHS int, maLop int
                // $lop bên form đang gửi maLop (int) — nếu nó là string, cast về int trước
                $maLopInt = (int)$lop;
                $stmtLH->bind_param("ii", $userID_new, $maLopInt);
                if (!$stmtLH->execute()) throw new Exception("Lỗi execute hocsinh_lophoc: " . $stmtLH->error);
                $stmtLH->close();

                // commit
                $conn->commit();
                header("Location: hocsinh.php?success=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $msg = "❌ Lỗi khi thêm học sinh: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .container {
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #0b1e6b;
            margin-bottom: 25px;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        label {
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            display: block;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .full {
            grid-column: span 2;
        }

        .radio-group {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .buttons {
            margin-top: 20px;
            text-align: right;
        }

        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-cancel {
            background: #fff;
            border: 1px solid #0b1e6b;
            color: #0b1e6b;
        }

        .btn-add {
            background: #0b1e6b;
            color: #fff;
            margin-left: 10px;
        }

        .msg {
            text-align: center;
            margin-bottom: 15px;
            font-weight: 500;
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
                    <li class="active" onclick="window.location.href='../pagegiaovien/hocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pagegiaovien/lophoc.php'"><i class="fa-solid fa-school"></i> Lớp học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pagegiaovien/tlhoctap.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
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
        <div class="container">
            <h1>THÊM HỌC SINH</h1>

            <?php if ($msg): ?>
                <div class="msg"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form method="post">
                <div>
                    <label>Năm học:</label>
                    <input type="text" name="namHoc" value="<?= $namHocDefault ?>" readonly>
                </div>
                <div>
                    <label>Học kỳ:</label>
                    <input type="text" name="hocKy" value="<?= $hocKyDefault ?>" readonly>
                </div>
                <div class="full">
                    <label>Họ và Tên:</label>
                    <input type="text" name="hoTen" required>
                </div>
                <div>
                    <label>Email:</label>
                    <input type="email" name="email">
                </div>
                <div>
                    <label>Số điện thoại:</label>
                    <input type="text" name="sdt" required>
                </div>
                <div>
                    <label>Giới tính:</label>
                    <select name="gioiTinh">
                        <option value="Nam">Nam</option>
                        <option value="Nữ">Nữ</option>
                    </select>
                </div>
                <div>
                    <label>Chức vụ:</label>
                    <input type="text" name="chucVu" placeholder="VD: Lớp trưởng, Bí thư...">
                </div>
                <div>
                    <label>Lớp:</label>
                    <select name="lop" required>
                        <?php foreach ($lops as $lop): ?>
                            <option value="<?= htmlspecialchars($lop['maLop']) ?>"><?= htmlspecialchars($lop['tenLop']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="full">
                    <label>Trạng thái:</label>
                    <div class="radio-group">
                        <label><input type="radio" name="trangThai" value="active" checked> Đang học</label>
                        <label><input type="radio" name="trangThai" value="inactive"> Đã nghỉ</label>
                    </div>
                </div>
                <div class="buttons full">
                    <button type="button" class="btn btn-cancel" onclick="window.location='hocsinh.php'">Hủy</button>
                    <button type="submit" class="btn btn-add"><i class="fa-solid fa-plus"></i> Thêm mới</button>
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