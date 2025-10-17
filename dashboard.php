<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tổng quan</title>
  <style>
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      margin: 0;
      color: #1a1a1a;
    }

    h1 {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 24px;
    }

    .stats {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
    }

    .card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      flex: 1;
      min-width: 250px;
      padding: 20px;
    }

    .card h3 {
      font-size: 14px;
      color: #444;
      margin: 0 0 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-weight: 600;
    }

    .card .number {
      font-size: 32px;
      font-weight: 700;
      color: #0b3364;
    }

    .card p {
      font-size: 13px;
      color: #777;
      margin: 6px 0 0;
    }

    .bottom {
      display: flex;
      margin-top: 30px;
      gap: 20px;
      flex-wrap: wrap;
    }

    .recent,
    .quick {
      flex: 1;
      min-width: 300px;
      background: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }

    .recent h2,
    .quick h2 {
      font-size: 16px;
      margin-bottom: 16px;
      font-weight: 700;
      color: #1a1a1a;
    }

    .activity {
      margin-bottom: 14px;
    }

    .activity p {
      margin: 0;
      font-size: 14px;
      color: #333;
    }

    .activity span {
      font-size: 12px;
      color: #777;
    }

    .quick {
      background: #0b3364;
      color: #fff;
    }

    .quick h2 {
      color: #fff;
    }

    .quick button {
      width: 100%;
      background: #fff;
      border: none;
      border-radius: 6px;
      padding: 12px;
      margin-bottom: 10px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      color: #0b3364;
      transition: background 0.2s;
    }

    .quick button:hover {
      background: #f0f2f6;
    }

    @media (max-width: 768px) {

      .stats,
      .bottom {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <?php
  include_once(__DIR__ . "/../csdl/db.php");

  // Lấy 4 thông báo mới nhất
  $sql = "SELECT t.tieuDe, t.noiDung, t.ngayGui, u.hoVaTen 
          FROM thongbao t 
          LEFT JOIN user u ON t.nguoiGui = u.userID 
          ORDER BY t.ngayGui DESC 
          LIMIT 4";
  $result = $conn->query($sql);
  ?>

  <h1>TỔNG QUAN</h1>

  <div class="stats">
    <div class="card">
      <h3>TỔNG HỌC SINH <span>🎓</span></h3>
      <div class="number">
        <?php
        $rs = $conn->query("SELECT COUNT(*) AS total FROM user WHERE vaiTro='HocSinh'");
        echo $rs->fetch_assoc()['total'];
        ?>
      </div>
      <p>Học sinh hoạt động trong năm nay</p>
    </div>

    <div class="card">
      <h3>GIÁO VIÊN <span>👩‍🏫</span></h3>
      <div class="number">
        <?php
        $rs = $conn->query("SELECT COUNT(*) AS total FROM user WHERE vaiTro='GiaoVien'");
        echo $rs->fetch_assoc()['total'];
        ?>
      </div>
      <p>Cán bộ/giáo viên</p>
    </div>

    <div class="card">
      <h3>LỚP HỌC <span>🏫</span></h3>
      <div class="number">
        <?php
        $rs = $conn->query("SELECT COUNT(*) AS total FROM lophoc");
        echo $rs->fetch_assoc()['total'];
        ?>
      </div>
      <p>Lớp đang vận hành</p>
    </div>
  </div>

  <div class="bottom">
    <div class="recent">
      <h2>HOẠT ĐỘNG GẦN ĐÂY</h2>

      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="activity">
            <p>📝 <strong><?= htmlspecialchars($row['tieuDe']) ?></strong><br>
              <?= htmlspecialchars($row['noiDung']) ?>
              <?= $row['hoVaTen'] ? ' - <em>' . htmlspecialchars($row['hoVaTen']) . '</em>' : '' ?>
            </p>
            <span><?= date('d/m/Y H:i', strtotime($row['ngayGui'])) ?></span>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>Không có hoạt động gần đây.</p>
      <?php endif; ?>
    </div>

    <div class="quick">
      <h2>CÁC TÁC VỤ NHANH</h2>
      <button onclick="window.location.href='qlhocsinh.php'">🎓 Thêm học sinh</button>
      <button onclick="window.location.href='qlgiaovien.php'">👩‍🏫 Thêm giáo viên</button>
      <button onclick="window.location.href='qllophoc.php'">🏫 Tạo lớp học mới</button>
    </div>
  </div>
</body>
</html>
