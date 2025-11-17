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

// === Lấy danh sách học sinh và môn học ===
$dsHS = $conn->query("SELECT u.userID AS maHS, u.hoVaTen, h.lopHocPhuTrach 
                      FROM hocsinh h 
                      JOIN user u ON h.maHS = u.userID
                      ORDER BY h.lopHocPhuTrach, u.hoVaTen ASC");
$dsMon = $conn->query("SELECT * FROM monhoc ORDER BY tenMonHoc ASC");

// === Khi submit form ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $maHS = $_POST["maHS"];
    $maMon = $_POST["maMonHoc"];
    $diem = [
        'hk1_mieng' => $_POST['hk1_mieng'] ?? null,
        'hk1_1tiet' => $_POST['hk1_1tiet'] ?? null,
        'hk1_thiGK' => $_POST['hk1_thiGK'] ?? null,
        'hk1_thiCK' => $_POST['hk1_thiCK'] ?? null,
        'hk2_mieng' => $_POST['hk2_mieng'] ?? null,
        'hk2_1tiet' => $_POST['hk2_1tiet'] ?? null,
        'hk2_thiGK' => $_POST['hk2_thiGK'] ?? null,
        'hk2_thiCK' => $_POST['hk2_thiCK'] ?? null,
    ];

    foreach ($diem as $loai => $value) {
        if ($value !== '' && is_numeric($value)) {

            // Không cho điểm âm hoặc quá 10
            if ($value < 0 || $value > 10) {
                echo "<script>
                alert('Điểm phải nằm trong khoảng 0 - 10!');
                window.history.back();
            </script>";
                exit();
            }

            $sql = "INSERT INTO diemso (maHS, maMonHoc, loaiDiem, diem, ngayCapNhat) VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisd", $maHS, $maMon, $loai, $value);
            if (!$stmt->execute()) {
                echo "Lỗi SQL: " . $stmt->error;
            }
        }
    }

    echo "<script>
        alert('Thêm điểm thành công!');
        window.location.href = 'qldiemso.php';
    </script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm điểm học sinh</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 40px;
        }

        .main-content {
            margin-left: 220px;
            margin-top: -40px;
            margin-right: -50px;
            width: calc(100% - 200px);
            display: flex;
            flex-direction: column;
            height: 100vh;
        }

        h2 {
            text-align: center;
            color: #ffffffff;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            color: #0b3364;
            margin-bottom: 20px;
        }
        
        form{
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            /* max-width: 800px;
            margin: 0 auto 40px auto */
        }

        select,
        input {
            width: 100%;
            padding: 6px 10px;
            margin: 5px 0 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        label {
            font-weight: bold;
        }

        .hk-section {
            background: #f0f4ff;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .hk-section h3 {
            margin: 0 0 10px;
            background: #0b3364;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 30px;
        }

        button {
            border: none;
            border-radius: 6px;
            padding: 10px 20px;
            margin-top: 20px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-submit {
            background: #28a745;
            color: white;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #ddd;
            color: black;
            margin-left: 10px;
        }

        .btn-cancel:hover {
            background: #bbb;
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
        <h1>THÊM ĐIỂM HỌC SINH</h1>
        <form method="POST">
            <label for="maHS">Chọn học sinh:</label>
            <select name="maHS" required>
                <option value="">-- Chọn học sinh --</option>
                <?php while ($hs = $dsHS->fetch_assoc()): ?>
                    <option value="<?= $hs['maHS'] ?>">
                        <?= htmlspecialchars($hs['hoVaTen']) ?> (<?= htmlspecialchars($hs['lopHocPhuTrach']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="maMonHoc">Chọn môn học:</label>
            <select name="maMonHoc" required>
                <option value="">-- Chọn môn học --</option>
                <?php while ($m = $dsMon->fetch_assoc()): ?>
                    <option value="<?= $m['maMonHoc'] ?>"><?= htmlspecialchars($m['tenMonHoc']) ?></option>
                <?php endwhile; ?>
            </select>

            <div class="hk-section">
                <h3>HỌC KỲ I</h3>
                <div class="grid">
                    <div>
                        <label>Điểm miệng:</label>
                        <input type="number" step="0.1" name="hk1_mieng">
                    </div>
                    <div>
                        <label>Điểm 1 tiết:</label>
                        <input type="number" step="0.1" name="hk1_1tiet">
                    </div>
                    <div>
                        <label>Điểm thi GK:</label>
                        <input type="number" step="0.1" name="hk1_thiGK">
                    </div>
                    <div>
                        <label>Điểm thi CK:</label>
                        <input type="number" step="0.1" name="hk1_thiCK">
                    </div>
                </div>
            </div>

            <div class="hk-section">
                <h3>HỌC KỲ II</h3>
                <div class="grid">
                    <div>
                        <label>Điểm miệng:</label>
                        <input type="number" step="0.1" name="hk2_mieng">
                    </div>
                    <div>
                        <label>Điểm 1 tiết:</label>
                        <input type="number" step="0.1" name="hk2_1tiet">
                    </div>
                    <div>
                        <label>Điểm thi GK:</label>
                        <input type="number" step="0.1" name="hk2_thiGK">
                    </div>
                    <div>
                        <label>Điểm thi CK:</label>
                        <input type="number" step="0.1" name="hk2_thiCK">
                    </div>
                </div>
            </div>

            <div style="text-align:center;">
                <button type="submit" class="btn-submit">➕ Thêm điểm</button>
                <button type="button" class="btn-cancel" onclick="window.location.href='qldiemso.php'">Hủy</button>
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

        // === TÌM KIẾM ĐIỂM SỐ ===
        const searchInput = document.getElementById("searchBox");
        const searchIcon = document.querySelector(".search-box i");

        function timKiemBangDiem() {
            const keyword = searchInput.value.trim().toLowerCase();
            const rows = document.querySelectorAll("tbody tr");
            let found = 0;

            rows.forEach(row => {
                const maHS = row.children[1]?.innerText.toLowerCase() || "";
                const hoTen = row.children[2]?.innerText.toLowerCase() || "";
                const monHoc = row.children[3]?.innerText.toLowerCase() || "";

                if (
                    maHS.includes(keyword) ||
                    hoTen.includes(keyword) ||
                    monHoc.includes(keyword)
                ) {
                    row.style.display = "";
                    found++;
                } else {
                    row.style.display = "none";
                }
            });

            // Xóa dòng "Không tìm thấy" cũ nếu có
            const oldRow = document.getElementById("noResultRow");
            if (oldRow) oldRow.remove();

            // Nếu không có kết quả
            if (found === 0) {
                const tbody = document.querySelector("tbody");
                const tr = document.createElement("tr");
                tr.id = "noResultRow";
                tr.innerHTML = `
            <td colspan="8" style="text-align:center;color:gray;">Không tìm thấy kết quả phù hợp</td>
        `;
                tbody.appendChild(tr);
            }
        }

        // Tự động lọc khi gõ
        searchInput.addEventListener("input", timKiemBangDiem);

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