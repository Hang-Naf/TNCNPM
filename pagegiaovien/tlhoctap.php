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
           WHERE g.maGV = '$maGV' 
           LIMIT 1";
$resultGV = $conn->query($sqlGV);
$gv = $resultGV && $resultGV->num_rows > 0 ? $resultGV->fetch_assoc() : ['hoVaTen' => 'Giáo viên'];

// ==== Lấy danh sách môn học GV được phân công ====
$sqlMon = "SELECT DISTINCT m.maMonHoc, m.tenMonHoc
            FROM lophoc_monhoc lm
            JOIN monhoc m ON lm.maMonHoc = m.maMonHoc
            WHERE lm.maGV = '$maGV'";
$monhocList = $conn->query($sqlMon);

// ==== Lấy danh sách lớp GV dạy ====
$sqlLop = "SELECT DISTINCT l.maLop, l.tenLop
           FROM lophoc_monhoc lm
           JOIN lophoc l ON lm.maLop = l.maLop
           WHERE lm.maGV = '$maGV'";
$lopList = $conn->query($sqlLop);

// ==== Lọc tài liệu ====
$cond = "WHERE t.maGV = '$maGV'";
if (!empty($_GET['maLop'])) {
    $maLop = intval($_GET['maLop']);
    $cond .= " AND t.maLop = $maLop";
}
if (!empty($_GET['maMonHoc'])) {
    $maMonHoc = intval($_GET['maMonHoc']);
    $cond .= " AND t.maMonHoc = $maMonHoc";
}

$sql = "SELECT t.maTL, t.tieuDe, t.noiDung, t.trangThai, 
               m.tenMonHoc, u.hoVaTen AS nguoiTao, l.tenLop
        FROM tailieu t
        LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
        LEFT JOIN lophoc l ON t.maLop = l.maLop
        LEFT JOIN user u ON t.maGV = u.userID
        $cond
        ORDER BY t.maTL DESC";
$ds = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tài liệu học tập</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <link rel="stylesheet" href="../form.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #fff;
            margin: 0;
            padding: 0;
        }

        .content-area {
            padding: 20px;
        }

        .filter-box {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            background: #fff;
        }

        .btn-add {
            background: #0b3364;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-left: auto;
        }

        .btn-add:hover {
            background: #124b8a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #fafafa;
        }

        .actions i {
            cursor: pointer;
            margin: 0 5px;
        }

        .actions i.fa-pen {
            color: #0b3364;
        }

        .actions i.fa-trash {
            color: #d9534f;
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
                    <input type="text" placeholder="Tìm kiếm..." class="searchb">
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
            <h1>DANH SÁCH TÀI LIỆU</h1>

            <form method="GET" class="filter-box">
                <div>
                    <label>Lớp:</label>
                    <select name="maLop" onchange="this.form.submit()">
                        <option value="">Tất cả lớp</option>
                        <?php while ($lop = $lopList->fetch_assoc()): ?>
                            <option value="<?= $lop['maLop'] ?>" <?= (isset($_GET['maLop']) && $_GET['maLop'] == $lop['maLop']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lop['tenLop']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label>Môn:</label>
                    <select name="" id=""></select>
                </div>

                <button type="button" class="btn-add" onclick="window.location='themtlht.php'">
                    + Thêm tài liệu
                </button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>TIÊU ĐỀ</th>
                        <th>MÔ TẢ</th>
                        <th>LỚP</th>
                        <th>NGƯỜI TẠO</th>
                        <th>TRẠNG THÁI</th>
                        <th>TÁC VỤ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($ds && $ds->num_rows > 0):
                        $stt = 1;
                        while ($r = $ds->fetch_assoc()):
                    ?>
                            <tr>
                                <td><?= $stt ?></td>
                                <td><?= htmlspecialchars($r['tieuDe']) ?></td>
                                <td><?= htmlspecialchars($r['noiDung']) ?></td>
                                <td><?= htmlspecialchars($r['tenLop']) ?></td>
                                <td><?= htmlspecialchars($r['nguoiTao']) ?></td>
                                <td><?= htmlspecialchars($r['trangThai']) ?></td>
                                <td class="actions">
                                    <i class="fa-solid fa-pen"
                                        title="Sửa"
                                        onclick="window.location.href='suatlht.php?maTL=<?= urlencode($r['maTL']) ?>'"></i>
                                    <i class="fa-solid fa-trash"
                                        title="Xóa"
                                        onclick="if(confirm('Bạn có chắc muốn xóa tài liệu này không?')) window.location.href='xoatlht.php?maTL=<?= urlencode($r['maTL']) ?>'"></i>
                                </td>
                            </tr>
                        <?php
                            $stt++;
                        endwhile;
                    else:
                        ?>
                        <tr>
                            <td colspan="8" style="text-align:center;padding:20px;">Không có tài liệu nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function chonMon() {
            const maMon = document.getElementById("maMonHoc").value;
            if (maMon) {
                window.location = "tlhoctap.php?maMonHoc=" + maMon;
            }
        }
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