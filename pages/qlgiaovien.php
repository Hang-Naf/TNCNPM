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

// ==== Lấy danh sách giáo viên ====
$sql = "
    SELECT 
        g.maGV, u.hoVaTen, u.gioiTinh, u.email, u.sdt,
        g.boMon, g.trinhDo, g.phongBan, g.namHoc, g.hocKy, g.trangThai
    FROM giaovien g
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien'
";
$result = $conn->query($sql);

// ==== Lấy danh sách môn học ====
$monhoc_rs = $conn->query("SELECT maMonHoc, tenMonHoc FROM monhoc");
$monhoc_list = [];
while ($mh = $monhoc_rs->fetch_assoc()) {
    $monhoc_list[] = $mh;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý giáo viên</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <link rel="stylesheet" href="popup.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .container {
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .add-btn {
            background: #0b1e6b;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            width: 150px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 20px;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f1f3f9;
        }

        .status.active {
            color: green;
            font-weight: 500;
        }

        .status.inactive {
            color: gray;
        }

        .actions i {
            cursor: pointer;
            margin-right: 10px;
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
                    <li class="active" onclick="window.location.href='../pages/qlgiaovien.php'"><i
                            class="fa-solid fa-chalkboard-user"></i> Giáo viên</li>
                    <li onclick="window.location.href='../pages/qlhocsinh.php'"><i
                            class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li onclick="window.location.href='../pages/qllophoc.php'"><i class="fa-solid fa-school"></i> Lớp
                        học</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý dữ liệu</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học
                    </li>
                    <li onclick="window.location.href='../pages/qltailieu.php'"><i class="fa-solid fa-file-lines"></i>
                        Tài liệu</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý đánh giá</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlchuyencan.php'"><i class="fa-solid fa-check"></i>
                        Chuyên cần</li>
                    <li onclick="window.location.href='../pages/qldiemso.php'"><i
                            class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý thông tin</div>
                <ul>
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông
                        báo</li>
                    <li onclick="window.location.href='../pages/qlsukien.php'"><i class="fa-solid fa-calendar-days"></i>
                        Sự kiện</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý tài khoản</div>
                <ul>
                    <li onclick="window.location.href='../pages/phanconggiangday.php'"><i class="fa-solid fa-users"></i>
                        Phân công giảng dạy</li>
                    <li onclick="window.location.href='../pages/qlphanquyen.php'"><i
                            class="fa-solid fa-user-shield"></i> Phân quyền</li>
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
                        <li><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <h1>QUẢN LÝ GIÁO VIÊN</h1>
        <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm Giáo Viên</button>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>STT</th>
                    <th>MÃ GV</th>
                    <th>HỌ TÊN</th>
                    <th>GIỚI TÍNH</th>
                    <th>EMAIL</th>
                    <th>SDT</th>
                    <th>BỘ MÔN</th>
                    <th>TRÌNH ĐỘ</th>
                    <th>PHÒNG BAN</th>
                    <th>NĂM HỌC</th>
                    <th>HỌC KỲ</th>
                    <th>TRẠNG THÁI</th>
                    <th>TÁC VỤ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0):
                    $stt = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['maGV'] ?>">
                            <td><input type="checkbox"></td>
                            <td><?= $stt++ ?></td>
                            <td><?= $row['maGV'] ?></td>
                            <td><?= htmlspecialchars($row['hoVaTen']) ?></td>
                            <td><?= htmlspecialchars($row['gioiTinh']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['sdt']) ?></td>
                            <td><?= htmlspecialchars($row['boMon']) ?></td>
                            <td><?= htmlspecialchars($row['trinhDo']) ?></td>
                            <td><?= htmlspecialchars($row['phongBan']) ?></td>
                            <td><?= htmlspecialchars($row['namHoc']) ?></td>
                            <td><?= htmlspecialchars($row['hocKy']) ?></td>
                            <td><span class="status <?= $row['trangThai'] === 'active' ? 'active' : 'inactive' ?>">
                                    <?= $row['trangThai'] === 'active' ? 'Hoạt động' : 'Tạm dừng' ?>
                                </span></td>
                            <td class="actions">
                                <i class="fa-solid fa-pen edit-btn"></i>
                                <i class="fa-solid fa-trash delete-btn"></i>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="14" style="text-align:center;">Không có dữ liệu</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById("bellIcon").addEventListener("click", function () {
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
        document.addEventListener("click", function (e) {
            const dropdown = document.getElementById("notificationDropdown");
            const bell = document.getElementById("bellIcon");
            if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });

        const apiGiaoVien = "../src/giaovien.php";
        let currentId = null;

        function closePopup(id) {
            const popup = document.getElementById(id);

        }

        // === Xử lý nhấn nút sửa ===
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");

                // Lấy thông tin từ hàng
                const data = {
                    maGV: tr.children[2].innerText.trim(),
                    hoVaTen: tr.children[3].innerText.trim(),
                    gioiTinh: tr.children[4].innerText.trim(),
                    email: tr.children[5].innerText.trim(),
                    sdt: tr.children[6].innerText.trim(),
                    boMon: tr.children[7].innerText.trim(),
                    trinhDo: tr.children[8].innerText.trim(),
                    phongBan: tr.children[9].innerText.trim(),
                    namHoc: tr.children[10].innerText.trim(),
                    hocKy: tr.children[11].innerText.trim(),
                    trangThai: tr.children[12].innerText.includes("Hoạt") ? "active" : "inactive"
                };

                // Mã hóa dữ liệu lên URL
                const params = new URLSearchParams(data).toString();
                window.location.href = `themgiaovien.php?edit=1&${params}`;
            }
        });

        // Xóa giáo viên
        document.addEventListener("click", async (e) => {
            if (e.target.classList.contains("delete-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;
                if (confirm("Bạn có chắc muốn xóa giáo viên này?")) {
                    const res = await fetch(apiGiaoVien, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: "delete",
                            userId: id
                        })
                    });
                    const json = await res.json();
                    alert(json.message || json.error);
                    if (json.message) location.reload();
                }
            }
        });

        // === Xác định học kỳ và năm học theo thời gian hiện tại ===
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

        // Gán tự động khi mở form thêm
        function showAddPopup() {
            window.location = 'themgiaovien.php';
        }

        function toggleUserMenu() {
            const menu = document.getElementById("userMenu");
            menu.style.display = (menu.style.display === "block") ? "none" : "block";
        }

        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>