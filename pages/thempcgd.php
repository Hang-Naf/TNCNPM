<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập và Phân quyền Admin ====
if (!isset($_SESSION["userID"]) || $_SESSION["vaiTro"] !== "Admin") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Lấy danh sách lớp ====
$lops = $conn->query("SELECT maLop, tenLop FROM lophoc");

// ==== Lấy danh sách môn học ====
$mons = $conn->query("SELECT maMonHoc, tenMonHoc FROM monhoc");

// ==== Lấy danh sách giáo viên đang hoạt động ====
$giaoviens = $conn->query("
    SELECT g.maGV, g.boMon, u.hoVaTen 
    FROM giaovien g
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien' AND g.trangThai='active'
");
if (!$giaoviens) {
    die("Lỗi truy vấn giáo viên: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Thêm Phân Công Giảng Dạy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
        }

        /* Container riêng cho form thêm */
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 500px;
            margin: 50px auto;
            /* Căn giữa */
        }

        h1 {
            font-size: 28px;
            margin-bottom: 30px;
            color: #0b1e6b;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            width: 120px;
            /* Căn chỉnh cột label */
            flex-shrink: 0;
        }

        .form-group select {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            appearance: none;
            background: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23000000%22%20d%3D%22M287%2064.9L146.2%20205.7%205.4%2064.9c-2.4-2.4-5.6-3.8-9-3.8-3.3%200-6.5%201.4-9%203.8-4.9%204.9-4.9%2012.8%200%2017.7l150%20150c2.4%202.4%205.6%203.8%209%203.8s6.6-1.4%209-3.8l150-150c4.9-4.9%204.9-12.8%200-17.7-2.5-2.5-5.7-3.9-9.1-3.9z%22%2F%3E%3C%2Fsvg%3E') no-repeat right 10px center;
            background-size: 10px;
        }

        .form-actions {
            text-align: right;
            margin-top: 40px;
        }

        .form-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin-left: 10px;
        }

        .cancel-btn {
            background: #e0e0e0;
            color: #333;
            border: 1px solid #ccc;
        }

        .save-btn {
            background: #0b1e6b;
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
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Quản lý tài khoản</div>
                <ul>
                    <li class="active" onclick="window.location.href='../pages/phanconggiangday.php'"><i class="fa-solid fa-users"></i> Phân công giảng dạy</li>
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
        <div class="form-container">
            <h1>THÊM PHÂN CÔNG</h1>
            <form id="addForm">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label for="maLop">LỚP:</label>
                    <select name="maLop" id="maLop" required>
                        <option value="">Chọn lớp...</option>
                        <?php while ($lop = $lops->fetch_assoc()): ?>
                            <option value="<?= $lop['maLop'] ?>"><?= htmlspecialchars($lop['tenLop']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="maMonHoc">MÔN:</label>
                    <select name="maMonHoc" id="maMonHoc" required>
                        <option value="">Chọn môn...</option>
                        <?php while ($m = $mons->fetch_assoc()): ?>
                            <option value="<?= $m['maMonHoc'] ?>" data-bomon="<?= htmlspecialchars($m['tenMonHoc']) ?>">
                                <?= htmlspecialchars($m['tenMonHoc']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="maGV">GIÁO VIÊN:</label>
                    <select name="maGV" id="maGV">
                        <option value="">Chọn giáo viên...</option>
                        <?php while ($gv = $giaoviens->fetch_assoc()): ?>
                            <option value="<?= $gv['maGV'] ?>" data-bomon="<?= htmlspecialchars($gv['boMon']) ?>">
                                <?= htmlspecialchars($gv['hoVaTen']) ?> (<?= htmlspecialchars($gv['boMon']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="window.location.href='phanconggiangday.php'">HỦY</button>
                    <button type="submit" class="save-btn">LƯU</button>
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


        // Đường dẫn API để xử lý thêm phân công
        const api = "../src/phancong.php";

        // === Lọc giáo viên theo môn học ===
        const monSelect = document.getElementById("maMonHoc");
        const gvSelect = document.getElementById("maGV");

        monSelect.addEventListener("change", () => {
            const monName = monSelect.options[monSelect.selectedIndex]?.dataset.bomon?.trim().toLowerCase() || "";

            // Hiện/Ẩn các giáo viên không thuộc bộ môn tương ứng
            for (let opt of gvSelect.options) {
                const gvMon = opt.dataset.bomon?.trim().toLowerCase() || "";
                // Hiển thị nếu là option "Chọn giáo viên..." HOẶC bộ môn của GV trùng với tên môn học
                opt.style.display = (opt.value === "" || gvMon === "" || gvMon === monName) ? "block" : "none";
            }
            gvSelect.value = ""; // Đặt lại giá trị sau khi lọc
        });

        // === Xử lý Submit Form Thêm ===
        document.getElementById("addForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target).entries());

            // Xác nhận trước khi lưu
            if (!confirm("Bạn có chắc muốn thêm phân công này?")) {
                return;
            }

            try {
                const res = await fetch(api, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(data)
                });
                const json = await res.json();

                alert(json.message || json.error);

                if (json.message) {
                    // Thêm thành công, chuyển về trang danh sách
                    window.location.href = "phanconggiangday.php";
                }
            } catch (error) {
                alert("Đã xảy ra lỗi trong quá trình kết nối API.");
                console.error("Lỗi:", error);
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

        // === TÌM KIẾM PHÂN CÔNG GIẢNG DẠY ===
        const searchInput = document.getElementById("searchBox");
        const searchIcon = document.querySelector(".search-box i");
        const tableRows = document.querySelectorAll("tbody tr");

        function thucHienTimKiem() {
            const keyword = searchInput.value.trim().toLowerCase();
            let found = 0;

            tableRows.forEach(row => {
                const tenLop = row.children[1]?.innerText.toLowerCase() || "";
                const tenMon = row.children[3]?.innerText.toLowerCase() || "";
                const tenGV = row.children[4]?.innerText.toLowerCase() || "";

                if (
                    tenLop.includes(keyword) ||
                    tenMon.includes(keyword) ||
                    tenGV.includes(keyword)
                ) {
                    row.style.display = "";
                    found++;
                } else {
                    row.style.display = "none";
                }
            });

            // Xóa dòng thông báo cũ nếu có
            const oldRow = document.getElementById("noResultRow");
            if (oldRow) oldRow.remove();

            // Nếu không có kết quả
            if (found === 0) {
                const tbody = document.querySelector("tbody");
                const tr = document.createElement("tr");
                tr.id = "noResultRow";
                tr.innerHTML = `<td colspan="6" style="text-align:center;color:gray;">Không tìm thấy phân công phù hợp.</td>`;
                tbody.appendChild(tr);
            }
        }

        // Kích hoạt tìm kiếm khi nhập, nhấn Enter hoặc bấm icon
        searchInput.addEventListener("input", thucHienTimKiem);
        searchInput.addEventListener("keypress", e => {
            if (e.key === "Enter") {
                e.preventDefault();
                thucHienTimKiem();
            }
        });
        searchIcon.addEventListener("click", thucHienTimKiem);

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