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

// ==== Cấu hình phân trang ====
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// ==== Lấy tổng số môn học ====
$countSql = "SELECT COUNT(*) as total FROM monhoc";
$countResult = $conn->query($countSql);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// ==== Lấy danh sách môn học và trưởng bộ môn ====
$sql = "
    SELECT 
        m.maMonHoc,
        m.tenMonHoc,
        m.moTa,
        m.hocKy,
        m.trangThai,
        m.namHoc,
        u.hoVaTen AS truongBoMon
    FROM monhoc m
    LEFT JOIN (
        SELECT gm.maMonHoc, gm.maGV
        FROM giaovien_monhoc gm
        GROUP BY gm.maMonHoc
    ) AS gvmh ON m.maMonHoc = gvmh.maMonHoc
    LEFT JOIN user u ON u.userID = gvmh.maGV
    ORDER BY m.maMonHoc DESC
    LIMIT $offset, $itemsPerPage
";
$result = $conn->query($sql);

// ==== Lấy danh sách giáo viên cho select ====
$gv_rs = $conn->query("
    SELECT g.maGV, u.hoVaTen, g.boMon 
    FROM giaovien g 
    JOIN user u ON g.maGV = u.userID
");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý môn học</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .header {
            padding: 10px 25px;
        }

        .container {
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
            margin-left: 50px;
        }

        .button-container {
            text-align: right;
            margin-right: 50px;
        }

        .hide-col {
            display: none;
        }

        .add-btn {
            background: #0b1e6b;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            /* display: flex; */
            align-items: center;
            gap: 6px;
            width: 150px;
        }

        table {
            width: 95%;
            border-collapse: collapse;
            background: white;
            margin: 40px 30px;
        }

        tr {
            text-align: center;
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

        .popup input,
        .popup select,
        .popup textarea {
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
                    <li class="active" onclick="window.location.href='../pages/qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học</li>
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
        <h1>QUẢN LÝ MÔN HỌC</h1>
        <div class="button-container">
            <button class="add-btn" onclick="window.location.href='themmonhoc.php'">
                <i class="fa-solid fa-plus"></i> Thêm Môn Học
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="checkAll"></th>
                    <th>STT</th>
                    <th>MÃ MH</th>
                    <th>TÊN MÔN HỌC</th>
                    <th>TRƯỞNG BỘ MÔN</th>
                    <th>GHI CHÚ</th>
                    <th class="hide-col">HỌC KỲ</th>
                    <th class="hide-col">NĂM HỌC</th>
                    <th>TRẠNG THÁI</th>
                    <th>TÁC VỤ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): $stt = $offset + 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['maMonHoc'] ?>">
                            <td><input type="checkbox" class="row-check"></td>
                            <td><?= $stt++ ?></td>
                            <td><?= $row['maMonHoc'] ?></td>
                            <td><?= htmlspecialchars($row['tenMonHoc']) ?></td>
                            <td><?= htmlspecialchars($row['truongBoMon'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['moTa']) ?></td>
                            <td class="hide-col"><?= htmlspecialchars($row['hocKy']) ?></td>
                            <td class="hide-col"><?= htmlspecialchars($row['namHoc']) ?></td>
                            <td><span class="status <?= $row['trangThai'] === 'Hoạt động' ? 'active' : 'inactive' ?>">
                                    <?= htmlspecialchars($row['trangThai']) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="suamonhoc.php?maMonHoc=<?= $row['maMonHoc'] ?>" style="text-decoration: none;">
                                    <i class="fa-solid fa-pen" style="color: black;"></i>
                                </a>
                                <i class="fa-solid fa-trash delete-btn"></i>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center;">Không có dữ liệu</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Thanh phân trang -->
        <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:-30px;">
            <span style="font-size:14px; color:#333;">Trang <?= $page ?>/<?= max(1, $totalPages) ?> (Tổng: <?= $totalItems ?> môn học)</span>
            <div style="display:flex; gap:8px; align-items:center;">
                <?php if ($page > 1): ?>
                    <a href="?page=1" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">⏮ Đầu</a>
                    <a href="?page=<?= $page - 1 ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">◀ Trước</a>
                <?php else: ?>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">⏮ Đầu</button>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">◀ Trước</button>
                <?php endif; ?>

                <span style="font-weight:600; font-size:14px; min-width:30px; text-align:center;"><?= $page ?></span>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Sau ▶</a>
                    <a href="?page=<?= $totalPages ?>" style="border:none; background:#eee; border-radius:4px; padding:5px 10px; text-decoration:none; color:#333; font-weight:600;">Cuối ⏭</a>
                <?php else: ?>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Sau ▶</button>
                    <button disabled style="border:none; background:#eee; border-radius:4px; padding:5px 10px; opacity:0.5; cursor:default;">Cuối ⏭</button>
                <?php endif; ?>
            </div>
        </div>
        <!-- Nút xóa -->
        <div class="button-container">
            <button id="deleteSelected"
                style="margin:20px 0; padding:10px 0px; background: red; color:white; border:none; border-radius:6px; cursor:pointer; width:150px;">
                Xóa môn học
            </button>
        </div>
    </div>

    <script>
        // === Checkbox chọn tất cả ===
        const checkAll = document.getElementById("checkAll");
        checkAll.addEventListener("change", () => {
            document.querySelectorAll(".row-check").forEach(cb => cb.checked = checkAll.checked);
        });

        // Nếu bỏ tick một checkbox con thì bỏ tick "chọn tất cả"
        // document.addEventListener("change", (e) => {
        //     if (e.target.classList.contains("row-check")) {
        //         const all = document.querySelectorAll(".row-check");
        //         const checked = document.querySelectorAll(".row-check:checked");
        //         checkAll.checked = (all.length === checked.length);
        //     }
        // });

        // === Xóa nhiều môn học ===
        document.getElementById("deleteSelected").addEventListener("click", async () => {
            const checked = document.querySelectorAll(".row-check:checked");
            if (checked.length === 0) {
                alert("❌ Vui lòng chọn ít nhất một môn học để xóa!");
                return;
            }

            const ids = Array.from(checked).map(cb => cb.closest("tr").dataset.id);

            if (!confirm("Bạn có chắc muốn xóa " + ids.length + " môn học đã chọn?")) return;

            const res = await fetch("../src/monhoc.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    action: "deleteMany",
                    maMonHocs: ids
                })
            });
            const json = await res.json();
            alert(json.message || json.error);
            if (json.message) location.reload();
        });

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

        const api = "../src/monhoc.php";

        function showAddPopup() {
            document.getElementById("addPopup").style.display = "flex";
        }

        function closePopup(id) {
            document.getElementById(id).style.display = "none";
        }


        // Mở popup sửa
        document.addEventListener("click", e => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");
                document.getElementById("editId").value = tr.dataset.id;
                document.getElementById("editTenMonHoc").value = tr.children[2].innerText;
                // Cập nhật counter cho form sửa
                document.getElementById("charCountEdit").textContent = tr.children[2].innerText.length;
                // Lấy tên trưởng bộ môn hiện tại
                const truongBoMon = tr.children[3].innerText.trim();
                const selectGV = document.getElementById("editTruongBoMon");
                for (let opt of selectGV.options) {
                    // Nếu tên giáo viên có chứa chuỗi hiển thị trong bảng (vd: "Nguyễn Văn A (Toán)")
                    if (opt.text.includes(truongBoMon)) {
                        opt.selected = true;
                        break;
                    }
                }
                document.getElementById("editMoTa").value = tr.children[4].innerText;
                document.getElementById("editHocKy").value = tr.children[5].innerText;
                document.getElementById("editNamHoc").value = tr.children[6].innerText;
                const active = tr.children[7].innerText === "Hoạt động";
                document.getElementById(active ? "editActive" : "editInactive").checked = true;
                document.getElementById("editPopup").style.display = "flex";
            }
        });


        // Xóa môn học
        document.addEventListener("click", async e => {
            if (e.target.classList.contains("delete-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;
                if (confirm("Bạn có chắc muốn xóa môn học này?")) {
                    const res = await fetch(api, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: "delete",
                            maMonHoc: id
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

        // === TÌM KIẾM MÔN HỌC THEO MÃ HOẶC TÊN ===
        document.getElementById("searchBox").addEventListener("input", function() {
            const keyword = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll("tbody tr");
            let found = false;

            rows.forEach(row => {
                // Bỏ qua dòng "Không có dữ liệu"
                if (row.children.length < 10) return;

                const maMH = row.children[1]?.innerText.toLowerCase() || "";
                const tenMH = row.children[2]?.innerText.toLowerCase() || "";

                if (maMH.includes(keyword) || tenMH.includes(keyword)) {
                    row.style.display = "";
                    found = true;
                } else {
                    row.style.display = "none";
                }
            });

            // Nếu không tìm thấy kết quả → thêm dòng thông báo
            let noResultRow = document.getElementById("noResultRow");
            if (!found) {
                if (!noResultRow) {
                    noResultRow = document.createElement("tr");
                    noResultRow.id = "noResultRow";
                    noResultRow.innerHTML = `
                <td colspan="10" style="text-align:center;color:gray;">
                    Không tìm thấy môn học phù hợp.
                </td>
            `;
                    document.querySelector("tbody").appendChild(noResultRow);
                }
            } else if (noResultRow) {
                noResultRow.remove();
            }
        });

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