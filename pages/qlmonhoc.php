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

// ==== Lấy danh sách môn học và trưởng bộ môn ====
$sql = "
    SELECT 
        m.maMonHoc,
        m.tenMonHoc,
        m.moTa,
        m.hocKy,
        m.trongSo,
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
    <link rel="stylesheet" href="../form.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        #main-container {
            padding: 20px;
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
            background-color: rgba(32, 164, 99, 0.2);
            color: #20a463;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .status.inactive {
            background-color: rgba(128, 128, 128, 0.2);
            color: gray;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }

        .actions i {
            cursor: pointer;
            margin-right: 10px;
        }

        td:not(.no-center) {
            text-align: center;
        }

        #addPopup {
            display: none;
        }

        .hide-column {
            display: none;
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
                    <li onclick="window.location.href='../pages/qlgiaovien.php'"><i
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
                    <li class="active" onclick="window.location.href='../pages/qlmonhoc.php'"><i
                            class="fa-solid fa-book"></i> Môn học</li>
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
        <div id="main-container">
            <h1>QUẢN LÝ MÔN HỌC</h1>
            <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm Môn Học</button>

            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>MÃ MH</th>
                        <th>TÊN MÔN HỌC</th>
                        <th>TRƯỞNG BỘ MÔN</th>
                        <th>MÔ TẢ</th>
                        <th>HỌC KỲ</th>
                        <th>TRỌNG SỐ</th>
                        <th>NĂM HỌC</th>
                        <th>TRẠNG THÁI</th>
                        <th>TÁC VỤ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0):
                        $stt = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['maMonHoc'] ?>">
                                <td><?= $stt++ ?></td>
                                <td><?= $row['maMonHoc'] ?></td>
                                <td><?= htmlspecialchars($row['tenMonHoc']) ?></td>
                                <td><?= htmlspecialchars($row['truongBoMon'] ?? '—') ?></td>
                                <td class="no-center"><?= htmlspecialchars($row['moTa']) ?></td>
                                <td><?= htmlspecialchars($row['hocKy']) ?></td>
                                <td><?= htmlspecialchars($row['trongSo']) ?></td>
                                <td><?= htmlspecialchars($row['namHoc']) ?></td>
                                <td><span class="status <?= $row['trangThai'] === 'Hoạt động' ? 'active' : 'inactive' ?>">
                                        <?= htmlspecialchars($row['trangThai']) ?>
                                    </span></td>
                                <td class="actions">
                                    <i class="fa-solid fa-pen edit-btn"></i>
                                    <i class="fa-solid fa-trash delete-btn"></i>
                                </td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="10" style="text-align:center;">Không có dữ liệu</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="popup-bg" id="addPopup">
            <div class="popup">
                <h2 id="title-h2">THÊM MÔN HỌC</h2>
                <div class="them-hocsinh">
                    <form id="addForm" class="student-form">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="maMonHoc">
                        <div class="row">
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Năm học:</label>
                                <input type="text" name="namHoc" placeholder="VD: 2024-2025" required>
                            </div>
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Học kỳ:</label>
                                <select name="hocKy">
                                    <option value="HK1">Học kỳ 1</option>
                                    <option value="HK2">Học kỳ 2</option>
                                    <option value="Hè">Học kỳ Hè</option>
                                </select>
                            </div>
                            <div class="form-group-horizontal">
                                <label class="label-width-auto">Trọng số:</label>
                                <input type="number" name="trongSo" placeholder="Trọng số" step="0.1" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Tên môn:</label>
                                <input type="text" name="tenMonHoc" required>
                            </div>
                            <div class="form-group">
                                <label>Trưởng bộ môn:</label>
                                <select name="truongBoMon">
                                    <option value="">--Chọn Trưởng Bộ Môn--</option>
                                    <?php
                                    $gv_rs->data_seek(0);
                                    while ($gv = $gv_rs->fetch_assoc()):
                                        ?>
                                        <option value="<?= $gv['maGV'] ?>"><?= htmlspecialchars($gv['hoVaTen']) ?>
                                            (<?= htmlspecialchars($gv['boMon']) ?>)</option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Mô tả:</label>
                                <textarea name="moTa"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <div class="form-group-horizontal">
                                    <label class="label-width-auto"><input type="radio" name="trangThai" value="● Active"> Đang hoạt
                                        động</label>
                                    <label class="label-width-auto"><input type="radio" name="trangThai" value="● Inctive"> Tạm dừng</label>
                                </div>
                            </div>
                        </div>

                        <div class="buttons">
                            <button type="button" class="btn-secondary"
                                onclick="window.location.href='qlmonhoc.php'">Hủy</button>
                            <button type="submit" class="btn-primary" id="submitBtn">
                                <i class="fa-solid fa-plus"></i> Thêm mới
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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

        document.getElementById("addForm").addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch("../src/monhoc.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.error) {
                    alert(result.error);
                } else {
                    alert(result.message);
                    window.location.href = "qlmonhoc.php";
                }
            } catch (error) {
                console.error("Lỗi khi thêm môn học:", error);
                alert("Lỗi khi thêm môn học. Vui lòng thử lại!");
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

        const api = "../src/monhoc.php";

        function showAddPopup() {
            document.getElementById('addPopup').style.display = 'block';
            document.getElementById('main-container').style.display = 'none';
        }

        function closePopup(id) {
            document.getElementById(id).style.display = "none";
        }

        // === Mở popup sửa môn học ===
        document.addEventListener("click", e => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");

                // Lấy dữ liệu từ dòng
                const maMonHoc = tr.dataset.id;
                const tenMonHoc = tr.children[2].innerText.trim();
                const truongBoMonText = tr.children[3].innerText.trim();
                const moTa = tr.children[4].innerText.trim();
                const hocKy = tr.children[5].innerText.trim();
                const trongSo = tr.children[6].innerText.trim();
                const namHoc = tr.children[7].innerText.trim();
                const trangThai = tr.children[8].innerText.trim();

                // Hiển thị popup và đổi giao diện nút
                showAddPopup();
                document.getElementById("title-h2").innerText = "CHỈNH SỬA MÔN HỌC";
                document.querySelector("input[name='action']").value = "update";
                document.getElementById("submitBtn").innerHTML = '<i class="fa-solid fa-pen"></i> Cập nhật';

                // Gán dữ liệu vào form theo name
                const form = document.getElementById("addForm");
                form.querySelector("input[name='maMonHoc']").value = maMonHoc;
                form.querySelector("input[name='tenMonHoc']").value = tenMonHoc;
                form.querySelector("textarea[name='moTa']").value = moTa;
                form.querySelector("input[name='namHoc']").value = namHoc;
                form.querySelector("input[name='trongSo']").value = trongSo;

                // Chọn trưởng bộ môn trong select
                const selectGV = form.querySelector("select[name='truongBoMon']");
                for (let opt of selectGV.options) {
                    if (opt.text.includes(truongBoMonText)) {
                        opt.selected = true;
                        break;
                    }
                }

                // Chọn radio trạng thái
                const radio = form.querySelector(`input[name='trangThai'][value='${trangThai}']`);
                if (radio) radio.checked = true;
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
        document.addEventListener("click", function (e) {
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