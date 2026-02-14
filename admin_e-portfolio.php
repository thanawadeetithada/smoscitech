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
    <title>หน้า E-Poartfolio</title>
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

    /* ... CSS เดิมที่มีอยู่ ... */

    .profile-header {
        background: linear-gradient(90deg, #2563eb 0%, #4f46e5 100%);
        /* สีน้ำเงินไล่เฉด */
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
    }

    .profile-info {
        padding-top: 10px;
        padding-left: 180px;
        /* เว้นที่ให้รูปโปรไฟล์ */
        padding-bottom: 20px;
    }

    .skill-card,
    .activity-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .progress {
        height: 10px;
        border-radius: 5px;
        background-color: #e9ecef;
    }

    .progress-bar {
        border-radius: 5px;
    }

    .activity-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 10px 0 0 10px;
        min-height: 200px;
    }

    /* ปรับแต่ง Badge */
    .badge-soft {
        background-color: #f3f4f6;
        color: #4b5563;
        font-weight: 400;
        border: 1px solid #e5e7eb;
    }

    .status-pass {
        background-color: #d1fae5;
        color: #065f46;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
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

    <div class="container my-5">

        <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
            <div class="profile-header">
                <div class="profile-img-container">
                    <img src="https://placehold.co/150x150" alt="Profile" class="profile-img">
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="d-flex flex-wrap justify-content-between align-items-end">
                    <div class="profile-info mt-2">
                        <h3 class="fw-bold mb-1">สมชาย วิทยาศาสตร์</h3>
                        <p class="text-muted mb-2">รหัสนักศึกษา: 64010912345</p>
                        <div class="d-flex gap-2 mb-3">
                            <span class="badge bg-light text-primary border"><i class="fas fa-flask"></i>
                                วิทยาศาสตร์และเทคโนโลยี</span>
                            <span class="badge bg-light text-primary border"><i class="fas fa-book"></i>
                                วิทยาการคอมพิวเตอร์ (ชั้นปีที่ 3)</span>
                        </div>
                    </div>
                    <div class="mb-3 me-3">
                        <button class="btn btn-primary px-4 rounded-pill">
                            <i class="fas fa-download me-2"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card skill-card p-4 h-100 bg-white">
                    <h5 class="fw-bold mb-4 text-dark"><i class="fas fa-star text-warning me-2"></i> ทักษะและความสามารถ
                    </h5>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium">ภาวะผู้นำ (Leadership)</span>
                            <span class="text-primary fw-bold">85%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 85%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium">การทำงานร่วมกับผู้อื่น (Collaboration)</span>
                            <span class="text-primary fw-bold">95%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 95%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium">ความรับผิดชอบ (Responsibility)</span>
                            <span class="text-primary fw-bold">90%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 90%"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium">ความคิดสร้างสรรค์ (Creativity)</span>
                            <span class="text-primary fw-bold">75%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 75%"></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-medium">ทักษะดิจิทัล (Digital Literacy)</span>
                            <span class="text-primary fw-bold">80%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 80%"></div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded border border-dashed text-center text-muted fst-italic mt-auto">
                        "การประเมินตนเองตามเกณฑ์มาตรฐานสโมสรนักศึกษา"
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <h5 class="fw-bold mb-3"><i class="far fa-image me-2 text-primary"></i> แกลเลอรี่กิจกรรมและผลงาน</h5>

                <div class="card activity-card mb-3 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <img src="https://placehold.co/400x300" class="activity-img" alt="Activity 1">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <small class="text-primary fw-bold">10-12 มกราคม 2567</small>
                                    <span class="status-pass">ผ่าน</span>
                                </div>
                                <h5 class="card-title fw-bold">ค่ายอาสาพัฒนาชนบท ครั้งที่ 15</h5>
                                <p class="card-text text-muted small">
                                    ร่วมสร้างอาคารเรียนและสอนหนังสือเด็กในถิ่นทุรกันดาร</p>

                                <div class="mb-3">
                                    <span class="badge badge-soft rounded-pill me-1">#จิตอาสา</span>
                                    <span class="badge badge-soft rounded-pill me-1">#ความสามัคคี</span>
                                    <span class="badge badge-soft rounded-pill me-1">#การทำงานเป็นทีม</span>
                                </div>

                                <div class="mt-auto d-flex justify-content-between align-items-center text-muted small">
                                    <span><i class="far fa-user me-1"></i> หน้าที่: ฝ่ายสวัสดิการ</span>
                                    <span>24 ชม.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card activity-card mb-3 overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <img src="https://placehold.co/400x300" class="activity-img" alt="Activity 2">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body h-100 d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <small class="text-primary fw-bold">15 สิงหาคม 2566</small>
                                    <span class="status-pass">ผ่าน</span>
                                </div>
                                <h5 class="card-title fw-bold">กิจกรรมรับน้องสานสัมพันธ์ Sci-Tech</h5>
                                <p class="card-text text-muted small">เป็นพี่สันทนาการดูแลน้องๆ นักศึกษาใหม่</p>

                                <div class="mb-3">
                                    <span class="badge badge-soft rounded-pill me-1">#ภาวะผู้นำ</span>
                                    <span class="badge badge-soft rounded-pill me-1">#ความกล้าแสดงออก</span>
                                </div>

                                <div class="mt-auto d-flex justify-content-between align-items-center text-muted small">
                                    <span><i class="far fa-user me-1"></i> หน้าที่: พี่สันทนาการ</span>
                                    <span>8 ชม.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="position-fixed bottom-0 end-0 p-4">
            <button class="btn btn-dark rounded-circle p-3 shadow-lg">
                <i class="fas fa-print fa-lg"></i>
            </button>
        </div>
    </div>

    <script>

    </script>

</body>

</html>