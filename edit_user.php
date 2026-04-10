<?php
session_start();
require 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['user_id'])) {
    die("ไม่มีการระบุ ID");
}

$user_id = $_GET['user_id'];

// ดึงข้อมูลเป้าหมายที่ต้องการแก้ไข
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("ไม่พบข้อมูล");
}

$row = $result->fetch_assoc();

// ดึงข้อมูลรูปโปรไฟล์ของแอดมินที่ล็อกอินอยู่ (สำหรับ Navbar)
$logged_in_user_id = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $logged_in_user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
$admin_profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$stmt_profile->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="SMO SCITECH KPRU">
    <meta name="application-name" content="SMO SCITECH KPRU">
    <meta name="theme-color" content="#A37E5E">
    <title>แก้ไขข้อมูลผู้ใช้งาน - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&family=Prompt:wght@300;400;500;600&display=swap"
        rel="stylesheet">

    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F4F7FA;
        --btn-blue: #6358E1;
        --btn-blue-hover: #4e44b8;
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
        padding: 40px 20px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .form-card {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        width: 100%;
        max-width: 800px;
        padding: 40px;
        border: none;
        font-family: 'Prompt', sans-serif;
    }

    .form-title {
        color: var(--top-bar-bg);
        font-weight: 600;
        text-align: center;
        margin-bottom: 30px;
        position: relative;
        font-size: 24px;
    }

    .form-title::after {
        content: '';
        width: 60px;
        height: 4px;
        background-color: var(--yellow-sidebar);
        display: block;
        margin: 10px auto 0;
        border-radius: 2px;
    }

    .form-label {
        font-weight: 500;
        color: #4a5568;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
        padding: 12px 15px;
        border: 1px solid #cbd5e1;
        font-size: 15px;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--top-bar-bg);
        box-shadow: 0 0 0 0.25rem rgba(163, 126, 94, 0.25);
    }

    .profile-preview-wrapper {
        text-align: center;
        margin-top: 15px;
    }

    .profile-preview {
        width: 140px;
        height: 140px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        display: inline-block;
    }

    .btn-submit {
        background-color: var(--btn-blue);
        color: white;
        font-weight: 500;
        padding: 12px 30px;
        border-radius: 8px;
        border: none;
        transition: all 0.3s;
        min-width: 150px;
    }

    .btn-submit:hover {
        background-color: var(--btn-blue-hover);
        color: white;
        transform: translateY(-2px);
    }

    .btn-cancel {
        background-color: #e2e8f0;
        color: #4a5568;
        font-weight: 500;
        padding: 12px 30px;
        border-radius: 8px;
        border: none;
        transition: all 0.3s;
        min-width: 150px;
    }

    .btn-cancel:hover {
        background-color: #cbd5e1;
        transform: translateY(-2px);
    }

    .btn-group-custom {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 30px;
    }

    
    .membership-status-container {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 25px;
    }

    .status-title {
        font-size: 24px;
        font-weight: bold;
        margin-top: 5px;
    }

    .status-options-wrapper {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .status-item {
        display: flex;
        align-items: center;
        gap: 15px;
        cursor: pointer;
    }

    .status-circle {
        width: 35px;
        height: 35px;
        border-radius: 50%;
    }

    .circle-red {
        background-color: #FF3B30;
    }

    .circle-green {
        background-color: #34C759;
    }

    .status-label-box {
        font-size: 16px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 5px 25px;
        min-width: 150px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .status-radio-input {
        display: none;
    }

    
    .status-radio-input:checked+.status-item .status-label-box {
        border-color: var(--top-bar-bg);
        background-color: #fcfcfc;
        font-weight: bold;
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

        .form-card {
            padding: 25px 20px;
        }

        .logout-text {
            padding: 2px !important;
            font-size: 9px !important;
        }

        .btn-group-custom {
            flex-direction: column;
        }

        .btn-group-custom .btn {
            width: 100%;
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
                    <span class="text-page-pill-btn mt-1">แก้ไขข้อมูลผู้ใช้งาน</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'ผู้ใช้งาน'); ?>
                </span>

                <div class="logout-area">
                    <a href="user_management.php">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($admin_profile_image); ?>" alt="Profile"
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
                <a href="admin_user_management.php" class="sidebar-item mb-3"
                    style="background: #FDFDFD; border-left: 5px solid var(--top-bar-bg);">
                    <i class="fa-solid fa-users"></i>
                    <span>ข้อมูลสมาชิกสโมสร / นายกสโมสร / รองนายกสโมสร </span>
                </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <a href="admin_user_management.php" class="sidebar-item mb-3"
                    style="background: #FDFDFD; border-left: 5px solid var(--top-bar-bg);">
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
                <div class="form-card">
                    <h2 class="form-title">แก้ไขข้อมูลผู้ใช้งาน</h2>

                    <form action="update_user.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($row['user_id']) ?>">

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="first_name" class="form-label">ชื่อ</label>
                                <input class="form-control" type="text" id="first_name" name="first_name"
                                    value="<?= htmlspecialchars($row['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">นามสกุล</label>
                                <input class="form-control" type="text" id="last_name" name="last_name"
                                    value="<?= htmlspecialchars($row['last_name']) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="idstudent" class="form-label">รหัสนักศึกษา</label>
                                <input class="form-control" type="text" id="idstudent" name="idstudent"
                                    value="<?= htmlspecialchars($row['idstudent']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                                <input class="form-control" type="tel" id="phone" name="phone"
                                    value="<?= htmlspecialchars($row['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="email" class="form-label">E-mail</label>
                                <input class="form-control" type="email" id="email" name="email"
                                    value="<?= htmlspecialchars($row['email']) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="academic_year" class="form-label">ปีการศึกษา</label>
                                <input class="form-control" type="text" id="academic_year" name="academic_year"
                                    value="<?= htmlspecialchars($row['academic_year']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="year_level" class="form-label">ชั้นปี</label>
                                <select class="form-select" id="year_level" name="year_level" required>
                                    <option value="" disabled <?= empty($row['year_level']) ? 'selected' : ''; ?>>--
                                        เลือกชั้นปี --</option>
                                    <?php
                                    $year_levels = ["ชั้นปีที่ 1", "ชั้นปีที่ 2", "ชั้นปีที่ 3", "ชั้นปีที่ 4", "อื่นๆ"];
                                    foreach ($year_levels as $yl) {
                                        $selected = ($row['year_level'] == $yl) ? 'selected' : '';
                                        echo "<option value=\"$yl\" $selected>$yl</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="department" class="form-label">สาขาวิชา</label>
                                <select class="form-select" id="department" name="department" required>
                                    <?php
                                    $departments = [
                                        "วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "นวัตกรรมและธุรกิจอาหาร",
                                        "สาธารณสุขศาสตร์", "เคมี (วท.บ.)", "วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม",
                                        "ฟิสิกส์", "เคมี (ค.บ.)", "ชีววิทยา", "คณิตศาสตร์ประยุกต์"
                                    ];
                                    foreach ($departments as $dept) {
                                        $selected = ($row['department'] == $dept) ? 'selected' : '';
                                        echo "<option value=\"$dept\" $selected>$dept</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="userrole" class="form-label">ตำแหน่ง</label>
                                <select class="form-select" id="userrole" name="userrole" required>
                                    <option value="">-- เลือกประเภท --</option>
                                    <option value="executive"
                                        <?= ($row['userrole'] == 'executive') ? 'selected' : ''; ?>>ผู้บริหาร</option>
                                    <option value="academic_officer"
                                        <?= ($row['userrole'] == 'academic_officer') ? 'selected' : ''; ?>>
                                        นักวิชาการศึกษา</option>
                                    <option value="club_president"
                                        <?= ($row['userrole'] == 'club_president') ? 'selected' : ''; ?>>
                                        นายกสโมสรนักศึกษาและรองนายกสโมสรนักศึกษา</option>
                                    <option value="club_member"
                                        <?= ($row['userrole'] == 'club_member') ? 'selected' : ''; ?>>
                                        สมาชิกสโมสรนักศึกษาคณะวิทยาศาสตร์และเทคโนโลยี</option>
                                </select>
                            </div>
                        </div>

                        <div class="membership-status-container">
                            <label for="first_name" class="form-label">สถานะ</label>
                            <div class="status-options-wrapper">
                                <label>
                                    <input type="radio" name="membership_status" value="no_member"
                                        class="status-radio-input"
                                        <?= ($row['membership_status'] == 'no_member') ? 'checked' : ''; ?>>
                                    <div class="status-item">
                                        <div class="status-circle circle-red"></div>
                                        <div class="status-label-box">ไม่อนุมัติ</div>
                                    </div>
                                </label>

                                <label>
                                    <input type="radio" name="membership_status" value="member"
                                        class="status-radio-input"
                                        <?= ($row['membership_status'] == 'member') ? 'checked' : ''; ?>>
                                    <div class="status-item">
                                        <div class="status-circle circle-green"></div>
                                        <div class="status-label-box">อนุมัติ</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-12">
                                <label for="profile_image" class="form-label">รูปโปรไฟล์</label>
                                <input type="file" id="profile_image" name="profile_image" class="form-control"
                                    accept="image/*" onchange="previewImage(event)">

                                <div class="profile-preview-wrapper">
                                    <?php 
                                    // -------------------------------------------------------------
                                    // แก้ไขพาธรูปภาพมาดึงที่ uploads/profiles/ ทั้งหมด
                                    // -------------------------------------------------------------
                                    $image_src = "uploads/profiles/default.png";
                                    if (!empty($row['profile_image']) && $row['profile_image'] !== 'default.png' && file_exists("uploads/profiles/" . $row['profile_image'])) {
                                        $image_src = "uploads/profiles/" . htmlspecialchars($row['profile_image']);
                                    }
                                    ?>
                                    <img id="preview" src="<?= $image_src ?>" alt="Profile Preview"
                                        class="profile-preview">
                                </div>
                            </div>
                        </div>

                        <div class="btn-group-custom">
                            <button type="submit" class="btn btn-submit">
                                <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูล
                            </button>
                            <button type="button" onclick="window.location.href='admin_user_management.php'"
                                class="btn btn-cancel">
                                <i class="fa-solid fa-xmark me-1"></i> ยกเลิก
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow" style="font-family: 'Prompt', sans-serif;">
                <div class="modal-header text-white" style="background-color: var(--top-bar-bg);">
                    <h5 class="modal-title fw-bold" id="statusModalLabel">แจ้งเตือนระบบ</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4" id="statusMessage">
                </div>
                <div class="modal-footer justify-content-center border-0 pb-4">
                    <button type="button" class="btn text-white px-4" id="closeModalBtn" data-bs-dismiss="modal"
                        style="background-color: var(--top-bar-bg); border-radius: 8px;">ตกลง</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Mobile Sidebar Toggle
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });

        // Close Sidebar when clicking outside
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn')
                    .length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });

        // Status Modal Handling
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        if (status === 'success') {
            $("#statusMessage").html(
                "<i class='fa-solid fa-circle-check text-success fa-3x mb-3'></i><h4 class='text-success fw-bold'>สำเร็จ</h4><p class='text-muted mb-0'>อัปเดตข้อมูลผู้ใช้งานเรียบร้อยแล้ว</p>"
            );
            $("#statusModal").modal("show");
        } else if (status === 'error') {
            $("#statusMessage").html(
                "<i class='fa-solid fa-triangle-exclamation text-danger fa-3x mb-3'></i><h4 class='text-danger fw-bold'>ผิดพลาด</h4><p class='text-muted mb-0'>เกิดข้อผิดพลาดในการอัปเดตข้อมูล</p>"
            );
            $("#statusModal").modal("show");
        }

        $("#closeModalBtn").on("click", function() {
            window.location.href = "admin_user_management.php";
        });

        $('#statusModal').on('hidden.bs.modal', function() {
            if (status === 'success' || status === 'error') {
                window.location.href = "admin_user_management.php";
            }
        });
    });

    // Image Preview Function
    function previewImage(event) {
        const reader = new FileReader();
        const imageField = document.getElementById("preview");

        reader.onload = function() {
            if (reader.readyState === 2) {
                imageField.src = reader.result;
            }
        }

        if (event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }
    </script>
</body>

</html>