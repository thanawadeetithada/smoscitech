<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];
if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

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

$profile_image = 'https://placehold.co/150x150';
if (!empty($user_profile['profile_image']) && $user_profile['profile_image'] != 'default.png') {
    $profile_image = 'uploads/profiles/' . $user_profile['profile_image']; 
}

$activities = [];
$total_hours = 0;

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
    <title>Transcript ของ <?php echo htmlspecialchars($user_profile['first_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --primary-color: #4e73df;
    }

    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f8f9fc;
        margin: 0;
    }

    .nav-item a {
        color: white;
        margin-right: 1rem;
    }

    .navbar {
        padding: 15px 20px;
    }

    .main-content {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 15px;
    }

    .profile-header {
        background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
        height: 150px;
        border-radius: 15px 15px 0 0;
        position: relative;
    }

    .profile-img-container {
        position: absolute;
        bottom: -40px;
        left: 40px;
    }

    .profile-img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid #fff;
        object-fit: cover;
        background: white;
    }

    .profile-info {
        padding-top: 10px;
        padding-left: 180px;
        padding-bottom: 20px;
    }

    .transcript-card {
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        background-color: #fff;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .table th {
        font-weight: 600;
        color: #495057;
        background-color: #fff;
        border-bottom: 2px solid #e3e6f0;
    }

    .table td {
        vertical-align: middle;
        padding: 1rem 0.75rem;
    }

    .status-badge {
        color: #1cc88a;
        font-weight: bold;
    }

    .signature-area {
        font-family: 'Sarabun', serif;
        font-style: italic;
        color: #4e73df;
        font-size: 1.2rem;
    }

    .total-hours-row {
        background-color: #f8f9fc;
        font-weight: bold;
        color: #2563eb;
    }

    .footer-note {
        background-color: #eef2ff;
        border: 1px dashed #a5b4fc;
        color: #1e40af;
        border-radius: 8px;
    }

    .print-btn-float {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #2c3e50;
        color: white;
        border: 2px solid white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        cursor: pointer;
        transition: 0.2s;
    }

    .print-btn-float:hover {
        transform: scale(1.1);
        background-color: #1a252f;
        color: white;
    }

    @media (max-width: 768px) {
        .main-content {
            margin: 15px auto;
        }

        .profile-header {
            height: 120px;
        }

        .profile-img-container {
            left: 50%;
            transform: translateX(-50%);
            bottom: -50px;
        }

        .profile-info {
            padding: 65px 20px 20px 20px;
            text-align: center;
        }

        .profile-info .d-flex {
            justify-content: center;
            flex-wrap: wrap;
        }

        .export-btn-container {
            text-align: center;
            width: 100%;
            margin-top: 10px;
        }

        .transcript-card {
            padding: 1.5rem !important;
        }

        .table thead {
            display: none;
        }

        .table tr {
            display: block;
            border: 1px solid #e3e6f0;
            border-radius: 10px;
            margin-bottom: 15px;
            padding: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .table td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 5px !important;
            border-bottom: 1px solid #f8f9fc;
            font-size: 0.95rem;
        }

        .table td:last-child {
            border-bottom: none;
        }

        .table td::before {
            content: attr(data-label);
            font-weight: bold;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .table td[data-label="กิจกรรม"] {
            flex-direction: column;
            align-items: flex-end;
            text-align: right;
        }

        .total-hours-row td {
            justify-content: space-between;
            background-color: #eef2ff;
            border-radius: 8px;
            border: none;
        }

        .total-hours-row td::before {
            content: none;
        }

        .signature-row {
            flex-direction: column;
            gap: 30px;
            text-align: center !important;
        }

        .signature-row .text-md-end,
        .signature-row .text-md-start {
            text-align: center !important;
        }

        .signature-row div[style*="width: 200px"] {
            margin: 0 auto !important;
        }
    }

    @media print {
        @page {
            margin: 0;
            size: A4 portrait;
        }

        body {
            background-color: #fff;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            margin: 0;
            padding: 1.5cm;
            font-size: 14px !important;
        }

        body>*:not(.main-content) {
            display: none !important;
        }

        .d-print-none {
            display: none !important;
        }

        .main-content,
        .container {
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        .transcript-card {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            margin: 0 !important;
            border-radius: 0 !important;
        }

        .row {
            display: flex !important;
            flex-wrap: nowrap !important;
            flex-direction: row !important;
        }

        .col-md-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
            width: 50% !important;
        }

        .text-md-end {
            text-align: right !important;
        }

        .text-md-start {
            text-align: left !important;
        }

        .table {
            display: table !important;
            width: 100% !important;
            border-collapse: collapse !important;
        }

        .table thead {
            display: table-header-group !important;
        }

        .table tbody {
            display: table-row-group !important;
        }

        .table tr {
            display: table-row !important;
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            page-break-inside: avoid !important;
        }

        .table th,
        .table td {
            display: table-cell !important;
            padding: 12px 8px !important;
            border-bottom: 1px solid #e3e6f0 !important;
            text-align: left !important;
            vertical-align: middle !important;
            font-size: 14px !important;
        }

        .table td::before {
            display: none !important;
        }

        .table td[data-label="กิจกรรม"] {
            flex-direction: column !important;
            align-items: flex-start !important;
            text-align: left !important;
        }

        .table th.text-center,
        .table td.text-center {
            text-align: center !important;
        }

        .table th.text-end,
        .table td.text-end {
            text-align: right !important;
        }

        .signature-row {
            display: flex !important;
            align-items: flex-end !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .signature-row .col-md-6 {
            margin-top: 0 !important;
        }

        .footer-note,
        .print-protect {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .total-hours-row td {
            background-color: #f8f9fc !important;
            border-top: none !important;
            color: #000 !important;
        }

        .table td.text-primary,
        .total-hours-row td.text-primary {
            color: #000 !important;
        }
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
                <li>
                    <a href="admin_e-portfolio_transcript.php" class="text-white text-decoration-none d-block py-2">
                        <i class="fa-regular fa-address-book"></i>
                        <?php echo (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'executive') ? 'E-Portfolio' : 'E-Portfolio / Transcript'; ?>
                    </a>
                </li>
                <?php if (isset($_SESSION['userrole']) && $_SESSION['userrole'] === 'club_president'): ?>
                <li>
                    <a href="admin_score_activity.php" class="text-white text-decoration-none d-block py-2">
                        <i class="fa-regular fa-star"></i> คะแนนกิจกรรม
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['userrole']) && in_array($_SESSION['userrole'], ['academic_officer', 'club_president'])): ?>
                <li><a href="admin_user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="main-content">

        <a href="admin_e-portfolio_transcript.php" class="btn mb-3 px-4 d-print-none">
            <i class="fa-solid fa-arrow-left me-2"></i>ย้อนกลับ
        </a>

        <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden d-print-none">
            <div class="profile-header">
                <div class="profile-img-container">
                    <img src="<?php echo $profile_image; ?>" alt="Profile" class="profile-img">
                </div>
            </div>
            <div class="card-body pt-0 pb-4">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-center align-items-md-end">
                    <div class="profile-info">
                        <h2 class="fw-bold mb-1 text-dark">
                            <?php echo htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']); ?>
                        </h2>
                        <p class="text-muted mb-2 fs-6"><i class="fa-regular fa-id-badge me-1"></i> รหัสนักศึกษา: <span
                                class="fw-bold text-dark"><?php echo htmlspecialchars($user_profile['idstudent'] ?? 'ไม่ระบุ'); ?></span>
                        </p>
                        <div class="d-flex gap-2 mb-2">
                            <span class="badge bg-light text-primary border p-2">
                                <i class="fas fa-flask"></i> คณะวิทยาศาสตร์และเทคโนโลยี
                            </span>
                        </div>
                    </div>
                    <div class="export-btn-container mb-2">
                        <button class="btn btn-primary px-4 py-2 rounded-pill shadow-sm fw-bold"
                            onclick="window.print()">
                            <i class="fas fa-download me-2"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="transcript-card p-4 p-md-5">
            <div class="d-flex justify-content-between mb-4 pb-3 border-bottom print-protect">
                <div class="w-100">
                    <h3 class="fw-bold mb-1 text-center">ใบแสดงผลการเข้าร่วมกิจกรรม<br><small
                            class="text-muted fs-5">(Activity Transcript)</small></h3>
                    <div class="row mt-4 text-dark fs-6">
                        <div class="col-md-6 text-center text-md-start">
                            <p class="mb-2"><strong>ชื่อ-นามสกุล:</strong>
                                <?php echo htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']); ?>
                            </p>
                            <p class="mb-2"><strong>สาขาวิชา:</strong>
                                <?php echo htmlspecialchars($user_profile['department'] ?? '-'); ?></p>
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <p class="mb-2"><strong>รหัสนักศึกษา:</strong>
                                <?php echo htmlspecialchars($user_profile['idstudent'] ?? '-'); ?></p>
                            <p class="mb-2"><strong>วันที่ออกใบรับรอง:</strong> <?php echo $current_date; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-borderless align-middle">
                    <thead>
                        <tr>
                            <th scope="col" width="5%" class="text-center">ลำดับ</th>
                            <th scope="col" width="45%">ชื่อกิจกรรม</th>
                            <th scope="col" width="25%">บทบาท/หน้าที่</th>
                            <th scope="col" width="10%" class="text-center">ชั่วโมง</th>
                            <th scope="col" width="15%" class="text-end">สถานะ</th>
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
                            <td data-label="กิจกรรม">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($act['title']); ?></div>
                                <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i>
                                    <?php echo $date_str; ?></small>
                            </td>
                            <td data-label="บทบาท">
                                <?php echo htmlspecialchars($act['task_name'] ?? 'ผู้เข้าร่วมทั่วไป'); ?></td>
                            <td data-label="ชั่วโมง" class="text-center fw-bold text-primary">
                                <?php echo intval($act['hours_count']); ?></td>
                            <td data-label="สถานะ" class="text-end status-badge"><i
                                    class="fa-solid fa-circle-check me-1"></i> ผ่าน</td>
                        </tr>
                        <?php endforeach; ?>

                        <tr class="total-hours-row">
                            <td colspan="3" class="text-end">รวมชั่วโมงกิจกรรมทั้งหมด:</td>
                            <td class="text-center fs-5 text-primary"><?php echo $total_hours; ?></td>
                            <td class="text-end">ชั่วโมง</td>
                        </tr>
                        <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fa-regular fa-folder-open mb-2 fs-4 d-block"></i>
                                ยังไม่มีประวัติกิจกรรมที่ผ่านการประเมิน
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-5 mb-4 signature-row align-items-end">
                <div class="col-md-6 text-center text-md-start">
                    <div class="text-muted d-none d-md-block">(
                        ............................................................ )</div>
                    <div class="text-muted d-block d-md-none">( ........................................ )</div>
                    <div class="mt-2 text-dark">
                        <strong><?php echo htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']); ?></strong><br>
                        <small class="text-muted">นักศึกษาผู้รับรอง</small>
                    </div>
                </div>
                <div class="col-md-6 text-center text-md-end mt-4 mt-md-0">
                    <div class="text-muted d-none d-md-block">(
                        ............................................................ )</div>
                    <div class="text-muted d-block d-md-none">( ........................................ )</div>
                    <div class="mt-2 text-dark fw-bold">ผู้รับรองกิจกรรม</div>
                    <div class="text-muted small">สโมสรนักศึกษาคณะวิทยาศาสตร์และเทคโนโลยี</div>
                </div>
            </div>

            <div class="footer-note p-3 d-flex align-items-start mt-4">
                <i class="fa-solid fa-circle-info mt-1 me-3 text-primary fs-5"></i>
                <small class="text-dark">
                    ใบรับรองนี้ใช้เพื่อแสดงผลการเข้าร่วมกิจกรรมของนักศึกษาคณะวิทยาศาสตร์และเทคโนโลยีเท่านั้น<br>
                    สามารถนำไปใช้ประกอบการยื่นขอทุนการศึกษา หรือกองทุนเงินให้กู้ยืมเพื่อการศึกษา (กยศ.)
                    ได้ตามระเบียบของมหาวิทยาลัย
                </small>
            </div>
        </div>
    </div>

    <div class="print-btn-float d-print-none" onclick="window.print()" title="พิมพ์ / Export PDF">
        <i class="fas fa-print"></i>
    </div>

</body>

</html>