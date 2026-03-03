<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$sql_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_profile = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$profile_image = 'https://placehold.co/150x150';
if (!empty($user_profile['profile_image']) && $user_profile['profile_image'] != 'default.png') {
    $profile_image = 'uploads/profiles/' . $user_profile['profile_image']; 
}

$portfolio_activities = [];
$sql_act = "SELECT 
                a.title, a.description, a.start_date, a.end_date, a.hours_count, a.cover_image,
                t.task_name
            FROM activity_registrations ar
            JOIN activities a ON ar.activity_id = a.activity_id
            LEFT JOIN activity_tasks t ON ar.task_id = t.task_id
            WHERE ar.user_id = ? AND ar.registration_status = 'approved' AND ar.participation_status = 'passed'
            ORDER BY a.start_date DESC";
            
$stmt_act = $conn->prepare($sql_act);
$stmt_act->bind_param("i", $user_id);
$stmt_act->execute();
$result_act = $stmt_act->get_result();

while ($row = $result_act->fetch_assoc()) {
    $portfolio_activities[] = $row;
}
$stmt_act->close();

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>หน้า E-Portfolio</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    :root {
        --primary-color: #4e73df;
    }

    body { font-family: 'Prompt', sans-serif; background-color: #f8f9fc; margin: 0; }
    .nav-item a { color: white; margin-right: 1rem; }
    .navbar { padding: 15px 20px; }
    .nav-link:hover { color: white; }
    
    .main-content {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 15px;
    }

    .profile-header {
        background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
        height: 160px;
        border-radius: 15px 15px 0 0;
        position: relative;
    }

    .profile-img-container { 
        position: absolute; 
        bottom: -50px; 
        left: 40px; 
    }
    
    .profile-img { 
        width: 130px; 
        height: 130px; 
        border-radius: 50%; 
        border: 5px solid #fff; 
        object-fit: cover; 
        background: white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .profile-info { 
        padding-top: 10px; 
        padding-left: 190px; 
        padding-bottom: 20px; 
    }

    .activity-card { 
        border: none; 
        border-radius: 15px; 
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); 
        transition: transform 0.2s ease-in-out;
    }
    
    .activity-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); 
    }

    .activity-img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        border-radius: 15px 0 0 15px; 
        min-height: 220px; 
    }

    .status-pass {
        background-color: #d1fae5;
        color: #065f46;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
    }

    @media (max-width: 767.98px) {
        .main-content { margin: 15px auto; }
        .profile-header { height: 120px; }
        
        .profile-img-container { 
            left: 50%; 
            transform: translateX(-50%); 
            bottom: -50px; 
        }
        
        .profile-info { 
            padding-left: 15px; 
            padding-right: 15px; 
            padding-top: 65px; 
            text-align: center; 
        }
        
        .profile-info .badge-container { 
            justify-content: center; 
            flex-wrap: wrap; 
        }
        
        .export-btn-container {
            text-align: center;
            margin-top: 15px;
            width: 100%;
        }

        .activity-img { 
            border-radius: 15px 15px 0 0; 
            height: 200px; 
            min-height: auto;
        }
    }
    @media print {
        @page {
            margin: 0;
        }
        body { 
            background-color: #fff; 
            margin: 1.5cm;
        }
        .navbar, .offcanvas, .position-fixed, .btn-primary { display: none !important; }
        .main-content { margin: 0; padding: 0; max-width: 100%; }
        .card { border: 1px solid #e0e0e0 !important; box-shadow: none !important; margin-bottom: 20px; break-inside: avoid; }
        .profile-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .status-pass { border: 1px solid #065f46; }
        .empty-portfolio { display: none; }
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
                <li><a href="report_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-chart-line"></i> สถิติการเข้าร่วมกิจกรรม</a></li>
                <li><a href="activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-list-check"></i> กิจกรรม</a></li>
                <li><a href="e-portfolio.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-address-book"></i> E-Portfolio </a></li>
                <li><a href="transcript.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-file-lines"></i> Transcript</a></li>
                <li><a href="score_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
            <div class="profile-header">
                <div class="profile-img-container">
                    <img src="<?php echo $profile_image; ?>" alt="Profile" class="profile-img">
                </div>
            </div>
            <div class="card-body pt-0 pb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center align-items-md-end">
                    
                    <div class="profile-info">
                        <h2 class="fw-bold mb-1 text-dark">
                            <?php echo htmlspecialchars($user_profile['first_name'] . ' ' . $user_profile['last_name']); ?>
                        </h2>
                        <p class="text-muted mb-2 fs-6"><i class="fa-regular fa-id-badge me-1"></i> รหัสนักศึกษา: <span class="fw-bold text-dark"><?php echo htmlspecialchars($user_profile['idstudent'] ?? 'ไม่ระบุ'); ?></span></p>
                        
                        <div class="d-flex gap-2 mb-2 badge-container">
                            <span class="badge bg-light text-primary border p-2">
                                <i class="fas fa-flask"></i> คณะวิทยาศาสตร์และเทคโนโลยี
                            </span>
                            <span class="badge bg-light text-primary border p-2">
                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($user_profile['department'] ?? 'ไม่ระบุสาขาวิชา'); ?> 
                                <?php echo !empty($user_profile['year_level']) ? '('.htmlspecialchars($user_profile['year_level']).')' : ''; ?>
                            </span>
                        </div>
                    </div>

                    <div class="export-btn-container mb-2">
                        <button class="btn btn-primary px-4 py-2 rounded-pill shadow-sm fw-bold d-print-none" onclick="window.print()">
                            <i class="fas fa-download me-2"></i> Export PDF
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="row g-4 mt-2">
            <div class="col-12">
                <h4 class="fw-bold mb-4 border-bottom pb-3 text-dark"><i class="far fa-images me-2 text-primary"></i> แกลเลอรี่กิจกรรมและผลงาน</h4>

                <?php if (count($portfolio_activities) > 0): ?>
                    <?php foreach ($portfolio_activities as $act): 
                        $cover_img = !empty($act['cover_image']) ? 'uploads/covers/' . $act['cover_image'] : 'https://placehold.co/600x400?text=No+Image';
                        $start_date = date('d M Y', strtotime($act['start_date']));
                        $end_date = date('d M Y', strtotime($act['end_date']));
                        $date_display = ($start_date == $end_date) ? $start_date : $start_date . ' - ' . $end_date;
                    ?>
                    <div class="card activity-card mb-4">
                        <div class="row g-0 h-100">
                            <div class="col-md-4">
                                <img src="<?php echo $cover_img; ?>" class="activity-img" alt="Activity Cover">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body d-flex flex-column h-100 p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <small class="text-primary fw-bold bg-light px-2 py-1 rounded border"><i class="fa-regular fa-calendar-days me-1"></i> <?php echo $date_display; ?></small>
                                        <span class="status-pass shadow-sm"><i class="fa-solid fa-check-circle me-1"></i> ผ่าน</span>
                                    </div>
                                    <h5 class="card-title fw-bold text-dark mt-2 mb-2"><?php echo htmlspecialchars($act['title']); ?></h5>
                                    
                                    <p class="card-text text-muted mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; font-size: 0.95rem;">
                                        <?php echo htmlspecialchars($act['description']); ?>
                                    </p>

                                    <div class="mt-auto d-flex flex-wrap justify-content-between align-items-center text-dark small bg-light p-3 rounded-3 border">
                                        <span class="mb-2 mb-md-0"><i class="fa-solid fa-user-tag me-1 text-primary"></i> หน้าที่รับผิดชอบ: <strong><?php echo htmlspecialchars($act['task_name'] ?? 'ผู้เข้าร่วมทั่วไป'); ?></strong></span>
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6"><i class="fa-regular fa-clock me-1"></i> <strong><?php echo intval($act['hours_count']); ?></strong> ชั่วโมง</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 bg-white rounded-4 shadow-sm border-0 empty-portfolio">
                        <div class="mb-3">
                            <div style="width: 80px; height: 80px; background-color: #f8f9fc; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 auto;">
                                <i class="fa-regular fa-folder-open text-muted" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="text-muted fw-bold">ยังไม่มีประวัติกิจกรรมที่ผ่านการประเมิน</h5>
                        <p class="text-muted small">เมื่อคุณเข้าร่วมกิจกรรมและได้รับการประเมินว่า "ผ่าน" ข้อมูลจะมาแสดงที่นี่</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        
    </div>

    <style>
        .hover-bg:hover { background-color: rgba(255,255,255,0.1); }
    </style>
</body>
</html>