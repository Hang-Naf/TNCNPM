<?php
include_once("func.php");
$action = $_GET["action"] ?? "";

switch ($action) {
    // LẤY TOÀN BỘ ĐIỂM
    case "getAll":
        $sql = "SELECT ds.maDiem, ds.giaTri, hs.maHS, u.userName AS tenHocSinh, 
                       mh.tenMon, ds.kyHoc, ds.namHoc
                FROM diemso ds
                JOIN hocsinh hs ON ds.hocSinh = hs.maHS
                JOIN user u ON hs.userId = u.userId
                JOIN monhoc mh ON ds.monHoc = mh.maMon";
        $data = getData($sql);
        responseJSON($data);
        break;

    // LẤY ĐIỂM THEO HỌC SINH
    case "getByStudent":
        $maHS = $_GET["maHS"] ?? null;
        if (!$maHS) responseJSON(["error" => "Thiếu mã học sinh"], 400);

        $sql = "SELECT ds.maDiem, ds.giaTri, mh.tenMon, ds.kyHoc, ds.namHoc
                FROM diemso ds
                JOIN monhoc mh ON ds.monHoc = mh.maMon
                WHERE ds.hocSinh = ?";
        $data = getData($sql, [$maHS], "i");
        responseJSON($data);
        break;

    // THÊM ĐIỂM MỚI
    case "add":
        $input = getJSONInput();
        $hocSinh = $input["hocSinh"] ?? null;
        $monHoc = $input["monHoc"] ?? null;
        $giaTri = $input["giaTri"] ?? null;
        $kyHoc = $input["kyHoc"] ?? null;
        $namHoc = $input["namHoc"] ?? null;

        if (!$hocSinh || !$monHoc || $giaTri === null) {
            responseJSON(["error" => "Thiếu dữ liệu bắt buộc"], 400);
        }

        $sql = "INSERT INTO diemso (giaTri, hocSinh, monHoc, kyHoc, namHoc)
                VALUES (?, ?, ?, ?, ?)";
        $ok = executeSQL($sql, [$giaTri, $hocSinh, $monHoc, $kyHoc, $namHoc], "diiss");

        if ($ok) {
            responseJSON(["message" => "Thêm điểm thành công"]);
        } else {
            responseJSON(["error" => "Không thể thêm điểm"], 500);
        }
        break;

    // CẬP NHẬT ĐIỂM
    case "update":
        $input = getJSONInput();
        $maDiem = $input["maDiem"] ?? null;
        $giaTri = $input["giaTri"] ?? null;

        if (!$maDiem || $giaTri === null) {
            responseJSON(["error" => "Thiếu mã điểm hoặc giá trị"], 400);
        }

        $sql = "UPDATE diemso SET giaTri = ? WHERE maDiem = ?";
        $ok = executeSQL($sql, [$giaTri, $maDiem], "di");

        if ($ok) {
            responseJSON(["message" => "Cập nhật điểm thành công"]);
        } else {
            responseJSON(["error" => "Không thể cập nhật"], 500);
        }
        break;

    // XÓA ĐIỂM
    case "delete":
        $input = getJSONInput();
        $maDiem = $input["maDiem"] ?? null;

        if (!$maDiem) responseJSON(["error" => "Thiếu mã điểm"], 400);

        $ok = executeSQL("DELETE FROM diemso WHERE maDiem = ?", [$maDiem], "i");

        if ($ok) {
            responseJSON(["message" => "Xóa điểm thành công"]);
        } else {
            responseJSON(["error" => "Không thể xóa"], 500);
        }
        break;

    // MẶC ĐỊNH
    default:
        responseJSON(["error" => "Hành động không hợp lệ"], 400);
}
?>
