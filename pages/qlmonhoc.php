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
                    <li  onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                    <li  onclick="window.location.href='../pages/qltsukien.php'"><i class="fa-solid fa-calendar-days"></i> Sự kiện</li>
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
                <?php if ($result->num_rows > 0): $stt = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['maMonHoc'] ?>">
                            <td><?= $stt++ ?></td>
                            <td><?= $row['maMonHoc'] ?></td>
                            <td><?= htmlspecialchars($row['tenMonHoc']) ?></td>
                            <td><?= htmlspecialchars($row['truongBoMon'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($row['moTa']) ?></td>
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
    <!-- Popup thêm -->
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <h3>Thêm môn học</h3>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <input type="text" name="tenMonHoc" placeholder="Tên môn học" required>
                <select name="truongBoMon">
                    <option value="">--Chọn Trưởng Bộ Môn--</option>
                    <?php
                    $gv_rs->data_seek(0);
                    while ($gv = $gv_rs->fetch_assoc()):
                    ?>
                        <option value="<?= $gv['maGV'] ?>"><?= htmlspecialchars($gv['hoVaTen']) ?> (<?= htmlspecialchars($gv['boMon']) ?>)</option>
                    <?php endwhile; ?>
                </select>
                <textarea name="moTa" placeholder="Mô tả"></textarea>
                <select name="hocKy">
                    <option value="HK1">Học kỳ 1</option>
                    <option value="HK2">Học kỳ 2</option>
                    <option value="Hè">Học kỳ Hè</option>
                </select>
                <input type="number" name="trongSo" placeholder="Trọng số" step="0.1" required>
                <input type="text" name="namHoc" placeholder="VD: 2024-2025" required>
                <div>
                    <label><input type="radio" name="trangThai" value="Hoạt động" checked> Hoạt động</label>
                    <label><input type="radio" name="trangThai" value="Ngưng"> Ngưng</label>
                </div>
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
            <h3>Chỉnh sửa môn học</h3>
            <form id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="maMonHoc" id="editId">
                <input type="text" name="tenMonHoc" id="editTenMonHoc" required>
                <select name="truongBoMon" id="editTruongBoMon">
                    <option value="">--Chọn Trưởng Bộ Môn--</option>
                    <?php
                    $gv_rs->data_seek(0);
                    while ($gv = $gv_rs->fetch_assoc()):
                    ?>
                        <option value="<?= $gv['maGV'] ?>"><?= htmlspecialchars($gv['hoVaTen']) ?> (<?= htmlspecialchars($gv['boMon']) ?>)</option>
                    <?php endwhile; ?>
                </select>
                <textarea name="moTa" id="editMoTa"></textarea>
                <select name="hocKy" id="editHocKy">
                    <option value="HK1">HK1</option>
                    <option value="HK2">HK2</option>
                    <option value="Hè">Hè</option>
                </select>
                <input type="number" name="trongSo" id="editTrongSo" step="0.1" required>
                <input type="text" name="namHoc" id="editNamHoc" required>
                <div>
                    <label><input type="radio" name="trangThai" id="editActive" value="Hoạt động"> Hoạt động</label>
                    <label><input type="radio" name="trangThai" id="editInactive" value="Ngưng"> Ngưng</label>
                </div>
                <div class="popup-buttons">
                    <button type="button" class="cancel-btn" onclick="closePopup('editPopup')">Hủy</button>
                    <button type="submit" class="save-btn">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const api = "../src/monhoc.php";

        function showAddPopup() {
            document.getElementById("addPopup").style.display = "flex";
        }

        function closePopup(id) {
            document.getElementById(id).style.display = "none";
        }

        // Thêm môn học
        document.getElementById("addForm").addEventListener("submit", async e => {
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

        // Mở popup sửa
        document.addEventListener("click", e => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");
                document.getElementById("editId").value = tr.dataset.id;
                document.getElementById("editTenMonHoc").value = tr.children[2].innerText;
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
                document.getElementById("editTrongSo").value = tr.children[6].innerText;
                document.getElementById("editNamHoc").value = tr.children[7].innerText;
                const active = tr.children[8].innerText === "Hoạt động";
                document.getElementById(active ? "editActive" : "editInactive").checked = true;
                document.getElementById("editPopup").style.display = "flex";
            }
        });

        // Lưu cập nhật
        document.getElementById("editForm").addEventListener("submit", async e => {
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
        
        // Xử lý đăng xuất
        function logout() {
            if (confirm("Bạn có chắc muốn đăng xuất không?")) {
                window.location.href = "dangxuat.php"; // hoặc logout.php nếu có xử lý session
            }
        }
    </script>
</body>

</html>