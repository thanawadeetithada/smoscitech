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
    <title>หน้ากิจกรรม</title>
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

    .search-container {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 30px;
    }

    .activity-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: transform 0.3s;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        background: white;
        height: 100%;
    }

    .activity-card:hover {
        transform: translateY(-5px);
    }

    .card-img-top-custom {
        height: 160px;
        background: linear-gradient(45deg, #3a7bd5, #00d2ff);
        position: relative;
    }

    .status-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: bold;
        color: white;
    }

    .status-open {
        background-color: #28a745;
    }

    .status-closed {
        background-color: #dc3545;
    }

    .status-upcoming {
        background-color: #ffc107;
        color: #000;
    }

    .card-body-custom {
        padding: 20px;
    }

    .activity-title {
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 10px;
    }

    .activity-info {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .manage-link {
        text-decoration: none;
        color: #4e73df;
        font-weight: bold;
        float: right;
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
    <div class="main-content">
        <div class="container-fluid">
            <div class="mb-4">
                <h4 class="fw-bold">กิจกรรมสโมสร</h4>
                <p class="text-muted">ค้นหาและลงทะเบียนเข้าร่วมกิจกรรม</p>
            </div>

            <div class="search-container">
                <div class="row g-3">
                    <div class="col-md-10">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="fa fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" placeholder="ค้นหากิจกรรม...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option selected>ทุกสถานะ</option>
                            <option>Open</option>
                            <option>Closed</option>
                            <option>Upcoming</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-4">

                <div class="col">
                    <div class="activity-card">
                        <div class="card-img-top-custom">
                            <span class="status-badge status-open">Open</span>
                        </div>
                        <div class="card-body-custom">
                            <div class="activity-info"><i class="far fa-calendar-alt"></i> 2023-10-15</div>
                            <div class="activity-title">ค่ายอาสาพัฒนาชนบท</div>
                            <p class="text-muted small">รายละเอียดกิจกรรมคร่าวๆ เพื่อให้นักศึกษาได้ทราบขอบเขต...</p>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">45/50 คน</span>
                                <a href="#" class="manage-link">จัดการกิจกรรม</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="activity-card">
                        <div class="card-img-top-custom" style="background: linear-gradient(45deg, #12c2e9, #c471ed);">
                            <span class="status-badge status-closed">Closed</span>
                        </div>
                        <div class="card-body-custom">
                            <div class="activity-info"><i class="far fa-calendar-alt"></i> 2023-11-01</div>
                            <div class="activity-title">อบรม Python for Data Science</div>
                            <p class="text-muted small">รายละเอียดกิจกรรมคร่าวๆ เพื่อให้นักศึกษาได้ทราบขอบเขต...</p>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">80/80 คน</span>
                                <a href="#" class="manage-link">จัดการกิจกรรม</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="activity-card">
                        <div class="card-img-top-custom" style="background: linear-gradient(45deg, #00b09b, #96c93d);">
                            <span class="status-badge status-upcoming">Upcoming</span>
                        </div>
                        <div class="card-body-custom">
                            <div class="activity-info"><i class="far fa-calendar-alt"></i> 2023-12-20</div>
                            <div class="activity-title">Sport Day 2023</div>
                            <p class="text-muted small">รายละเอียดกิจกรรมคร่าวๆ เพื่อให้นักศึกษาได้ทราบขอบเขต...</p>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">120/500 คน</span>
                                <a href="#" class="manage-link">จัดการกิจกรรม</a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>

    </script>

</body>

</html>