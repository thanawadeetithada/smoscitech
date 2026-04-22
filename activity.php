<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_year_level = $_SESSION['year_level'] ?? '';
$user_academic_year = $_SESSION['academic_year'] ?? '';
$user_department = $_SESSION['department'] ?? '';

$stmt_profile = $conn->prepare("SELECT profile_image, first_name FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : 'ผู้ใช้งาน';
$stmt_profile->close();

$sql = "SELECT a.*, 
               ar.registration_id, 
               ar.registration_status,
               (SELECT SUM(capacity) FROM activity_tasks WHERE activity_id = a.activity_id) as total_capacity,
               (SELECT COUNT(*) FROM activity_registrations WHERE activity_id = a.activity_id AND registration_status != 'cancelled') as current_registrations
        FROM activities a
        LEFT JOIN activity_registrations ar ON a.activity_id = ar.activity_id AND ar.user_id = ?
        WHERE (a.allowed_year_level IS NULL OR a.allowed_year_level = '' OR a.allowed_year_level LIKE CONCAT('%', ?, '%'))
        AND (a.allowed_academic_year IS NULL OR a.allowed_academic_year = '' OR a.allowed_academic_year LIKE CONCAT('%', ?, '%'))
        AND (a.allowed_department IS NULL OR a.allowed_department = '' OR a.allowed_department LIKE CONCAT('%', ?, '%'))
        ORDER BY a.start_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isss", $user_id, $user_year_level, $user_academic_year, $user_department);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้ากิจกรรม - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F4F6F9;
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
        padding: 30px;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .table-container-box {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin: 0 auto;
        overflow: hidden;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table-title {
        padding: 10px;
        font-size: 18px;
        color: #333;
        background-color: #FEFBEA;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .custom-table {
        width: 100%;
        border-collapse: collapse;
        text-align: center;
    }

    .custom-table th,
    .custom-table td {
        white-space: nowrap;
    }

    .custom-table thead th {
        background-color: #D3D4D6;
        padding: 12px;
        font-weight: 500;
        color: #333;
        border-top: 2px solid white;
        border-bottom: 2px solid white;
    }

    .custom-table tbody tr {
        background-color: #EBECF0;
    }

    .custom-table td {
        padding: 12px 10px;
        vertical-align: middle;
        color: #333;
        border-bottom: 1px solid white;
    }

    .btn-upload-custom {
        background: white;
        border: none;
        border-radius: 5px;
        padding: 4px 12px;
        font-size: 12px;
        color: #333;
        display: inline-flex;
        align-items: center;
        justify-content: space-between;
        min-width: 90px;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-upload-custom:hover {
        background: #f0f0f0;
        color: #000;
    }

    .status-badge-green {
        background-color: #28A745;
        color: white;
        padding: 4px 15px;
        border-radius: 5px;
        font-size: 11px;
        display: inline-block;
        min-width: 90px;
    }

    .status-badge-yellow {
        background-color: #FFC107;
        color: #000;
        padding: 4px 15px;
        border-radius: 5px;
        font-size: 11px;
        display: inline-block;
        min-width: 90px;
    }

    .status-badge-gray {
        background-color: #6C757D;
        color: white;
        padding: 4px 15px;
        border-radius: 5px;
        font-size: 11px;
        display: inline-block;
        min-width: 90px;
    }

    .btn-action-detail {
        background-color: #5C55D9;
        color: white;
        border-radius: 5px;
        padding: 4px 15px;
        font-size: 11px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-action-edit {
        background-color: #D633FF;
        color: white;
        border-radius: 5px;
        padding: 4px 15px;
        font-size: 11px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-action-delete {
        background-color: #DC3545;
        color: white;
        border-radius: 5px;
        padding: 4px 15px;
        font-size: 11px;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
        border: none;
    }

    .btn-action-detail:hover,
    .btn-action-edit:hover,
    .btn-action-delete:hover {
        color: white;
        opacity: 0.8;
    }

    .pagination-area {
        margin-top: auto;
        padding: 30px 15px;
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .btn-bottom {
        background-color: #6358E1;
        color: white;
        border: none;
        border-radius: 20px;
        padding: 8px 30px;
        font-size: 14px;
        transition: 0.3s;
    }

    .btn-bottom:hover {
        background-color: #4a40bd;
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
            padding: 15px;
        }

        .logout-text {
            padding: 2px !important;
            font-size: 9px !important;
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
                <img src="img/logo.png" alt="Logo" class="brand-logo"
                    onerror="this.src='https://via.placeholder.com/60'">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <span class="text-page-pill-btn mt-1">ข้อมูลกิจกรรม</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($first_name); ?>
                </span>
                <div class="logout-area">
                    <a href="user_management.php">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile"
                            style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"
                            onerror="this.src='https://via.placeholder.com/45'">
                    </a>
                    <a href="logout.php" class="logout-text mt-1">Log out</a>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            <aside class="sidebar">
                <a href="report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="e-portfolio.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-book-open"></i>
                    <span>E -portfolio</span>
                </a>
                <a href="user_management.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-users"></i>
                    <span>ข้อมูลสมาชิกสโมสร</span>
                </a>
                <a href="activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>ข้อมูลกิจกรรม</span>
                </a>
            </aside>

            <main class="content-area">
                <?php
                
                $registered_activities = [];
                $unregistered_activities = [];

                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        if (!empty($row['registration_id'])) {
                            $registered_activities[] = $row;
                        } else {
                            $unregistered_activities[] = $row;
                        }
                    }
                }
                ?>

                <div class="table-container-box mb-5">
                    <div class="table-title">กิจกรรมที่ยังไม่ได้ลงทะเบียน</div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อกิจกรรม</th>
                                    <th>วันที่</th>
                                    <th>อัพโหลดหลักฐาน</th>
                                    <th>สถานะ</th>
                                    <th>เพิ่มเติม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($unregistered_activities)): $i = 1; ?>
                                    <?php foreach ($unregistered_activities as $row): 
                                        $date_display = date('d-m-Y', strtotime($row['start_date']));
                                    ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo $date_display; ?></td>
                                        <td>
                                            <span class="text-muted">-</span>
                                        </td>
                                        <td>
                                            <span class="status-badge-gray">ยังไม่ได้ลงทะเบียน</span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="participate_activity.php?id=<?php echo $row['activity_id']; ?>"
                                                    class="btn-action-detail">รายละเอียด</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">ไม่พบข้อมูลกิจกรรมใหม่</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-container-box">
                    <div class="table-title">กิจกรรมที่ลงทะเบียนแล้ว</div>
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ลำดับ</th>
                                    <th>ชื่อกิจกรรม</th>
                                    <th>วันที่</th>
                                    <th>อัพโหลดหลักฐาน</th>
                                    <th>สถานะ</th>
                                    <th>เพิ่มเติม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($registered_activities)): $i = 1; ?>
                                    <?php foreach ($registered_activities as $row): 
                                        $date_display = date('d-m-Y', strtotime($row['start_date']));
                                        
                                        if ($row['registration_status'] == 'pending') {
                                            $status_class = 'status-badge-yellow'; 
                                            $status_text = 'รอการอนุมัติ';
                                        } elseif ($row['registration_status'] == 'approved') {
                                            $status_class = 'status-badge-green'; 
                                            $status_text = 'ลงทะเบียนแล้ว';
                                        } else {
                                            $status_class = 'status-badge-gray';
                                            $status_text = ucfirst($row['registration_status']); 
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo $date_display; ?></td>
                                        <td>
                                            <a href="upload_evidence.php?reg_id=<?php echo $row['registration_id']; ?>"
                                                class="btn-upload-custom">
                                                Upload <i class="fas fa-caret-down text-dark"></i>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a href="participate_activity.php?id=<?php echo $row['activity_id']; ?>"
                                                    class="btn-action-detail">รายละเอียด</a>
                                                <a href="upload_evidence.php?reg_id=<?php echo $row['registration_id']; ?>&edit=1"
                                                    class="btn-action-edit">แก้ไข</a>
                                                <button type="button" class="btn-action-delete"
                                                    onclick="showDeleteModal(<?php echo $row['registration_id']; ?>)">ลบ</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">ไม่มีรายการกิจกรรมที่ลงทะเบียน</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-area">
                        <button class="btn-bottom" onclick="history.back()">กลับ</button>
                        <button class="btn-bottom">ถัดไป</button>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <?php if (isset($_SESSION['status_modal'])): ?>
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg" style="border-radius: 20px; border: none;">
                <div
                    class="modal-header <?php echo ($_SESSION['status_modal']['type'] == 'success') ? 'bg-primary' : 'bg-danger'; ?> text-white border-0">
                    <h5 class="modal-title fw-bold"><?php echo $_SESSION['status_modal']['title']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="fas <?php echo ($_SESSION['status_modal']['type'] == 'success') ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?> mb-3"
                        style="font-size: 4rem;"></i>
                    <h5 class="text-dark"><?php echo $_SESSION['status_modal']['message']; ?></h5>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <button type="button" class="btn btn-primary px-5" data-bs-dismiss="modal">ตกลง</button>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['status_modal']); endif; ?>

    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-3" style="border-radius: 20px; border: none;">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0 pb-4">
                    <div class="mb-3">
                        <div class="mx-auto d-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle"
                            style="width: 80px; height: 80px;">
                            <i class="fas fa-trash text-danger fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">ยืนยันการลบกิจกรรม</h5>
                    <p class="text-muted mb-1">คุณต้องการลบกิจกรรมนี้หรือไม่?</p>

                    <form id="deleteForm" action="delete_registration.php" method="POST">
                        <input type="hidden" name="registration_id" id="deleteRegistrationId" value="">
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-light px-4 rounded-pill fw-medium"
                                data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-danger px-4 rounded-pill fw-medium"
                                id="confirmDeleteBtn">ใช่, ลบข้อมูล</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn')
                    .length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });
        var statusModalEl = document.getElementById('statusModal');
        if (statusModalEl) {
            var myModal = new bootstrap.Modal(statusModalEl);
            myModal.show();
        }
    });

    function showDeleteModal(registrationId) {
        document.getElementById('deleteRegistrationId').value = registrationId;
        var deleteModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        deleteModal.show();
    }
    </script>
</body>

</html>