<?php
include_once(__DIR__ . '/../src/func.php');
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Admin ====
if ($_SESSION["vaiTro"] !== "Admin") {
    session_destroy();
    header("Location: dangnhap.php");
    exit();
}

// ==== Lấy danh sách lớp học ====
$sql = "
    SELECT 
        l.maLop, 
        l.tenLop, 
        LEFT(l.tenLop, 2) AS khoi,  -- Tự động lấy khối từ tên lớp
        l.siSo, 
        l.trangThai, 
        l.namHoc,
        g.maGV, 
        u.hoVaTen AS tenGV
    FROM lophoc l
    LEFT JOIN giaovien g ON l.maGV = g.maGV
    LEFT JOIN user u ON g.maGV = u.userID
";
$result = $conn->query($sql);

// ==== Lấy danh sách giáo viên cho select ====
$giaovien_rs = $conn->query("
    SELECT g.maGV, u.hoVaTen 
    FROM giaovien g 
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien'
");
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý lớp học</title>
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
            text-align: center;
        }

        th {
            background: #f1f3f9;
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
                    <li onclick="window.location.href='../pages/qlgiaovien.php'"><i
                            class="fa-solid fa-chalkboard-user"></i> Giáo viên</li>
                    <li onclick="window.location.href='../pages/qlhocsinh.php'"><i
                            class="fa-solid fa-user-graduate"></i> Học sinh</li>
                    <li class="active" onclick="window.location.href='../pages/qllophoc.php'"><i
                            class="fa-solid fa-school"></i> Lớp học</li>
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
                    <li><i class="fa-solid fa-bell"></i> Thông báo</li>
                    <li><i class="fa-solid fa-calendar-days"></i> Sự kiện</li>
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
        <h1>QUẢN LÝ LỚP HỌC</h1>
        <button class="add-btn" onclick="showAddPopup()">
            <i class="fa-solid fa-plus"></i> Thêm Lớp Học
        </button>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>STT</th>
                    <th>MÃ LỚP</th>
                    <th>TÊN LỚP</th>
                    <th>KHỐI</th> <!-- Cột khối mới -->
                    <th>SĨ SỐ</th>
                    <th>GIÁO VIÊN PHỤ TRÁCH</th>
                    <th>NĂM HỌC</th>
                    <th>TRẠNG THÁI</th>
                    <th>TÁC VỤ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0):
                    $stt = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['maLop'] ?>">
                            <td><input type="checkbox"></td>
                            <td><?= $stt++ ?></td>
                            <td><?= $row['maLop'] ?></td>
                            <td><?= htmlspecialchars($row['tenLop']) ?></td>
                            <td><?= htmlspecialchars($row['khoi']) ?></td> <!-- Hiển thị khối -->
                            <td><?= htmlspecialchars($row['siSo']) ?></td>
                            <td data-gv="<?= $row['maGV'] ?? '' ?>"><?= htmlspecialchars($row['tenGV'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['namHoc']) ?></td>
                            <td><?= htmlspecialchars($row['trangThai']) ?></td>
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

    <!-- Popup thêm -->
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <button class="close-btn" onclick="closePopup()">✖</button>
            <div class="them-hocsinh">
                <h2>THÊM LỚP HỌC</h2>
                <form class="student-form" id="addForm" >
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="form-group">
                            <label>Tên lớp:</label>
                            <input type="text" name="tenLop" required>
                        </div>
                        <div class="form-group">
                            <label>Sĩ số:</label>
                            <input type="number" name="siSo" min="0">
                        </div>
                        <div class="form-group">
                            <label>GVCN:</label>
                            <select name="maGV">
                                <option value="">-- Chọn giáo viên phụ trách --</option>
                                <?php $giaovien_rs->data_seek(0);
                                while ($gv = $giaovien_rs->fetch_assoc()): ?>
                                    <option value="<?= $gv['maGV'] ?>"><?= htmlspecialchars($gv['hoVaTen']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Năm học:</label>
                            <input type="text" name="namHoc">
                        </div>
                        <div class="form-group">
                            <label>Trạng thái:</label>
                            <div class="radio-group">
                                <label><input type="radio" name="trangThai" value="Đang học"> Đang hoạt động</label>
                                <label><input type="radio" name="trangThai" value="Tạm dừng"> Tạm dừng</label>
                            </div>
                        </div>
                    </div>


        
                    <div class="buttons">
                        <button type="button" class="btn-secondary" onclick="closePopup('addPopup')">Hủy</button>
                        <button type="submit" class="btn-primary">Thêm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Popup sửa -->
    <div class="popup-bg" id="editPopup">
        <div class="popup">
            <h3>Chỉnh sửa lớp học</h3>
            <form id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="maLop" id="editMaLop">
                <input type="text" name="tenLop" id="editTenLop" required>
                <input type="number" name="siSo" id="editSiSo" min="0">
                <select name="maGV" id="editMaGV">
                    <option value="">-- Chọn giáo viên phụ trách --</option>
                    <?php
                    $giaovien_rs->data_seek(0);
                    while ($gv = $giaovien_rs->fetch_assoc()): ?>
                        <option value="<?= $gv['maGV'] ?>"><?= htmlspecialchars($gv['hoVaTen']) ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="namHoc" id="editNamHoc">
                <select name="trangThai" id="editTrangThai">
                    <option value="Đang học">Đang học</option>
                    <option value="Tạm dừng">Tạm dừng</option>
                </select>
                <div class="popup-buttons">
                    <button type="button" class="cancel-btn" onclick="closePopup('editPopup')">Hủy</button>
                    <button type="submit" class="save-btn">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const api = "../src/lophoc.php";
    let currentId = null;

    function showAddPopup(mode = "add", row = null) {
        const popup = document.getElementById("addPopup");
        const form = document.getElementById("addForm");
        const title = popup.querySelector("h2");
        const submitBtn = form.querySelector(".btn-primary");
        const hiddenAction = form.querySelector("input[name='action']");

        popup.style.display = "flex";

        if (mode === "edit" && row) {
            // Chế độ sửa
            title.textContent = "CHỈNH SỬA LỚP HỌC";
            hiddenAction.value = "update";
            submitBtn.textContent = "Lưu";

            // Thêm input ẩn mã lớp nếu chưa có
            let maLopInput = form.querySelector("input[name='maLop']");
            if (!maLopInput) {
                maLopInput = document.createElement("input");
                maLopInput.type = "hidden";
                maLopInput.name = "maLop";
                form.appendChild(maLopInput);
            }
            maLopInput.value = row.dataset.id;

            // Gán dữ liệu lên form
            form.tenLop.value = row.children[3].innerText;
            form.siSo.value = row.children[5].innerText;
            form.maGV.value = row.children[6].dataset.gv || "";
            form.namHoc.value = row.children[7].innerText;
            const tt = row.children[8].innerText;
            form.trangThai.value = tt;
            form.querySelectorAll('input[name="trangThai"]').forEach(r => {
                r.checked = (r.value === tt);
            });
        } else {
            // Chế độ thêm
            title.textContent = "THÊM LỚP HỌC";
            hiddenAction.value = "add";
            submitBtn.textContent = "Thêm";
            form.reset();
            const maLopInput = form.querySelector("input[name='maLop']");
            if (maLopInput) maLopInput.remove();
        }
    }

    function closePopup() {
        document.getElementById("addPopup").style.display = "none";
        document.getElementById("addForm").reset();
    }

    // === Thêm / Cập nhật lớp học ===
    document.getElementById("addForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        const res = await fetch(api, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
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
            showAddPopup("edit", tr);
        }
    });

    // === Xóa lớp học ===
    document.addEventListener("click", async (e) => {
        if (e.target.classList.contains("delete-btn")) {
            const tr = e.target.closest("tr");
            const id = tr.dataset.id;
            if (confirm("Bạn có chắc muốn xóa lớp học này?")) {
                const res = await fetch(api, {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: "delete", maLop: id })
                });
                const json = await res.json();
                alert(json.message || json.error);
                if (json.message) location.reload();
            }
        }
    });

    // === Xử lý user menu ===
    function toggleUserMenu() {
        const menu = document.getElementById("userMenu");
        menu.style.display = (menu.style.display === "block") ? "none" : "block";
    }

    document.addEventListener("click", function (e) {
        const menu = document.getElementById("userMenu");
        const userInfo = document.querySelector(".user-info");
        if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
            menu.style.display = "none";
        }
    });

    function logout() {
        if (confirm("Bạn có chắc muốn đăng xuất không?")) {
            window.location.href = "dangxuat.php";
        }
    }
</script>

</body>

</html>