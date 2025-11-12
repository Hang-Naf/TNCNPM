<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập & vai trò ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "GiaoVien") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$maGV = $_SESSION["userID"];
$maHS = $_GET['maHS'] ?? '';
$maMon = $_GET['mon'] ?? '';

if ($maHS === '' || $maMon === '') {
    die("❌ Thiếu thông tin học sinh hoặc môn học.");
}

// ====== Lấy thông tin giáo viên ======
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = ? LIMIT 1";
$stmtGV = $conn->prepare($sqlGV);
$stmtGV->bind_param("i", $maGV);
$stmtGV->execute();
$gv = $stmtGV->get_result()->fetch_assoc();
$stmtGV->close();

// ====== Lấy thông tin học sinh & môn học ======
$sqlInfo = "
SELECT u.hoVaTen AS tenHS, m.tenMonHoc
FROM user u
JOIN hocsinh h ON u.userID = h.maHS
JOIN monhoc m ON m.maMonHoc = ?
WHERE h.maHS = ?";
$stmt = $conn->prepare($sqlInfo);
$stmt->bind_param("ii", $maMon, $maHS);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$stmt->close();

$tenHS = $info['tenHS'] ?? 'Không xác định';
$tenMonHoc = $info['tenMonHoc'] ?? '';

// ====== Lấy điểm học sinh ======
$sqlDiem = "SELECT loaiDiem, diem FROM diemso WHERE maHS=? AND maMonHoc=?";
$stmtD = $conn->prepare($sqlDiem);
$stmtD->bind_param("ii", $maHS, $maMon);
$stmtD->execute();
$res = $stmtD->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
    $data[$row['loaiDiem']] = $row['diem'];
}
$stmtD->close();

// ====== Tính điểm trung bình ======
function tinhTB($hk, $data)
{
    $w = ['mieng' => 1, '1tiet' => 2, 'thiGK' => 2, 'thiCK' => 3];
    $tong = 0;
    $tongW = 0;
    foreach ($w as $k => $h) {
        $key = "{$hk}_{$k}";
        if (isset($data[$key]) && is_numeric($data[$key])) {
            $tong += $data[$key] * $h;
            $tongW += $h;
        }
    }
    return $tongW ? round($tong / $tongW, 1) : '-';
}

$diemHK1 = tinhTB('hk1', $data);
$diemHK2 = tinhTB('hk2', $data);
$diemTB = (is_numeric($diemHK1) && is_numeric($diemHK2)) ? round(($diemHK1 + $diemHK2) / 2, 1) : '-';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Chi tiết điểm học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: #fff;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #0b3364;
            color: #fff;
        }

        input {
            border: none;
            background: #f8f8f8;
            text-align: center;
            width: 60px;
        }

        .info {
            margin: 15px 0;
            font-weight: bold;
        }

        .btn-back {
            background: #0b3364;
            color: #fff;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }

        .btn-back:hover {
            background: #09264a;
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
                    <li class="active" onclick="window.location.href='../pagegiaovien/diemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
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
        <h2>CHI TIẾT ĐIỂM HỌC SINH</h2>

        <div class="info">
            <div>HỌC SINH: <?= htmlspecialchars($tenHS) ?></div>
            <div>MÃ HỌC SINH: K<?= str_pad($maHS, 7, '0', STR_PAD_LEFT) ?></div>
            <div>MÔN HỌC: <?= htmlspecialchars($tenMonHoc) ?></div>
        </div>

        <h3>HỌC KỲ I</h3>
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
                <td><strong><?= $diemHK1 ?></strong></td>
            </tr>
        </table>

        <h3>HỌC KỲ II</h3>
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
                <td><strong><?= $diemHK2 ?></strong></td>
            </tr>
        </table>

        <h3>Trung bình cả năm</h3>
        <table>
            <tr>
                <th>Trung bình môn</th>
            </tr>
            <tr>
                <td><strong><?= $diemTB ?></strong></td>
            </tr>
        </table>

        <button class="btn-back" onclick="window.history.back()">⬅ Quay lại</button>
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