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

// ==== Lấy id từ URL ====
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    die("ID không hợp lệ");
}

// ==== Lấy dữ liệu phân công theo id ====
$sql = "
    SELECT lm.id, lm.maLop, lm.maMonHoc, lm.maGV,
           l.tenLop, m.tenMonHoc, u.hoVaTen AS tenGV
    FROM lophoc_monhoc lm
    LEFT JOIN lophoc l ON lm.maLop = l.maLop
    LEFT JOIN monhoc m ON lm.maMonHoc = m.maMonHoc
    LEFT JOIN giaovien g ON lm.maGV = g.maGV
    LEFT JOIN user u ON g.maGV = u.userID
    WHERE lm.id = $id
";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) {
    die("Không tìm thấy phân công");
}
$data = $result->fetch_assoc();

// ==== Lấy danh sách lớp, môn, giáo viên ====
$lops = $conn->query("SELECT maLop, tenLop FROM lophoc");
$mons = $conn->query("SELECT maMonHoc, tenMonHoc FROM monhoc");
$giaoviens = $conn->query("
    SELECT g.maGV, g.boMon, u.hoVaTen 
    FROM giaovien g
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien' AND g.trangThai='active'
");

// ==== Xử lý khi submit form ====
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $maLop = $_POST['maLop'];
    $maMonHoc = $_POST['maMonHoc'];
    $maGV = $_POST['maGV'];

    $updateSql = "UPDATE lophoc_monhoc 
                  SET maLop='$maLop', maMonHoc='$maMonHoc', maGV='$maGV'
                  WHERE id=$id";
    if ($conn->query($updateSql)) {
        echo "<script>alert('Cập nhật thành công'); window.location.href='phanconggiangday.php';</script>";
        exit();
    } else {
        echo "Lỗi cập nhật: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Sửa phân công giảng dạy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .container {
            padding: 20px;
        }

        .form-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 500px;
            margin: 50px auto;
        }


        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #0b3364;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .buttons {
            display: flex;
            justify-content: flex-end;
        }

        .buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-left: 20px;
        }

        .cancel-btn {
            background: #ccc;
            color: #333;
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
            <h2>CHỈNH SỬA PHÂN CÔNG</h2>
            <form method="post">
                <label for="maLop">LỚP:</label>
                <select name="maLop" required>
                    <option value="">Chọn lớp...</option>
                    <?php while ($lop = $lops->fetch_assoc()): ?>
                        <option value="<?= $lop['maLop'] ?>" <?= $lop['maLop'] == $data['maLop'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lop['tenLop']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="maMonHoc">MÔN:</label>
                <select name="maMonHoc" id="editMon" required>
                    <option value="">Chọn môn...</option>
                    <?php while ($m = $mons->fetch_assoc()): ?>
                        <option
                            value="<?= $m['maMonHoc'] ?>"
                            data-bomon="<?= htmlspecialchars($m['tenMonHoc']) ?>"
                            <?= $m['maMonHoc'] == $data['maMonHoc'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['tenMonHoc']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="maGV">GIÁO VIÊN:</label>
                <select name="maGV" id="editGV">
                    <option value="">Chọn giáo viên...</option>
                    <?php while ($gv = $giaoviens->fetch_assoc()): ?>
                        <option
                            value="<?= $gv['maGV'] ?>"
                            data-bomon="<?= htmlspecialchars($gv['boMon']) ?>"
                            <?= $gv['maGV'] == $data['maGV'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gv['hoVaTen']) ?> (<?= htmlspecialchars($gv['boMon']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>

                <div class="buttons">
                    <a href="phanconggiangday.php"><button type="button" class="cancel-btn">HỦY</button></a>
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

        const api = "../src/phancong.php";

        // === Lọc giáo viên theo môn học ===
        function filterGVByMon(monSelectId, gvSelectId) {
            const monSelect = document.getElementById(monSelectId);
            const gvSelect = document.getElementById(gvSelectId);

            if (!monSelect || !gvSelect) return;

            const applyFilter = () => {
                const monName = monSelect.options[monSelect.selectedIndex]?.dataset.bomon?.trim().toLowerCase() || "";
                for (let opt of gvSelect.options) {
                    const gvMon = opt.dataset.bomon?.trim().toLowerCase() || "";
                    opt.style.display = (monName === "" || gvMon === "" || gvMon === monName) ? "block" : "none";
                }
                // Nếu giáo viên hiện tại không khớp, reset về rỗng
                const currentGV = gvSelect.value;
                if (currentGV) {
                    const selectedOpt = gvSelect.querySelector(`option[value="${currentGV}"]`);
                    if (selectedOpt && selectedOpt.style.display === "none") {
                        gvSelect.value = "";
                    }
                }
            };

            // Áp dụng ngay khi load (lọc theo môn đã chọn sẵn)
            applyFilter();

            // Lọc khi đổi môn
            monSelect.addEventListener("change", applyFilter);
        }

        document.addEventListener("DOMContentLoaded", function() {
            filterGVByMon("editMon", "editGV");
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Chỉ gọi cho form sửa trên trang này
            filterGVByMon("editMon", "editGV");
        });


        // === Mở/đóng popup ===
        function showAddPopup() {
            document.getElementById("addPopup").style.display = "flex";
        }

        function closePopup(id) {
            document.getElementById(id).style.display = "none";
        }


        // === Xóa phân công ===
        document.addEventListener("click", async (e) => {
            if (e.target.classList.contains("delete-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;
                if (confirm("Bạn có chắc muốn xóa phân công này?")) {
                    const res = await fetch(api, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: "delete",
                            id
                        })
                    });
                    const json = await res.json();
                    alert(json.message || json.error);
                    if (json.message) location.reload();
                }
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