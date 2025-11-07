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

// ========= LỌC DỮ LIỆU =========
$lopChon = $_GET['lop'] ?? '';
$monChon = $_GET['mon'] ?? '';

$dsLop = $conn->query("SELECT DISTINCT lopHocPhuTrach AS tenLop FROM hocsinh WHERE lopHocPhuTrach IS NOT NULL");
$dsMon = $conn->query("SELECT * FROM monhoc ORDER BY tenMonHoc ASC");

// ========= TRUY VẤN DỮ LIỆU CHÍNH =========
$sql = "
SELECT 
    u.userID AS maHS,
    u.hoVaTen,
    h.lopHocPhuTrach,
    m.tenMonHoc,

    -- HỌC KỲ I
    ROUND(
        SUM(CASE 
            WHEN d.loaiDiem = 'hk1_mieng' THEN d.diem * 1
            WHEN d.loaiDiem = 'hk1_1tiet' THEN d.diem * 2
            WHEN d.loaiDiem = 'hk1_thiGK' THEN d.diem * 2
            WHEN d.loaiDiem = 'hk1_thiCK' THEN d.diem * 3
            ELSE 0 END) /
        NULLIF(SUM(CASE 
            WHEN d.loaiDiem = 'hk1_mieng' THEN 1
            WHEN d.loaiDiem = 'hk1_1tiet' THEN 2
            WHEN d.loaiDiem = 'hk1_thiGK' THEN 2
            WHEN d.loaiDiem = 'hk1_thiCK' THEN 3
            ELSE 0 END), 0), 1
    ) AS diemHK1,

    -- HỌC KỲ II
    ROUND(
        SUM(CASE 
            WHEN d.loaiDiem = 'hk2_mieng' THEN d.diem * 1
            WHEN d.loaiDiem = 'hk2_1tiet' THEN d.diem * 2
            WHEN d.loaiDiem = 'hk2_thiGK' THEN d.diem * 2
            WHEN d.loaiDiem = 'hk2_thiCK' THEN d.diem * 3
            ELSE 0 END) /
        NULLIF(SUM(CASE 
            WHEN d.loaiDiem = 'hk2_mieng' THEN 1
            WHEN d.loaiDiem = 'hk2_1tiet' THEN 2
            WHEN d.loaiDiem = 'hk2_thiGK' THEN 2
            WHEN d.loaiDiem = 'hk2_thiCK' THEN 3
            ELSE 0 END), 0), 1
    ) AS diemHK2

FROM hocsinh h
JOIN user u ON h.maHS = u.userID
LEFT JOIN diemso d ON d.maHS = h.maHS
LEFT JOIN monhoc m ON d.maMonHoc = m.maMonHoc
WHERE 1=1
";

if ($lopChon != '') {
    $sql .= " AND h.lopHocPhuTrach = '" . $conn->real_escape_string($lopChon) . "'";
}
if ($monChon != '') {
    $sql .= " AND m.tenMonHoc = '" . $conn->real_escape_string($monChon) . "'";
}

$sql .= " GROUP BY u.userID, m.tenMonHoc ORDER BY h.lopHocPhuTrach, u.hoVaTen ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý điểm số</title>
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

        <h2>BẢNG ĐIỂM</h2>
        <button class="add-btn" onclick="window.location.href='../pages/nhapdiem.php'"><i class="fa-solid fa-plus"></i> Thêm </button>

        <form method="GET" class="filter-box">
            <div>
                <label for="lop"><strong>Lớp:</strong></label><br>
                <select name="lop" id="lop" onchange="this.form.submit()">
                    <option value="">Tất cả lớp</option>
                    <?php while ($l = $dsLop->fetch_assoc()) {
                        $sel = ($l['tenLop'] == $lopChon) ? "selected" : "";
                        echo "<option value='{$l['tenLop']}' $sel>{$l['tenLop']}</option>";
                    } ?>
                </select>
            </div>

            <div>
                <label for="mon"><strong>Môn:</strong></label><br>
                <select name="mon" id="mon" onchange="this.form.submit()">
                    <option value="">Tất cả môn</option>
                    <?php while ($m = $dsMon->fetch_assoc()) {
                        $sel = ($m['tenMonHoc'] == $monChon) ? "selected" : "";
                        echo "<option value='{$m['tenMonHoc']}' $sel>{$m['tenMonHoc']}</option>";
                    } ?>
                </select>
            </div>
        </form>

        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>MÃ HS</th>
                    <th>HỌ TÊN</th>
                    <th>MÔN HỌC</th>
                    <th>ĐIỂM HK I</th>
                    <th>ĐIỂM HK II</th>
                    <th>TRUNG BÌNH MÔN</th>
                    <th>TÁC VỤ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    $stt = 1;
                    while ($row = $result->fetch_assoc()) {
                        $tb = "-";
                        if (is_numeric($row['diemHK1']) || is_numeric($row['diemHK2'])) {
                            $tong = 0;
                            $dem = 0;
                            foreach (['diemHK1', 'diemHK2'] as $c) {
                                if (is_numeric($row[$c])) {
                                    $tong += $row[$c];
                                    $dem++;
                                }
                            }
                            $tb = $dem ? round($tong / $dem, 1) : "-";
                        }

                        $hrefSua = "../pages/suadiem.php?maHS=" . urlencode($row['maHS']) . "&mon=" . urlencode($row['tenMonHoc']);
                        $hrefXoa = "../pages/xoadiem.php?maHS=" . urlencode($row['maHS']) . "&mon=" . urlencode($row['tenMonHoc']);

                        echo "
                        <tr>
                            <td>{$stt}</td>
                            <td>K" . str_pad($row['maHS'], 7, '0', STR_PAD_LEFT) . "</td>
                            <td>" . htmlspecialchars($row['hoVaTen']) . "</td>
                            <td>" . htmlspecialchars($row['tenMonHoc'] ?? '-') . "</td>
                            <td>" . ($row['diemHK1'] ?? '-') . "</td>
                            <td>" . ($row['diemHK2'] ?? '-') . "</td>
                            <td><strong>$tb</strong></td>
                            <td>
                                <a href='{$hrefSua}' title='Sửa điểm'><i class='fa-solid fa-pen-to-square' style='color:#0b3364;'></i></a>
                                &nbsp;
                                <a href='{$hrefXoa}' onclick=\"return confirm('Bạn có chắc muốn xóa toàn bộ điểm của học sinh này trong môn " . htmlspecialchars($row['tenMonHoc']) . " không?');\" title='Xóa điểm'>
                                    <i class='fa-solid fa-trash' style='color:black;'></i>
                                </a>
                            </td>
                        </tr>";
                        $stt++;
                    }
                } else {
                    echo "<tr><td colspan='10'>Không có dữ liệu phù hợp.</td></tr>";
                }
                ?>
            </tbody>
        </table>
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