<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION["userID"])) {
    header("Location: dangnhap.php");
    exit();
}

// Chỉ cho phép Admin truy cập
if ($_SESSION["vaiTro"] !== "Admin") {
    // Nếu không phải admin thì quay lại trang đăng nhập
    session_destroy();
    header("Location: dangnhap.php");
    exit();
}

// Lưu thông tin hiển thị
$hoTen = $_SESSION["hoVaTen"];
$vaiTro = $_SESSION["vaiTro"];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Hệ thống quản lý - Viện đào tạo ABC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="content.css">
</head>
<style>
    body {
        font-family: "Segoe UI", Arial, sans-serif;
        margin: 0;
        color: #1a1a1a;
    }

    h1 {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 24px;
    }

    .stats {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        flex: 1;
        min-width: 250px;
        padding: 20px;
    }

    .card h3 {
        font-size: 14px;
        color: #444;
        margin: 0 0 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
    }

    .card .number {
        font-size: 32px;
        font-weight: 700;
        color: #0b3364;
    }

    .card p {
        font-size: 13px;
        color: #777;
        margin: 6px 0 0;
    }

    .bottom {
        display: flex;
        margin-top: 30px;
        gap: 20px;
        flex-wrap: wrap;
    }

    .recent,
    .quick {
        flex: 1;
        min-width: 300px;
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .recent h2,
    .quick h2 {
        font-size: 16px;
        margin-bottom: 16px;
        font-weight: 700;
        color: #1a1a1a;
    }

    .activity {
        margin-bottom: 14px;
    }

    .activity p {
        margin: 0;
        font-size: 14px;
        color: #333;
    }

    .activity span {
        font-size: 12px;
        color: #777;
    }

    .quick {
        background: #0b3364;
        color: #fff;
    }

    .quick h2 {
        color: #fff;
    }

    .quick button {
        width: 100%;
        background: #fff;
        border: none;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 10px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        color: #0b3364;
        transition: background 0.2s;
    }

    .quick button:hover {
        background: #f0f2f6;
    }

    @media (max-width: 768px) {

        .stats,
        .bottom {
            flex-direction: column;
        }
    }
</style>

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
                    <li class="active" onclick="window.location.href='index.php'"><i class="fa-solid fa-house"></i> Dashboard</li>
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
                    <li onclick="window.location.href='../pages/qldiemso.php'"><i class="fa-solid fa-clipboard-list"></i> Điểm số</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý thông tin</div>
                <ul>
                    <li><i class="fa-solid fa-bell"></i> Thông báo</li>
                    <li><i class="fa-solid fa-calendar-days"></i> Sự kiện</li>
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
                        <li><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <?php
        include_once(__DIR__ . "../csdl/db.php");

        // Lấy 4 thông báo mới nhất
        $sql = "SELECT t.tieuDe, t.noiDung, t.ngayGui, u.hoVaTen 
          FROM thongbao t 
          LEFT JOIN user u ON t.nguoiGui = u.userID 
          ORDER BY t.ngayGui DESC 
          LIMIT 4";
        $result = $conn->query($sql);
        ?>

        <h1>TỔNG QUAN</h1>

        <div class="stats">
            <div class="card">
                <h3>TỔNG HỌC SINH <span>🎓</span></h3>
                <div class="number">
                    <?php
                    $rs = $conn->query("SELECT COUNT(*) AS total FROM user WHERE vaiTro='HocSinh'");
                    echo $rs->fetch_assoc()['total'];
                    ?>
                </div>
                <p>Học sinh hoạt động trong năm nay</p>
            </div>

            <div class="card">
                <h3>GIÁO VIÊN <span>👩‍🏫</span></h3>
                <div class="number">
                    <?php
                    $rs = $conn->query("SELECT COUNT(*) AS total FROM user WHERE vaiTro='GiaoVien'");
                    echo $rs->fetch_assoc()['total'];
                    ?>
                </div>
                <p>Cán bộ/giáo viên</p>
            </div>

            <div class="card">
                <h3>LỚP HỌC <span>🏫</span></h3>
                <div class="number">
                    <?php
                    $rs = $conn->query("SELECT COUNT(*) AS total FROM lophoc");
                    echo $rs->fetch_assoc()['total'];
                    ?>
                </div>
                <p>Lớp đang vận hành</p>
            </div>
        </div>

        <div class="bottom">
            <div class="recent">
                <h2>HOẠT ĐỘNG GẦN ĐÂY</h2>

                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="activity">
                            <p>📝 <strong><?= htmlspecialchars($row['tieuDe']) ?></strong><br>
                                <?= htmlspecialchars($row['noiDung']) ?>
                                <?= $row['hoVaTen'] ? ' - <em>' . htmlspecialchars($row['hoVaTen']) . '</em>' : '' ?>
                            </p>
                            <span><?= date('d/m/Y H:i', strtotime($row['ngayGui'])) ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Không có hoạt động gần đây.</p>
                <?php endif; ?>
            </div>

            <div class="quick">
                <h2>CÁC TÁC VỤ NHANH</h2>
                <button onclick="window.location.href='../pages/qlhocsinh.php'">🎓 Thêm học sinh</button>
                <button onclick="window.location.href='../pages/qlgiaovien.php'">👩‍🏫 Thêm giáo viên</button>
                <button onclick="window.location.href='../pages/qllophoc.php'">🏫 Tạo lớp học mới</button>
            </div>
        </div>
    </div>
    <script>
        document.getElementById("bellIcon").addEventListener("click", function() {
            const dropdown = document.getElementById("notificationDropdown");
            // Hiện/ẩn menu
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";

            // Gọi AJAX lấy thông báo
            fetch("get_thongbao.php")
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
                fetch("update_trangthai.php", {
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
                window.location.href = "dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>