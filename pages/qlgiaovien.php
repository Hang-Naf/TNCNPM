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

// ==== Lấy tổng số giáo viên ====
$countSql = "SELECT COUNT(*) as total FROM giaovien g JOIN user u ON g.maGV = u.userID WHERE u.vaiTro = 'GiaoVien'";
$countResult = $conn->query($countSql);
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// ==== Lấy danh sách giáo viên ====
$sql = "
    SELECT 
        g.maGV, u.hoVaTen, u.gioiTinh, u.email, u.sdt,
        g.boMon, g.trinhDo, g.phongBan, g.namHoc, g.hocKy, g.trangThai
    FROM giaovien g
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien'
    ORDER BY g.maGV DESC
    LIMIT $offset, $itemsPerPage
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
            width: 450px;
        }

        .popup input,
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

        #counter {
            font-size: 0.9em;
            color: #555;
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
                    <li class="active" onclick="window.location.href='../pages/qlgiaovien.php'"><i class="fa-solid fa-chalkboard-user"></i> Giáo viên</li>
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

        <h1>QUẢN LÝ GIÁO VIÊN</h1>
        <div class="button-container">
            <button class="add-btn" onclick="window.location.href='themgiaovien.php'">
                <i class="fa-solid fa-plus"></i> Thêm Giáo Viên
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="checkAll"></th>
                    <th>STT</th>
                    <th>MÃ GV</th>
                    <th>HỌ TÊN</th>
                    <th>GIỚI TÍNH</th>
                    <th>EMAIL</th>
                    <th class="hide-col">SDT</th>
                    <th>BỘ MÔN</th>
                    <th class="hide-col">TRÌNH ĐỘ</th>
                    <th class="hide-col">PHÒNG BAN</th>
                    <th class="hide-col">NĂM HỌC</th>
                    <th class="hide-col">HỌC KỲ</th>
                    <th>TRẠNG THÁI</th>
                    <th>TÁC VỤ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0):
                    $stt = $offset + 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['maGV'] ?>">
                            <td><input type="checkbox" class="row-check"></td>
                            <td><?= $stt++ ?></td>
                            <td><?= $row['maGV'] ?></td>
                            <td><?= htmlspecialchars($row['hoVaTen']) ?></td>
                            <td><?= htmlspecialchars($row['gioiTinh']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td class="hide-col"><?= htmlspecialchars($row['sdt']) ?></td>
                            <td><?= htmlspecialchars($row['boMon']) ?></td>
                            <td class="hide-col"><?= htmlspecialchars($row['trinhDo']) ?></td>
                            <td class="hide-col"><?= htmlspecialchars($row['phongBan']) ?></td>
                            <td class="hide-col"><?= htmlspecialchars($row['namHoc']) ?></td>
                            <td class="hide-col"><?= htmlspecialchars($row['hocKy']) ?></td>
                            <td><span class="status <?= $row['trangThai'] === 'active' ? 'active' : 'inactive' ?>">
                                    <?= $row['trangThai'] === 'active' ? 'Hoạt động' : 'Tạm dừng' ?>
                                </span></td>
                            <td class="actions">
                                <i class="fa-solid fa-pen edit-btn" style="color: black;"></i>
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

        <!-- Thanh phân trang -->
        <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:-30px;">
            <span style="font-size:14px; color:#333;">Trang <?= $page ?>/<?= max(1, $totalPages) ?> (Tổng: <?= $totalItems ?> giáo viên)</span>
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
            <button id="importSelected"
                style="margin:20px 20px 20px 0px; padding:10px 0px; background: green; color:white; border:none; border-radius:6px; cursor:pointer; width:150px;" onclick="window.location.href='import_giaovien.php'">
                Import giáo viên
            </button>
            <button id="deleteSelected"
                style="margin:20px 0; padding:10px 0px; background: red; color:white; border:none; border-radius:6px; cursor:pointer; width:150px;">
                Xóa giáo viên
            </button>
        </div>
    </div>

    <!-- Popup thêm -->
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <h3>Thêm giáo viên</h3>
            <form id="addForm">
                <input type="hidden" name="action" value="add">

                <label>Họ và tên:</label>
                <input type="text" name="hoVaTen" id="addHoTen" required maxlength="255">
                <div id="addCounter">0 / 255 ký tự</div><br>

                <label>Email:</label>
                <input type="email" name="email" required>

                <label>Số điện thoại:</label>
                <input type="text" name="sdt" id="addSdt" placeholder="VD: 0912345678" pattern="^0[0-9]{9}$" title="Số điện thoại Việt Nam phải bắt đầu bằng 0 và có 10 chữ số" required>

                <label>Giới tính:</label>
                <select name="gioiTinh">
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>

                <label>Bộ môn:</label>
                <select name="boMon" required>
                    <option value="">-- Chọn bộ môn --</option>
                    <?php foreach ($monhoc_list as $mh): ?>
                        <option value="<?= htmlspecialchars($mh['tenMonHoc']) ?>"><?= htmlspecialchars($mh['tenMonHoc']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label>Trình độ:</label>
                <input type="text" name="trinhDo" placeholder="Trình độ (VD: Cử nhân, Thạc sĩ)">

                <label>Phòng ban:</label>
                <input type="text" name="phongBan" placeholder="Phòng ban (VD: Tổ Toán)">

                <label>Năm học:</label>
                <input type="text" name="namHoc" id="addNamHoc" placeholder="Năm học">

                <label>Học kỳ:</label>
                <select name="hocKy" id="addHocKy">
                    <option value="">-- Học kỳ tự động --</option>
                </select>

                <label>Trạng thái:</label>
                <select name="trangThai">
                    <option value="active">Đang công tác</option>
                    <option value="inactive">Nghỉ</option>
                </select>

                <div class="popup-buttons">
                    <button type="button" class="cancel-btn" onclick="closePopup('addPopup')">Hủy</button>
                    <button type="submit" class="save-btn">Thêm giáo viên</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup sửa -->
    <div class="popup-bg" id="editPopup">
        <div class="popup">
            <h3>Chỉnh sửa giáo viên</h3>
            <form id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="userId" id="editId">
                <input type="text" name="hoVaTen" id="editHoTen" placeholder="Họ và tên" required maxlength="255">
                <div id="editCounter">0 / 255 ký tự</div>
                <input type="email" name="email" id="editEmail" placeholder="Email" required>
                <input type="text" name="sdt" id="editSdt" placeholder="VD: 0912345678" pattern="^0[0-9]{9}$" title="Số điện thoại Việt Nam phải bắt đầu bằng 0 và có 10 chữ số" required>
                <select name="gioiTinh" id="editGioiTinh">
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>

                <!-- ✅ boMon dạng select -->
                <select name="boMon" id="editBoMon" required>
                    <option value="">-- Chọn bộ môn --</option>
                    <?php foreach ($monhoc_list as $mh): ?>
                        <option value="<?= htmlspecialchars($mh['tenMonHoc']) ?>"><?= htmlspecialchars($mh['tenMonHoc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="trinhDo" id="editTrinhDo" placeholder="Trình độ">
                <input type="text" name="phongBan" id="editPhongBan" placeholder="Phòng ban">
                <input type="text" name="namHoc" id="editNamHoc" placeholder="Năm học">
                <select name="hocKy" id="editHocKy">
                    <option value="HK1">HK1</option>
                    <option value="HK2">HK2</option>
                    <option value="Hè">Hè</option>
                </select>
                <div>
                    <label><input type="radio" name="trangThai" id="editActive" value="active"> Hoạt động</label>
                    <label><input type="radio" name="trangThai" id="editInactive" value="inactive"> Tạm dừng</label>
                </div>
                <div class="popup-buttons">
                    <button type="button" class="cancel-btn" onclick="closePopup('editPopup')">Hủy</button>
                    <button type="submit" class="save-btn">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Lấy checkbox "chọn tất cả"
        const checkAll = document.getElementById("checkAll");

        // Bắt sự kiện thay đổi
        checkAll.addEventListener("change", function() {
            // Lấy tất cả checkbox trong tbody
            const checkboxes = document.querySelectorAll(".row-check");
            checkboxes.forEach(cb => cb.checked = checkAll.checked);
        });

        document.getElementById("deleteSelected").addEventListener("click", async () => {
            // Lấy tất cả checkbox đã chọn
            const checked = document.querySelectorAll(".row-check:checked");
            if (checked.length === 0) {
                alert("❌ Vui lòng chọn ít nhất một giáo viên để xóa!");
                return;
            }

            // Lấy danh sách ID giáo viên
            const ids = Array.from(checked).map(cb => cb.closest("tr").dataset.id);

            if (!confirm("Bạn có chắc muốn xóa " + ids.length + " giáo viên đã chọn?")) return;

            // Gửi request xóa nhiều giáo viên
            const res = await fetch("../src/giaovien.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    action: "deleteMany",
                    userIds: ids
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

        const apiGiaoVien = "../src/giaovien.php";
        let currentId = null;


        function closePopup(id) {
            document.getElementById(id).style.display = "none";
        }

        // Hàm đếm ký tự Unicode chính xác
        function countChars(str) {
            return [...str].length;
        }

        // === Popup Thêm giáo viên ===
        const addInput = document.getElementById("addHoTen");
        const addCounter = document.getElementById("addCounter");

        addInput.addEventListener("input", () => {
            let len = countChars(addInput.value);
            if (len > 255) {
                addInput.value = [...addInput.value].slice(0, 255).join('');
                len = 255;
            }
            addCounter.textContent = `${len} / 255 ký tự`;
        });

        // === Popup Sửa giáo viên ===
        const editInput = document.getElementById("editHoTen");
        const editCounter = document.getElementById("editCounter");

        editInput.addEventListener("input", () => {
            let len = countChars(editInput.value);
            if (len > 255) {
                editInput.value = [...editInput.value].slice(0, 255).join('');
                len = 255;
            }
            editCounter.textContent = `${len} / 255 ký tự`;
        });

        // Thêm giáo viên
        document.getElementById("addForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            const hoTen = e.target.hoVaTen.value.trim();
            if (hoTen.length > 255) {
                alert("Họ và tên không được vượt quá 255 ký tự!");
                return;
            }

            const email = e.target.email.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                alert("❌ Vui lòng nhập email!");
                return;
            }
            if (!emailRegex.test(email)) {
                alert("❌ Email không hợp lệ! Email phải có định dạng: user@domain.com\n- Phải có @ và tên miền (ví dụ: .com, .vn)");
                return;
            }

            const sdt = e.target.sdt.value.trim();
            if (!sdt) {
                alert("❌ Vui lòng nhập số điện thoại!");
                return;
            }
            if (!/^0[0-9]{9}$/.test(sdt)) {
                alert("❌ Số điện thoại không hợp lệ! Phải bắt đầu bằng 0 và có 10 chữ số.\nVD: 0912345678");
                return;
            }

            const data = Object.fromEntries(new FormData(e.target).entries());
            const res = await fetch(apiGiaoVien, {
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

        // Mở popup sửa
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;
                window.location.href = "suagiaovien.php?id=" + id;
            }
        });

        // Cập nhật giáo viên
        document.getElementById("editForm").addEventListener("submit", async (e) => {
            e.preventDefault();

            const hoTen = e.target.hoVaTen.value.trim();
            if (hoTen.length > 255) {
                alert("Họ và tên không được vượt quá 255 ký tự!");
                return;
            }

            const email = e.target.email.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                alert("❌ Vui lòng nhập email!");
                return;
            }
            if (!emailRegex.test(email)) {
                alert("❌ Email không hợp lệ! Email phải có định dạng: user@domain.com\n- Phải có @ và tên miền (ví dụ: .com, .vn)");
                return;
            }

            const sdt = e.target.sdt.value.trim();
            if (!sdt) {
                alert("❌ Vui lòng nhập số điện thoại!");
                return;
            }
            if (!/^0[0-9]{9}$/.test(sdt)) {
                alert("❌ Số điện thoại không hợp lệ! Phải bắt đầu bằng 0 và có 10 chữ số.\nVD: 0912345678");
                return;
            }

            const data = Object.fromEntries(new FormData(e.target).entries());
            const res = await fetch(apiGiaoVien, {
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

        function showAddPopup() {
            const {
                hocKy,
                namHoc
            } = getHocKyVaNamHoc();
            document.getElementById("addNamHoc").value = namHoc;
            document.getElementById("addHocKy").innerHTML = `<option value="${hocKy}" selected>${hocKy}</option>`;
            document.getElementById("addPopup").style.display = "flex";
        }

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

        // === TÌM KIẾM GIÁO VIÊN THEO TÊN, MÃ, EMAIL HOẶC BỘ MÔN ===
        const searchInput = document.querySelector('.search-box input');
        const tableRows = document.querySelectorAll('tbody tr');

        searchInput.addEventListener('input', () => {
            const keyword = searchInput.value.trim().toLowerCase();
            let found = 0;

            tableRows.forEach(row => {
                const maGV = row.children[2]?.innerText.toLowerCase() || "";
                const hoTen = row.children[3]?.innerText.toLowerCase() || "";
                const email = row.children[5]?.innerText.toLowerCase() || "";
                const boMon = row.children[7]?.innerText.toLowerCase() || "";

                // So khớp nếu có chứa từ khóa trong mã GV, tên, email hoặc bộ môn
                if (maGV.includes(keyword) || hoTen.includes(keyword) || email.includes(keyword) || boMon.includes(keyword)) {
                    row.style.display = "";
                    found++;
                } else {
                    row.style.display = "none";
                }
            });

            // Xóa dòng "Không tìm thấy..." cũ nếu có
            const oldRow = document.getElementById('noResultRow');
            if (oldRow) oldRow.remove();

            // Nếu không tìm thấy kết quả
            if (found === 0) {
                const tbody = document.querySelector('tbody');
                const tr = document.createElement('tr');
                tr.id = "noResultRow";
                tr.innerHTML = `<td colspan="14" style="text-align:center; color:gray;">Không tìm thấy giáo viên phù hợp.</td>`;
                tbody.appendChild(tr);
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