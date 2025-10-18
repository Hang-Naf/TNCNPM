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

// ==== Lấy danh sách phân công ====
$sql = "
    SELECT 
        lm.id,
        l.maLop, l.tenLop,
        LEFT(l.tenLop, 2) AS khoi,
        m.maMonHoc, m.tenMonHoc,
        g.maGV, g.boMon, u.hoVaTen AS tenGV
    FROM lophoc_monhoc lm
    LEFT JOIN lophoc l ON lm.maLop = l.maLop
    LEFT JOIN monhoc m ON lm.maMonHoc = m.maMonHoc
    LEFT JOIN giaovien g ON lm.maGV = g.maGV
    LEFT JOIN user u ON g.maGV = u.userID
";
$result = $conn->query($sql);

// ==== Lấy danh sách lớp ====
$lops = $conn->query("SELECT maLop, tenLop FROM lophoc");

// ==== Lấy danh sách môn học ====
$mons = $conn->query("SELECT maMonHoc, tenMonHoc FROM monhoc");

// ==== Lấy danh sách giáo viên ====
$giaoviens = $conn->query("
    SELECT g.maGV, g.boMon, u.hoVaTen 
    FROM giaovien g
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien'
");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Phân công giảng dạy</title>
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
            width: 170px;
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
            text-align: center;
        }

        th {
            background: #f1f3f9;
        }

        .actions i {
            cursor: pointer;
            margin-right: 10px;
        }

        .popup-bg {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            justify-content: center;
            align-items: center;
        }

        .popup {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 420px;
        }

        .popup select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .popup-buttons {
            text-align: right;
        }

        .save-btn {
            background: #0b1e6b;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
        }

        .cancel-btn {
            background: #ccc;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
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
                    <li  onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                    <li  onclick="window.location.href='../pages/qltsukien.php'"><i class="fa-solid fa-calendar-days"></i> Sự kiện</li>
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

        <h1>PHÂN CÔNG GIẢNG DẠY</h1>
        <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm Phân Công</button>

        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>LỚP</th>
                    <th>KHỐI</th>
                    <th>MÔN HỌC</th>
                    <th>GIÁO VIÊN</th>
                    <th>TÁC VỤ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): $stt = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['id'] ?>"
                            data-malop="<?= $row['maLop'] ?>"
                            data-mamonhoc="<?= $row['maMonHoc'] ?>"
                            data-magv="<?= $row['maGV'] ?>">
                            <td><?= $stt++ ?></td>
                            <td><?= htmlspecialchars($row['tenLop']) ?></td>
                            <td><?= htmlspecialchars($row['khoi']) ?></td>
                            <td><?= htmlspecialchars($row['tenMonHoc']) ?></td>
                            <td><?= htmlspecialchars($row['tenGV'] ?? '—') ?></td>
                            <td class="actions">
                                <i class="fa-solid fa-pen edit-btn"></i>
                                <i class="fa-solid fa-trash delete-btn"></i>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Không có dữ liệu</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Popup thêm -->
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <h3>Thêm phân công</h3>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <select name="maLop" required>
                    <option value="">--Chọn lớp--</option>
                    <?php $lops->data_seek(0);
                    while ($lop = $lops->fetch_assoc()): ?>
                        <option value="<?= $lop['maLop'] ?>"><?= htmlspecialchars($lop['tenLop']) ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="maMonHoc" id="addMon" required>
                    <option value="">--Chọn môn học--</option>
                    <?php $mons->data_seek(0);
                    while ($m = $mons->fetch_assoc()): ?>
                        <option value="<?= $m['maMonHoc'] ?>" data-bomon="<?= htmlspecialchars($m['tenMonHoc']) ?>">
                            <?= htmlspecialchars($m['tenMonHoc']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="maGV" id="addGV">
                    <option value="">--Chọn giáo viên phụ trách--</option>
                    <?php $giaoviens->data_seek(0);
                    while ($gv = $giaoviens->fetch_assoc()): ?>
                        <option value="<?= $gv['maGV'] ?>" data-bomon="<?= htmlspecialchars($gv['boMon']) ?>">
                            <?= htmlspecialchars($gv['hoVaTen']) ?> (<?= htmlspecialchars($gv['boMon']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <div class="popup-buttons">
                    <button type="button" class="cancel-btn" onclick="closePopup('addPopup')">Hủy</button>
                    <button type="submit" class="save-btn">Thêm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup sửa -->
    <div class="popup-bg" id="editPopup">
        <div class="popup">
            <h3>Chỉnh sửa phân công</h3>
            <form id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editId">
                <select name="maLop" id="editLop" required>
                    <option value="">--Chọn lớp--</option>
                    <?php $lops->data_seek(0);
                    while ($lop = $lops->fetch_assoc()): ?>
                        <option value="<?= $lop['maLop'] ?>"><?= htmlspecialchars($lop['tenLop']) ?></option>
                    <?php endwhile; ?>
                </select>
                <select name="maMonHoc" id="editMon" required>
                    <option value="">--Chọn môn học--</option>
                    <?php $mons->data_seek(0);
                    while ($m = $mons->fetch_assoc()): ?>
                        <option value="<?= $m['maMonHoc'] ?>" data-bomon="<?= htmlspecialchars($m['tenMonHoc']) ?>">
                            <?= htmlspecialchars($m['tenMonHoc']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="maGV" id="editGV">
                    <option value="">--Chọn giáo viên phụ trách--</option>
                    <?php $giaoviens->data_seek(0);
                    while ($gv = $giaoviens->fetch_assoc()): ?>
                        <option value="<?= $gv['maGV'] ?>" data-bomon="<?= htmlspecialchars($gv['boMon']) ?>">
                            <?= htmlspecialchars($gv['hoVaTen']) ?> (<?= htmlspecialchars($gv['boMon']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <div class="popup-buttons">
                    <button type="button" class="cancel-btn" onclick="closePopup('editPopup')">Hủy</button>
                    <button type="submit" class="save-btn">Lưu</button>
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

            monSelect.addEventListener("change", () => {
                const monName = monSelect.options[monSelect.selectedIndex]?.dataset.bomon?.trim().toLowerCase() || "";
                for (let opt of gvSelect.options) {
                    const gvMon = opt.dataset.bomon?.trim().toLowerCase() || "";
                    opt.style.display = (monName === "" || gvMon === "" || gvMon === monName) ? "block" : "none";
                }
                gvSelect.value = "";
            });
        }

        filterGVByMon("addMon", "addGV");
        filterGVByMon("editMon", "editGV");

        // === Mở/đóng popup ===
        function showAddPopup() {
            document.getElementById("addPopup").style.display = "flex";
        }

        function closePopup(id) {
            document.getElementById(id).style.display = "none";
        }

        // === Thêm phân công ===
        document.getElementById("addForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target).entries());
            const res = await fetch(api, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            alert(json.message || json.error);
            if (json.message) location.reload();
        });

        // === Mở popup sửa ===
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");
                document.getElementById("editId").value = tr.dataset.id;
                document.getElementById("editLop").value = tr.dataset.malop;
                document.getElementById("editMon").value = tr.dataset.mamonhoc;
                document.getElementById("editGV").value = tr.dataset.magv || "";
                document.getElementById("editPopup").style.display = "flex";
            }
        });

        // === Lưu chỉnh sửa ===
        document.getElementById("editForm").addEventListener("submit", async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target).entries());
            const res = await fetch(api, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            });
            const json = await res.json();
            alert(json.message || json.error);
            if (json.message) location.reload();
        });

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
        
        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "../dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>