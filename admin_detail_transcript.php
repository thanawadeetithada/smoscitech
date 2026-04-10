<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

// ดึงรูปโปรไฟล์แอดมินสำหรับ Navbar
$logged_in_user_id = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $logged_in_user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$admin_data = $res_profile->fetch_assoc();
$admin_profile_image = !empty($admin_data['profile_image']) ? $admin_data['profile_image'] : 'default.png';
$stmt_profile->close();

$target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($target_user_id === 0) {
    header("Location: admin_e-portfolio_transcript.php");
    exit();
}

$sql_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $target_user_id);
$stmt_user->execute();
$user_profile = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$profile_image = 'img/default-profile.png'; // ใช้รูป default หากไม่มี
if (!empty($user_profile['profile_image']) && $user_profile['profile_image'] != 'default.png') {
    $profile_image = 'uploads/profiles/' . $user_profile['profile_image']; 
}

// ดึงข้อมูลสถิติ 4 กล่องบน (ใช้ข้อมูลจริง)
$sql_stats = "SELECT 
                COUNT(registration_id) as total_joined,
                SUM(CASE WHEN participation_status = 'passed' THEN 1 ELSE 0 END) as total_passed,
                SUM(CASE WHEN participation_status = 'not_passed' THEN 1 ELSE 0 END) as total_failed
              FROM activity_registrations 
              WHERE user_id = ? AND registration_status = 'approved'";
$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->bind_param("i", $target_user_id);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result()->fetch_assoc();
$total_joined = $stats_result['total_joined'] ?? 0;
$total_passed = $stats_result['total_passed'] ?? 0;
$total_failed = $stats_result['total_failed'] ?? 0;
$stmt_stats->close();

$activities = [];
$total_hours = 0;

// ดึงข้อมูลกิจกรรมที่ผ่าน
$sql_act = "SELECT 
                a.title, a.start_date, a.end_date, a.hours_count,
                t.task_name
            FROM activity_registrations ar
            JOIN activities a ON ar.activity_id = a.activity_id
            LEFT JOIN activity_tasks t ON ar.task_id = t.task_id
            WHERE ar.user_id = ? AND ar.registration_status = 'approved' AND ar.participation_status = 'passed'
            ORDER BY a.start_date DESC";
            
$stmt_act = $conn->prepare($sql_act);
$stmt_act->bind_param("i", $target_user_id);
$stmt_act->execute();
$result_act = $stmt_act->get_result();

while ($row = $result_act->fetch_assoc()) {
    $activities[] = $row;
    $total_hours += intval($row['hours_count']);
}
$stmt_act->close();

$thai_months = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
$current_date = date('j') . ' ' . $thai_months[date('n')] . ' ' . (date('Y') + 543);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="SMO SCITECH">
    <meta name="application-name" content="SMO SCITECH">
    <title>Transcript ของ <?php echo htmlspecialchars($user_profile['first_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">

    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #EAEAEB;
        
        --btn-purple: #6B4EE6;
        --cert-bg: #FCF9EF;
        
        --cert-border: #D1B87F;
        
        --text-dark: #333333;
        --btn-color: #8C6B4E;
        --btn-hover: #75583E;
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
        position: relative;
    }

    
    .cert-wrapper {
        background-color: var(--cert-bg);
        max-width: 850px;
        margin: 0 auto;
        padding: 20px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        position: relative;
    }

    .cert-border {
        border: 2px solid var(--cert-border);
        padding: 40px;
        position: relative;
        min-height: 1000px;
    }

    .cert-border::after {
        content: '';
        position: absolute;
        top: 6px;
        left: 6px;
        right: 6px;
        bottom: 6px;
        border: 1px solid var(--cert-border);
        pointer-events: none;
    }

    .cert-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .cert-title {
        color: #1A365D;
        font-weight: bold;
        font-size: 26px;
        margin-bottom: 5px;
    }

    .cert-subtitle {
        color: #666;
        font-size: 18px;
    }

    
    .info-grid {
        display: grid;
        grid-template-columns: 1fr 120px;
        gap: 20px;
        margin-bottom: 30px;
        font-size: 15px;
        color: #333;
    }

    .info-table td {
        padding: 4px 10px 4px 0;
        vertical-align: top;
    }

    .info-table td:first-child {
        font-weight: 600;
        white-space: nowrap;
        width: 1%;
    }

    .student-img {
        width: 110px;
        height: 140px;
        object-fit: cover;
        border: 2px solid #E2D3B3;
        padding: 2px;
        background: #fff;
    }

    
    .stats-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 35px;
    }

    .stat-card {
        border-radius: 8px;
        padding: 15px 10px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .stat-card.blue {
        background-color: #EBF4FA;
        border: 1px solid #BEE3F8;
    }

    .stat-card.pink {
        background-color: #FEF0F5;
        border: 1px solid #FED7E2;
    }

    .stat-card.purple {
        background-color: #F3E8FF;
        border: 1px solid #E9D8FD;
    }

    .stat-card.yellow {
        background-color: #FFFAF0;
        border: 1px solid #FEEBC8;
    }

    .stat-icon {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 8px;
        background: white;
        font-size: 16px;
    }

    .stat-card.blue .stat-icon {
        color: #3182CE;
    }

    .stat-card.pink .stat-icon {
        color: #D53F8C;
    }

    .stat-card.purple .stat-icon {
        color: #805AD5;
    }

    .stat-card.yellow .stat-icon {
        color: #D69E2E;
    }

    .stat-title {
        font-size: 13px;
        color: #555;
        margin-bottom: 5px;
        line-height: 1.2;
    }

    .stat-value {
        font-size: 20px;
        font-weight: bold;
        color: #222;
    }

    .stat-unit {
        font-size: 12px;
        font-weight: normal;
    }

    
    .cert-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        margin-bottom: 40px;
    }

    .cert-table th {
        background-color: #F8F1E5;
        color: #555;
        padding: 12px 10px;
        border-bottom: 2px solid #E2D3B3;
        font-weight: 600;
        text-align: center;
    }

    .cert-table td {
        padding: 12px 10px;
        border-bottom: 1px dashed #E2D3B3;
        vertical-align: middle;
    }

    .status-pass {
        background-color: #48BB78;
        color: white;
        padding: 4px 15px;
        border-radius: 15px;
        font-size: 12px;
        display: inline-block;
        font-weight: bold;
    }

    
    .signature-section {
        display: flex;
        justify-content: space-between;
        margin-top: 50px;
        padding: 0 20px;
    }

    .signature-box {
        text-align: center;
        width: 280px;
    }

    .sig-line {
        margin: 20px 0 10px;
        color: #666;
        letter-spacing: 2px;
    }

    
    .action-buttons {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        gap: 15px;
        z-index: 1000;
    }

    .btn-action {
        background-color: var(--btn-purple);
        color: white;
        border: none;
        border-radius: 20px;
        padding: 10px 30px;
        font-size: 16px;
        font-family: 'Prompt', sans-serif;
        font-weight: 500;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: 0.2s;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-action:hover {
        background-color: #5A42D1;
        transform: translateY(-2px);
        color: white;
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

        .logout-text {
            padding: 2px !important;
            font-size: 9px !important;
        }

        .content-area {
            padding: 15px;
        }

        .cert-wrapper {
            padding: 10px;
        }

        .cert-border {
            padding: 15px;
            min-height: auto;
        }

        .info-grid {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .info-table {
            margin: 0 auto;
            text-align: left;
        }

        .student-img {
            margin: 15px auto 0;
            display: block;
        }

        .stats-container {
            grid-template-columns: 1fr 1fr;
        }

        
        .cert-table thead {
            display: none;
        }

        .cert-table,
        .cert-table tbody,
        .cert-table tr,
        .cert-table td {
            display: block;
            width: 100%;
        }

        .cert-table tr {
            margin-bottom: 15px;
            border: 1px solid #E2D3B3;
            border-radius: 8px;
            padding: 10px;
        }

        .cert-table td {
            text-align: right;
            padding-left: 50%;
            position: relative;
            border-bottom: 1px solid #f0f0f0;
        }

        .cert-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 10px;
            width: 45%;
            padding-right: 10px;
            white-space: nowrap;
            text-align: left;
            font-weight: bold;
            color: #666;
        }

        .cert-table td:last-child {
            border-bottom: 0;
        }

        .total-row td {
            text-align: center !important;
            padding-left: 10px !important;
        }

        .total-row td::before {
            display: none;
        }

        .signature-section {
            flex-direction: column;
            align-items: center;
            gap: 40px;
            margin-top: 30px;
        }

        .action-buttons {
            position: static;
            justify-content: center;
            margin-top: 20px;
        }
    }

    
    @media print {
        @page {
            margin: 0;
            size: A4 portrait;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
            box-sizing: border-box !important;
        }

        body {
            background-color: var(--light-bg) !important;
            margin: 0;
            padding: 0;
            font-size: 14px;
            width: 100%;
        }

        
        .top-navbar,
        .sidebar,
        .action-buttons,
        .print-btn-float {
            display: none !important;
        }

        .main-wrapper {
            display: block;
            width: 100%;
        }

        .content-area {
            padding: 0 !important;
            margin: 0 !important;
            width: 100%;
        }

        .cert-wrapper {
            box-shadow: none !important;
            width: 210mm !important;
            height: 297mm !important;
            padding: 12mm !important;
            margin: 0 auto !important;
            background-color: var(--cert-bg) !important;
            overflow: hidden !important;
            page-break-after: avoid !important;
        }

        .cert-border {
            height: 100% !important;
            min-height: auto !important;
            border: 2px solid var(--cert-border) !important;
            display: flex !important;
            flex-direction: column !important;
            padding: 30px !important;
            position: relative !important;
        }

        .cert-border::after {
            border: 1px solid var(--cert-border) !important;
            top: 5px !important;
            left: 5px !important;
            right: 5px !important;
            bottom: 5px !important;
        }

        .info-grid {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
        }

        .stats-container {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            gap: 10px !important;
        }

        .stat-card {
            flex: 1 !important;
            width: 25% !important;
            padding: 10px 5px !important;
        }

        .stat-card.blue {
            background-color: #EBF4FA !important;
            border: 1px solid #BEE3F8 !important;
        }

        .stat-card.pink {
            background-color: #FEF0F5 !important;
            border: 1px solid #FED7E2 !important;
        }

        .stat-card.purple {
            background-color: #F3E8FF !important;
            border: 1px solid #E9D8FD !important;
        }

        .stat-card.yellow {
            background-color: #FFFAF0 !important;
            border: 1px solid #FEEBC8 !important;
        }

        .cert-table {
            display: table !important;
            width: 100% !important;
            border-collapse: collapse !important;
            table-layout: fixed !important;
        }

        .cert-table thead {
            display: table-header-group !important;
        }

        .cert-table tbody {
            display: table-row-group !important;
        }

        .cert-table tr {
            display: table-row !important;
            border: none !important;
        }

        .cert-table th {
            display: table-cell !important;
            text-align: center !important;
            padding: 8px 4px !important;
            background-color: #F8F1E5 !important;
            border-bottom: 2px solid #E2D3B3 !important;
            vertical-align: middle !important;
            word-wrap: break-word !important;
        }

        .cert-table th:first-child {
            width: 8% !important;
            white-space: nowrap !important;
        }

        .cert-table td {
            display: table-cell !important;
            text-align: center !important;
            padding: 8px 4px !important;
            border-bottom: 1px dashed #E2D3B3 !important;
            vertical-align: middle !important;
            word-wrap: break-word !important;
        }

        .cert-table td[data-label="ชื่อกิจกรรม"] {
            text-align: left !important;
        }

        .cert-table td[data-label="บทบาท"] {
            text-align: center !important;
        }

        .cert-table td::before {
            display: none !important;
        }

        .total-row,
        .total-row td {
            background-color: #FCF9EF !important;
            text-align: right !important;
        }

        .total-row td:nth-last-child(2),
        .total-row td:last-child {
            text-align: center !important;
        }

        .status-pass {
            background-color: #48BB78 !important;
            color: white !important;
        }

        .signature-section {
            display: flex !important;
            flex-direction: row !important;
            justify-content: space-between !important;
            margin-top: auto !important;
            padding: 0 10px !important;
            page-break-inside: avoid !important;
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
                    <span class="text-page-pill-btn mt-1">Transcript</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
               <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'ผู้ใช้งาน'); ?>
                </span>
                <div class="logout-area">
                    <img src="uploads/profiles/<?php echo htmlspecialchars($admin_profile_image); ?>" alt="Profile"
                        style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    <a href="logout.php" class="logout-text mt-1">Log out</a>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            <aside class="sidebar d-print-none">
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
                <a href="admin_transcript.php" class="sidebar-item"
                    style="background: #FDFDFD; border-left: 5px solid var(--top-bar-bg);">
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Transcript</span>
                </a>
                <?php endif; ?>
            </aside>

            <main class="content-area">
                <div class="cert-wrapper">
                    <div class="cert-border">

                        <div class="cert-header">
                            <h2 class="cert-title">ใบแสดงผลการเข้าร่วมกิจกรรม</h2>
                            <div class="cert-subtitle">(Activity Transcript)</div>
                        </div>

                        <div class="info-grid">
                            <div>
                                <table class="info-table">
                                    <tr>
                                        <td>ชื่อ-สกุล</td>
                                        <td>:
                                            <?php echo htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ชั้นปี</td>
                                        <td>: <?php echo htmlspecialchars($user_profile['year_level'] ?? '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>รหัสนักศึกษา</td>
                                        <td>: <?php echo htmlspecialchars($user_profile['idstudent'] ?? '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>สาขาวิชา</td>
                                        <td>: <?php echo htmlspecialchars($user_profile['department'] ?? '-'); ?></td>
                                    </tr>
                                    <tr>
                                        <td>วันที่ออกใบรับรอง</td>
                                        <td>: <?php echo $current_date; ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="text-md-end text-center">
                                <img src="<?php echo $profile_image; ?>" class="student-img" alt="Student Photo">
                            </div>
                        </div>

                        <div class="stats-container">
                            <div class="stat-card blue">
                                <div class="stat-icon"><i class="fa-solid fa-user-plus"></i></div>
                                <div class="stat-title">เข้าร่วมกิจกรรม</div>
                                <div><span class="stat-value"><?php echo number_format($total_joined); ?></span> <span
                                        class="stat-unit">กิจกรรม</span></div>
                            </div>
                            <div class="stat-card pink">
                                <div class="stat-icon"><i class="fa-solid fa-check"></i></div>
                                <div class="stat-title">ผ่านการประเมิน</div>
                                <div><span class="stat-value"><?php echo number_format($total_passed); ?></span> <span
                                        class="stat-unit">กิจกรรม</span></div>
                            </div>
                            <div class="stat-card purple">
                                <div class="stat-icon"><i class="fa-solid fa-xmark"></i></div>
                                <div class="stat-title">ไม่ผ่านการประเมิน</div>
                                <div><span class="stat-value"><?php echo number_format($total_failed); ?></span> <span
                                        class="stat-unit">กิจกรรม</span></div>
                            </div>
                            <div class="stat-card yellow">
                                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                                <div class="stat-title">ชั่วโมงกิจกรรมสะสม</div>
                                <div><span class="stat-value"><?php echo number_format($total_hours); ?></span> <span
                                        class="stat-unit">ชั่วโมง</span></div>
                            </div>
                        </div>

                        <div style="font-size: 15px; margin-bottom: 12px; font-weight: 600; color: #1A365D;">
                            <i class="fa-solid fa-list-check me-2"></i>ประวัติการเข้าร่วมกิจกรรมที่ผ่านประเมิน
                        </div>
                        <div class="cert-table-container">
                            <table class="cert-table">
                                <thead>
                                    <tr>
                                        <th width="8%">ลำดับ</th>
                                        <th width="32%">ชื่อกิจกรรม</th>
                                        <th width="20%">บทบาท</th>
                                        <th width="15%">วันที่เข้าร่วม</th>
                                        <th width="10%">ชั่วโมง</th>
                                        <th width="15%">สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($activities) > 0): ?>
                                    <?php 
                                        $i = 1;
                                        foreach ($activities as $act): 
                                            $start = date('d/m/Y', strtotime($act['start_date']));
                                            $end = date('d/m/Y', strtotime($act['end_date']));
                                            $date_str = ($start == $end) ? $start : "$start - $end";
                                        ?>
                                    <tr>
                                        <td data-label="ลำดับ" class="text-center"><?php echo $i++; ?></td>
                                        <td data-label="ชื่อกิจกรรม" style="text-align: left;">
                                            <?php echo htmlspecialchars($act['title']); ?></td>
                                        <td data-label="บทบาท" class="text-center">
                                            <?php echo htmlspecialchars($act['task_name'] ?? 'ผู้เข้าร่วมทั่วไป'); ?>
                                        </td>
                                        <td data-label="วันที่เข้าร่วม" class="text-center"><?php echo $date_str; ?>
                                        </td>
                                        <td data-label="ชั่วโมง" class="text-center fw-bold">
                                            <?php echo intval($act['hours_count']); ?></td>
                                        <td data-label="สถานะ" class="text-center"><span class="status-pass">ผ่าน</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <tr class="total-row">
                                        <td colspan="4" class="text-end"
                                            style="border-bottom: none; font-weight: bold;">รวมชั่วโมงกิจกรรมทั้งหมด
                                        </td>
                                        <td class="text-center"
                                            style="border-bottom: none; font-weight: bold; font-size: 18px; color: #1A365D;">
                                            <?php echo $total_hours; ?></td>
                                        <td style="border-bottom: none; font-weight: bold;">ชั่วโมง</td>
                                    </tr>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center"
                                            style="padding: 30px; color: #999; border: none;">
                                            ยังไม่มีประวัติกิจกรรมที่ผ่านการประเมิน</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="signature-section">
                            <div class="signature-box">
                                <div class="sig-line">( ....................................... )</div>
                                <div class="fw-bold text-dark mt-2">
                                    <?php echo htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']); ?>
                                </div>
                                <div class="text-muted small mt-1">สมาชิกสโมสร / นักศึกษาผู้รับรอง</div>
                            </div>
                            <div class="signature-box">
                                <div class="sig-line">( ....................................... )</div>
                                <div class="fw-bold text-dark mt-2">ผู้รับรองกิจกรรม</div>
                                <div class="text-muted small mt-1">สโมสรนักศึกษาคณะวิทยาศาสตร์และเทคโนโลยี</div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="action-buttons d-print-none">
                    <button class="btn-action" onclick="window.print()">Export</i></button>
                </div>

            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
    });
    </script>
</body>

</html>