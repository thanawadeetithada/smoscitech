<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

$can_edit = ($_SESSION['userrole'] === 'club_president');

// --- ดึงข้อมูลรูปโปรไฟล์และชื่อสำหรับ Top Navbar ---
$user_id_logged = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT profile_image, first_name FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id_logged);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : 'ผู้ใช้งาน';
$stmt_profile->close();
// ---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
   
    if (!$can_edit) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์ในการเปลี่ยนสถานะ']);
        exit();
    }

    $reg_id = intval($_POST['reg_id']);
    $new_status = $_POST['status'];
    
    if (in_array($new_status, ['pending', 'approved', 'rejected'])) {
        $upd_sql = "UPDATE activity_registrations SET registration_status = ? WHERE registration_id = ?";
        $stmt_upd = $conn->prepare($upd_sql);
        $stmt_upd->bind_param("si", $new_status, $reg_id);
        
        if ($stmt_upd->execute()) {
            echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึก']);
        }
        $stmt_upd->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'สถานะไม่ถูกต้อง']);
    }
    exit();
}

$activity_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT a.*, 
        (SELECT COUNT(*) FROM activity_registrations WHERE activity_id = a.activity_id AND registration_status != 'cancelled') as reg_count,
        (SELECT SUM(capacity) FROM activity_tasks WHERE activity_id = a.activity_id) as total_cap
        FROM activities a WHERE a.activity_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();
$activity = $result->fetch_assoc();

if (!$activity) {
    die("ไม่พบข้อมูลกิจกรรม");
}

$sql_reg = "SELECT r.*, u.first_name, u.last_name, u.idstudent, t.task_name 
            FROM activity_registrations r
            JOIN users u ON r.user_id = u.user_id
            LEFT JOIN activity_tasks t ON r.task_id = t.task_id
            WHERE r.activity_id = ? 
            ORDER BY r.registered_at DESC";
$stmt_reg = $conn->prepare($sql_reg);
$stmt_reg->bind_param("i", $activity_id);
$stmt_reg->execute();
$registrations = $stmt_reg->get_result();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $can_edit ? 'จัดการกิจกรรม' : 'รายละเอียดกิจกรรม'; ?> - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F4F6F9;
        --btn-blue: #6358E1;
        --text-dark: #333333;
    }

    body, html {
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

    .brand-logo { width: 60px; height: 60px; }

    .brand-name {
        font-size: clamp(16px, 4vw, 24px);
        font-family: serif;
        letter-spacing: 1px;
        white-space: nowrap;
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

    .login-pill-btn:hover { background: #eee; color: black; }

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

    .sidebar-item span { font-weight: bold; font-size: 13px; }

    
    .content-area {
        flex-grow: 1;
        padding: 30px;
    }

    .max-w-1200 {
        max-width: 1200px;
        margin: 0 auto;
    }

    .ui-card {
        background: white;
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        margin-bottom: 25px;
        overflow: hidden;
    }

    .stats-card {
        background: white;
        border-radius: 15px;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
        transition: transform 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .stats-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0, 0, 0, 0.05); }

    
    .table-container {
        padding: 25px;
    }

    .table th {
        background-color: #f8f9fa;
        color: #555;
        font-weight: 600;
        border-bottom: 2px solid #eee;
        padding: 15px;
    }

    .table td {
        padding: 15px;
        vertical-align: middle;
        color: #444;
        border-bottom: 1px solid #f0f0f0;
    }

    
    .status-select {
        font-weight: 600;
        border-radius: 50px;
        cursor: pointer;
        border: 1px solid transparent;
        padding: 5px 15px;
        text-align: center;
        text-align-last: center;
        appearance: none; 
        -moz-appearance: none;
        -webkit-appearance: none;
    }

    .status-select:disabled {
        cursor: not-allowed;
        opacity: 0.8;
    }

    .status-pending { background-color: #FFF3CD; color: #856404; border-color: #FFEEBA; }
    .status-approved { background-color: #D4EDDA; color: #155724; border-color: #C3E6CB; }
    .status-rejected { background-color: #F8D7DA; color: #721C24; border-color: #F5C6CB; }

    
    @media (max-width: 768px) {
        .sidebar {
            position: absolute;
            top: 0;
            left: -230px;
            height: 100%;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }
        .sidebar.active { left: 0; }
        .top-navbar { padding: 10px 15px; }
        .brand-name { font-size: 18px; }
        .content-area { padding: 15px; }
        .logout-text { padding: 2px !important; font-size: 10px !important; }
        .logout-area { margin-left: 10px; }
        
        .header-flex {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 15px;
        }
        .header-buttons {
            width: 100%;
            display: flex;
            gap: 10px;
        }
        .header-buttons .btn { flex: 1; }
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <nav class="top-navbar">
            <div class="brand-section">
                <i class="fa-solid fa-bars d-md-none me-2" id="mobileMenuBtn" style="font-size: 24px; cursor: pointer;"></i>
                <img src="img/logo.png" alt="Logo" class="brand-logo">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <span class="text-page-pill-btn mt-1">จัดการกิจกรรม</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($first_name); ?>
                </span>
                <div class="logout-area">
                    <a href="user_management.php">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image); ?>" alt="Profile"
                            style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    </a>
                    <a href="logout.php" class="logout-text mt-1">Log out</a>
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

                <a href="admin_activity.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-cubes"></i>
                    <span>ข้อมูลกิจกรรม</span>
                </a>

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
                <div class="max-w-1200">
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 header-flex">
                        <div class="d-flex align-items-center">
                            <a href="admin_activity.php" class="btn btn-outline-custom p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($activity['title']); ?></h4>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted small"><?php echo $can_edit ? 'อนุมัติผู้เข้าร่วมกิจกรรม' : 'รายละเอียดกิจกรรม'; ?></span>
                                    <span class="badge rounded-pill bg-<?php echo ($activity['status'] == 'open') ? 'success' : 'danger'; ?>">
                                        <?php echo ($activity['status'] == 'open') ? 'เปิดรับสมัคร' : 'ปิดรับสมัคร'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if($can_edit): ?>
                        <div class="header-buttons d-flex gap-2">
                            <button class="btn btn-warning shadow-sm fw-medium px-4 rounded-pill" onclick="prepareClose(<?php echo $activity_id; ?>)">
                                <i class="fas fa-lock me-1"></i> ปิดรับสมัคร
                            </button>
                            <button class="btn btn-danger shadow-sm fw-medium px-4 rounded-pill" onclick="prepareDelete(<?php echo $activity_id; ?>)">
                                <i class="fas fa-trash me-1"></i> ลบกิจกรรม
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-4">
                            <div class="stats-card p-4 text-center">
                                <div class="text-muted small mb-1">จำนวนผู้สมัครปัจจุบัน</div>
                                <div class="fw-bold" style="font-size: 1.8rem; color: var(--btn-blue);">
                                    <?php echo $activity['reg_count']; ?> <span style="font-size: 1rem; color: #999;">/ <?php echo ($activity['total_cap'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="stats-card p-4 text-center">
                                <div class="text-muted small mb-1">วันที่จัดกิจกรรม</div>
                                <div class="fw-bold text-dark" style="font-size: 1.3rem; margin-top: 5px;">
                                    <?php echo date('d M Y', strtotime($activity['start_date'])); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="stats-card p-4 text-center">
                                <div class="text-muted small mb-1">สถานที่</div>
                                <div class="fw-bold text-dark text-truncate" style="font-size: 1.2rem; margin-top: 5px;" title="<?php echo htmlspecialchars($activity['location'] ?? 'ไม่ระบุ'); ?>">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> <?php echo htmlspecialchars($activity['location'] ?? 'ไม่ระบุ'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ui-card table-container">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-users me-2" style="color: var(--top-bar-bg);"></i>รายชื่อผู้ลงทะเบียน</h5>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th width="15%">รหัสนักศึกษา</th>
                                        <th width="30%">ชื่อ-นามสกุล</th>
                                        <th width="20%">ฝ่าย/หน้าที่</th>
                                        <th width="15%">วันที่สมัคร</th>
                                        <th width="20%" class="text-center">สถานะการอนุมัติ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($registrations->num_rows > 0): ?>
                                    <?php while($reg = $registrations->fetch_assoc()): 
                                            $select_class = 'status-pending';
                                            if($reg['registration_status'] == 'approved') $select_class = 'status-approved';
                                            if($reg['registration_status'] == 'rejected') $select_class = 'status-rejected';
                                        ?>
                                    <tr>
                                        <td class="fw-bold" style="color: var(--btn-blue);"><?php echo htmlspecialchars($reg['idstudent']); ?></td>
                                        <td><?php echo htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']); ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <?php echo htmlspecialchars($reg['task_name'] ?: 'ไม่ระบุ'); ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo date('d/m/Y H:i', strtotime($reg['registered_at'])); ?>
                                        </td>
                                        <td class="text-center">
                                            <select class="form-select form-select-sm status-select <?php echo $select_class; ?> mx-auto"
                                                    data-reg-id="<?php echo $reg['registration_id']; ?>"
                                                    style="width: 140px;"
                                                    <?php echo !$can_edit ? 'disabled' : ''; ?>>
                                                <option value="pending" class="bg-white text-dark" <?php if($reg['registration_status'] == 'pending') echo 'selected'; ?>>รอพิจารณา</option>
                                                <option value="approved" class="bg-white text-dark" <?php if($reg['registration_status'] == 'approved') echo 'selected'; ?>>✓ อนุมัติ</option>
                                                <option value="rejected" class="bg-white text-dark" <?php if($reg['registration_status'] == 'rejected') echo 'selected'; ?>>✗ ปฏิเสธ</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fa-regular fa-folder-open fa-3x mb-3" style="opacity: 0.3;"></i><br>
                                            ยังไม่มีผู้ลงทะเบียนในกิจกรรมนี้
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <?php if($can_edit): ?>
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-3" style="border-radius: 20px; border: none;">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0 pb-4">
                    <div class="mb-3">
                        <div class="mx-auto d-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <i class="fas fa-trash text-danger fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">ยืนยันการลบกิจกรรม</h5>
                    <p class="text-muted mb-1">คุณต้องการลบกิจกรรมนี้หรือไม่?</p>
                    <p class="text-danger small mb-4">*ข้อมูลผู้สมัครและคะแนนทั้งหมดที่เกี่ยวข้องจะถูกลบออกถาวร</p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">ยกเลิก</button>
                        <a href="#" class="btn btn-danger px-4 rounded-pill fw-medium" id="confirmDeleteBtn">ใช่, ลบข้อมูล</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmCloseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-3" style="border-radius: 20px; border: none;">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-0 pb-4">
                    <div class="mb-3">
                        <div class="mx-auto d-flex align-items-center justify-content-center bg-warning bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <i class="fas fa-lock text-warning fa-2x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2">ยืนยันการปิดรับสมัคร</h5>
                    <p class="text-muted mb-4">เมื่อปิดแล้ว นักศึกษาจะไม่สามารถลงทะเบียนเพิ่มได้อีก<br>คุณแน่ใจหรือไม่?</p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light px-4 rounded-pill fw-medium" data-bs-dismiss="modal">ยกเลิก</button>
                        <a href="#" class="btn btn-warning text-dark px-4 rounded-pill fw-bold" id="confirmCloseBtn">ยืนยันปิดรับสมัคร</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Mobile Sidebar Toggle
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
    });

    <?php if($can_edit): ?>
    // Modal action prep
    function prepareDelete(id) {
        const myModal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
        document.getElementById('confirmDeleteBtn').href = 'process_delete_activity.php?id=' + id;
        myModal.show();
    }

    function prepareClose(id) {
        const myModal = new bootstrap.Modal(document.getElementById('confirmCloseModal'));
        document.getElementById('confirmCloseBtn').href = 'process_close_activity.php?id=' + id;
        myModal.show();
    }

    // AJAX for Select Status Change
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const selectElement = this;
            const regId = selectElement.dataset.regId;
            const newStatus = selectElement.value;

            // Update Color Class instantly for better UX
            selectElement.classList.remove('status-pending', 'status-approved', 'status-rejected');
            if (newStatus === 'pending') selectElement.classList.add('status-pending');
            else if (newStatus === 'approved') selectElement.classList.add('status-approved');
            else if (newStatus === 'rejected') selectElement.classList.add('status-rejected');

            // Send Request
            const formData = new URLSearchParams();
            formData.append('action', 'update_status');
            formData.append('reg_id', regId);
            formData.append('status', newStatus);

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด',
                        text: data.message,
                        confirmButtonColor: '#6358E1'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'ระบบขัดข้อง',
                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#6358E1'
                });
            });
        });
    });
    <?php endif; ?>
    </script>
</body>
</html>