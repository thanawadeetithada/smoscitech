<?php
session_start();
require 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$stmt = $conn->prepare("SELECT user_id, username, email, first_name, last_name, password, userrole, department, academic_year, profile_image
                        FROM users WHERE deleted_at IS NULL");
$stmt->execute();
$result = $stmt->get_result();

$role_names = [
    'executive'         => 'ผู้บริหาร',
    'academic_officer'  => 'นักวิชาการศึกษา',
    'club_president'    => 'นายก/รองนายกสโมสรนักศึกษา',
    'club_member'       => 'สมาชิกสโมสรนักศึกษาฯ'
];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>หน้าข้อมูลผู้ใช้งาน</title>
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
                <a class="nav-link" href="logout.php"><i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout</a>
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
                <li><a href="admin_e-portfolio.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-address-book"></i> E-Portfolio</a></li>
                <li><a href="admin_transcript.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-file-lines"></i> Transcript</a></li>
                <li><a href="admin_approve_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-calendar-check"></i> อนุมัติกิจกรรม</a></li>
                <li><a href="admin_score_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="admin_user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="header-card">
            <h3 class="text-left">รายชื่อ</h3>
            <div class="search-add">
                <div class="tab-func">
                    <button type="button" class="btn btn-primary"
                        onclick="window.location.href='add_user_management.php'">
                        <i class="fa-solid fa-file-medical"></i> เพิ่มรายชื่อ
                    </button>
                </div>
                <div class="tab-func">
                    <input type="text" class="form-control search-name" placeholder="ค้นหา...">
                </div>
            </div>
        </div>
        <br>
        <div class="table-responsive">
            <table class="table table-bordered" id="memberTable">
                <thead>
                    <tr>
                        <th>ชื่อ</th>
                        <th>นามสกุล</th>
                        <th>ชื่อผู้ใช้งาน</th>
                        <th>Email</th>
                        <th>สาขาวิชา</th>
                        <th>ปีการศึกษา</th>
                        <th>สถานะ</th>
                        <th>รูปโปรไฟล์</th>
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
                    <td>{$row['first_name']}</td>
                    <td>{$row['last_name']}</td>
                    <td>{$row['username']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['department']}</td>
                    <td>{$row['academic_year']}</td>
                    <td>{$display_name}</td> 
                    <td><img src='uploads/{$row['profile_image']}' width='50' height='50' class='rounded-circle'></td>
                    <td class='btn-action1'>
                        <a href='edit_user.php?user_id={$row['user_id']}' class='btn btn-warning btn-sm'>
                            <i class='fa-solid fa-pencil'></i>
                        </a>
                        &nbsp;&nbsp;
                        <a href='#' class='btn btn-danger btn-sm delete-btn' data-id='{$row['user_id']}'>
                            <i class='fa-regular fa-trash-can'></i>
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
        $(".search-name").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            var visibleRows = 0;
            $("#memberTable tbody tr").each(function() {
                if ($(this).attr("id") === "noResult" || $(this).find(".modal-dialog").length >
                    0) return;

                var match = $(this).text().toLowerCase().indexOf(value) > -1;
                $(this).toggle(match);
                if (match) visibleRows++;
            });

            if (visibleRows === 0) {
                $("#noResult").show();
            } else {
                $("#noResult").hide();
            }
        });

        let deleteUserId = null;

        $(".delete-btn").click(function(e) {
            e.preventDefault();
            deleteUserId = $(this).data("id");
            $("#confirmDeleteModal").modal("show");
        });

        $("#confirmDeleteBtn").click(function() {
            if (deleteUserId) {
                $.ajax({
                    url: "delete_user.php",
                    type: "POST",
                    data: {
                        user_id: deleteUserId
                    },
                    success: function(response) {
                        $("#confirmDeleteModal").modal("hide");
                        $("a.delete-btn[data-id='" + deleteUserId + "']").closest("tr")
                            .remove();
                        $("#alertMessage").text(response);
                        $("#alertModal").modal("show");
                        deleteUserId = null;
                        if ($("#memberTable tbody tr").length === 0) {
                            $("#noResult").show();
                        }
                    },
                    error: function() {
                        alert("เกิดข้อผิดพลาดในการลบข้อมูล");
                    }
                });
            }
        });
    });
    </script>

</body>

</html>