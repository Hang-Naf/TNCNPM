-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 05:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cnpm`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `maAdmin` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`maAdmin`) VALUES
(1);

-- --------------------------------------------------------

--
-- Table structure for table `bainop`
--

CREATE TABLE `bainop` (
  `maBaiNop` int(11) NOT NULL,
  `maHS` int(11) DEFAULT NULL,
  `maTL` int(11) DEFAULT NULL,
  `fileBaiNop` varchar(255) DEFAULT NULL,
  `thoiGianNop` datetime DEFAULT current_timestamp(),
  `diem` float DEFAULT NULL,
  `nhanXet` text DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `bainop`
--
DELIMITER $$
CREATE TRIGGER `after_bainop_insert` AFTER INSERT ON `bainop` FOR EACH ROW BEGIN
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Nộp bài', CONCAT('Học sinh ', NEW.maHS, ' đã nộp bài ', NEW.maTL), 'Info');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_bainop_notify` AFTER INSERT ON `bainop` FOR EACH ROW BEGIN
  DECLARE idThongBao INT;

  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Bài nộp mới', CONCAT('Học sinh ', NEW.maHS, ' đã nộp bài ', NEW.maTL), NEW.maHS);
  SET idThongBao = LAST_INSERT_ID();

  -- Gửi thông báo cho giáo viên phụ trách môn tương ứng
  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT gv.maGV, idThongBao
  FROM giaovien_monhoc gv
  JOIN tailieu t ON t.maMonHoc = gv.maMonHoc
  WHERE t.maTL = NEW.maTL;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_bainop_update_notify` AFTER UPDATE ON `bainop` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);
  DECLARE tenMon VARCHAR(100);
  DECLARE idThongBao INT;

  IF NEW.diem IS NOT NULL AND OLD.diem IS NULL THEN
    SELECT u.hoVaTen INTO tenGV
    FROM user u
    JOIN tailieu t ON t.maGV = u.userID
    WHERE t.maTL = NEW.maTL LIMIT 1;

    SELECT m.tenMonHoc INTO tenMon
    FROM monhoc m
    JOIN tailieu t ON t.maMonHoc = m.maMonHoc
    WHERE t.maTL = NEW.maTL LIMIT 1;

    INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
    VALUES (NEW.maHS, 'Chấm bài',
            CONCAT('Giáo viên ', tenGV, ' đã chấm bài ', tenMon, 
                   ' với điểm ', NEW.diem,
                   IF(NEW.nhanXet IS NOT NULL, CONCAT('. Nhận xét: ', NEW.nhanXet), '')),
            'Info');

    INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
    VALUES ('Bài nộp được chấm',
            CONCAT('Giáo viên ', tenGV, ' đã chấm bài ', tenMon, 
                   ' của bạn. Điểm: ', NEW.diem,
                   IF(NEW.nhanXet IS NOT NULL, CONCAT('. Nhận xét: ', NEW.nhanXet), '')),
            NULL);
    SET idThongBao = LAST_INSERT_ID();

    INSERT INTO thongbaouser (userID, maThongBao)
    VALUES (NEW.maHS, idThongBao);
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `baocao`
--

CREATE TABLE `baocao` (
  `maBaoCao` int(11) NOT NULL,
  `loaiBC` varchar(50) DEFAULT NULL,
  `noiDungBC` text DEFAULT NULL,
  `ngayTao` date DEFAULT curdate(),
  `maUser` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `baocao`
--
DELIMITER $$
CREATE TRIGGER `after_baocao_insert_notify` AFTER INSERT ON `baocao` FOR EACH ROW BEGIN
  DECLARE nguoi VARCHAR(100);
  DECLARE idThongBao INT;

  SELECT hoVaTen INTO nguoi FROM user WHERE userID = NEW.maUser LIMIT 1;

  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maUser, 'Gửi báo cáo', CONCAT('Người dùng ', nguoi, ' đã gửi báo cáo: ', NEW.loaiBC), 'Info');

  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Báo cáo mới', CONCAT(nguoi, ' vừa gửi báo cáo: ', NEW.loaiBC), NEW.maUser);
  SET idThongBao = LAST_INSERT_ID();

  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, idThongBao FROM user WHERE vaiTro = 'Admin';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `chuyencan`
--

CREATE TABLE `chuyencan` (
  `maDiemDanh` int(11) NOT NULL,
  `maHS` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL,
  `ngayHoc` date DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT NULL,
  `ghiChu` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `chuyencan`
--
DELIMITER $$
CREATE TRIGGER `after_chuyencan_insert_notify` AFTER INSERT ON `chuyencan` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);
  DECLARE idThongBao INT;

  -- Tìm tên giáo viên phụ trách lớp của học sinh đó
  SELECT u.hoVaTen INTO tenGV
  FROM user u
  JOIN lophoc_monhoc lm ON lm.maGV = u.userID
  JOIN hocsinh_lophoc hl ON hl.maLop = lm.maLop
  WHERE hl.maHS = NEW.maHS
  LIMIT 1;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Điểm danh',
          CONCAT('Giáo viên ', IFNULL(tenGV,'(Không rõ)'), 
                 ' đã điểm danh bạn ngày ', NEW.ngayHoc, 
                 ': ', NEW.trangThai), 'Info');

  -- Gửi thông báo cho học sinh
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Kết quả điểm danh',
          CONCAT('Bạn được điểm danh ngày ', NEW.ngayHoc, 
                 ' bởi giáo viên ', IFNULL(tenGV,'(Không rõ)'), 
                 ' (', NEW.trangThai, ').'),
          NULL);
  SET idThongBao = LAST_INSERT_ID();

  INSERT INTO thongbaouser (userID, maThongBao)
  VALUES (NEW.maHS, idThongBao);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_chuyencan_update_notify` AFTER UPDATE ON `chuyencan` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);
  DECLARE idThongBao INT;

  SELECT u.hoVaTen INTO tenGV
  FROM user u
  JOIN lophoc_monhoc lm ON lm.maGV = u.userID
  JOIN hocsinh_lophoc hl ON hl.maLop = lm.maLop
  WHERE hl.maHS = NEW.maHS
  LIMIT 1;

  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Cập nhật điểm danh',
          CONCAT('Giáo viên ', IFNULL(tenGV,'(Không rõ)'), 
                 ' đã sửa trạng thái điểm danh ngày ', NEW.ngayHoc,
                 ' thành: ', NEW.trangThai), 'Info');

  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Cập nhật điểm danh',
          CONCAT('Trạng thái điểm danh ngày ', NEW.ngayHoc,
                 ' của bạn được cập nhật thành ', NEW.trangThai, '.'),
          NULL);
  SET idThongBao = LAST_INSERT_ID();

  INSERT INTO thongbaouser (userID, maThongBao)
  VALUES (NEW.maHS, idThongBao);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `diemso`
--

CREATE TABLE `diemso` (
  `maDiem` int(11) NOT NULL,
  `maHS` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL,
  `loaiDiem` varchar(50) DEFAULT NULL,
  `diem` float DEFAULT NULL,
  `ngayCapNhat` date DEFAULT curdate(),
  `nhanXet` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `diemso`
--

INSERT INTO `diemso` (`maDiem`, `maHS`, `maMonHoc`, `loaiDiem`, `diem`, `ngayCapNhat`, `nhanXet`) VALUES
(1, 3, 2, 'Giữa kỳ', 8.5, '2025-10-01', 'Học tốt');

--
-- Triggers `diemso`
--
DELIMITER $$
CREATE TRIGGER `after_diemso_delete` AFTER DELETE ON `diemso` FOR EACH ROW BEGIN
  DECLARE idThongBao INT;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (OLD.maHS, 'Xóa điểm', 
          CONCAT('Điểm ', OLD.loaiDiem, ' môn ', OLD.maMonHoc, 
                 ' của học sinh ', OLD.maHS, ' đã bị xóa.'), 
          'Warning');

  -- Gửi thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Điểm bị xóa',
          CONCAT('Điểm ', OLD.loaiDiem, ' môn ', OLD.maMonHoc, 
                 ' của bạn đã bị xóa khỏi hệ thống.'), 
          NULL);

  SET idThongBao = LAST_INSERT_ID();

  INSERT INTO thongbaouser (userID, maThongBao)
  VALUES (OLD.maHS, idThongBao);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_diemso_delete_notify_admin` AFTER DELETE ON `diemso` FOR EACH ROW BEGIN
  DECLARE gvID INT;
  DECLARE tenGV VARCHAR(100);
  DECLARE tenLop VARCHAR(100);
  DECLARE tenMon VARCHAR(100);
  DECLARE idThongBao INT;

  -- Lấy lớp học sinh
  SELECT l.tenLop INTO tenLop
  FROM hocsinh_lophoc hl
  JOIN lophoc l ON hl.maLop = l.maLop
  WHERE hl.maHS = OLD.maHS
  LIMIT 1;

  -- Lấy giáo viên dạy môn đó của lớp
  SELECT lm.maGV INTO gvID
  FROM lophoc_monhoc lm
  JOIN hocsinh_lophoc hl ON lm.maLop = hl.maLop
  WHERE hl.maHS = OLD.maHS AND lm.maMonHoc = OLD.maMonHoc
  LIMIT 1;

  -- Lấy tên giáo viên & tên môn học
  SELECT hoVaTen INTO tenGV FROM user WHERE userID = gvID LIMIT 1;
  SELECT tenMonHoc INTO tenMon FROM monhoc WHERE maMonHoc = OLD.maMonHoc LIMIT 1;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (OLD.maHS, 'Xóa điểm',
          CONCAT('Giáo viên ', tenGV, ' đã xóa điểm ', OLD.loaiDiem,
                 ' môn ', tenMon, ' (', OLD.diem, ')'), 'Warning');

  -- Gửi thông báo cho học sinh
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Điểm bị xóa',
          CONCAT('Giáo viên ', tenGV, ' đã xóa điểm ', OLD.loaiDiem,
                 ' môn ', tenMon, ' của bạn khỏi hệ thống.'),
          gvID);
  SET idThongBao = LAST_INSERT_ID();
  INSERT INTO thongbaouser (userID, maThongBao) VALUES (OLD.maHS, idThongBao);

  -- Gửi thông báo cho admin
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Giáo viên xóa điểm',
          CONCAT('Giáo viên ', tenGV, ' đã xóa điểm ', OLD.loaiDiem,
                 ' của lớp ', IFNULL(tenLop,'(Không xác định)'), '.'),
          gvID);
  SET idThongBao = LAST_INSERT_ID();
  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, idThongBao FROM user WHERE vaiTro = 'Admin';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_diemso_insert` AFTER INSERT ON `diemso` FOR EACH ROW BEGIN
  DECLARE idThongBao INT;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Thêm điểm', 
          CONCAT('Giáo viên đã thêm điểm ', NEW.loaiDiem, 
                 ' môn ', NEW.maMonHoc, ' cho học sinh ', NEW.maHS, 
                 ' (', NEW.diem, ')'), 
          'Info');

  -- Gửi thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Điểm mới được thêm',
          CONCAT('Bạn vừa được thêm điểm ', NEW.loaiDiem, 
                 ' môn ', NEW.maMonHoc, ' với số điểm: ', NEW.diem),
          NULL);

  SET idThongBao = LAST_INSERT_ID();

  -- Gửi thông báo đến học sinh được chấm điểm
  INSERT INTO thongbaouser (userID, maThongBao)
  VALUES (NEW.maHS, idThongBao);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_diemso_insert_notify_admin` AFTER INSERT ON `diemso` FOR EACH ROW BEGIN
  DECLARE gvID INT;
  DECLARE tenGV VARCHAR(100);
  DECLARE tenLop VARCHAR(100);
  DECLARE tenMon VARCHAR(100);
  DECLARE idThongBao INT;

  -- Lấy lớp học sinh
  SELECT l.tenLop INTO tenLop
  FROM hocsinh_lophoc hl
  JOIN lophoc l ON hl.maLop = l.maLop
  WHERE hl.maHS = NEW.maHS
  LIMIT 1;

  -- Lấy giáo viên dạy môn đó của lớp
  SELECT lm.maGV INTO gvID
  FROM lophoc_monhoc lm
  JOIN hocsinh_lophoc hl ON lm.maLop = hl.maLop
  WHERE hl.maHS = NEW.maHS AND lm.maMonHoc = NEW.maMonHoc
  LIMIT 1;

  -- Lấy tên giáo viên & tên môn học
  SELECT hoVaTen INTO tenGV FROM user WHERE userID = gvID LIMIT 1;
  SELECT tenMonHoc INTO tenMon FROM monhoc WHERE maMonHoc = NEW.maMonHoc LIMIT 1;

  -- Ghi log cho học sinh
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Thêm điểm',
          CONCAT('Giáo viên ', tenGV, ' đã nhập điểm ', NEW.loaiDiem,
                 ' môn ', tenMon, ': ', NEW.diem), 'Info');

  -- Gửi thông báo cho học sinh
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Điểm mới được thêm',
          CONCAT('Giáo viên ', tenGV, ' đã nhập điểm ', NEW.loaiDiem,
                 ' môn ', tenMon, ' của bạn: ', NEW.diem),
          gvID);
  SET idThongBao = LAST_INSERT_ID();
  INSERT INTO thongbaouser (userID, maThongBao) VALUES (NEW.maHS, idThongBao);

  -- Gửi thông báo cho admin
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Giáo viên nhập điểm',
          CONCAT('Giáo viên ', tenGV, ' đã nhập điểm ', NEW.loaiDiem,
                 ' cho lớp ', IFNULL(tenLop,'(Không xác định)'), '.'),
          gvID);
  SET idThongBao = LAST_INSERT_ID();
  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, idThongBao FROM user WHERE vaiTro = 'Admin';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_diemso_update` AFTER UPDATE ON `diemso` FOR EACH ROW BEGIN
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Cập nhật điểm', CONCAT('Điểm mới cho môn ', NEW.maMonHoc, ': ', NEW.diem), 'Info');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_diemso_update_notify_admin` AFTER UPDATE ON `diemso` FOR EACH ROW BEGIN
  DECLARE gvID INT;
  DECLARE tenGV VARCHAR(100);
  DECLARE tenLop VARCHAR(100);
  DECLARE tenMon VARCHAR(100);
  DECLARE idThongBao INT;

  -- Lấy lớp học sinh
  SELECT l.tenLop INTO tenLop
  FROM hocsinh_lophoc hl
  JOIN lophoc l ON hl.maLop = l.maLop
  WHERE hl.maHS = NEW.maHS
  LIMIT 1;

  -- Lấy giáo viên dạy môn đó của lớp
  SELECT lm.maGV INTO gvID
  FROM lophoc_monhoc lm
  JOIN hocsinh_lophoc hl ON lm.maLop = hl.maLop
  WHERE hl.maHS = NEW.maHS AND lm.maMonHoc = NEW.maMonHoc
  LIMIT 1;

  -- Lấy tên giáo viên & môn học
  SELECT hoVaTen INTO tenGV FROM user WHERE userID = gvID LIMIT 1;
  SELECT tenMonHoc INTO tenMon FROM monhoc WHERE maMonHoc = NEW.maMonHoc LIMIT 1;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Cập nhật điểm',
          CONCAT('Giáo viên ', tenGV, ' đã chỉnh sửa điểm ', NEW.loaiDiem,
                 ' môn ', tenMon, ' từ ', OLD.diem, ' thành ', NEW.diem), 'Info');

  -- Gửi thông báo cho học sinh
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Điểm học tập được cập nhật',
          CONCAT('Giáo viên ', tenGV, ' đã chỉnh sửa điểm ', NEW.loaiDiem,
                 ' môn ', tenMon, ' của bạn từ ', OLD.diem, ' thành ', NEW.diem, '.'),
          gvID);
  SET idThongBao = LAST_INSERT_ID();
  INSERT INTO thongbaouser (userID, maThongBao) VALUES (NEW.maHS, idThongBao);

  -- Gửi thông báo cho admin
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Giáo viên chỉnh sửa điểm',
          CONCAT('Giáo viên ', tenGV, ' đã cập nhật điểm ', NEW.loaiDiem,
                 ' cho lớp ', IFNULL(tenLop,'(Không xác định)'), '.'),
          gvID);
  SET idThongBao = LAST_INSERT_ID();
  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, idThongBao FROM user WHERE vaiTro = 'Admin';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `ghilog`
--

CREATE TABLE `ghilog` (
  `maLog` int(11) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `hanhDong` varchar(255) DEFAULT NULL,
  `noiDungLog` text DEFAULT NULL,
  `thoiGianLog` datetime DEFAULT current_timestamp(),
  `loaiLog` enum('Info','Warning','Error') NOT NULL DEFAULT 'Info'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ghilog`
--

INSERT INTO `ghilog` (`maLog`, `userID`, `hanhDong`, `noiDungLog`, `thoiGianLog`, `loaiLog`) VALUES
(1, 44, 'Thêm người dùng', 'Tài khoản GV Test (GiaoVien) đã được thêm.', '2025-10-15 21:32:10', 'Info'),
(2, 44, 'Thêm người dùng', 'Tài khoản GV Test (GiaoVien) đã được thêm.', '2025-10-15 21:32:10', 'Info'),
(3, 45, 'Thêm người dùng', 'Tài khoản GV Test 1 (GiaoVien) đã được thêm.', '2025-10-15 21:33:49', 'Info'),
(4, 1, 'Thêm giáo viên', 'Đã thêm hồ sơ giáo viên admin (mã: 1).', '2025-10-15 21:36:36', 'Info'),
(5, 1, 'Cập nhật hồ sơ giáo viên', 'Giáo viên admin được cập nhật: Bộ môn: \"Toán\" → \"Lý\"; Học kỳ: \"HK1\" → \"HK2\"; ', '2025-10-15 21:36:36', 'Info'),
(6, 1, 'Xóa giáo viên', 'Giáo viên admin (mã: 1) đã bị xóa khỏi hệ thống.', '2025-10-15 21:36:36', 'Warning'),
(7, 44, 'Cập nhật hồ sơ giáo viên', 'Giáo viên GV Test được cập nhật: Bộ môn: \"Chưa xác định\" → \"Sinh học\"; Trình độ: \"Chưa cập nhật\" → \"Đại học\"; Phòng ban: \"Chưa cập nhật\" → \"KHTN\"; Năm học: \"Chưa cập nhật\" → \"2025-2026\"; Học kỳ: \"Chưa cập nhật\" → \"HK1\"; ', '2025-10-15 21:39:59', 'Info'),
(8, 1, 'Tạo thông báo', 'Người dùng admin tạo thông báo: Thông báo kiểm tra - Đây là thông báo hệ thống thử nghiệm.', '2025-10-15 21:43:57', 'Info'),
(9, 1, 'Gửi thông báo', 'Thông báo \"Thông báo kiểm tra\" được gửi đến 2 người dùng.', '2025-10-15 21:43:57', 'Info'),
(21, 45, 'Xóa giáo viên', 'Giáo viên GV Test 1 (mã: 45) đã bị xóa khỏi hệ thống.', '2025-10-15 22:17:17', 'Warning'),
(22, 0, 'Tạo thông báo', 'Hệ thống tạo thông báo: Xóa hồ sơ giáo viên - Giáo viên GV Test 1 đã bị xóa.', '2025-10-15 22:17:17', 'Info'),
(23, 0, 'Gửi thông báo', 'Thông báo \"Xóa hồ sơ giáo viên\" được gửi đến 1 người dùng.', '2025-10-15 22:17:17', 'Info'),
(24, 45, 'Xóa người dùng', 'Đã xóa tài khoản GV Test 1 (GiaoVien) khỏi hệ thống.', '2025-10-15 22:17:17', 'Warning'),
(25, 0, 'Tạo thông báo', 'Hệ thống tạo thông báo: Xóa người dùng - Tài khoản GV Test 1 (GiaoVien) đã bị xóa.', '2025-10-15 22:17:17', 'Info'),
(26, 0, 'Gửi thông báo', 'Thông báo \"Xóa người dùng\" được gửi đến 1 người dùng.', '2025-10-15 22:17:17', 'Info'),
(27, 47, 'Thêm học sinh', 'Đã thêm hồ sơ học sinh hs2 (mã: 47).', '2025-10-15 22:24:14', 'Info'),
(28, 47, 'Tạo thông báo', 'Người dùng hs2 tạo thông báo: Thêm hồ sơ học sinh - Học sinh hs2 vừa được thêm vào hệ thống.', '2025-10-15 22:24:14', 'Info'),
(29, 47, 'Gửi thông báo', 'Thông báo \"Thêm hồ sơ học sinh\" được gửi đến 2 người dùng.', '2025-10-15 22:24:14', 'Info'),
(30, 47, 'Thêm người dùng', 'Tài khoản hs2 (HocSinh) đã được thêm.', '2025-10-15 22:24:14', 'Info'),
(31, 47, 'Tạo thông báo', 'Người dùng hs2 tạo thông báo: Thêm người dùng mới - Đã thêm HocSinh hs2 vào hệ thống.', '2025-10-15 22:24:14', 'Info'),
(32, 47, 'Gửi thông báo', 'Thông báo \"Thêm người dùng mới\" được gửi đến 2 người dùng.', '2025-10-15 22:24:14', 'Info'),
(33, 47, 'Cập nhật học sinh', 'Học sinh hs2 được cập nhật: Lớp học: \"Chưa cập nhật\" → \"11A1\"; Năm học: \"Chưa cập nhật\" → \"2025-2026\"; Học kỳ: \"Chưa cập nhật\" → \"HK1\"; ', '2025-10-15 22:24:14', 'Info'),
(34, 47, 'Tạo thông báo', 'Người dùng hs2 tạo thông báo: Cập nhật hồ sơ học sinh - Thông tin học sinh hs2 đã được cập nhật.', '2025-10-15 22:24:14', 'Info'),
(35, 47, 'Gửi thông báo', 'Thông báo \"Cập nhật hồ sơ học sinh\" được gửi đến 2 người dùng.', '2025-10-15 22:24:14', 'Info'),
(36, 37, 'Cập nhật học sinh', 'Học sinh hs1 được cập nhật: Trạng thái: \"inactive\" → \"active\"; ', '2025-10-15 22:24:43', 'Info'),
(37, 37, 'Tạo thông báo', 'Người dùng hs1 tạo thông báo: Cập nhật hồ sơ học sinh - Thông tin học sinh hs1 đã được cập nhật.', '2025-10-15 22:24:43', 'Info'),
(38, 37, 'Gửi thông báo', 'Thông báo \"Cập nhật hồ sơ học sinh\" được gửi đến 2 người dùng.', '2025-10-15 22:24:43', 'Info');

-- --------------------------------------------------------

--
-- Table structure for table `giaovien`
--

CREATE TABLE `giaovien` (
  `maGV` int(11) NOT NULL,
  `boMon` varchar(100) NOT NULL,
  `trinhDo` varchar(100) NOT NULL,
  `anhDaiDien` varchar(255) NOT NULL DEFAULT 'Chưa cập nhật',
  `phongBan` varchar(100) NOT NULL,
  `namHoc` varchar(50) NOT NULL DEFAULT 'Chưa cập nhật',
  `hocKy` varchar(20) NOT NULL DEFAULT 'Chưa cập nhật',
  `trangThai` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giaovien`
--

INSERT INTO `giaovien` (`maGV`, `boMon`, `trinhDo`, `anhDaiDien`, `phongBan`, `namHoc`, `hocKy`, `trangThai`) VALUES
(2, 'Ngữ Văn', 'Thạc sĩ', 'Chưa cập nhật', 'Văn', '2022-2025', 'HK2', 'active'),
(16, 'Toán học', 'Thạc sĩ', '', 'toán', '2022-2025', 'HK2', 'active'),
(17, 'Hóa học', 'Chưa cập nhật', '', 'toán', '2022-2025', 'HK1', 'inactive'),
(36, 'Toán học', 'không', 'Chưa cập nhật', 'toán', '2022-2025', 'HK1', 'active'),
(40, 'Lịch sử', 'không', 'Chưa cập nhật', 'sử', '2022-2025', 'HK1', 'active'),
(41, 'Sinh học', 'không', 'Chưa cập nhật', 'sinh học', '2022-2025', 'HK1', 'active'),
(42, 'Vật lý', 'Thạc sĩ', 'Chưa cập nhật', 'lý', '2025-2026', 'HK1', 'active'),
(43, 'Hóa học', 'Thạc sĩ', 'Chưa cập nhật', 'hóa', '2025-2026', 'HK1', 'active'),
(44, 'Sinh học', 'Đại học', 'Chưa cập nhật', 'KHTN', '2025-2026', 'HK1', 'active');

--
-- Triggers `giaovien`
--
DELIMITER $$
CREATE TRIGGER `after_giaovien_delete` AFTER DELETE ON `giaovien` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);
  SELECT hoVaTen INTO tenGV FROM user WHERE userID = OLD.maGV;

  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (OLD.maGV, 'Xóa giáo viên', CONCAT('Giáo viên ', tenGV, ' (mã: ', OLD.maGV, ') đã bị xóa khỏi hệ thống.'), 'Warning');

  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Xóa hồ sơ giáo viên', CONCAT('Giáo viên ', tenGV, ' đã bị xóa.'), NULL);

  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_giaovien_insert` AFTER INSERT ON `giaovien` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);

  -- Lấy tên giáo viên từ bảng user
  SELECT hoVaTen INTO tenGV FROM user WHERE userID = NEW.maGV;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maGV, 'Thêm giáo viên', CONCAT('Đã thêm hồ sơ giáo viên ', tenGV, ' (mã: ', NEW.maGV, ').'), 'Info');

  -- Thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Thêm hồ sơ giáo viên', CONCAT('Giáo viên ', tenGV, ' vừa được thêm vào hệ thống.'), NEW.maGV);

  -- Gửi thông báo đến Admin
  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_giaovien_update` AFTER UPDATE ON `giaovien` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);
  DECLARE thaydoi TEXT DEFAULT '';

  SELECT hoVaTen INTO tenGV FROM user WHERE userID = NEW.maGV;

  IF OLD.boMon <> NEW.boMon THEN
    SET thaydoi = CONCAT(thaydoi, 'Bộ môn: "', OLD.boMon, '" → "', NEW.boMon, '"; ');
  END IF;
  IF OLD.trinhDo <> NEW.trinhDo THEN
    SET thaydoi = CONCAT(thaydoi, 'Trình độ: "', OLD.trinhDo, '" → "', NEW.trinhDo, '"; ');
  END IF;
  IF OLD.phongBan <> NEW.phongBan THEN
    SET thaydoi = CONCAT(thaydoi, 'Phòng ban: "', OLD.phongBan, '" → "', NEW.phongBan, '"; ');
  END IF;
  IF OLD.namHoc <> NEW.namHoc THEN
    SET thaydoi = CONCAT(thaydoi, 'Năm học: "', OLD.namHoc, '" → "', NEW.namHoc, '"; ');
  END IF;
  IF OLD.hocKy <> NEW.hocKy THEN
    SET thaydoi = CONCAT(thaydoi, 'Học kỳ: "', OLD.hocKy, '" → "', NEW.hocKy, '"; ');
  END IF;
  IF OLD.trangThai <> NEW.trangThai THEN
    SET thaydoi = CONCAT(thaydoi, 'Trạng thái: "', OLD.trangThai, '" → "', NEW.trangThai, '"; ');
  END IF;

  IF thaydoi <> '' THEN
    INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
    VALUES (NEW.maGV, 'Cập nhật hồ sơ giáo viên', CONCAT('Giáo viên ', tenGV, ' được cập nhật: ', thaydoi), 'Info');

    INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
    VALUES ('Cập nhật hồ sơ giáo viên', CONCAT('Thông tin giáo viên ', tenGV, ' đã được cập nhật.'), NEW.maGV);

    INSERT INTO thongbaouser (userID, maThongBao)
    SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `giaovien_monhoc`
--

CREATE TABLE `giaovien_monhoc` (
  `id` int(11) NOT NULL,
  `maGV` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `giaovien_monhoc`
--

INSERT INTO `giaovien_monhoc` (`id`, `maGV`, `maMonHoc`) VALUES
(4, 16, 1),
(7, 17, 3),
(11, 40, 6),
(12, 41, 4);

-- --------------------------------------------------------

--
-- Table structure for table `hocsinh`
--

CREATE TABLE `hocsinh` (
  `maHS` int(11) NOT NULL,
  `lopHocPhuTrach` varchar(50) DEFAULT NULL,
  `anhDaiDien` varchar(255) NOT NULL DEFAULT 'Chưa cập nhật',
  `namHoc` varchar(50) NOT NULL DEFAULT 'Chưa cập nhật',
  `hocKy` varchar(20) NOT NULL DEFAULT 'Chưa cập nhật',
  `trangThai` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hocsinh`
--

INSERT INTO `hocsinh` (`maHS`, `lopHocPhuTrach`, `anhDaiDien`, `namHoc`, `hocKy`, `trangThai`) VALUES
(3, '10A1', 'hs_b.jpg', '2024-2025', 'HK2', 'active'),
(4, '10A2', 'Chưa cập nhật', '2024-2025', 'HK2', 'inactive'),
(37, '10A3', 'Chưa cập nhật', '2024-2025', 'HK1', 'active'),
(47, '11A1', 'Chưa cập nhật', '2025-2026', 'HK1', 'active');

--
-- Triggers `hocsinh`
--
DELIMITER $$
CREATE TRIGGER `after_hocsinh_delete` AFTER DELETE ON `hocsinh` FOR EACH ROW BEGIN
  DECLARE tenHS VARCHAR(100);
  SELECT hoVaTen INTO tenHS FROM user WHERE userID = OLD.maHS;

  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (OLD.maHS, 'Xóa học sinh', CONCAT('Học sinh ', tenHS, ' (mã: ', OLD.maHS, ') đã bị xóa khỏi hệ thống.'), 'Warning');

  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Xóa hồ sơ học sinh', CONCAT('Học sinh ', tenHS, ' đã bị xóa khỏi hệ thống.'), NULL);

  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_hocsinh_insert` AFTER INSERT ON `hocsinh` FOR EACH ROW BEGIN
  DECLARE tenHS VARCHAR(100);

  -- Lấy tên học sinh từ bảng user
  SELECT hoVaTen INTO tenHS FROM user WHERE userID = NEW.maHS;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Thêm học sinh', CONCAT('Đã thêm hồ sơ học sinh ', tenHS, ' (mã: ', NEW.maHS, ').'), 'Info');

  -- Thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Thêm hồ sơ học sinh', CONCAT('Học sinh ', tenHS, ' vừa được thêm vào hệ thống.'), NEW.maHS);

  -- Gửi thông báo cho Admin
  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_hocsinh_update` AFTER UPDATE ON `hocsinh` FOR EACH ROW BEGIN
  DECLARE tenHS VARCHAR(100);
  DECLARE thaydoi TEXT DEFAULT '';

  SELECT hoVaTen INTO tenHS FROM user WHERE userID = NEW.maHS;

  IF OLD.lopHocPhuTrach <> NEW.lopHocPhuTrach THEN
    SET thaydoi = CONCAT(thaydoi, 'Lớp học: "', OLD.lopHocPhuTrach, '" → "', NEW.lopHocPhuTrach, '"; ');
  END IF;
  IF OLD.namHoc <> NEW.namHoc THEN
    SET thaydoi = CONCAT(thaydoi, 'Năm học: "', OLD.namHoc, '" → "', NEW.namHoc, '"; ');
  END IF;
  IF OLD.hocKy <> NEW.hocKy THEN
    SET thaydoi = CONCAT(thaydoi, 'Học kỳ: "', OLD.hocKy, '" → "', NEW.hocKy, '"; ');
  END IF;
  IF OLD.trangThai <> NEW.trangThai THEN
    SET thaydoi = CONCAT(thaydoi, 'Trạng thái: "', OLD.trangThai, '" → "', NEW.trangThai, '"; ');
  END IF;

  IF thaydoi <> '' THEN
    INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
    VALUES (NEW.maHS, 'Cập nhật học sinh', CONCAT('Học sinh ', tenHS, ' được cập nhật: ', thaydoi), 'Info');

    INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
    VALUES ('Cập nhật hồ sơ học sinh', CONCAT('Thông tin học sinh ', tenHS, ' đã được cập nhật.'), NEW.maHS);

    INSERT INTO thongbaouser (userID, maThongBao)
    SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `hocsinh_lophoc`
--

CREATE TABLE `hocsinh_lophoc` (
  `id` int(11) NOT NULL,
  `maHS` int(11) DEFAULT NULL,
  `maLop` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hocsinh_lophoc`
--

INSERT INTO `hocsinh_lophoc` (`id`, `maHS`, `maLop`) VALUES
(1, 3, 1);

--
-- Triggers `hocsinh_lophoc`
--
DELIMITER $$
CREATE TRIGGER `after_hocsinh_lophoc_delete_notify` AFTER DELETE ON `hocsinh_lophoc` FOR EACH ROW BEGIN
  DECLARE tenLop VARCHAR(100);
  SELECT tenLop INTO tenLop FROM lophoc WHERE maLop = OLD.maLop LIMIT 1;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (OLD.maHS, 'Rời lớp', CONCAT('Học sinh đã bị xóa khỏi lớp ', tenLop), 'Warning');

  -- Gửi thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Rời khỏi lớp', CONCAT('Bạn đã bị xóa khỏi lớp ', tenLop, '.'), NULL);
  INSERT INTO thongbaouser (userID, maThongBao)
  VALUES (OLD.maHS, LAST_INSERT_ID());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_hocsinh_lophoc_insert_notify` AFTER INSERT ON `hocsinh_lophoc` FOR EACH ROW BEGIN
  DECLARE tenLop VARCHAR(100);
  SELECT tenLop INTO tenLop FROM lophoc WHERE maLop = NEW.maLop LIMIT 1;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maHS, 'Phân lớp', CONCAT('Học sinh được thêm vào lớp ', tenLop), 'Info');

  -- Gửi thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Phân lớp mới', CONCAT('Bạn đã được thêm vào lớp ', tenLop, '.'), NULL);
  INSERT INTO thongbaouser (userID, maThongBao)
  VALUES (NEW.maHS, LAST_INSERT_ID());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lophoc`
--

CREATE TABLE `lophoc` (
  `maLop` int(11) NOT NULL,
  `tenLop` varchar(100) DEFAULT NULL,
  `siSo` int(11) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT NULL,
  `namHoc` varchar(20) DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lophoc`
--

INSERT INTO `lophoc` (`maLop`, `tenLop`, `siSo`, `trangThai`, `namHoc`, `maGV`) VALUES
(1, '10A1', 40, 'Đang học', '2024-2025', NULL),
(2, '10A2', 38, 'Đang học', '2024-2025', NULL),
(3, '10A3', 30, 'Đang học', '2024-2025', 16),
(8, '11A1', 35, 'Đang học', '2024-2025', 17),
(9, '11A2', 35, 'Đang học', '2024-2025', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lophoc_monhoc`
--

CREATE TABLE `lophoc_monhoc` (
  `id` int(11) NOT NULL,
  `maLop` int(11) DEFAULT NULL,
  `maMonHoc` int(11) DEFAULT NULL,
  `maGV` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lophoc_monhoc`
--

INSERT INTO `lophoc_monhoc` (`id`, `maLop`, `maMonHoc`, `maGV`) VALUES
(1, 1, 1, 16),
(2, 1, 7, 2),
(3, 1, 3, 17),
(4, 2, 3, 17);

--
-- Triggers `lophoc_monhoc`
--
DELIMITER $$
CREATE TRIGGER `after_lophoc_monhoc_delete_notify` AFTER DELETE ON `lophoc_monhoc` FOR EACH ROW BEGIN
  DECLARE tenLop VARCHAR(100);
  SELECT tenLop INTO tenLop FROM lophoc WHERE maLop = OLD.maLop LIMIT 1;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (OLD.maGV, 'Hủy phân công', CONCAT('Giáo viên bị gỡ khỏi lớp ', tenLop), 'Warning');

  -- Gửi thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Hủy phân công giảng dạy', CONCAT('Bạn đã được gỡ khỏi lớp ', tenLop, '.'), NULL);
  INSERT INTO thongbaouser (userID, maThongBao)
  VALUES (OLD.maGV, LAST_INSERT_ID());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_lophoc_monhoc_insert_notify` AFTER INSERT ON `lophoc_monhoc` FOR EACH ROW BEGIN
  DECLARE tenLop VARCHAR(100);
  SELECT tenLop INTO tenLop FROM lophoc WHERE maLop = NEW.maLop LIMIT 1;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maGV, 'Phân công giảng dạy', CONCAT('Giáo viên được phân công dạy lớp ', tenLop), 'Info');

  -- Gửi thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Phân công giảng dạy', CONCAT('Bạn được phân công giảng dạy lớp ', tenLop, '.'), NULL);
  INSERT INTO thongbaouser (userID, maThongBao)
  VALUES (NEW.maGV, LAST_INSERT_ID());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `monhoc`
--

CREATE TABLE `monhoc` (
  `maMonHoc` int(11) NOT NULL,
  `tenMonHoc` varchar(100) DEFAULT NULL,
  `moTa` text NOT NULL,
  `hocKy` varchar(20) NOT NULL,
  `trongSo` float NOT NULL,
  `trangThai` varchar(50) NOT NULL,
  `namHoc` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `monhoc`
--

INSERT INTO `monhoc` (`maMonHoc`, `tenMonHoc`, `moTa`, `hocKy`, `trongSo`, `trangThai`, `namHoc`) VALUES
(1, 'Toán học', 'Môn học nền tảng về đại số và hình học.', 'HK2', 3, 'Hoạt động', '2024-2025'),
(2, 'Vật lý', 'Nghiên cứu về cơ học, nhiệt học và điện học.', 'HK1', 4, 'Hoạt động', '2024-2025'),
(3, 'Hóa học', 'Môn hóa', 'HK1', 2, 'Hoạt động', '2024-2025'),
(4, 'Sinh học', 'sinh', 'HK1', 2, 'Hoạt động', '2024-2025'),
(6, 'Lịch sử', 'không có', 'HK1', 3, 'Hoạt động', '2024-2025'),
(7, 'Ngữ Văn', 'văn', 'HK1', 3, 'Hoạt động', '2024-2025');

-- --------------------------------------------------------

--
-- Table structure for table `tailieu`
--

CREATE TABLE `tailieu` (
  `maTL` int(11) NOT NULL,
  `maMonHoc` int(11) DEFAULT NULL,
  `tieuDe` varchar(255) DEFAULT NULL,
  `noiDung` text DEFAULT NULL,
  `ngayTai` date DEFAULT curdate(),
  `maGV` int(11) DEFAULT NULL,
  `trangThai` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tailieu`
--

INSERT INTO `tailieu` (`maTL`, `maMonHoc`, `tieuDe`, `noiDung`, `ngayTai`, `maGV`, `trangThai`) VALUES
(1, 1, 'Bài giảng Đại số', 'Tài liệu học đại số cơ bản', '2025-09-15', NULL, 'Công khai'),
(2, 3, 'Bài giảng Cơ học', 'Tài liệu học phần cơ học', '2025-09-20', NULL, 'Công khai'),
(4, 6, 'sử ', 'khó thuộc', '2025-10-12', 40, 'Riêng tư');

--
-- Triggers `tailieu`
--
DELIMITER $$
CREATE TRIGGER `after_tailieu_delete_notify` AFTER DELETE ON `tailieu` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);
  DECLARE idThongBao INT;

  SELECT hoVaTen INTO tenGV FROM user WHERE userID = OLD.maGV LIMIT 1;

  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (OLD.maGV, 'Xóa tài liệu',
          CONCAT('Giáo viên ', tenGV, ' đã xóa tài liệu: ', OLD.tieuDe), 'Warning');

  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Tài liệu bị xóa',
          CONCAT('Giáo viên ', tenGV, ' đã xóa tài liệu: ', OLD.tieuDe, '.'),
          OLD.maGV);
  SET idThongBao = LAST_INSERT_ID();

  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT hl.maHS, idThongBao
  FROM hocsinh_lophoc hl
  JOIN lophoc_monhoc lm ON lm.maLop = hl.maLop
  WHERE lm.maGV = OLD.maGV AND lm.maMonHoc = OLD.maMonHoc;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_tailieu_insert_notify` AFTER INSERT ON `tailieu` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);
  DECLARE idThongBao INT;

  SELECT hoVaTen INTO tenGV FROM user WHERE userID = NEW.maGV LIMIT 1;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maGV, 'Đăng tài liệu',
          CONCAT('Giáo viên ', tenGV, ' đã đăng tài liệu mới: ', NEW.tieuDe), 'Info');

  -- Gửi thông báo đến học sinh của lớp dạy
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Tài liệu mới',
          CONCAT('Giáo viên ', tenGV, ' đã đăng tài liệu mới: ', NEW.tieuDe, '.'),
          NEW.maGV);
  SET idThongBao = LAST_INSERT_ID();

  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT hl.maHS, idThongBao
  FROM hocsinh_lophoc hl
  JOIN lophoc_monhoc lm ON lm.maLop = hl.maLop
  WHERE lm.maGV = NEW.maGV AND lm.maMonHoc = NEW.maMonHoc;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_tailieu_update_notify` AFTER UPDATE ON `tailieu` FOR EACH ROW BEGIN
  DECLARE tenGV VARCHAR(100);
  DECLARE idThongBao INT;

  SELECT hoVaTen INTO tenGV FROM user WHERE userID = NEW.maGV LIMIT 1;

  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.maGV, 'Cập nhật tài liệu',
          CONCAT('Giáo viên ', tenGV, ' đã cập nhật tài liệu: ', NEW.tieuDe), 'Info');

  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Tài liệu được cập nhật',
          CONCAT('Giáo viên ', tenGV, ' đã chỉnh sửa tài liệu: ', NEW.tieuDe, '.'),
          NEW.maGV);
  SET idThongBao = LAST_INSERT_ID();

  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT hl.maHS, idThongBao
  FROM hocsinh_lophoc hl
  JOIN lophoc_monhoc lm ON lm.maLop = hl.maLop
  WHERE lm.maGV = NEW.maGV AND lm.maMonHoc = NEW.maMonHoc;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `thongbao`
--

CREATE TABLE `thongbao` (
  `maThongBao` int(11) NOT NULL,
  `tieuDe` varchar(255) DEFAULT NULL,
  `noiDung` text DEFAULT NULL,
  `ngayGui` datetime DEFAULT current_timestamp(),
  `nguoiGui` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thongbao`
--

INSERT INTO `thongbao` (`maThongBao`, `tieuDe`, `noiDung`, `ngayGui`, `nguoiGui`) VALUES
(1, 'Lịch kiểm tra giữa kỳ', 'Các lớp sẽ kiểm tra Toán vào ngày 25/10/2025.', '2025-10-12 00:56:15', 2),
(2, 'Nộp bài tập Vật lý', 'Hạn cuối nộp bài là 20/10/2025.', '2025-10-12 00:56:15', 2),
(3, 'Bài giảng Cơ học', '1111', '2025-10-12 00:13:44', 1),
(4, 'Thêm người dùng mới', 'Đã thêm GiaoVien GV Test.', '2025-10-15 21:32:10', 44),
(5, 'Thêm người dùng mới', 'Đã thêm GiaoVien GV Test vào hệ thống.', '2025-10-15 21:32:10', 44),
(6, 'Thêm người dùng mới', 'Đã thêm GiaoVien GV Test 1 vào hệ thống.', '2025-10-15 21:33:49', NULL),
(7, 'Thêm hồ sơ giáo viên', 'Giáo viên admin vừa được thêm vào hệ thống.', '2025-10-15 21:36:36', 1),
(8, 'Cập nhật hồ sơ giáo viên', 'Thông tin giáo viên admin đã được cập nhật.', '2025-10-15 21:36:36', 1),
(9, 'Xóa hồ sơ giáo viên', 'Giáo viên admin đã bị xóa.', '2025-10-15 21:36:36', NULL),
(10, 'Cập nhật hồ sơ giáo viên', 'Thông tin giáo viên GV Test đã được cập nhật.', '2025-10-15 21:39:59', 44),
(11, 'Thông báo kiểm tra', 'Đây là thông báo hệ thống thử nghiệm.', '2025-10-15 21:43:57', 1),
(16, 'Xóa hồ sơ giáo viên', 'Giáo viên GV Test 1 đã bị xóa.', '2025-10-15 22:17:17', NULL),
(17, 'Xóa người dùng', 'Tài khoản GV Test 1 (GiaoVien) đã bị xóa.', '2025-10-15 22:17:17', NULL),
(18, 'Thêm hồ sơ học sinh', 'Học sinh hs2 vừa được thêm vào hệ thống.', '2025-10-15 22:24:14', 47),
(19, 'Thêm người dùng mới', 'Đã thêm HocSinh hs2 vào hệ thống.', '2025-10-15 22:24:14', 47),
(20, 'Cập nhật hồ sơ học sinh', 'Thông tin học sinh hs2 đã được cập nhật.', '2025-10-15 22:24:14', 47),
(21, 'Cập nhật hồ sơ học sinh', 'Thông tin học sinh hs1 đã được cập nhật.', '2025-10-15 22:24:43', 37);

--
-- Triggers `thongbao`
--
DELIMITER $$
CREATE TRIGGER `after_thongbao_delete` AFTER DELETE ON `thongbao` FOR EACH ROW BEGIN
  DECLARE senderName VARCHAR(100) DEFAULT '';

  IF OLD.nguoiGui IS NOT NULL THEN
    SELECT hoVaTen INTO senderName FROM user WHERE userID = OLD.nguoiGui;
  END IF;

  -- Ghi log xóa thông báo
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (
    IFNULL(OLD.nguoiGui, 0),
    'Xóa thông báo',
    CONCAT(
      IF(senderName <> '', CONCAT('Người dùng ', senderName, ' xóa thông báo: '), 'Hệ thống xóa thông báo: '),
      OLD.tieuDe
    ),
    'Warning'
  );

  -- Xóa các dòng liên quan trong bảng thongbaouser
  DELETE FROM thongbaouser WHERE maThongBao = OLD.maThongBao;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_thongbao_insert` AFTER INSERT ON `thongbao` FOR EACH ROW BEGIN
  DECLARE senderName VARCHAR(100) DEFAULT '';
  DECLARE receiverCount INT DEFAULT 0;

  -- Nếu có người gửi, lấy tên người gửi
  IF NEW.nguoiGui IS NOT NULL THEN
    SELECT hoVaTen INTO senderName FROM user WHERE userID = NEW.nguoiGui;
  END IF;

  -- Ghi log hệ thống
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (
    IFNULL(NEW.nguoiGui, 0),
    'Tạo thông báo',
    CONCAT(
      IF(senderName <> '', CONCAT('Người dùng ', senderName, ' tạo thông báo: '), 'Hệ thống tạo thông báo: '),
      NEW.tieuDe, ' - ', NEW.noiDung
    ),
    'Info'
  );

  -- Gửi thông báo cho tất cả Admin
  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, NEW.maThongBao FROM user WHERE vaiTro='Admin';
  SET receiverCount = ROW_COUNT();

  -- Nếu có người gửi, gửi lại thông báo cho chính họ (tránh sót)
  IF NEW.nguoiGui IS NOT NULL THEN
    INSERT IGNORE INTO thongbaouser (userID, maThongBao) VALUES (NEW.nguoiGui, NEW.maThongBao);
    SET receiverCount = receiverCount + 1;
  END IF;

  -- Ghi log phụ cho hệ thống
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (IFNULL(NEW.nguoiGui, 0), 'Gửi thông báo', CONCAT('Thông báo "', NEW.tieuDe, '" được gửi đến ', receiverCount, ' người dùng.'), 'Info');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `thongbaouser`
--

CREATE TABLE `thongbaouser` (
  `id` int(11) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `maThongBao` int(11) DEFAULT NULL,
  `trangThai` enum('Đã đọc','Chưa đọc') DEFAULT 'Chưa đọc',
  `thoiGianNhan` datetime DEFAULT current_timestamp(),
  `thoiGianDoc` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thongbaouser`
--

INSERT INTO `thongbaouser` (`id`, `userID`, `maThongBao`, `trangThai`, `thoiGianNhan`, `thoiGianDoc`) VALUES
(1, 3, 1, 'Chưa đọc', '2025-10-12 00:56:15', NULL),
(2, 4, 2, 'Đã đọc', '2025-10-12 00:56:15', NULL),
(3, 1, 4, 'Chưa đọc', '2025-10-15 21:32:10', NULL),
(4, 1, 5, 'Chưa đọc', '2025-10-15 21:32:10', NULL),
(5, 1, 6, 'Chưa đọc', '2025-10-15 21:33:49', NULL),
(6, 1, 7, 'Chưa đọc', '2025-10-15 21:36:36', NULL),
(7, 1, 8, 'Chưa đọc', '2025-10-15 21:36:36', NULL),
(8, 1, 9, 'Chưa đọc', '2025-10-15 21:36:36', NULL),
(9, 1, 10, 'Chưa đọc', '2025-10-15 21:39:59', NULL),
(10, 1, 11, 'Chưa đọc', '2025-10-15 21:43:57', NULL),
(11, 1, 11, 'Chưa đọc', '2025-10-15 21:43:57', NULL),
(12, 1, 16, 'Chưa đọc', '2025-10-15 22:17:17', NULL),
(13, 1, 16, 'Chưa đọc', '2025-10-15 22:17:17', NULL),
(14, 1, 17, 'Chưa đọc', '2025-10-15 22:17:17', NULL),
(15, 1, 17, 'Chưa đọc', '2025-10-15 22:17:17', NULL),
(16, 1, 18, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(17, 47, 18, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(18, 1, 18, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(19, 1, 19, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(20, 47, 19, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(21, 1, 19, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(22, 1, 20, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(23, 47, 20, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(24, 1, 20, 'Chưa đọc', '2025-10-15 22:24:14', NULL),
(25, 1, 21, 'Chưa đọc', '2025-10-15 22:24:43', NULL),
(26, 37, 21, 'Chưa đọc', '2025-10-15 22:24:43', NULL),
(27, 1, 21, 'Chưa đọc', '2025-10-15 22:24:43', NULL);

--
-- Triggers `thongbaouser`
--
DELIMITER $$
CREATE TRIGGER `after_update_thongbaouser` BEFORE UPDATE ON `thongbaouser` FOR EACH ROW BEGIN
  -- Nếu trạng thái thay đổi từ 'Chưa đọc' sang 'Đã đọc'
  IF OLD.trangThai = 'Chưa đọc' AND NEW.trangThai = 'Đã đọc' THEN
    SET NEW.thoiGianDoc = NOW();
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `userID` int(11) NOT NULL,
  `hoVaTen` varchar(100) DEFAULT NULL,
  `matKhau` varchar(255) DEFAULT NULL,
  `sdt` varchar(20) DEFAULT NULL,
  `ngaySinh` date DEFAULT NULL,
  `gioiTinh` varchar(10) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `vaiTro` enum('Admin','GiaoVien','HocSinh') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`userID`, `hoVaTen`, `matKhau`, `sdt`, `ngaySinh`, `gioiTinh`, `email`, `vaiTro`) VALUES
(1, 'admin', '12345678', '0945678987', NULL, '', 'admin@gmail.com', 'Admin'),
(2, 'Nguyễn Văn A', '123456', '0912345690', '1985-05-15', 'Nam', 'nguyenvana@gmail.com', 'GiaoVien'),
(3, 'Trần Thị B', '123456', '0903333444', '2006-09-20', 'Nữ', 'tranthib@gmail.com', 'HocSinh'),
(4, 'Lê Văn C', '123456', '0905555666', '2006-07-18', 'Nam', 'levanc@gmail.com', 'HocSinh'),
(16, 'Nguyen An', '123456', '0912345691', NULL, 'Nữ', 'na@gmail.com', 'GiaoVien'),
(17, 'gv1', '123456', '0912345698', NULL, 'Nam', 'gv1@gmail.com', 'GiaoVien'),
(36, 'gv2', '$2y$10$sFOfXm4WxKwt9tAxXxn0IucLa.MOllepAoCxN5D7JLZbUr5cWQruG', '0912345693', NULL, 'Nam', 'gv2@gmail.com', 'GiaoVien'),
(37, 'hs1', '$2y$10$lBD8DjmNNEOrCEPY2ygxd.MLmi2nqj5egmyPLwGd01Nhb1rmLPJfK', '0903333440', NULL, 'Nam', 'hs1@gmail.com', 'HocSinh'),
(40, 'gv3', '$2y$10$bkssfrIiLayWjY7P.PyZd.cEFpO13U0noZSTpVBsBwZq7KKbhKm2S', '0912345694', NULL, 'Nam', 'gv3@gmail.com', 'GiaoVien'),
(41, 'gv4', '$2y$10$WrudIGbpYmLAH3Hf8nZzpuIjBFEIhb93Q68cH1KIqQtcJfypS4UmK', '0912345692', NULL, 'Nam', 'gv4@gmail.com', 'GiaoVien'),
(42, 'gv5', '$2y$10$Qwkr8y2QUBRYxPl/KF56J.IW7LWWRoALV0b4CtXlWktSeKi6ZDApG', '0903333455', NULL, 'Nam', 'gv5@gmail.com', 'GiaoVien'),
(43, 'gv6', '$2y$10$CuQTxGKq81yMNn/LzWRnZ.lgQBw2uh.9LU457E.E2/f3sshuXztFu', '0903333452', NULL, 'Nữ', 'gv6@gmail.com', 'GiaoVien'),
(44, 'GV Test', '123456', '0909123123', NULL, 'Nam', 'gvtest@gmail.com', 'GiaoVien'),
(47, 'hs2', '$2y$10$BnbEipBi/Z7p17Xr7ash0ORGWN68OvBlGAW.aCFp3CqXCSj2LM.si', '0912345696', NULL, 'Nam', 'hs2@gmail.com', 'HocSinh');

--
-- Triggers `user`
--
DELIMITER $$
CREATE TRIGGER `after_user_insert` AFTER INSERT ON `user` FOR EACH ROW BEGIN
  -- Thêm dữ liệu phụ theo vai trò
  IF NEW.vaiTro = 'GiaoVien' THEN
    INSERT INTO giaovien (maGV, boMon, trinhDo, anhDaiDien, phongBan, namHoc, hocKy, trangThai)
    VALUES (NEW.userID, 'Chưa xác định', 'Chưa cập nhật', 'Chưa cập nhật', 'Chưa cập nhật', 'Chưa cập nhật', 'Chưa cập nhật', 'active');
  ELSEIF NEW.vaiTro = 'HocSinh' THEN
    INSERT INTO hocsinh (maHS, lopHocPhuTrach, anhDaiDien, namHoc, hocKy, trangThai)
    VALUES (NEW.userID, 'Chưa cập nhật', 'Chưa cập nhật', 'Chưa cập nhật', 'Chưa cập nhật', 'active');
  ELSEIF NEW.vaiTro = 'Admin' THEN
    INSERT INTO admin (maAdmin) VALUES (NEW.userID);
  END IF;

  -- Ghi log
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (NEW.userID, 'Thêm người dùng', CONCAT('Tài khoản ', NEW.hoVaTen, ' (', NEW.vaiTro, ') đã được thêm.'), 'Info');

  -- Tạo thông báo và gửi cho Admin
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Thêm người dùng mới', CONCAT('Đã thêm ', NEW.vaiTro, ' ', NEW.hoVaTen, ' vào hệ thống.'), NEW.userID);

  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_user_update` AFTER UPDATE ON `user` FOR EACH ROW BEGIN
  DECLARE thaydoi TEXT DEFAULT '';

  IF OLD.hoVaTen <> NEW.hoVaTen THEN
    SET thaydoi = CONCAT(thaydoi, 'Tên: "', OLD.hoVaTen, '" → "', NEW.hoVaTen, '"; ');
  END IF;
  IF OLD.email <> NEW.email THEN
    SET thaydoi = CONCAT(thaydoi, 'Email: "', OLD.email, '" → "', NEW.email, '"; ');
  END IF;
  IF OLD.sdt <> NEW.sdt THEN
    SET thaydoi = CONCAT(thaydoi, 'SĐT: "', OLD.sdt, '" → "', NEW.sdt, '"; ');
  END IF;
  IF OLD.gioiTinh <> NEW.gioiTinh THEN
    SET thaydoi = CONCAT(thaydoi, 'Giới tính: "', OLD.gioiTinh, '" → "', NEW.gioiTinh, '"; ');
  END IF;

  IF thaydoi <> '' THEN
    -- Ghi log thay đổi
    INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
    VALUES (NEW.userID, 'Cập nhật người dùng', CONCAT('Thông tin tài khoản ', OLD.hoVaTen, ' đã thay đổi: ', thaydoi), 'Info');

    -- Gửi thông báo cho Admin
    INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
    VALUES ('Cập nhật người dùng', CONCAT('Tài khoản ', NEW.hoVaTen, ' (', NEW.vaiTro, ') vừa được cập nhật thông tin.'), NEW.userID);

    INSERT INTO thongbaouser (userID, maThongBao)
    SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_user_delete` BEFORE DELETE ON `user` FOR EACH ROW BEGIN
  -- Xóa dữ liệu phụ theo vai trò trước khi user bị xóa
  IF OLD.vaiTro = 'GiaoVien' THEN
    DELETE FROM giaovien WHERE maGV = OLD.userID;
  ELSEIF OLD.vaiTro = 'HocSinh' THEN
    DELETE FROM hocsinh WHERE maHS = OLD.userID;
  ELSEIF OLD.vaiTro = 'Admin' THEN
    DELETE FROM admin WHERE maAdmin = OLD.userID;
  END IF;

  -- Ghi log (user vẫn còn, FK chưa lỗi)
  INSERT INTO ghilog (userID, hanhDong, noiDungLog, loaiLog)
  VALUES (OLD.userID, 'Xóa người dùng', CONCAT('Đã xóa tài khoản ', OLD.hoVaTen, ' (', OLD.vaiTro, ') khỏi hệ thống.'), 'Warning');

  -- Gửi thông báo
  INSERT INTO thongbao (tieuDe, noiDung, nguoiGui)
  VALUES ('Xóa người dùng', CONCAT('Tài khoản ', OLD.hoVaTen, ' (', OLD.vaiTro, ') đã bị xóa.'), NULL);

  INSERT INTO thongbaouser (userID, maThongBao)
  SELECT userID, LAST_INSERT_ID() FROM user WHERE vaiTro='Admin';
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`maAdmin`);

--
-- Indexes for table `bainop`
--
ALTER TABLE `bainop`
  ADD PRIMARY KEY (`maBaiNop`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `maTL` (`maTL`);

--
-- Indexes for table `baocao`
--
ALTER TABLE `baocao`
  ADD PRIMARY KEY (`maBaoCao`),
  ADD KEY `maUser` (`maUser`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Indexes for table `chuyencan`
--
ALTER TABLE `chuyencan`
  ADD PRIMARY KEY (`maDiemDanh`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Indexes for table `diemso`
--
ALTER TABLE `diemso`
  ADD PRIMARY KEY (`maDiem`),
  ADD KEY `maHS` (`maHS`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Indexes for table `ghilog`
--
ALTER TABLE `ghilog`
  ADD PRIMARY KEY (`maLog`),
  ADD KEY `ghilog_ibfk_1` (`userID`);

--
-- Indexes for table `giaovien`
--
ALTER TABLE `giaovien`
  ADD PRIMARY KEY (`maGV`);

--
-- Indexes for table `giaovien_monhoc`
--
ALTER TABLE `giaovien_monhoc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_gv_monhoc` (`maGV`,`maMonHoc`),
  ADD KEY `maMonHoc` (`maMonHoc`);

--
-- Indexes for table `hocsinh`
--
ALTER TABLE `hocsinh`
  ADD PRIMARY KEY (`maHS`);

--
-- Indexes for table `hocsinh_lophoc`
--
ALTER TABLE `hocsinh_lophoc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_hs_lop` (`maHS`,`maLop`),
  ADD KEY `maLop` (`maLop`);

--
-- Indexes for table `lophoc`
--
ALTER TABLE `lophoc`
  ADD PRIMARY KEY (`maLop`),
  ADD KEY `maGV` (`maGV`);

--
-- Indexes for table `lophoc_monhoc`
--
ALTER TABLE `lophoc_monhoc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lop_monhoc` (`maLop`,`maMonHoc`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maGV` (`maGV`);

--
-- Indexes for table `monhoc`
--
ALTER TABLE `monhoc`
  ADD PRIMARY KEY (`maMonHoc`);

--
-- Indexes for table `tailieu`
--
ALTER TABLE `tailieu`
  ADD PRIMARY KEY (`maTL`),
  ADD KEY `maMonHoc` (`maMonHoc`),
  ADD KEY `maGV` (`maGV`);

--
-- Indexes for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD PRIMARY KEY (`maThongBao`),
  ADD KEY `nguoiGui` (`nguoiGui`);

--
-- Indexes for table `thongbaouser`
--
ALTER TABLE `thongbaouser`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_thongbaouser_thongbao` (`maThongBao`),
  ADD KEY `fk_thongbaouser_user` (`userID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `sdt` (`sdt`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bainop`
--
ALTER TABLE `bainop`
  MODIFY `maBaiNop` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `baocao`
--
ALTER TABLE `baocao`
  MODIFY `maBaoCao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chuyencan`
--
ALTER TABLE `chuyencan`
  MODIFY `maDiemDanh` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `diemso`
--
ALTER TABLE `diemso`
  MODIFY `maDiem` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ghilog`
--
ALTER TABLE `ghilog`
  MODIFY `maLog` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `giaovien_monhoc`
--
ALTER TABLE `giaovien_monhoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `hocsinh_lophoc`
--
ALTER TABLE `hocsinh_lophoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lophoc`
--
ALTER TABLE `lophoc`
  MODIFY `maLop` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `lophoc_monhoc`
--
ALTER TABLE `lophoc_monhoc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `monhoc`
--
ALTER TABLE `monhoc`
  MODIFY `maMonHoc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tailieu`
--
ALTER TABLE `tailieu`
  MODIFY `maTL` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `thongbao`
--
ALTER TABLE `thongbao`
  MODIFY `maThongBao` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `thongbaouser`
--
ALTER TABLE `thongbaouser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`maAdmin`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `bainop`
--
ALTER TABLE `bainop`
  ADD CONSTRAINT `bainop_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `bainop_ibfk_2` FOREIGN KEY (`maTL`) REFERENCES `tailieu` (`maTL`) ON DELETE CASCADE;

--
-- Constraints for table `baocao`
--
ALTER TABLE `baocao`
  ADD CONSTRAINT `baocao_ibfk_1` FOREIGN KEY (`maUser`) REFERENCES `user` (`userID`) ON DELETE SET NULL,
  ADD CONSTRAINT `baocao_ibfk_2` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE SET NULL;

--
-- Constraints for table `chuyencan`
--
ALTER TABLE `chuyencan`
  ADD CONSTRAINT `chuyencan_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `chuyencan_ibfk_2` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE;

--
-- Constraints for table `diemso`
--
ALTER TABLE `diemso`
  ADD CONSTRAINT `diemso_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `diemso_ibfk_2` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE;

--
-- Constraints for table `giaovien`
--
ALTER TABLE `giaovien`
  ADD CONSTRAINT `giaovien_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `giaovien_monhoc`
--
ALTER TABLE `giaovien_monhoc`
  ADD CONSTRAINT `giaovien_monhoc_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE CASCADE,
  ADD CONSTRAINT `giaovien_monhoc_ibfk_2` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE;

--
-- Constraints for table `hocsinh`
--
ALTER TABLE `hocsinh`
  ADD CONSTRAINT `hocsinh_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `user` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `hocsinh_lophoc`
--
ALTER TABLE `hocsinh_lophoc`
  ADD CONSTRAINT `hocsinh_lophoc_ibfk_1` FOREIGN KEY (`maHS`) REFERENCES `hocsinh` (`maHS`) ON DELETE CASCADE,
  ADD CONSTRAINT `hocsinh_lophoc_ibfk_2` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE;

--
-- Constraints for table `lophoc`
--
ALTER TABLE `lophoc`
  ADD CONSTRAINT `lophoc_ibfk_1` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `lophoc_monhoc`
--
ALTER TABLE `lophoc_monhoc`
  ADD CONSTRAINT `lophoc_monhoc_ibfk_1` FOREIGN KEY (`maLop`) REFERENCES `lophoc` (`maLop`) ON DELETE CASCADE,
  ADD CONSTRAINT `lophoc_monhoc_ibfk_2` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `lophoc_monhoc_ibfk_3` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `tailieu`
--
ALTER TABLE `tailieu`
  ADD CONSTRAINT `tailieu_ibfk_1` FOREIGN KEY (`maMonHoc`) REFERENCES `monhoc` (`maMonHoc`) ON DELETE CASCADE,
  ADD CONSTRAINT `tailieu_ibfk_2` FOREIGN KEY (`maGV`) REFERENCES `giaovien` (`maGV`) ON DELETE SET NULL;

--
-- Constraints for table `thongbao`
--
ALTER TABLE `thongbao`
  ADD CONSTRAINT `thongbao_ibfk_1` FOREIGN KEY (`nguoiGui`) REFERENCES `user` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `thongbaouser`
--
ALTER TABLE `thongbaouser`
  ADD CONSTRAINT `fk_thongbaouser_thongbao` FOREIGN KEY (`maThongBao`) REFERENCES `thongbao` (`maThongBao`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_thongbaouser_user` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `thongbaouser_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `user` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `thongbaouser_ibfk_2` FOREIGN KEY (`maThongBao`) REFERENCES `thongbao` (`maThongBao`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
