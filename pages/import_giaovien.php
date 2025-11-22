<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

use PhpOffice\PhpSpreadsheet\IOFactory;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    header("Location: ../dangnhap.php");
    exit();
}

require '../vendor/autoload.php';

$errorList = [];
$count = 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["excelFile"])) {
    $fileTmp = $_FILES["excelFile"]["tmp_name"];

    try {
        $spreadsheet = IOFactory::load($fileTmp);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
    } catch (Exception $e) {
        $errorList[] = "File Excel không hợp lệ!";
        $rows = [];
    }

    if (empty($rows)) {
        $errorList[] = "File Excel trống!";
    } else {

        $header = array_map('trim', $rows[0]);
        $headerLower = array_map('mb_strtolower', $header);

        $requiredHeaders = ['họ và tên', 'giới tính', 'email', 'sđt', 'mật khẩu'];

        foreach ($requiredHeaders as $col) {
            if (!in_array(mb_strtolower($col), $headerLower)) {
                $errorList[] = "File Excel thiếu cột bắt buộc: $col";
            }
        }

        if (!empty($errorList)) {
            $_SESSION["import_errors"] = $errorList;
            $_SESSION["import_success"] = 0;
            echo "<script>alert('File Excel thiếu cột bắt buộc!'); window.history.back();</script>";
            exit();
        }

        array_shift($rows); // bỏ dòng tiêu đề
        if (count($rows) > 100) $rows = array_slice($rows, 0, 100);

        foreach ($rows as $index => $r) {
            $hoVaTen = isset($r[0]) ? trim($r[0]) : '';
            $gioiTinh = isset($r[1]) ? trim($r[1]) : '';
            $email = isset($r[2]) ? strtolower(trim($r[2])) : '';
            $sdt = isset($r[3]) ? trim($r[3]) : '';
            $matKhau = isset($r[4]) ? trim($r[4]) : '';
            $boMon = isset($r[5]) ? trim($r[5]) : '';
            $trinhDo = isset($r[6]) ? trim($r[6]) : '';
            $phongBan = isset($r[7]) ? trim($r[7]) : '';
            $namHoc = isset($r[8]) ? trim($r[8]) : '';
            $hocKy = isset($r[9]) ? trim($r[9]) : '';
            $trangThai = 'active';

            if (!$hoVaTen || !$email || !$matKhau) {
                $errorList[] = "Dòng " . ($index + 2) . ": Thiếu dữ liệu bắt buộc (Họ và tên, Email, Mật khẩu)";
                continue;
            }

            $chkStmt = $conn->prepare("SELECT userID FROM user WHERE email = ?");
            $chkStmt->bind_param("s", $email);
            $chkStmt->execute();
            $chkResult = $chkStmt->get_result();
            if ($chkResult->num_rows > 0) {
                $errorList[] = "Dòng " . ($index + 2) . ": Email '$email' đã tồn tại";
                $chkStmt->close();
                continue;
            }
            $chkStmt->close();

            $hash = password_hash($matKhau, PASSWORD_BCRYPT);

            $stmtUser = $conn->prepare("INSERT INTO user (hoVaTen, gioiTinh, email, sdt, matKhau, vaiTro) VALUES (?, ?, ?, ?, ?, 'GiaoVien')");
            if (!$stmtUser) {
                $errorList[] = "Dòng " . ($index + 2) . ": Lỗi prepare user - " . $conn->error;
                continue;
            }
            $stmtUser->bind_param("sssss", $hoVaTen, $gioiTinh, $email, $sdt, $hash);
            if (!$stmtUser->execute()) {
                $errorList[] = "Dòng " . ($index + 2) . ": Lỗi thêm user - " . $stmtUser->error;
                $stmtUser->close();
                continue;
            }

            $maGV = $stmtUser->insert_id;
            $stmtUser->close();

            $checkGVStmt = $conn->prepare("SELECT maGV FROM giaovien WHERE maGV = ?");
            $checkGVStmt->bind_param("i", $maGV);
            $checkGVStmt->execute();
            $checkGVResult = $checkGVStmt->get_result();
            $gvExists = ($checkGVResult->num_rows > 0);
            $checkGVStmt->close();

            if ($gvExists) {
                $stmtGV = $conn->prepare("UPDATE giaovien SET boMon=?, trinhDo=?, phongBan=?, namHoc=?, hocKy=?, trangThai=? WHERE maGV=?");
                $stmtGV->bind_param("ssssssi", $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai, $maGV);
            } else {
                $stmtGV = $conn->prepare("INSERT INTO giaovien (maGV, boMon, trinhDo, phongBan, namHoc, hocKy, trangThai) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmtGV->bind_param("issssss", $maGV, $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai);
            }
            $stmtGV->execute();
            $stmtGV->close();

            write_log($conn, $_SESSION["userID"], "Import", "Thêm giáo viên: $hoVaTen", "Info");
            $count++;
        }
    }

    $_SESSION["import_errors"] = $errorList;
    $_SESSION["import_success"] = $count;

    // Nếu có dữ liệu hợp lệ → báo thành công + chuyển trang
    if ($count > 0) {
        echo "<script>alert('Import thành công $count giáo viên!'); window.location.href='qlgiaovien.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Import Giáo Viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        .header {
            padding: 10px 25px;
        }

        .import-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            width: 60%;
            margin: 50px auto;
        }

        h2 {
            text-align: center;
            color: #ffffffff;
            margin-bottom: 30px;
        }

        h1 {
            text-align: center;
            color: #0b3364;
            margin-bottom: 30px;
        }

        input[type="file"] {
            margin-bottom: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-upload {
            background: #0b3364;
            color: white;
        }

        .btn-sample {
            background: #28a745;
            color: white;
            margin-left: 10px;
        }

        .btn-back {
            background: #ddd;
            color: black;
            margin-top: 30px;
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
                    <li onclick="window.location.href='../index.php'"><i class="fa-solid fa-house"></i> Dashboard</li>
                    <li class="active" onclick="window.location.href='../pages/qlgiaovien.php'"><i class="fa-solid fa-chalkboard-user"></i> Giáo viên</li>
                    <li onclick="window.location.href='../pages/qlhocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pages/qllophoc.php'"><i class="fa-solid fa-school"></i> Lớp học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học</li>
                    <li onclick="window.location.href='../pages/qltailieu.php'"><i class="fa-solid fa-file-lines"></i> Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlchuyencan.php'"><i class="fa-solid fa-check"></i> Chuyên cần</li>
                    <li onclick="window.location.href='../pages/qldiemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý thông tin</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý tài khoản</div>
                <ul>
                    <li onclick="window.location.href='../pages/phanconggiangday.php'"><i class="fa-solid fa-users"></i> Phân công giảng dạy</li>
                    <li onclick="window.location.href='../pages/qlphanquyen.php'"><i class="fa-solid fa-user-shield"></i> Phân quyền</li>
                </ul>
            </div>
        </nav>
    </aside>
    <div class="main-content">
        <header class="header">
            <div class="left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchBox" placeholder="Tìm kiếm...">
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
                        <div id="xemChiTietThongBao"
                            style="text-align:center;padding:10px;background:#f0f2f6;cursor:pointer;font-size:13px;font-weight:600;color:#0b3364;border-top:1px solid #ddd;">
                            🔍 Xem chi tiết thông báo
                        </div>
                    </div>
                </div>

                <div class="user-info" onclick="toggleUserMenu()">
                    <i class="fa-solid fa-user"></i>
                    <span>Quản trị viên</span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>
        <h1>Import Giáo Viên</h1>
        <div class="import-box">
            <form method="POST" enctype="multipart/form-data">
                <label>
                    <h2 style="color: #0b3364; text-align: left;">Chọn file Excel (.xlsx, .xls, .csv): </h2>
                </label>
                <input type="file" name="excelFile" accept=".xlsx,.xls,.csv" required><br>

                <a href="mau_import_giaovien.xlsx" class="btn btn-sample" style="text-decoration: none;" download>File mẫu</a>
                <button type="submit" class="btn btn-upload" style="width: 104px; height: 41px; margin: 20px;">Import</button>
            </form>
        </div>
        <?php
        // Hiển thị lỗi/ thông báo bằng alert (không render trực tiếp trên trang).
        if (!empty($errorList)) {
            // Sử dụng json_encode để escape chuỗi JS an toàn (bao gồm newline và dấu nháy)
            $jsMsg = "Có lỗi xảy ra:\n" . implode("\n", $errorList);
            echo "<script>alert(" . json_encode($jsMsg) . ");</script>";
        }
        ?>

        <br>
        <div style="text-align:center;">
            <a href="qlgiaovien.php" class="btn btn-back" style="text-decoration: none;">⬅ Quay lại</a>
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

        // Khi click vào "Xem chi tiết thông báo"
        document.getElementById("xemChiTietThongBao").addEventListener("click", function() {
            window.location.href = "../pages/qlthongbao.php";
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