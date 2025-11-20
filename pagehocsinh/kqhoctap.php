<?php
include_once(__DIR__ . '/../csdl/db.php');
session_start();

// ==== Kiểm tra đăng nhập ====
if (!isset($_SESSION["userID"])) {
    header("Location: ../dangnhap.php");
    exit();
}

// ==== Chỉ cho phép Học sinh ====
if ($_SESSION["vaiTro"] !== "HocSinh") {
    session_destroy();
    header("Location: ../dangnhap.php");
    exit();
}

$userID = $_SESSION["userID"];

// === PHÂN TRANG ===
$itemsPerPage = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// === Lấy thông tin học sinh ===
$sqlHS = "SELECT hoVaTen FROM user WHERE userID = ?";
$stmtHS = $conn->prepare($sqlHS);
$stmtHS->bind_param("i", $userID);
$stmtHS->execute();
$resultHS = $stmtHS->get_result();
$hs = $resultHS->fetch_assoc();

// === Lấy điểm theo môn ===
$sql = "SELECT d.maMonHoc, m.tenMonHoc, d.loaiDiem, d.diem
        FROM diemso d
        JOIN monhoc m ON d.maMonHoc = m.maMonHoc
        WHERE d.maHS = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();

$bangDiem = [];
while ($r = $result->fetch_assoc()) {
    $maMonHoc = $r['maMonHoc'];
    $mon = $r['tenMonHoc'];
    $loai = strtolower($r['loaiDiem']);
    $diem = is_numeric($r['diem']) ? (float)$r['diem'] : null;

    if (!isset($bangDiem[$maMonHoc])) {
        $bangDiem[$maMonHoc] = [
            'maMonHoc' => $maMonHoc,
            'tenMonHoc' => $mon,
            'hk1_mieng' => null,
            'hk1_1tiet' => null,
            'hk1_thiGK' => null,
            'hk1_thiCK' => null,
            'hk2_mieng' => null,
            'hk2_1tiet' => null,
            'hk2_thiGK' => null,
            'hk2_thiCK' => null,
            'tbHK1' => null,
            'tbHK2' => null,
            'tb' => null
        ];
    }

    // Gán điểm theo loại
    if (strpos($loai, 'hk1_mieng') !== false) $bangDiem[$maMonHoc]['hk1_mieng'] = $diem;
    elseif (strpos($loai, 'hk1_1tiet') !== false) $bangDiem[$maMonHoc]['hk1_1tiet'] = $diem;
    elseif (strpos($loai, 'hk1_thigk') !== false) $bangDiem[$maMonHoc]['hk1_thiGK'] = $diem;
    elseif (strpos($loai, 'hk1_thick') !== false) $bangDiem[$maMonHoc]['hk1_thiCK'] = $diem;
    elseif (strpos($loai, 'hk2_mieng') !== false) $bangDiem[$maMonHoc]['hk2_mieng'] = $diem;
    elseif (strpos($loai, 'hk2_1tiet') !== false) $bangDiem[$maMonHoc]['hk2_1tiet'] = $diem;
    elseif (strpos($loai, 'hk2_thigk') !== false) $bangDiem[$maMonHoc]['hk2_thiGK'] = $diem;
    elseif (strpos($loai, 'hk2_thick') !== false) $bangDiem[$maMonHoc]['hk2_thiCK'] = $diem;

    // Tính trung bình HK1
    $sum = 0;
    $count = 0;
    if (is_numeric($bangDiem[$maMonHoc]['hk1_mieng'])) {
        $sum += $bangDiem[$maMonHoc]['hk1_mieng'] * 1;
        $count += 1;
    }
    if (is_numeric($bangDiem[$maMonHoc]['hk1_1tiet'])) {
        $sum += $bangDiem[$maMonHoc]['hk1_1tiet'] * 2;
        $count += 2;
    }
    if (is_numeric($bangDiem[$maMonHoc]['hk1_thiGK'])) {
        $sum += $bangDiem[$maMonHoc]['hk1_thiGK'] * 2;
        $count += 2;
    }
    if (is_numeric($bangDiem[$maMonHoc]['hk1_thiCK'])) {
        $sum += $bangDiem[$maMonHoc]['hk1_thiCK'] * 3;
        $count += 3;
    }
    $bangDiem[$maMonHoc]['tbHK1'] = $count > 0 ? round($sum / $count, 1) : null;

    // Tính trung bình HK2
    $sum = 0;
    $count = 0;
    if (is_numeric($bangDiem[$maMonHoc]['hk2_mieng'])) {
        $sum += $bangDiem[$maMonHoc]['hk2_mieng'] * 1;
        $count += 1;
    }
    if (is_numeric($bangDiem[$maMonHoc]['hk2_1tiet'])) {
        $sum += $bangDiem[$maMonHoc]['hk2_1tiet'] * 2;
        $count += 2;
    }
    if (is_numeric($bangDiem[$maMonHoc]['hk2_thiGK'])) {
        $sum += $bangDiem[$maMonHoc]['hk2_thiGK'] * 2;
        $count += 2;
    }
    if (is_numeric($bangDiem[$maMonHoc]['hk2_thiCK'])) {
        $sum += $bangDiem[$maMonHoc]['hk2_thiCK'] * 3;
        $count += 3;
    }
    $bangDiem[$maMonHoc]['tbHK2'] = $count > 0 ? round($sum / $count, 1) : null;

    // Trung bình môn
    $hk1 = $bangDiem[$maMonHoc]['tbHK1'];
    $hk2 = $bangDiem[$maMonHoc]['tbHK2'];
    if (is_numeric($hk1) && is_numeric($hk2)) {
        $bangDiem[$maMonHoc]['tb'] = round(($hk1 + $hk2) / 2, 1);
    } elseif (is_numeric($hk1)) {
        $bangDiem[$maMonHoc]['tb'] = $hk1;
    } elseif (is_numeric($hk2)) {
        $bangDiem[$maMonHoc]['tb'] = $hk2;
    }
}

// === TÍNH TOÁN PHÂN TRANG ===
$totalItems = count($bangDiem);
$totalPages = ceil($totalItems / $itemsPerPage);
$bangDiemPaged = array_slice($bangDiem, $offset, $itemsPerPage, true);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Bảng điểm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../sidebar.css">
    <link rel="stylesheet" href="../content.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f7f9fb;
            margin: 0;
        }

        .header {
            padding: 10px 25px;
        }

        h1 {
            text-align: center;
            color: #0b1e6b;
            margin-top: 20px;
            letter-spacing: 1px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #eee;
            padding: 10px;
            text-align: center;
        }

        th {
            background: #f1f3f9;
            text-transform: uppercase;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        td:first-child {
            width: 60px;
        }

        .button-container {
            margin-right: 50px;
            display: flex;
            justify-content: flex-end;
            height: 80px;
        }

        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
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
                <div class="menu-title">Trang cá nhân</div>
                <ul>
                    <li onclick="window.location.href='../pagehocsinh/ttcanhan.php'"><i class="fa-solid fa-house"></i> Thông tin cá nhân</li>
                    <li onclick="window.location.href='../pagehocsinh/thongbao.php'"><i class="fa-solid fa-bell"></i> Thông báo</li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-title">Tra cứu thông tin</div>
                <ul>
                    <li onclick="window.location.href='../pagehocsinh/tlhoctap.php'"><i class="fa-solid fa-book"></i> Tài liệu học tập</li>
                    <li class="active" onclick="window.location.href='../pagehocsinh/kqhoctap.php'"><i class="fa-solid fa-file-lines"></i> Kết quả học tập</li>
                </ul>
            </div>
        </nav>
    </aside>
    <div class="main-content">
        <header class="header">
            <div class="left">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchScores" placeholder="Tìm kiếm môn học...">
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
                    <span><?= htmlspecialchars($hs['hoVaTen']) ?></span>
                    <i class="fa-solid fa-angle-down"></i>
                </div>
                <div class="user-menu" id="userMenu">
                    <ul>
                        <li onclick="window.location.href='../pagehocsinh/ttcanhan.php'"><i class="fa-solid fa-user-gear"></i> Hồ sơ</li>
                        <li onclick="logout()"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="container">
            <h1>BẢNG ĐIỂM</h1>

            <?php if (empty($bangDiem)): ?>
                <div class="no-data">Chưa có dữ liệu điểm.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="checkAll"></th>
                            <th>STT</th>
                            <th>MÔN HỌC</th>
                            <th>ĐIỂM HK I</th>
                            <th>ĐIỂM HK II</th>
                            <th>TRUNG BÌNH</th>
                            <th>TÁC VỤ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = $offset + 1;
                        foreach ($bangDiemPaged as $maMonHoc => $d): ?>
                            <tr class="score-row" data-subject="<?= htmlspecialchars(strtolower($d['tenMonHoc'])) ?>">
                                <td><input type='checkbox' class='rowCheckbox'></td>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($d['tenMonHoc']) ?></td>
                                <td><?= $d['tbHK1'] ?? '-' ?></td>
                                <td><?= $d['tbHK2'] ?? '-' ?></td>
                                <td><strong><?= $d['tb'] ?? '-' ?></strong></td>
                                <td>
                                    <a href="xemchitiet.php?maMonHoc=<?= urlencode($d['maMonHoc']) ?>" title="Xem chi tiết" style="text-decoration: none">
                                        <i class="fa-solid fa-eye" style="color:black;"></i>
                                    </a>
                                    &nbsp;
                                    <a href="xuat_diem_excel.php?mon=<?= urlencode($d['tenMonHoc']) ?>" title="Xuất điểm" style="text-decoration: none">
                                        <i class="fa-solid fa-file-export" style="color:black;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- ========= THANH PHÂN TRANG ========= -->
                <div style="padding:12px 16px; background:#f9f9f9; display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; margin-top:10px;">
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
                <!-- Nút EXport -->
                <div class="button-container">
                    <form method="POST" action="xuat_diem_excel.php" id="exportForm">
                        <input type="hidden" name="selectedHS" id="selectedHS">
                        <button type="submit" style="margin:20px 0; padding:10px 16px; background:green; color:white; border:none; border-radius:6px; cursor:pointer; width:200px; height: 41px;">
                            Xuất bảng điểm
                        </button>
                    </form>
                </div>
                <!-- ========= HẾT THANH PHÂN TRANG ========= -->
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Checkbox "chọn tất cả"
        const checkAll = document.getElementById("checkAll");
        const rowCheckboxes = document.querySelectorAll(".rowCheckbox");

        // Khi tick vào checkbox đầu tiên
        checkAll.addEventListener("change", function() {
            rowCheckboxes.forEach(cb => cb.checked = checkAll.checked);
        });

        // Khi submit form xuất nhiều môn
        document.getElementById("exportForm").addEventListener("submit", function(e) {
            const selected = [];
            document.querySelectorAll(".rowCheckbox:checked").forEach(cb => {
                selected.push(cb.value);
            });
            if (selected.length === 0) {
                alert("Vui lòng chọn ít nhất một môn để xuất!");
                e.preventDefault();
                return;
            }
            document.getElementById("selectedMon").value = selected.join(",");
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

        // === CHỨC NĂNG TÌM KIẾM BẢNG ĐIỂM ===
        const searchInput = document.getElementById("searchScores");
        const scoreRows = document.querySelectorAll(".score-row");

        if (searchInput) {
            searchInput.addEventListener("input", function() {
                const keyword = this.value.trim().toLowerCase();
                let foundCount = 0;

                scoreRows.forEach(row => {
                    const subject = row.getAttribute("data-subject").toLowerCase();

                    if (subject.includes(keyword)) {
                        row.style.display = "";
                        foundCount++;
                    } else {
                        row.style.display = "none";
                    }
                });
            });
        }
    </script>
</body>

</html>