<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
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
    <title>หน้า Transcript</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <style>
    :root {
        --primary-color: #4e73df;
        --sidebar-width: 250px;
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
        padding: 20px;
    }

    .nav-link:hover {
        color: white;
    }

    .main-content {
        margin: 30px 50px;
        padding: 20px;
    }

    /* ... existing css ... */

    .profile-header-banner {
        height: 120px;
        background: linear-gradient(90deg, #2563eb 0%, #4f46e5 100%);
        /* สีน้ำเงินไล่เฉดตามรูป */
        border-radius: 10px 10px 0 0;
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border: 4px solid #fff;
        margin-top: -50px;
        /* ดึงรูปขึ้นไปซ้อนกับ Banner */
        background-color: #fff;
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
        font-weight: 500;
    }

    .signature-area {
        font-family: 'Sarabun', serif;
        /* หรือ font ที่ดูเป็นทางการ */
        font-style: italic;
        color: #4e73df;
        font-size: 1.2rem;
    }

    .total-hours-row {
        background-color: #f8f9fc;
        font-weight: bold;
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
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        z-index: 1000;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .print-btn-float:hover {
        transform: scale(1.1);
        color: white;
    }

    .footer-note {
        background-color: #eef2ff;
        border: 1px solid #dbeafe;
        color: #1e40af;
        border-radius: 8px;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark px-3">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <i class="fa-solid fa-bars text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
                style="cursor: pointer;"></i>
            <div class="nav-item">
                <a class="nav-link text-white" href="logout.php"><i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout</a>
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

    <a href="javascript:window.print()" class="print-btn-float">
        <i class="fa-solid fa-print"></i>
    </a>

    <div class="container py-4">

        <div class="card border-0 shadow-sm mb-4 rounded-3">
            <div class="profile-header-banner"></div>
            <div class="card-body pt-0 px-4">
                <div
                    class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end">
                    <div class="d-flex align-items-end">
                        <img src="https://via.placeholder.com/150" alt="Profile"
                            class="rounded-3 profile-avatar shadow-sm me-3">
                        <div class="mb-3">
                            <h4 class="mb-1 fw-bold">สมชาย วิทยาศาสตร์</h4>
                            <p class="text-muted mb-2">รหัสนักศึกษา: 64010912345</p>
                            <div>
                                <span class="badge bg-light text-primary border border-primary me-1">
                                    <i class="fa-solid fa-graduation-cap"></i> วิทยาศาสตร์และเทคโนโลยี
                                </span>
                                <span class="badge bg-light text-primary border border-primary">
                                    <i class="fa-solid fa-book"></i> วิทยาการคอมพิวเตอร์ (ชั้นปีที่ 3)
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 mt-3 mt-md-0">
                        <button class="btn btn-primary px-4"><i class="fa-solid fa-file-export"></i> Export PDF</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="transcript-card p-5">

            <div class="d-flex justify-content-between mb-5">
                <div>
                    <h3 class="fw-bold mb-1">ใบแสดงผลการเข้าร่วมกิจกรรม (Activity Transcript)</h3>
                    <p class="text-muted text-uppercase mb-0">Faculty of Science and Technology, SSRU</p>

                    <div class="row mt-4 text-secondary">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>ชื่อ-นามสกุล:</strong> <span class="text-dark">สมชาย
                                    วิทยาศาสตร์</span></p>
                            <p class="mb-1"><strong>สาขาวิชา:</strong> <span
                                    class="text-dark">วิทยาการคอมพิวเตอร์</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>รหัสนักศึกษา:</strong> <span class="text-dark">64010912345</span>
                            </p>
                            <p class="mb-1"><strong>วันที่ออกใบรับรอง:</strong> <span class="text-dark">14/2/2569</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div style="width: 80px; height: 80px; background-color: #f8f9fa; border-radius: 8px;"></div>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-borderless">
                    <thead>
                        <tr>
                            <th scope="col" width="5%">ลำดับ</th>
                            <th scope="col" width="45%">ชื่อกิจกรรม</th>
                            <th scope="col" width="25%">บทบาท</th>
                            <th scope="col" width="10%" class="text-center">ชั่วโมง</th>
                            <th scope="col" width="15%" class="text-end">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>
                                <div class="fw-bold">ค่ายอาสาพัฒนาชนบท ครั้งที่ 15</div>
                                <small class="text-muted">10-12 มกราคม 2567</small>
                            </td>
                            <td>ฝ่ายสวัสดิการ</td>
                            <td class="text-center fw-bold">24</td>
                            <td class="text-end status-badge"><i class="fa-regular fa-circle-check"></i> ผ่าน</td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>
                                <div class="fw-bold">กิจกรรมรับน้องสานสัมพันธ์ Sci-Tech</div>
                                <small class="text-muted">15 สิงหาคม 2566</small>
                            </td>
                            <td>พี่สันทนาการ</td>
                            <td class="text-center fw-bold">8</td>
                            <td class="text-end status-badge"><i class="fa-regular fa-circle-check"></i> ผ่าน</td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>
                                <div class="fw-bold">อบรมเชิงปฏิบัติการ AI for Future</div>
                                <small class="text-muted">20 ตุลาคม 2566</small>
                            </td>
                            <td>ผู้เข้าร่วมกิจกรรม</td>
                            <td class="text-center fw-bold">6</td>
                            <td class="text-end status-badge"><i class="fa-regular fa-circle-check"></i> ผ่าน</td>
                        </tr>

                        <tr class="total-hours-row" style="border-top: 1px solid #dee2e6;">
                            <td colspan="3" class="text-end pe-4 py-3">รวมชั่วโมงกิจกรรมทั้งหมด</td>
                            <td class="text-center py-3 text-primary fs-5">38</td>
                            <td class="text-muted text-end py-3"><small>ชั่วโมง</small></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="row mt-5 mb-5">
                <div class="col-md-6">
                    <div style="width: 150px; border-bottom: 1px solid #ccc;"></div>
                    <div class="mt-2">
                        <strong>สมชาย วิทยาศาสตร์</strong><br>
                        <small class="text-muted">นักศึกษา</small>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="signature-area mb-2">Officer Signature</div>
                    <div>( ............................................................ )</div>
                    <div class="mt-2 text-muted small">คณบดี/รองคณบดีฝ่ายกิจการนักศึกษา</div>
                </div>
            </div>

            <div class="footer-note p-3 d-flex align-items-start">
                <i class="fa-regular fa-bookmark mt-1 me-3"></i>
                <small>
                    ใบรับรองนี้ใช้เพื่อแสดงผลการเข้าร่วมกิจกรรมของนักศึกษาคณะวิทยาศาสตร์และเทคโนโลยีเท่านั้น
                    สามารถนำไปใช้ประกอบการยื่นขอทุนการศึกษา หรือกองทุนเงินให้กู้ยืมเพื่อการศึกษา (กยศ.)
                    ได้ตามระเบียบของมหาวิทยาลัย
                </small>
            </div>

        </div>
    </div>
    <script>

    </script>

</body>

</html>