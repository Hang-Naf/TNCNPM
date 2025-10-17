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

// ==== Lấy danh sách học sinh ====
$sql = "
    SELECT 
        h.maHS, u.hoVaTen, u.gioiTinh, u.email, u.sdt,
        h.lopHocPhuTrach, h.namHoc, h.hocKy, h.trangThai
    FROM hocsinh h
    JOIN user u ON h.maHS = u.userID
    WHERE u.vaiTro = 'HocSinh'
";
$result = $conn->query($sql);

// ==== Lấy danh sách lớp học ====
$lophoc_rs = $conn->query("SELECT maLop, tenLop FROM lophoc");
$lophoc_list = [];
while ($lh = $lophoc_rs->fetch_assoc()) {
    $lophoc_list[] = $lh;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý học sinh</title>
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
                    <li class="active" onclick="window.location.href='../pages/qlhocsinh.php'"><i class="fa-solid fa-user-graduate"></i> Học sinh</li>
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
        <h1>QUẢN LÝ HỌC SINH</h1>
        <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm Học Sinh</button>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>STT</th>
                    <th>MÃ HS</th>
                    <th>HỌ TÊN</th>
                    <th>GIỚI TÍNH</th>
                    <th>EMAIL</th>
                    <th>SDT</th>
                    <th>LỚP</th>
                    <th>KHÓA HỌC</th>
                    <th>HỌC KỲ</th>
                    <th>TRẠNG THÁI</th>
                    <th>TÁC VỤ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): $stt = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['maHS'] ?>">
                            <td><input type="checkbox"></td>
                            <td><?= $stt++ ?></td>
                            <td><?= $row['maHS'] ?></td>
                            <td><?= htmlspecialchars($row['hoVaTen']) ?></td>
                            <td><?= htmlspecialchars($row['gioiTinh']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['sdt']) ?></td>
                            <td><?= htmlspecialchars($row['lopHocPhuTrach']) ?></td>
                            <td><?= htmlspecialchars($row['namHoc']) ?></td>
                            <td><?= htmlspecialchars($row['hocKy']) ?></td>
                            <td>
                                <span class="status <?= $row['trangThai'] === 'active' ? 'active' : 'inactive' ?>">
                                    <?= $row['trangThai'] === 'active' ? 'Hoạt động' : 'Tạm dừng' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <i class="fa-solid fa-pen edit-btn"></i>
                                <i class="fa-solid fa-trash delete-btn"></i>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="12" style="text-align:center;">Không có dữ liệu</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Popup thêm -->
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <h3>Thêm học sinh</h3>
            <form id="addForm">
                <input type="hidden" name="action" value="add">
                <input type="text" name="hoVaTen" placeholder="Họ tên" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="sdt" placeholder="Số điện thoại">
                <select name="gioiTinh">
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>

                <!-- ✅ lớp dạng select -->
                <select name="lopHocPhuTrach" required>
                    <option value="">-- Chọn lớp học --</option>
                    <?php foreach ($lophoc_list as $lh): ?>
                        <option value="<?= htmlspecialchars($lh['tenLop']) ?>"><?= htmlspecialchars($lh['tenLop']) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="namHoc" id="addNamHoc" placeholder="VD: 2022-2025" required readonly>
                <select name="hocKy" id="addHocKy" readonly>
                    <option value="">-- Học kỳ tự động --</option>
                </select>
                <div>
                    <label><input type="radio" name="trangThai" value="active" checked> Hoạt động</label>
                    <label><input type="radio" name="trangThai" value="inactive"> Tạm dừng</label>
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
            <h3>Chỉnh sửa học sinh</h3>
            <form id="editForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="userId" id="editId">
                <input type="text" name="hoVaTen" id="editHoTen" placeholder="Họ và tên" required>
                <input type="email" name="email" id="editEmail" placeholder="Email" required>
                <input type="text" name="sdt" id="editSdt" placeholder="Số điện thoại">
                <select name="gioiTinh" id="editGioiTinh">
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>

                <!-- ✅ lớp dạng select -->
                <select name="lopHocPhuTrach" id="editLop" required>
                    <option value="">-- Chọn lớp học --</option>
                    <?php foreach ($lophoc_list as $lh): ?>
                        <option value="<?= htmlspecialchars($lh['tenLop']) ?>"><?= htmlspecialchars($lh['tenLop']) ?></option>
                    <?php endforeach; ?>
                </select>

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
        const api = "../src/hocsinh.php";
        let currentId = null;

        function showAddPopup() {
            document.getElementById("addPopup").style.display = "flex";
        }

        function closePopup(id) {
            document.getElementById(id).style.display = "none";
        }

        // === Thêm học sinh ===
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
                currentId = tr.dataset.id;
                document.getElementById("editId").value = currentId;
                document.getElementById("editHoTen").value = tr.children[3].innerText;
                document.getElementById("editGioiTinh").value = tr.children[4].innerText;
                document.getElementById("editEmail").value = tr.children[5].innerText;
                document.getElementById("editSdt").value = tr.children[6].innerText;
                document.getElementById("editLop").value = tr.children[7].innerText; // chọn đúng lớp
                document.getElementById("editNamHoc").value = tr.children[8].innerText;
                document.getElementById("editHocKy").value = tr.children[9].innerText;
                const active = tr.children[10].innerText.includes("Hoạt");
                document.getElementById(active ? "editActive" : "editInactive").checked = true;
                document.getElementById("editPopup").style.display = "flex";
            }
        });

        // === Cập nhật học sinh ===
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

        // === Xóa học sinh ===
        document.addEventListener("click", async (e) => {
            if (e.target.classList.contains("delete-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;
                if (confirm("Bạn có chắc muốn xóa học sinh này?")) {
                    const res = await fetch(api, {
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

        // Gán tự động khi mở form thêm
        function showAddPopup() {
            const {
                hocKy,
                namHoc
            } = getHocKyVaNamHoc();
            document.getElementById("addNamHoc").value = namHoc;
            document.getElementById("addHocKy").innerHTML = `<option value="${hocKy}" selected>${hocKy}</option>`;
            document.getElementById("addPopup").style.display = "flex";
        }

        // Khi mở popup sửa, nếu dữ liệu trống thì cũng tự động set lại
        // document.addEventListener("click", (e) => {
        //     if (e.target.classList.contains("edit-btn")) {
        //         const tr = e.target.closest("tr");
        //         currentId = tr.dataset.id;
        //         document.getElementById("editId").value = currentId;
        //         document.getElementById("editHoTen").value = tr.children[3].innerText;
        //         document.getElementById("editGioiTinh").value = tr.children[4].innerText;
        //         document.getElementById("editEmail").value = tr.children[5].innerText;
        //         document.getElementById("editSdt").value = tr.children[6].innerText;
        //         document.getElementById("editBoMon").value = tr.children[7].innerText;
        //         document.getElementById("editTrinhDo").value = tr.children[8].innerText;
        //         document.getElementById("editPhongBan").value = tr.children[9].innerText;

        //         // ✅ Nếu năm học và học kỳ chưa có, tự động điền
        //         const {
        //             hocKy,
        //             namHoc
        //         } = getHocKyVaNamHoc();
        //         const editNamHoc = tr.children[10].innerText || namHoc;
        //         const editHocKy = tr.children[11].innerText || hocKy;
        //         document.getElementById("editNamHoc").value = editNamHoc;
        //         document.getElementById("editHocKy").innerHTML = `<option value="${editHocKy}" selected>${editHocKy}</option>`;

        //         const active = tr.children[12].innerText.includes("Hoạt");
        //         document.getElementById(active ? "editActive" : "editInactive").checked = true;
        //         document.getElementById("editPopup").style.display = "flex";
        //     }
        // });


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