<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

$maHS = $_GET['maHS'] ?? '';
$maMon = $_GET['mon'] ?? '';
$maGV = $_SESSION["userID"];


if ($maHS == '' || $maMon == '') {
    die("Thiếu thông tin học sinh hoặc môn học.");
}
// ==== Lấy thông tin giáo viên ====
$sqlGV = "SELECT u.hoVaTen 
           FROM user u 
           JOIN giaovien g ON u.userID = g.maGV 
           WHERE g.maGV = '$maGV' 
           LIMIT 1";
$resultGV = $conn->query($sqlGV);
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];


// ====== Lấy thông tin học sinh & môn học ======
$sqlInfo = "
SELECT u.hoVaTen AS tenHS, m.tenMonHoc 
FROM user u 
JOIN monhoc m ON m.maMonHoc = ? 
WHERE u.userID = ?";
$stmt = $conn->prepare($sqlInfo);
$stmt->bind_param("ii", $maMon, $maHS);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();
$tenHS = $info['tenHS'] ?? '';
$tenMonHoc = $info['tenMonHoc'] ?? '';

// ====== Cập nhật khi nhấn Lưu ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cacLoai = [
        'hk1_mieng',
        'hk1_1tiet',
        'hk1_thiGK',
        'hk1_thiCK',
        'hk2_mieng',
        'hk2_1tiet',
        'hk2_thiGK',
        'hk2_thiCK'
    ];

    $sqlUp = "REPLACE INTO diemso (maHS, maMonHoc, loaiDiem, diem, ngayCapNhat)
              VALUES (?, ?, ?, ?, NOW())";
    $stmt2 = $conn->prepare($sqlUp);
    if (!$stmt2) {
        die("❌ Lỗi prepare: " . $conn->error);
    }


    $count = 0;
    foreach ($cacLoai as $loai) {
        if (isset($_POST[$loai]) && $_POST[$loai] !== '') {
            $diem = floatval($_POST[$loai]);
            if ($diem >= 0 && $diem <= 10) {
                $stmt2->bind_param("iisd", $maHS, $maMon, $loai, $diem);
                $stmt2->execute();
                $count++;
            }
        }
    }

    echo "<script>alert('✅ Đã cập nhật điểm thành công!'); window.location='diemso.php';</script>";
    exit();
}


// ====== Lấy điểm hiện tại ======
$sqlD = "SELECT loaiDiem, diem FROM diemso WHERE maHS = ? AND maMonHoc = ?";
$stmt3 = $conn->prepare($sqlD);
$stmt3->bind_param("ii", $maHS, $maMon);
$stmt3->execute();
$dsDiem = $stmt3->get_result();
$diemArr = [];
while ($r = $dsDiem->fetch_assoc()) {
    $diemArr[$r['loaiDiem']] = $r['diem'];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
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
        }

        .filter-box {
            display: flex;
            justify-content: flex-start;
            gap: 40px;
            background: #fff;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-box label {
            font-weight: 600;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f4f4f4;
        }

        form {
            margin-bottom: 20px;
        }

        input,
        select,
        textarea {
            margin: 5px 0;
            padding: 5px;
        }

        button {
            padding: 6px 12px;
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
        <div class="form-box">
            <h2>CẬP NHẬT ĐIỂM</h2>
            <div class="info">
                <div>HỌ TÊN HỌC SINH: <?= htmlspecialchars($tenHS) ?></div>
                <div>MÃ HỌC SINH: K<?= str_pad($maHS, 7, '0', STR_PAD_LEFT) ?></div>
            </div>
            <form method="POST">
                <div class="section">
                    <div class="section-title">HỌC KỲ I</div>
                    <div class="row">
                        <label>ĐIỂM MIỆNG: <input type="number" name="hk1_mieng" step="0.1" value="<?= $data['hk1_mieng'] ?? '' ?>"></label>
                        <label>ĐIỂM THI GK: <input type="number" name="hk1_thiGK" step="0.1" value="<?= $data['hk1_thiGK'] ?? '' ?>"></label>
                    </div>
                    <div class="row">
                        <label>ĐIỂM 1 TIẾT: <input type="number" name="hk1_1tiet" step="0.1" value="<?= $data['hk1_1tiet'] ?? '' ?>"></label>
                        <label>ĐIỂM THI CK: <input type="number" name="hk1_thiCK" step="0.1" value="<?= $data['hk1_thiCK'] ?? '' ?>"></label>
                    </div>
                </div>

                <div class="section" style="margin-top:25px;">
                    <div class="section-title">HỌC KỲ II</div>
                    <div class="row">
                        <label>ĐIỂM MIỆNG: <input type="number" name="hk2_mieng" step="0.1" value="<?= $data['hk2_mieng'] ?? '' ?>"></label>
                        <label>ĐIỂM THI GK: <input type="number" name="hk2_thiGK" step="0.1" value="<?= $data['hk2_thiGK'] ?? '' ?>"></label>
                    </div>
                    <div class="row">
                        <label>ĐIỂM 1 TIẾT: <input type="number" name="hk2_1tiet" step="0.1" value="<?= $data['hk2_1tiet'] ?? '' ?>"></label>
                        <label>ĐIỂM THI CK: <input type="number" name="hk2_thiCK" step="0.1" value="<?= $data['hk2_thiCK'] ?? '' ?>"></label>
                    </div>
                </div>

                <div class="buttons">
                    <button type="button" class="cancel" onclick="window.location.href='diemso.php'">HỦY</button>
                    <button type="submit" class="save">CẬP NHẬT</button>
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