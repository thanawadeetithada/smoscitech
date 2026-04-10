<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

// --- ดึงข้อมูลรูปโปรไฟล์และชื่อสำหรับ Top Navbar ---
$user_id = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT profile_image, first_name FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$first_name = !empty($user_data['first_name']) ? $user_data['first_name'] : 'ผู้ใช้งาน';
$stmt_profile->close();
// ---------------------------------------------------------

// ดึงข้อมูลปีการศึกษาจากฐานข้อมูล
$academic_years = [];
$sql_years = "SELECT DISTINCT academic_year FROM users WHERE academic_year IS NOT NULL AND academic_year != '' ORDER BY academic_year DESC";
$result_years = $conn->query($sql_years);
if ($result_years && $result_years->num_rows > 0) {
    while ($row = $result_years->fetch_assoc()) {
        $academic_years[] = $row['academic_year'];
    }
}

$year_levels = ["ชั้นปีที่ 1", "ชั้นปีที่ 2", "ชั้นปีที่ 3", "ชั้นปีที่ 4"];
$depts = [
    "วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "นวัตกรรมและธุรกิจอาหาร", 
    "สาธารณสุขศาสตร์", "เคมี (วท.บ.)", "วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม", 
    "ฟิสิกส์", "เคมี (ค.บ.)", "ชีววิทยา", "คณิตศาสตร์ประยุกต์"
];
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าสร้างกิจกรรม - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

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

    .ui-card {
        background: white;
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 25px;
        overflow: hidden;
    }

    .card-header-custom {
        background: rgba(163, 126, 94, 0.1); 
        padding: 15px 25px;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        font-weight: 600;
        color: var(--top-bar-bg);
        font-size: 16px;
    }

    .form-label {
        font-weight: 600;
        color: #555;
        font-size: 14px;
    }

    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ddd;
        padding: 10px 15px;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--btn-blue);
        box-shadow: 0 0 0 0.2rem rgba(99, 88, 225, 0.15);
    }

    
    .task-row {
        background: #FAFAFA;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 15px;
        position: relative;
        border: 1px solid #EEE;
        transition: 0.3s;
    }
    
    .task-row:hover { border-color: #DDD; background: #F5F5F5; }

    .btn-remove-task {
        position: absolute;
        top: -10px;
        right: -10px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        border: none;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        transition: 0.2s;
    }
    
    .btn-remove-task:hover { transform: scale(1.1); }

    
    .btn-purple-custom {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 10px 25px;
        font-weight: 500;
        transition: 0.3s;
    }

    .btn-purple-custom:hover {
        background-color: #4a40bd;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99, 88, 225, 0.3);
    }
    
    .btn-outline-custom {
        border: 1px solid #ccc;
        background: white;
        border-radius: 8px;
        padding: 10px 25px;
        color: #555;
        font-weight: 500;
        transition: 0.3s;
    }
    
    .btn-outline-custom:hover { background: #f0f0f0; color: #333; }

    
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 42px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    
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
        
        .task-row { padding: 15px; }
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
                    <span class="text-page-pill-btn mt-1">กิจกรรมสโมสร</span>
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
                <div class="container-fluid max-w-1000 mx-auto" style="max-width: 1000px;">
                    <div class="d-flex align-items-center mb-4">
                        <a href="admin_activity.php" class="btn btn-outline-custom p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border-radius: 50%;">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark">สร้างกิจกรรมใหม่</h4>
                            <p class="text-muted mb-0 small">เพิ่มข้อมูลกิจกรรมและขอบเขตงาน</p>
                        </div>
                    </div>

                    <form action="process_create_activity.php" method="POST" enctype="multipart/form-data">
                        
                        <div class="ui-card">
                            <div class="card-header-custom">
                                <i class="fas fa-info-circle me-2"></i>ข้อมูลทั่วไป
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-12">
                                        <label class="form-label">รูปหน้าปกกิจกรรม (ถ้ามี)</label>
                                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">ชื่อกิจกรรม <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control" placeholder="เช่น ค่ายอาสาพัฒนาชนบท" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">รายละเอียดกิจกรรม</label>
                                        <textarea name="description" class="form-control" rows="4" placeholder="กรอกรายละเอียด..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">สถานที่จัดกิจกรรม</label>
                                        <input type="text" name="location" class="form-control" placeholder="ระบุสถานที่">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">จำนวนชั่วโมงกิจกรรม (กยศ.)</label>
                                        <input type="number" name="hours_count" class="form-control" value="0" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">วัน/เวลาที่เริ่มลงทะเบียน <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="start_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">วัน/เวลาที่สิ้นสุดลงทะเบียน <span class="text-danger">*</span></label>
                                        <input type="datetime-local" name="end_date" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ui-card">
                            <div class="card-header-custom">
                                <i class="fas fa-user-lock me-2"></i>เงื่อนไขผู้มีสิทธิ์เข้าร่วม <span class="fw-normal text-muted" style="font-size: 13px;">(ปล่อยว่างหากเปิดรับทุกคน)</span>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <label class="form-label">ชั้นปีที่รับสมัคร</label>
                                        <select name="target_year_level[]" class="form-select select2-multiple" multiple="multiple" data-placeholder="เลือกชั้นปี...">
                                            <?php foreach ($year_levels as $yl): ?>
                                            <option value="<?php echo htmlspecialchars($yl); ?>">
                                                <?php echo htmlspecialchars($yl); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">ปีการศึกษา</label>
                                        <select name="target_academic_year[]" class="form-select select2-multiple" multiple="multiple" data-placeholder="เลือกปีการศึกษา...">
                                            <?php foreach ($academic_years as $ay): ?>
                                            <option value="<?php echo htmlspecialchars($ay); ?>">
                                                <?php echo htmlspecialchars($ay); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">สาขาวิชา</label>
                                        <select name="target_department[]" class="form-select select2-multiple" multiple="multiple" data-placeholder="เลือกสาขาวิชา...">
                                            <?php foreach ($depts as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept); ?>">
                                                <?php echo htmlspecialchars($dept); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ui-card">
                            <div class="card-header-custom d-flex justify-content-between align-items-center">
                                <div><i class="fas fa-tasks me-2"></i>หน้าที่และขอบเขตงาน (Tasks)</div>
                                <button type="button" class="btn btn-sm btn-outline-custom py-1" onclick="addTask()">
                                    <i class="fas fa-plus me-1"></i> เพิ่มหน้าที่
                                </button>
                            </div>
                            <div class="card-body p-4">
                                <div id="tasks-container">
                                    <div class="task-row">
                                        <button type="button" class="btn-remove-task" onclick="this.parentElement.remove()" title="ลบออก">&times;</button>
                                        <div class="row g-3">
                                            <div class="col-md-5">
                                                <label class="form-label small">ชื่อฝ่าย/หน้าที่ <span class="text-danger">*</span></label>
                                                <input type="text" name="task_name[]" class="form-control" placeholder="เช่น ฝ่ายสถานที่, ผู้เข้าร่วมทั่วไป" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small">จำนวนที่รับ (คน)</label>
                                                <input type="number" name="task_capacity[]" class="form-control" value="10" min="1">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small">รายละเอียด</label>
                                                <input type="text" name="task_detail[]" class="form-control" placeholder="ระบุเพิ่มเติม (ถ้ามี)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-3 mt-4 mb-5">
                            <button type="reset" class="btn btn-outline-custom px-4">ล้างข้อมูล</button>
                            <button type="submit" class="btn btn-purple-custom px-5"><i class="fas fa-save me-2"></i>บันทึกและสร้างกิจกรรม</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

        // Initialize Select2
        $('.select2-multiple').select2({
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        });
    });

    // Add Task Function
    function addTask() {
        const container = document.getElementById('tasks-container');
        const div = document.createElement('div');
        div.className = 'task-row';
        div.innerHTML = `
        <button type="button" class="btn-remove-task" onclick="this.parentElement.remove()" title="ลบออก">&times;</button>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label small">ชื่อฝ่าย/หน้าที่ <span class="text-danger">*</span></label>
                <input type="text" name="task_name[]" class="form-control" placeholder="เช่น ฝ่ายสวัสดิการ" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small">จำนวนที่รับ (คน)</label>
                <input type="number" name="task_capacity[]" class="form-control" value="5" min="1">
            </div>
            <div class="col-md-4">
                <label class="form-label small">รายละเอียด</label>
                <input type="text" name="task_detail[]" class="form-control" placeholder="ระบุเพิ่มเติม (ถ้ามี)">
            </div>
        </div>
        `;
        container.appendChild(div);
    }
    </script>
</body>
</html>