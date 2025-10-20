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

// ==== Lấy danh sách giáo viên ====
$sql = "
    SELECT 
        g.maGV, u.hoVaTen, u.gioiTinh, u.email, u.sdt,
        g.boMon, g.trinhDo, g.phongBan, g.namHoc, g.hocKy, g.trangThai
    FROM giaovien g
    JOIN user u ON g.maGV = u.userID
    WHERE u.vaiTro = 'GiaoVien'
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
    <link rel="stylesheet" href="popup.css">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f8f9fb;
            margin: 0;
        }

        .header {
            padding: 0px 25px;
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
                    <li class="active" onclick="window.location.href='../pages/qlgiaovien.php'"><i
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
                    <li onclick="window.location.href='../pages/qlthongbao.php'"><i class="fa-solid fa-bell"></i> Thông
                        báo</li>
                    <li onclick="window.location.href='../pages/qlsukien.php'"><i class="fa-solid fa-calendar-days"></i>
                        Sự kiện</li>
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

        <h1>QUẢN LÝ GIÁO VIÊN</h1>
        <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm Giáo Viên</button>

        <table>
            <thead>
                <tr>
                    <th><input type="checkbox"></th>
                    <th>STT</th>
                    <th>MÃ GV</th>
                    <th>HỌ TÊN</th>
                    <th>GIỚI TÍNH</th>
                    <th>EMAIL</th>
                    <th>SDT</th>
                    <th>BỘ MÔN</th>
                    <th>TRÌNH ĐỘ</th>
                    <th>PHÒNG BAN</th>
                    <th>NĂM HỌC</th>
                    <th>HỌC KỲ</th>
                    <th>TRẠNG THÁI</th>
                    <th>TÁC VỤ</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0):
                    $stt = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr data-id="<?= $row['maGV'] ?>">
                            <td><input type="checkbox"></td>
                            <td><?= $stt++ ?></td>
                            <td><?= $row['maGV'] ?></td>
                            <td><?= htmlspecialchars($row['hoVaTen']) ?></td>
                            <td><?= htmlspecialchars($row['gioiTinh']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['sdt']) ?></td>
                            <td><?= htmlspecialchars($row['boMon']) ?></td>
                            <td><?= htmlspecialchars($row['trinhDo']) ?></td>
                            <td><?= htmlspecialchars($row['phongBan']) ?></td>
                            <td><?= htmlspecialchars($row['namHoc']) ?></td>
                            <td><?= htmlspecialchars($row['hocKy']) ?></td>
                            <td><span class="status <?= $row['trangThai'] === 'active' ? 'active' : 'inactive' ?>">
                                    <?= $row['trangThai'] === 'active' ? 'Hoạt động' : 'Tạm dừng' ?>
                                </span></td>
                            <td class="actions">
                                <i class="fa-solid fa-pen edit-btn"></i>
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
    </div>

    <!-- Popup thêm -->
    <div class="popup-bg" id="addPopup">
        <div class="popup">
            <div class="them-hocsinh">
                <button class="close-btn" onclick="window.closePopup()">✖</button>

                <h2 id="title-h2">THÊM GIÁO VIÊN</h2>
                <form id="addForm" class="student-form">
                    <input type="hidden" name="action" value="add" id="formAction">
                    <input type="hidden" name="userId" id="userId">

                    <div class="row">
                        <div class="form-group">
                            <label>Họ và Tên:</label>
                            <input type="text" name="hoVaTen">
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input type="email" name="email">
                        </div>
                        <div class="form-group">
                            <label>Số Điện Thoại:</label>
                            <input type="text" name="sdt">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Giới tính:</label>
                            <select name="gioiTinh">
                                <option value="Nam">Nam</option>
                                <option value="Nữ">Nữ</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bộ môn:</label>
                            <select name="boMon" required>
                                <option value="">-- Chọn bộ môn --</option>
                                <?php foreach ($monhoc_list as $mh): ?>
                                    <option value="<?= htmlspecialchars($mh['tenMonHoc']) ?>">
                                        <?= htmlspecialchars($mh['tenMonHoc']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Trình độ:</label>
                            <input type="text" name="trinhDo" placeholder="Trình độ (VD: Cử nhân, Thạc sĩ)">
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Phòng ban:</label>
                            <input type="text" name="phongBan" placeholder="Phòng ban (VD: Tổ Toán)">

                        </div>
                        <div class="form-group">
                            <label>Năm học:</label>
                            <input type="text" name="namHoc" id="addNamHoc" placeholder="Năm học" readonly>
                        </div>
                        <div class="form-group">
                            <label>Học kỳ:</label>
                            <select name="hocKy" id="addHocKy" readonly>
                                <option value="">-- Học kỳ tự động --</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group">
                            <label>Trạng thái:</label>
                            <div class="radio-group">
                                <label><input type="radio" name="trangThai" value="active"> Đang hoạt động</label>
                                <label><input type="radio" name="trangThai" value="inactive"> Tạm dừng</label>
                            </div>
                        </div>
                        <div class="popup-buttons">
                            <button type="button" class="btn-secondary" onclick="closePopup('addPopup')">Hủy</button>
                            <button type="submit" class="btn-primary" id="submitButton">Thêm giáo viên</button>
                        </div>
                    </div>
                </form>
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

        // Ẩn dropdown khi click ra ngoài
        document.addEventListener("click", function (e) {
            const dropdown = document.getElementById("notificationDropdown");
            const bell = document.getElementById("bellIcon");
            if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });

        const apiGiaoVien = "../src/giaovien.php";
        let currentId = null;

        function showAddPopup() {
            document.getElementById("addPopup").style.display = "flex";
        }

        function closePopup(id) {
            const popup = document.getElementById(id);
            popup.style.display = "none";

            // Reset về chế độ thêm
            const form = document.getElementById("addForm");
            form.reset();
            document.getElementById("formAction").value = "add";
            document.getElementById("userId").value = "";
            document.querySelector("h2").innerText = "THÊM GIÁO VIÊN";
            document.getElementById("submitButton").innerText = "Thêm giáo viên";
        }


        // Thêm giáo viên
        document.getElementById("addForm").addEventListener("submit", async (e) => {
            e.preventDefault();
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

        // === Xử lý nhấn nút sửa ===
        document.addEventListener("click", (e) => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;

                // Lấy thông tin hiện có trong hàng
                const data = {
                    maGV: tr.children[2].innerText,
                    hoVaTen: tr.children[3].innerText,
                    gioiTinh: tr.children[4].innerText,
                    email: tr.children[5].innerText,
                    sdt: tr.children[6].innerText,
                    boMon: tr.children[7].innerText,
                    trinhDo: tr.children[8].innerText,
                    phongBan: tr.children[9].innerText,
                    namHoc: tr.children[10].innerText,
                    hocKy: tr.children[11].innerText,
                    trangThai: tr.children[12].innerText.includes("Hoạt") ? "active" : "inactive"
                };

                // Gán dữ liệu vào form thêm (để tái sử dụng)
                document.getElementById("formAction").value = "update";
                document.getElementById("userId").value = id;
                document.getElementById("title-h2").innerText = "CHỈNH SỬA GIÁO VIÊN";
                document.getElementById("submitButton").innerText = "Lưu thay đổi";

                document.querySelector("#addForm [name='hoVaTen']").value = data.hoVaTen;
                document.querySelector("#addForm [name='email']").value = data.email;
                document.querySelector("#addForm [name='sdt']").value = data.sdt;
                document.querySelector("#addForm [name='gioiTinh']").value = data.gioiTinh;
                document.querySelector("#addForm [name='boMon']").value = data.boMon;
                document.querySelector("#addForm [name='trinhDo']").value = data.trinhDo;
                document.querySelector("#addForm [name='phongBan']").value = data.phongBan;
                document.querySelector("#addForm [name='namHoc']").value = data.namHoc;
                document.querySelector("#addForm [name='hocKy']").innerHTML =
                    `<option value="${data.hocKy}" selected>${data.hocKy}</option>`;

                document.querySelector(`#addForm [name='trangThai'][value='${data.trangThai}']`).checked = true;

                // Hiện popup
                document.getElementById("addPopup").style.display = "flex";
            }
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