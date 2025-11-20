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

$maHS = $_GET['maHS'] ?? '';
$tenMon = $_GET['mon'] ?? '';

if ($maHS === '' || $tenMon === '') {
    die("Thiếu thông tin học sinh hoặc môn học.");
}

// Lấy mã môn học
$getMon = $conn->prepare("SELECT maMonHoc FROM monhoc WHERE tenMonHoc = ?");
$getMon->bind_param("s", $tenMon);
$getMon->execute();
$getMon->bind_result($maMonHoc);
$getMon->fetch();
$getMon->close();

if (!$maMonHoc) die("Không tìm thấy môn học trong CSDL!");

// Lấy điểm học sinh
$sql = "SELECT loaiDiem, diem
        FROM diemso
        WHERE maHS=? AND maMonHoc=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $maHS, $maMonHoc);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[$row['loaiDiem']] = $row['diem'];
}
$stmt->close();

// Lấy tên học sinh
$sqlHS = "SELECT u.hoVaTen FROM hocsinh h JOIN user u ON h.maHS = u.userID WHERE h.maHS=?";
$stmt = $conn->prepare($sqlHS);
$stmt->bind_param("i", $maHS);
$stmt->execute();
$stmt->bind_result($tenHS);
$stmt->fetch();
$stmt->close();

// Tính điểm trung bình
function diemTrungBinh($hk, $data)
{
    $weights = [
        'mieng' => 1,
        '1tiet' => 2,
        'thiGK' => 2,
        'thiCK' => 3
    ];
    $sum = 0;
    $total = 0;
    foreach ($weights as $loai => $w) {
        $key = $hk . '_' . $loai;
        if (isset($data[$key]) && is_numeric($data[$key])) {
            $sum += $data[$key] * $w;
            $total += $w;
        }
    }
    return $total ? round($sum / $total, 1) : '-';
}

$diemHK1 = diemTrungBinh('hk1', $data);
$diemHK2 = diemTrungBinh('hk2', $data);
$diemTB = (is_numeric($diemHK1) && is_numeric($diemHK2)) ? round(($diemHK1 + $diemHK2) / 2, 1) : '-';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết điểm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .header {
            padding: 10px 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #f4f4f4;
        }

        input {
            width: 60px;
            text-align: center;
            border: none;
            background: #f9f9f9;
        }

        .info {
            margin-bottom: 15px;
        }

        .section-title {
            font-weight: bold;
            margin-top: 15px;
        }

        .buttons {
            margin-top: 15px;
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
                    <li onclick="window.location.href='../pages/qlgiaovien.php'"><i class="fa-solid fa-chalkboard-user"></i> Giáo viên</li>
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
                    <li class="active" onclick="window.location.href='../pages/qldiemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
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
        <div class="info">
            <div>HỌ TÊN HỌC SINH: <?= htmlspecialchars($tenHS) ?></div>
            <div>MÃ HỌC SINH: K<?= str_pad($maHS, 7, '0', STR_PAD_LEFT) ?></div>
            <div>MÔN HỌC: <?= htmlspecialchars($tenMon) ?></div>
        </div>

        <div class="section">
            <div class="section-title">HỌC KỲ I</div>
            <table>
                <tr>
                    <th>Miệng</th>
                    <th>1 Tiết</th>
                    <th>Thi GK</th>
                    <th>Thi CK</th>
                    <th>Trung bình HK I</th>
                </tr>
                <tr>
                    <td><input type="text" value="<?= $data['hk1_mieng'] ?? '' ?>" readonly></td>
                    <td><input type="text" value="<?= $data['hk1_1tiet'] ?? '' ?>" readonly></td>
                    <td><input type="text" value="<?= $data['hk1_thiGK'] ?? '' ?>" readonly></td>
                    <td><input type="text" value="<?= $data['hk1_thiCK'] ?? '' ?>" readonly></td>
                    <td><input type="text" value="<?= $diemHK1 ?>" readonly></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">HỌC KỲ II</div>
            <table>
                <tr>
                    <th>Miệng</th>
                    <th>1 Tiết</th>
                    <th>Thi GK</th>
                    <th>Thi CK</th>
                    <th>Trung bình HK II</th>
                </tr>
                <tr>
                    <td><input type="text" value="<?= $data['hk2_mieng'] ?? '' ?>" readonly></td>
                    <td><input type="text" value="<?= $data['hk2_1tiet'] ?? '' ?>" readonly></td>
                    <td><input type="text" value="<?= $data['hk2_thiGK'] ?? '' ?>" readonly></td>
                    <td><input type="text" value="<?= $data['hk2_thiCK'] ?? '' ?>" readonly></td>
                    <td><input type="text" value="<?= $diemHK2 ?>" readonly></td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Trung bình cả năm</div>
            <table>
                <tr>
                    <th>Trung bình môn</th>
                </tr>
                <tr>
                    <td><input type="text" value="<?= $diemTB ?>" readonly></td>
                </tr>
            </table>
        </div>

        <div class="buttons">
            <button onclick="if (document.referrer) { window.history.back(); } else { window.location.href='qldiemso.php'; }">Quay lại</button>
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