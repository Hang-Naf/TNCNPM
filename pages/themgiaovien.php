<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    header("Location: ../dangnhap.php");
    exit();
}

// Lấy danh sách môn học
$monhoc_rs = $conn->query("SELECT maMonHoc, tenMonHoc FROM monhoc");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $hoVaTen = $_POST['hoVaTen'];
    $email = $_POST['email'];
    $sdt = $_POST['sdt'];
    $gioiTinh = $_POST['gioiTinh'];
    $boMon = $_POST['boMon'];
    $trinhDo = $_POST['trinhDo'];
    $phongBan = $_POST['phongBan'];
    $namHoc = $_POST['namHoc'];
    $hocKy = $_POST['hocKy'];
    $trangThai = $_POST['trangThai'];

    // Tự động set mật khẩu và mã hóa
    $matKhauMacDinh = '12345678';
    $matKhauHash = password_hash($matKhauMacDinh, PASSWORD_BCRYPT);

    // Use prepared statements for user insertion
    $stmtUser = $conn->prepare("INSERT INTO user (hoVaTen, email, sdt, gioiTinh, matKhau, vaiTro) VALUES (?, ?, ?, ?, ?, 'GiaoVien')");
    if ($stmtUser) {
        $stmtUser->bind_param('sssss', $hoVaTen, $email, $sdt, $gioiTinh, $matKhauHash);
        if ($stmtUser->execute()) {
            $maGV = $stmtUser->insert_id;
            $stmtUser->close();

            // Check if giaovien record already exists
            $checkStmt = $conn->prepare("SELECT maGV FROM giaovien WHERE maGV = ?");
            $checkStmt->bind_param('i', $maGV);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $exists = ($checkResult->num_rows > 0);
            $checkStmt->close();

            if ($exists) {
                // Update existing giaovien record
                $stmtGV = $conn->prepare("UPDATE giaovien SET boMon=?, trinhDo=?, phongBan=?, namHoc=?, hocKy=?, trangThai=? WHERE maGV=?");
                if ($stmtGV) {
                    $stmtGV->bind_param('ssssssi', $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai, $maGV);
                    if ($stmtGV->execute()) {
                        $stmtGV->close();
                        echo "<script>alert('Cập nhật giáo viên thành công'); window.location.href='qlgiaovien.php';</script>";
                        exit();
                    } else {
                        echo "Lỗi cập nhật giáo viên: " . $stmtGV->error;
                        $stmtGV->close();
                    }
                } else {
                    echo "Lỗi prepare update: " . $conn->error;
                }
            } else {
                // Insert new giaovien record
                $stmtGV = $conn->prepare("INSERT INTO giaovien (maGV, boMon, trinhDo, anhDaiDien, phongBan, namHoc, hocKy, trangThai) VALUES (?, ?, ?, 'Chưa cập nhật', ?, ?, ?, ?)");
                if ($stmtGV) {
                    $stmtGV->bind_param('isssssss', $maGV, $boMon, $trinhDo, $phongBan, $namHoc, $hocKy, $trangThai);
                    if ($stmtGV->execute()) {
                        $stmtGV->close();
                        echo "<script>alert('Thêm giáo viên thành công'); window.location.href='qlgiaovien.php';</script>";
                        exit();
                    } else {
                        echo "Lỗi thêm chi tiết giáo viên: " . $stmtGV->error;
                        $stmtGV->close();
                    }
                } else {
                    echo "Lỗi prepare giaovien: " . $conn->error;
                }
            }
        } else {
            echo "Lỗi thêm user: " . $stmtUser->error;
            $stmtUser->close();
        }
    } else {
        echo "Lỗi prepare user: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI";
            background: #f8f9fb;
        }

        .header {
            padding: 10px 25px;
        }

        .main-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 500px;
            margin: 50px auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: 600;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .buttons {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 10px;
        }

        .cancel-btn {
            background: #ccc;
        }

        .save-btn {
            background: #0b3364;
            color: white;
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
        <h2 style="margin-left: 30px;">THÊM GIÁO VIÊN</h2>
        <form method="post" style="margin-left: 50px; margin-right: 50px;">
            <label>Họ và tên:</label>
            <input type="text" name="hoVaTen" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Số điện thoại:</label>
            <input type="text" name="sdt" required pattern="^0[0-9]{9}$">

            <label>Giới tính:</label>
            <select name="gioiTinh">
                <option value="Nam">Nam</option>
                <option value="Nữ">Nữ</option>
            </select>

            <label>Bộ môn:</label>
            <select name="boMon" required>
                <option value="">-- Chọn bộ môn --</option>
                <?php while ($mh = $monhoc_rs->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($mh['tenMonHoc']) ?>"><?= htmlspecialchars($mh['tenMonHoc']) ?></option>
                <?php endwhile; ?>
            </select>

            <label>Trình độ:</label>
            <input type="text" name="trinhDo">

            <label>Phòng ban:</label>
            <input type="text" name="phongBan">

            <label>Năm học:</label>
            <input type="text" name="namHoc" id="addNamHoc" readonly>

            <label>Học kỳ:</label>
            <input type="text" name="hocKy" id="addHocKy" readonly>

            <label>Trạng thái:</label>
            <select name="trangThai">
                <option value="active">Đang công tác</option>
                <option value="inactive">Nghỉ</option>
            </select>

            <div class="buttons">
                <button type="button" class="cancel-btn" onclick="window.location.href='qlgiaovien.php'">HỦY</button>
                <button type="submit" class="save-btn">THÊM</button>
            </div>
        </form>
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

        function getHocKyVaNamHoc() {
            const now = new Date();
            const thang = now.getMonth() + 1; // getMonth() trả 0-11
            const nam = now.getFullYear();
            let hocKy, namHoc;

            // Quy ước:
            // HK1: Tháng 8 -> 12
            // HK2: Tháng 1 -> 5
            // Hè: Tháng 6 -> 7
            if (thang >= 8 && thang <= 12) {
                hocKy = "HK1";
                namHoc = `${nam}-${nam + 1}`;
            } else if (thang >= 1 && thang <= 5) {
                hocKy = "HK2";
                namHoc = `${nam - 1}-${nam}`;
            } else {
                hocKy = "Hè";
                namHoc = `${nam - 1}-${nam}`;
            }

            return {
                hocKy,
                namHoc
            };
        }

        document.addEventListener("DOMContentLoaded", function() {
            const {
                hocKy,
                namHoc
            } = getHocKyVaNamHoc();
            document.getElementById("addNamHoc").value = namHoc;
            document.getElementById("addHocKy").value = hocKy;
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