<?php
session_start();
require 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT user_id, idstudent, email, first_name, last_name, password, userrole, department, academic_year, year_level,profile_image
                        FROM users WHERE deleted_at IS NULL");
$stmt->execute();
$result = $stmt->get_result();

$year_query = $conn->query("SELECT DISTINCT academic_year FROM users WHERE deleted_at IS NULL ORDER BY academic_year DESC");
$academic_years = [];
while($y = $year_query->fetch_assoc()) {
    $academic_years[] = $y['academic_year'];
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>หน้า E-Poartfolio / Transcript</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Prompt', sans-serif;
        background: url('bg/sky.png') no-repeat center center;
        background-size: cover;
        background-attachment: fixed;
        min-height: 100vh;
        background: #f8f9fc;
    }

    .card {
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.2);
        background: white;
        margin-top: 50px;
        margin: 3% 5%;
        background-color: #ffffff;
    }

    .table th,
    .table td {
        text-align: center;
        font-size: 14px;

    }

    .table {
        background: #f8f9fa;
        border-radius: 10px;
    }

    .table th {
        background-color: #f9fafc;
        color: black;
    }

    .modal-dialog {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;

    }

    .modal-content {
        width: 100%;
        max-width: 500px;
    }

    .header-card {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
    }

    .form-control modal-text {
        height: fit-content;
        width: 50%;
    }

    .btn-action {
        display: flex;
        justify-content: center;
        align-items: center;
    }


    .modal-text {
        width: 100%;
    }

    .modal-header {
        font-weight: bold;
    }

    .nav-item a {
        color: white;
        margin-right: 1rem;
    }

    .navbar {
        padding: 20px;
    }

    .nav-link:hover {
        color: white;
    }

    .modal-body {
        padding: 10px 40px;
    }

    .search-add {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .tab-func {
        display: flex;
        justify-content: flex-start;
        align-items: center;
    }

    @media (max-width: 768px) {
        .search-add {
            flex-direction: row;
            gap: 10px;
        }

        .search-name {
            width: 20%;
            flex: 1;
        }

        .tab-func button {
            width: max-content;
        }
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 40px;
        height: 20px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 20px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked+.slider {
        background-color: #28a745;
    }

    input:checked+.slider:before {
        transform: translateX(18px);
    }

    .btn-purple {
        width: 20%;
        background-color: #8c99bc !important;
        color: white !important;
        border: none;
    }

    .bg-purple {
        background-color: #8c99bc !important;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark px-3">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <i class="fa-solid fa-bars text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
                style="cursor: pointer;"></i>
            <div class="nav-item">
                <a class="nav-link text-white" href="logout.php">
                    <i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout</a>
            </div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">รายการ</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <ul class="list-unstyled">
                <li><a href="admin_report_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-chart-line"></i> สถิติการเข้าร่วมกิจกรรม</a></li>
                <li><a href="admin_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-list-check"></i> กิจกรรม</a></li>
                <li><a href="admin_e-portfolio_transcript.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-address-book"></i> E-Portfolio / Transcript</a></li>
                <li><a href="admin_score_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="admin_user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="header-card">
            <h3 class="text-left">ข้อมูล E-Poartfolio / Transcript</h3>
            <div class="search-add">
                <div class="tab-func flex-wrap">
                    <input type="text" class="form-control search-name me-2 mb-2" placeholder="ค้นหาชื่อ/รหัส..."
                        style="width: 200px;">

                    <select id="filterAcademicYear" class="form-select me-2 mb-2" style="width: auto;">
                        <option value="">ทุกปีการศึกษา</option>
                        <?php foreach ($academic_years as $year): ?>
                        <option value="<?= htmlspecialchars($year) ?>"><?= htmlspecialchars($year) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <select id="filterDepartment" class="form-select me-2 mb-2" style="width: auto;">
                        <option value="">ทุกสาขาวิชา</option>
                        <?php 
                $depts = ["วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "นวัตกรรมและธุรกิจอาหาร", "สาธารณสุขศาสตร์", "เคมี (วท.บ.)", "วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม", "ฟิสิกส์", "เคมี (ค.บ.)", "ชีววิทยา", "คณิตศาสตร์ประยุกต์"];
                foreach ($depts as $dept) echo "<option value='$dept'>$dept</option>";
            ?>
                    </select>

                    <select id="filterYearLevel" class="form-select mb-2" style="width: auto;">
                        <option value="">ทุกชั้นปี</option>
                        <option value="1">ชั้นปีที่ 1</option>
                        <option value="2">ชั้นปีที่ 2</option>
                        <option value="3">ชั้นปีที่ 3</option>
                        <option value="4">ชั้นปีที่ 4</option>
                    </select>
                </div>
            </div>
        </div>
        <br>
        <div class="table-responsive">
            <table class="table table-bordered" id="memberTable">
                <thead>
                    <tr>
                        <th>รหัสนักศึกษา</th>
                        <th>ชื่อ</th>
                        <th>นามสกุล</th>
                        <th>Email</th>
                        <th>ปีการศึกษา</th>
                        <th>สาขาวิชา</th>
                        <th>ชั้นปีการศึกษา</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $role_key = $row['userrole'];
            $display_name = isset($role_names[$role_key]) ? $role_names[$role_key] : $role_key;

            echo "<tr>
                    <td>{$row['idstudent']}</td>
                    <td>{$row['first_name']}</td>
                    <td>{$row['last_name']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['academic_year']}</td>
                    <td>{$row['department']}</td>
                    <td>{$row['year_level']}</td>
                   
                    <td class='btn-action1'>
                        <a href='admin_detail_E-portfolio.php?user_id={$row['user_id']}&action=portfolio' class='btn btn-outline-primary btn-sm'>
                            <i class='fa-solid fa-address-card'></i> E-portfolio
                        </a>
                        &nbsp;&nbsp;
                        <a href='admin_detail_transcript.php?user_id={$row['user_id']}&action=transcript' class='btn btn-outline-warning btn-sm'>
                            <i class='fa-solid fa-file-lines'></i> Transcript
                        </a>
                    </td>
                </tr>";
        }
    }
    $conn->close();
    ?>
                </tbody>
                <tr id="noResult" style="display:none;">
                    <td colspan="13" class="text-center text-muted fw-bold bg-light py-3">
                        <i class="fa-solid fa-circle-info"></i> ไม่พบข้อมูลที่ค้นหา
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header bg-purple text-white">
                    <h5 class="modal-title" id="alertModalLabel">แจ้งเตือน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4 pb-4" id="alertMessage">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-purple px-4" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteLabel">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4 pb-3">
                    <p>คุณต้องการลบรายชื่อนี้หรือไม่?</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">ลบ</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        function filterTable() {
            var searchTerm = $(".search-name").val().toLowerCase();
            var academicYear = $("#filterAcademicYear").val();
            var department = $("#filterDepartment").val();
            var yearLevel = $("#filterYearLevel").val();

            var visibleRows = 0;

            $("#memberTable tbody tr").each(function() {
                if ($(this).attr("id") === "noResult") return;
                var rowText = $(this).text().toLowerCase();
                var rowAcademicYear = $(this).find("td:eq(4)").text().trim();
                var rowDepartment = $(this).find("td:eq(5)").text().trim();
                var rowYearLevel = $(this).find("td:eq(6)").text().trim();
                var matchSearch = rowText.indexOf(searchTerm) > -1;
                var matchYear = (academicYear === "" || rowAcademicYear === academicYear);
                var matchDept = (department === "" || rowDepartment === department);
                var matchLevel = (yearLevel === "" || rowYearLevel.includes(yearLevel));

                if (matchSearch && matchYear && matchDept && matchLevel) {
                    $(this).show();
                    visibleRows++;
                } else {
                    $(this).hide();
                }
            });

            if (visibleRows === 0) {
                $("#noResult").show();
            } else {
                $("#noResult").hide();
            }
        }

        $(".search-name").on("keyup", filterTable);
        $("#filterAcademicYear, #filterDepartment, #filterYearLevel").on("change", filterTable);
    });
    </script>

</body>

</html>