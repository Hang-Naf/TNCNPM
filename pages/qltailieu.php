<?php
include_once(__DIR__ . "/../csdl/db.php"); // Đảm bảo có file kết nối CSDL

// Xử lý thêm mới
if (isset($_POST['add'])) {
    $maMonHoc = $_POST['maMonHoc'];
    $tieuDe = $_POST['tieuDe'];
    $noiDung = $_POST['noiDung'];
    $ngayTai = date('Y-m-d');
    $maGV = $_POST['maGV'];
    $trangThai = $_POST['trangThai'];

    $sql = "INSERT INTO tailieu (maMonHoc, tieuDe, noiDung, ngayTai, maGV, trangThai)
            VALUES ('$maMonHoc', '$tieuDe', '$noiDung', '$ngayTai', '$maGV', '$trangThai')";
    if ($conn->query($sql)) {
        echo "<script>alert('Thêm tài liệu thành công!'); window.location='qltailieu.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Xử lý xóa
if (isset($_GET['delete'])) {
    $maTL = $_GET['delete'];
    $sql = "DELETE FROM tailieu WHERE maTL = $maTL";
    if ($conn->query($sql)) {
        echo "<script>alert('Xóa thành công!'); window.location='qltailieu.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Xử lý cập nhật
if (isset($_POST['update'])) {
    $maTL = $_POST['maTL'];
    $maMonHoc = $_POST['maMonHoc'];
    $tieuDe = $_POST['tieuDe'];
    $noiDung = $_POST['noiDung'];
    $trangThai = $_POST['trangThai'];

    $sql = "UPDATE tailieu 
            SET maMonHoc='$maMonHoc', tieuDe='$tieuDe', noiDung='$noiDung', trangThai='$trangThai'
            WHERE maTL='$maTL'";
    if ($conn->query($sql)) {
        echo "<script>alert('Cập nhật thành công!'); window.location='qltailieu.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}

// Lấy danh sách môn học để hiển thị dropdown
$monhoc = $conn->query("SELECT * FROM monhoc");

// Lấy danh sách giáo viên
$giaovien = $conn->query("SELECT g.maGV, u.hoVaTen FROM giaovien g 
                          JOIN user u ON g.maGV = u.userID");

// Lấy danh sách tài liệu
$sql = "SELECT t.maTL, t.tieuDe, t.noiDung, t.ngayTai, t.trangThai, 
               m.tenMonHoc, u.hoVaTen AS tenGV
        FROM tailieu t
        LEFT JOIN monhoc m ON t.maMonHoc = m.maMonHoc
        LEFT JOIN user u ON t.maGV = u.userID";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tài liệu</title>
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

        textarea {
            width: 100%;
            height: 150%;
        }

        .center {
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
                    <li onclick="window.location.href='../pages/qlmonhoc.php'"><i class="fa-solid fa-book"></i> Môn học
                    </li>
                    <li class="active" onclick="window.location.href='../pages/qltailieu.php'"><i
                            class="fa-solid fa-file-lines"></i> Tài liệu</li>
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
                    <input type="text" placeholder="Tìm kiếm..." class="search">
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
        <div id="main-container">
            <h1>DANH SÁCH TÀI LIỆU</h1>
            <button class="add-btn" onclick="showAddPopup()"><i class="fa-solid fa-plus"></i> Thêm tài liệu</button>
            <table>
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã TL</th>
                        <th>Tiêu đề</th>
                        <th>Môn học</th>
                        <th>Giáo viên</th>
                        <th>Ngày tải</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0):
                        $stt = 1;
                        while ($row = $result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['maTL'] ?>">
                                <td class="center"><?= $stt++ ?></td>
                                <td class="center"><?= $row['maTL'] ?></td>
                                <td><?= htmlspecialchars($row['tieuDe']) ?></td>
                                <td class="center"><?= htmlspecialchars($row['tenMonHoc']) ?></td>
                                <td class="center"><?= htmlspecialchars($row['tenGV']) ?></td>
                                <td class="center"><?= $row['ngayTai'] ?></td>
                                <td class="center"><?= $row['trangThai'] ?></td>
                                <td class="center">
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
                <h2 id="title-h2">THÊM TÀI LIỆU</h2>
                <div class="them-hocsinh">
                    <form method="post" action="" id="addForm" class="student-form">
                        <input type="hidden" name="action" value="add" id="formAction">
                        <input type="hidden" name="maTL" id="maTL">
                        <div class="row">
                            <div class="form-group">
                                <label>Môn học:</label>
                                <select name="maMonHoc" required>
                                    <option value="">-- Chọn môn học --</option>
                                    <?php while ($row = $monhoc->fetch_assoc()) { ?>
                                        <option value="<?= $row['maMonHoc'] ?>"><?= htmlspecialchars($row['tenMonHoc']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tiêu đề:</label>
                                <input type="text" name="tieuDe" required>
                            </div>
                            <div class="form-group">
                                <label>Giáo viên tải lên:</label>
                                <select name="maGV" required>
                                    <option hidden></option>
                                    <?php while ($row = $giaovien->fetch_assoc()) { ?>
                                        <option value="<?= $row['maGV'] ?>"><?= htmlspecialchars($row['hoVaTen']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group">
                                <label>Nội dung:</label>
                                <textarea name="noiDung" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái:</label>
                                <select name="trangThai" required>
                                    <option value="Công khai">Công khai</option>
                                    <option value="Riêng tư">Riêng tư</option>
                                </select>
                            </div>
                        </div>
                        <div class="buttons">
                            <button type="submit" class="btn-primary" name="add">
                                <i class="fa-solid fa-plus"></i> Thêm mới
                            </button>
                            <button type="button" class="btn-secondary"
                                onclick="window.location.href='qltailieu.php'">Hủy</button>
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
                fetch("update_trangthai.php", {
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

        // === Mở popup sửa tài liệu ===
        document.addEventListener("click", e => {
            if (e.target.classList.contains("edit-btn")) {
                const tr = e.target.closest("tr");

                const maTL = tr.dataset.id;
                const tieuDe = tr.children[2].innerText.trim();
                const monHoc = tr.children[3].innerText.trim();
                const giaoVien = tr.children[4].innerText.trim();
                const noiDung = tr.children[5].innerText.trim();
                const trangThai = tr.children[6].innerText.trim();

                showAddPopup();
                document.getElementById("title-h2").innerText = "CHỈNH SỬA TÀI LIỆU";
                document.getElementById("formAction").value = "update";
                document.getElementById("submitButton").innerHTML = '<i class="fa-solid fa-pen"></i> Cập nhật';
                document.getElementById("maTL").value = maTL;

                const form = document.getElementById("addForm");
                form.tieuDe.value = tieuDe;
                form.noiDung.value = noiDung;
                form.trangThai.value = trangThai;

                // Chọn môn học
                for (let opt of form.maMonHoc.options) {
                    if (opt.text === monHoc) opt.selected = true;
                }

                // Chọn giáo viên
                for (let opt of form.maGV.options) {
                    if (opt.text === giaoVien) opt.selected = true;
                }
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

        // Đóng menu nếu click ra ngoài
        document.addEventListener("click", function (e) {
            const menu = document.getElementById("userMenu");
            const userInfo = document.querySelector(".user-info");
            if (!userInfo.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = "none";
            }
        });
        const apiTaiLieu = "../src/tailieu.php";
        function showAddPopup() {
            document.getElementById('addPopup').style.display = 'block';
            document.getElementById('main-container').style.display = 'none';
        }
        // Xóa tài liệu
        document.addEventListener("click", async (e) => {
            if (e.target.classList.contains("delete-btn")) {
                const tr = e.target.closest("tr");
                const id = tr.dataset.id;
                if (confirm("Bạn có chắc muốn xóa tài liệu này?")) {
                    const res = await fetch(apiTaiLieu, {
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
    </script>

</body>

</html>