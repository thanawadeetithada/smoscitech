<?php
session_start();
require 'db.php';

$allowed_roles = ['academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$current_user_role = $_SESSION['userrole'];
$user_id = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();

$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$stmt_profile->close();

if ($current_user_role === 'academic_officer') {
    $sql = "SELECT user_id, idstudent, email, first_name, last_name, password, userrole, department, membership_status, academic_year, year_level, profile_image
            FROM users WHERE deleted_at IS NULL 
            ORDER BY department ASC, idstudent ASC";
    $stmt = $conn->prepare($sql);
} else if ($current_user_role === 'club_president') {
    $sql = "SELECT user_id, idstudent, email, first_name, last_name, password, userrole, department, membership_status, academic_year, year_level, profile_image
            FROM users WHERE deleted_at IS NULL AND userrole = 'club_member' 
            ORDER BY department ASC, idstudent ASC";
    $stmt = $conn->prepare($sql);
}

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
    <title>หน้าข้อมูลผู้ใช้งาน - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">

    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #FFFFFF;
        --table-header: #D9D9D9;
        --table-row: #EFEFEF;
        --btn-blue: #6358E1;
    }

    body,
    html {
        height: 100%;
        margin: 0;
        font-family: 'Sarabun', sans-serif;
        background-color: var(--light-bg);
        overflow-x: hidden;
    }

    .wrapper {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .top-navbar {
        background-color: var(--top-bar-bg);
        min-height: 80px;
        display: flex;
        align-items: center;
        padding: 10px 20px;
        justify-content: space-between;
        color: white;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        z-index: 100;
        position: sticky;
        top: 0;
    }

    .brand-section {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .brand-logo {
        width: 60px;
        height: 60px;
    }

    .brand-name {
        font-size: clamp(16px, 4vw, 24px);
        font-family: serif;
        letter-spacing: 1px;
        white-space: nowrap;
    }

    .login-pill-btn {
        background: white;
        color: black;
        padding: 6px 25px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        font-size: 16px;
        transition: 0.3s;
    }

    .login-pill-btn:hover {
        background: #eee;
        color: black;
    }

    .text-page-pill-btn {
        background: white;
        color: black;
        padding: 3px 15px;
        border-radius: 5px;
        text-decoration: none;
        font-size: 13px;
        letter-spacing: 0.5px;
        font-weight: 500;
    }

    .logout-area {
        text-align: center;
        margin-left: 20px;
    }

    .logout-text {
        color: #000;
        font-weight: bold;
        text-decoration: none;
        font-size: 14px;
        background: #D9D9D9;
        padding: 2px 10px;
        border-radius: 5px;
        display: block;
    }

    .main-wrapper {
        display: flex;
        flex: 1;
        position: relative;
    }

    .sidebar {
        width: 230px;
        background-color: var(--yellow-sidebar);
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        border-right: 1px solid rgba(0, 0, 0, 0.05);
        transition: 0.3s ease-in-out;
        z-index: 99;
    }

    .sidebar-item {
        background: white;
        padding: 25px 10px;
        text-align: center;
        border-bottom: 1px solid #eee;
        text-decoration: none;
        color: #333;
        display: block;
        transition: all 0.3s ease;
    }

    .sidebar-item:hover {
        background: #FDFDFD;
        transform: translateX(5px);
    }

    .sidebar-item i {
        font-size: 32px;
        display: block;
        margin-bottom: 8px;
        color: #000;
    }

    .sidebar-item span {
        font-weight: bold;
        font-size: 13px;
    }

    .content-area {
        flex-grow: 1;
        padding: 40px;
        display: flex;
        flex-direction: column;
    }

    .header-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .search-add-group {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .btn-purple {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 20px;
        padding: 8px 25px;
        font-size: 14px;
        transition: 0.3s;
    }

    .btn-purple:hover {
        background-color: #4a40bd;
        color: white;
    }

    .search-input-group {
        background: #D9D9D9;
        border-radius: 10px;
        padding: 8px 15px;
        display: flex;
        align-items: center;
        width: 250px;
    }

    .search-input-group input {
        border: none;
        background: transparent;
        font-size: 14px;
        outline: none;
        width: 100%;
    }

    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .custom-table th {
        background: var(--table-header);
        padding: 12px;
        text-align: center;
        font-size: 14px;
        border: none;
        font-weight: bold;
    }

    .custom-table td {
        background: var(--table-row);
        padding: 12px;
        text-align: center;
        font-size: 14px;
        border: none;
        vertical-align: middle;
    }

    .custom-table tr td:first-child,
    .custom-table tr th:first-child {
        border-radius: 5px 0 0 5px;
    }

    .custom-table tr td:last-child,
    .custom-table tr th:last-child {
        border-radius: 0 5px 5px 0;
    }

    .status-select {
        text-align: center;
        font-weight: bold;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .status-select:focus {
        box-shadow: none;
        outline: 2px solid #fff;
    }

    .modal-header.bg-purple {
        background-color: var(--btn-blue);
    }

    @media (max-width: 768px) {
        .sidebar {
            position: absolute;
            top: 0;
            left: -230px;
            height: 100%;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar.active {
            left: 0;
        }

        .top-navbar {
            padding: 10px 15px;
        }

        .brand-name {
            font-size: 18px;
        }

        .content-area {
            padding: 20px 10px;
        }

        .logout-text {
            padding: 2px !important;
            font-size: 9px !important;
        }

        .header-actions {
            flex-direction: column;
            align-items: flex-start;
        }

        .search-add-group {
            width: 100%;
            justify-content: space-between;
        }
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <nav class="top-navbar">
            <div class="brand-section">
                <i class="fa-solid fa-bars d-md-none me-2" id="mobileMenuBtn"
                    style="font-size: 24px; cursor: pointer;"></i>
                <img src="img/logo.png" alt="Logo" class="brand-logo">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'academic_officer'): ?>
                    <span class="text-page-pill-btn mt-1">ข้อมูลสมาชิกสโมสร / นายกสโมสร / รองนายกสโมสร </span>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                    <span class="text-page-pill-btn mt-1">ข้อมูลสมาชิกสโมสร</span>
                    <?php endif; ?>

                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'ผู้ใช้งาน'); ?>
                </span>
                <div class="logout-area">
                    <a href="user_management.php">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile"
                            style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    </a>
                    <a href="logout.php" class="logout-text mt-1">ออกจากระบบ</a>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            <aside class="sidebar">
                <a href="admin_report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="admin_e-portfolio.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-book-open"></i>
                    <span>รายงาน E-portfolio</span>
                </a>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'academic_officer'): ?>
                <a href="admin_user_management.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-users"></i>
                    <span>ข้อมูลสมาชิกสโมสร / นายกสโมสร / รองนายกสโมสร </span>
                </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <a href="admin_user_management.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-users"></i>
                    <span>ข้อมูลสมาชิกสโมสร</span>
                </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] !== 'executive'): ?>
                <a href="admin_activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-cubes"></i>
                    <span>ข้อมูลกิจกรรม</span>
                </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <a href="admin_score_activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-folder-open"></i>
                    <span>ข้อมูลการเข้าร่วมกิจกรรม</span>
                </a>
                <?php endif; ?>
                <?php if (isset($_SESSION['userrole']) && in_array($_SESSION['userrole'], ['academic_officer', 'club_president'])): ?>
                <a href="admin_transcript.php" class="sidebar-item">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Transcript</span>
                </a>
                <?php endif; ?>
            </aside>

            <main class="content-area">
                <div class="header-actions">
                    <div class="search-add-group">
                        <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                        <button type="button" class="btn btn-purple"
                            onclick="window.location.href='add_user_management.php'">
                            <i class="fa-solid fa-file-medical"></i> เพิ่มรายชื่อ
                        </button>
                        <?php endif; ?>

                        <div class="search-input-group">
                            <i class="fa fa-search me-2" style="font-size: 14px; color: #666;"></i>
                            <input type="text" class="search-name" placeholder="ค้นหาชื่อ, รหัส, สาขา...">
                        </div>
                    </div>
                </div>

                <div class="table-responsive w-100">
                    <table class="custom-table" id="memberTable">
                        <thead>
                            <tr>
                                <th>ลำดับ</th>
                                <th>รหัสนักศึกษา</th>
                                <th>ชื่อ - นามสกุล</th>
                                <th>ชั้นปี</th>
                                <th>สาขาวิชา</th>
                                <th>ตำแหน่ง</th>
                                <th>สถานะสมาชิก</th>
                                <th>จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
            if ($result->num_rows > 0) {
                
                $ลำดับ = 1; 

                while ($row = $result->fetch_assoc()) {
                    $role_key = $row['userrole'];
                    $display_role = isset($role_names[$role_key]) ? $role_names[$role_key] : $role_key;
                    
                    $full_name = $row['first_name'] . " " . $row['last_name'];

                    $status_val = $row['membership_status'];
                    $select_class = '';
                    
                    if ($status_val === 'member') {
                        $select_class = 'bg-success text-white'; 
                    } else if ($status_val === 'no_member') {
                        $select_class = 'bg-danger text-white'; 
                    } else {
                        $select_class = 'bg-warning text-dark'; 
                    }

                    $status_html = "
                        <select class='form-select form-select-sm status-select {$select_class} mx-auto' 
                                data-user-id='{$row['user_id']}' style='width: 190px;'>
                            <option value='pending' class='bg-white text-dark' " . ($status_val == 'pending' ? 'selected' : '') . ">รอพิจารณา</option>
                            <option value='member' class='bg-white text-dark' " . ($status_val == 'member' ? 'selected' : '') . ">✓ อนุมัติเป็นสมาชิก</option>
                            <option value='no_member' class='bg-white text-dark' " . ($status_val == 'no_member' ? 'selected' : '') . ">✗ ไม่อนุมัติการเป็นสมาชิก</option>
                        </select>
                    ";

                    echo "<tr>
                            <td>" . $ลำดับ++ . "</td>
                            <td class='text-start'>" . htmlspecialchars($row['idstudent']) . "</td>
                            <td class='text-start'>" . htmlspecialchars($full_name) . "</td>
                            <td>" . htmlspecialchars($row['year_level']) . "</td>
                            <td>" . htmlspecialchars($row['department']) . "</td>
                            <td>" . htmlspecialchars($display_role) . "</td>
                            <td>" . $status_html . "</td>
                            <td>
                                <a href='edit_user.php?user_id={$row['user_id']}' class='btn btn-warning btn-sm shadow-sm' title='แก้ไข'>
                                    <i class='fa-solid fa-pencil'></i>
                                </a>
                                &nbsp;
                                <a href='#' class='btn btn-danger btn-sm delete-btn shadow-sm' data-id='{$row['user_id']}' title='ลบ'>
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
                            <td colspan="7" class="text-center text-muted fw-bold py-4"
                                style="background-color: transparent;">
                                <i class="fa-solid fa-circle-info fs-4 d-block mb-2"></i> ไม่พบข้อมูลที่ค้นหา
                            </td>
                        </tr>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center border-0 shadow">
                <div class="modal-header bg-purple text-white">
                    <h5 class="modal-title" id="alertModalLabel">แจ้งเตือน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4 pb-4" id="alertMessage" style="font-size: 16px;">
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-purple px-4" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteLabel"><i class="fa-solid fa-triangle-exclamation"></i>
                        ยืนยันการลบ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-4 pb-3" style="font-size: 16px;">
                    <p>คุณต้องการลบรายชื่อผู้ใช้งานนี้หรือไม่?</p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">ลบข้อมูล</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });

        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn').length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });

        $(".search-name").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            var visibleRows = 0;
            $("#memberTable tbody tr").each(function() {
                if ($(this).attr("id") === "noResult") return;

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

        $(document).on('change', '.status-select', function() {
            var userId = $(this).data('user-id');
            var newStatus = $(this).val();
            var selectElement = $(this);

            $.ajax({
                url: 'update_membership_status.php',
                type: 'POST',
                data: {
                    user_id: userId,
                    status: newStatus
                },
                success: function(response) {
                    
                    selectElement.removeClass('bg-success bg-danger bg-warning text-white text-dark');
                    
                    if (newStatus === 'member') {
                        selectElement.addClass('bg-success text-white');
                    } else if (newStatus === 'no_member') {
                        selectElement.addClass('bg-danger text-white');
                    } else {
                        selectElement.addClass('bg-warning text-dark');
                    }
                },
                error: function() {
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
                }
            });
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
                            .fadeOut(300, function() {
                                $(this).remove();
                                if ($("#memberTable tbody tr:visible").length === 0) {
                                    $("#noResult").show();
                                }
                            });
                        $("#alertMessage").text(response);
                        $("#alertModal").modal("show");
                        deleteUserId = null;
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