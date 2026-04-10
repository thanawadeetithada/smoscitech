<?php
session_start();
include 'db.php';

// ตัดโค้ดส่วนที่เช็ค $_SESSION['user_id'] ของ Admin ออกไป เพราะหน้านี้เปิดให้ทุกคนเข้าดูได้

$target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($target_user_id === 0) {
    // ถ้าไม่มีการส่ง ID มา ให้เด้งกลับไปหน้า E-portfolio สาธารณะ
    header("Location: main_e-portfolio.php");
    exit();
}

// 1. ดึงข้อมูลผู้ใช้งานจริง (นักศึกษาที่ถูกดู E-Portfolio)
$sql_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $target_user_id);
$stmt_user->execute();
$user_profile = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

$full_name = ($user_profile['first_name'] ?? '') . ' ' . ($user_profile['last_name'] ?? '');

$profile_image_file = (!empty($user_profile['profile_image']) && $user_profile['profile_image'] != 'default.png') ? $user_profile['profile_image'] : 'default.png';
$profile_image_url = 'uploads/profiles/' . $profile_image_file;
if (!file_exists($profile_image_url) && $profile_image_file !== 'default.png') {
     $profile_image_url = 'https://placehold.co/150x150?text=No+Image';
}

// 2. ดึงข้อมูล Soft Skills
$sql_skills = "SELECT skill_name, skill_level FROM user_skills WHERE user_id = ? ORDER BY skill_level DESC";
$stmt_skills = $conn->prepare($sql_skills);
$stmt_skills->bind_param("i", $target_user_id);
$stmt_skills->execute();
$result_skills = $stmt_skills->get_result();
$saved_skills = [];
while ($row = $result_skills->fetch_assoc()) {
    $saved_skills[$row['skill_name']] = $row['skill_level'];
}
$stmt_skills->close();

// 3. ดึงข้อมูล Hard Skills (เทคโนโลยี)
$hard_skills_data = [];
$sql_hs = "SELECT * FROM user_hard_skills WHERE user_id = ?";
$stmt_hs = $conn->prepare($sql_hs);
$stmt_hs->bind_param("i", $target_user_id);
$stmt_hs->execute();
$result_hs = $stmt_hs->get_result();
while ($row = $result_hs->fetch_assoc()) {
    $hard_skills_data[] = $row;
}
$stmt_hs->close();

// 4. ดึงข้อมูลด้านภาษา
$languages_data = [];
$sql_lang = "SELECT * FROM user_languages WHERE user_id = ?";
$stmt_lang = $conn->prepare($sql_lang);
$stmt_lang->bind_param("i", $target_user_id);
$stmt_lang->execute();
$result_lang = $stmt_lang->get_result();
while ($row = $result_lang->fetch_assoc()) {
    $languages_data[] = $row;
}
$stmt_lang->close();

// 5. ดึงกิจกรรมเพิ่มเติมที่ผู้ใช้เพิ่มเอง (Custom Activities)
$custom_activities = [];
$sql_custom = "SELECT * FROM user_custom_activities WHERE user_id = ? ORDER BY id DESC";
$stmt_custom = $conn->prepare($sql_custom);
$stmt_custom->bind_param("i", $target_user_id);
$stmt_custom->execute();
$result_custom = $stmt_custom->get_result();
while ($row = $result_custom->fetch_assoc()) {
    $custom_activities[] = $row;
}
$stmt_custom->close();

// 6. ดึงกิจกรรมจากระบบที่ผ่านการประเมินจริง (System Activities)
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
$stmt_act->bind_param("i", $target_user_id);
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
    <title>E-Portfolio - <?php echo htmlspecialchars($user_profile['first_name'] ?? 'ไม่มีชื่อ'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F4F6F9;
        --btn-blue: #6358E1;
        
        --theme-dark: #3b3d42;
        --theme-light: #f8fbff;
        --text-dark: #222222;
        --text-muted: #555555;
    }

    body, html {
        height: 100%; margin: 0; font-family: 'Sarabun', sans-serif;
        background-color: var(--light-bg); overflow-x: hidden;
    }

    .wrapper { display: flex; flex-direction: column; min-height: 100vh; }

    
    .top-navbar {
        background-color: var(--top-bar-bg); min-height: 80px; display: flex;
        align-items: center; padding: 10px 20px; justify-content: space-between;
        color: white; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); z-index: 100; position: sticky; top: 0;
    }
    .brand-section { display: flex; align-items: center; gap: 12px; }
    .brand-logo { width: 60px; height: 60px; }
    .brand-name { font-size: clamp(16px, 4vw, 24px); font-family: serif; letter-spacing: 1px; white-space: nowrap; }
    
    .login-pill-btn { background: white; color: black; padding: 6px 25px; border-radius: 50px; text-decoration: none; font-weight: bold; font-size: 16px; transition: 0.3s; }
    .login-pill-btn:hover { background: #eee; color: black; }
    .text-page-pill-btn { background: white; color: black; padding: 3px 15px; border-radius: 5px; text-decoration: none; font-size: 13px; font-weight: 500; }
    
    .logout-area { text-align: center; margin-left: 20px; }
    .logout-text { color: #000; font-weight: bold; text-decoration: none; font-size: 14px; background: #D9D9D9; padding: 2px 10px; border-radius: 5px; display: block; }

    
    .main-wrapper { display: flex; flex: 1; position: relative; }

    
    .sidebar { width: 230px; background-color: var(--yellow-sidebar); flex-shrink: 0; display: flex; flex-direction: column; border-right: 1px solid rgba(0, 0, 0, 0.05); transition: 0.3s ease-in-out; z-index: 99; }
    .sidebar-item { background: white; padding: 25px 10px; text-align: center; border-bottom: 1px solid #eee; text-decoration: none; color: #333; display: block; transition: all 0.3s ease; }
    .sidebar-item:hover { background: #FDFDFD; transform: translateX(5px); }
    .sidebar-item i { font-size: 32px; display: block; margin-bottom: 8px; color: #000; }
    .sidebar-item span { font-weight: bold; font-size: 13px; }

    
    .content-area { flex-grow: 1; padding: 40px; display: flex; justify-content: center; align-items: flex-start; }
    .portfolio-container { width: 100%; max-width: 1100px; background: white; display: flex; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); border-radius: 8px; overflow: hidden; }

    .section-pill { background-color: var(--theme-dark); color: white; padding: 8px 20px; border-radius: 25px; font-weight: 700; font-size: 16px; margin-bottom: 20px; display: flex; align-items: center; letter-spacing: 0.5px; }
    .section-pill i { margin-right: 10px; font-size: 14px; }

    
    .portfolio-left { width: 35%; padding: 40px 30px; border-right: 1px solid #EBEBEB; }
    .profile-img-wrapper { text-align: center; margin-bottom: 20px; }
    .portfolio-profile-img { width: 180px; height: 180px; object-fit: cover; border: 5px solid #eaeaea; border-radius: 10px; background-color: #fff; }
    .portfolio-name { font-size: 24px; font-weight: 700; color: var(--text-dark); line-height: 1.2; margin-bottom: 5px; }
    .portfolio-edu { font-size: 14px; color: var(--text-muted); font-weight: 500; margin-bottom: 30px; display: flex; align-items: flex-start; gap:8px;}
    
    .contact-item { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; font-size: 14px; color: var(--text-dark); }
    .contact-item i { color: var(--text-dark); font-size: 16px; width: 20px; text-align: center; }

    .skill-block { margin-bottom: 15px; }
    .skill-name { font-size: 14px; font-weight: 600; color: var(--text-dark); margin-bottom: 5px; }
    .skill-dots { color: #dcdcdc; font-size: 12px; }
    .skill-dots .active { color: var(--theme-dark); }

    
    .portfolio-right { width: 65%; background: #FFFFFF; padding: 40px; }
    .about-text { font-size: 14.5px; color: var(--text-muted); line-height: 1.8; text-indent: 30px; margin-bottom: 40px; }

    .timeline-wrapper { border-left: 2px dashed #999; margin-left: 10px; padding-left: 30px; margin-top: 10px; }
    .timeline-item { position: relative; margin-bottom: 40px; }
    .timeline-item::before { content: ''; position: absolute; left: -37px; top: 0; width: 12px; height: 12px; background: var(--theme-dark); border-radius: 50%; }

    .act-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px; }
    .act-grid img { width: 100%; height: 110px; object-fit: cover; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .act-single-img { width: 100%; max-height: 250px; object-fit: cover; border-radius: 4px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }

    .act-title { font-weight: 700; font-size: 16px; color: var(--text-dark); margin-bottom: 8px; }
    .act-desc { font-size: 13.5px; color: var(--text-muted); line-height: 1.6; padding-left: 15px; position: relative; }
    .act-desc::before { content: '•'; position: absolute; left: 0; color: var(--text-dark); }

    
    .btn-group-fixed { position: fixed; bottom: 30px; right: 40px; display: flex; gap: 15px; z-index: 100; }
    .btn-pill { background: var(--btn-blue); color: white; border-radius: 50px; padding: 10px 30px; border: none; font-size: 14px; font-weight: 500; text-decoration: none; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); transition: 0.3s; cursor: pointer; }
    .btn-pill:hover { transform: translateY(-2px); opacity: 0.9; color: white; box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3); }

    
    @media (max-width: 992px) {
        .portfolio-container { flex-direction: column; }
        .portfolio-left, .portfolio-right { width: 100%; border: none; }
    }
    @media (max-width: 768px) {
        .sidebar { position: absolute; top: 0; left: -230px; height: 100%; box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1); }
        .sidebar.active { left: 0; }
        .top-navbar { padding: 10px 15px; }
        .brand-name { font-size: 18px; }
        .content-area { padding: 15px; }
        .logout-text { padding: 2px !important; font-size: 10px !important; }
        .logout-area { margin-left: 10px; }
        .act-grid { grid-template-columns: repeat(2, 1fr); }
        .btn-group-fixed { bottom: 20px; right: 20px; flex-direction: column; }
    }

    @media print {
        @page {
            size: A4; 
            margin: 5mm; 
            font-family: 'Sarabun', sans-serif;
        }
        
        body, html { 
            background: white !important; 
            margin: 0; 
            padding: 0; 
            height: auto !important; 
            font-size: 11px !important; 
            color: #000 !important;
            overflow: visible !important; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact;
        }

        .wrapper, .main-wrapper, .content-area {
            display: block !important;
            padding: 0 !important;
            margin: 0 !important;
            height: auto !important;
            overflow: visible !important;
        }

        .top-navbar, .sidebar, .btn-group-fixed, .d-print-none { 
            display: none !important; 
        }
        
        .portfolio-container { 
            border: none !important; 
            border-radius: 0; 
            flex-direction: row !important; 
            width: 100%; 
            max-width: 100%;
            margin: 0 !important;
            box-shadow: none !important;
            height: auto !important;
            overflow: visible !important;
            
        }
        
        .portfolio-left { 
            width: 30% !important; 
            border-right: 0px solid #EEE; 
            padding: 15px !important; 
        }
        
        .portfolio-right { 
            width: 70% !important; 
            padding: 15px !important; 
            background: white !important;
        }

        
        .profile-img-wrapper { margin-bottom: 10px !important; }
        .portfolio-profile-img { width: 90px !important; height: 90px !important; border-width: 3px !important; }
        .portfolio-name { font-size: 18px !important; margin-bottom: 3px !important; }
        .portfolio-edu, .contact-item { font-size: 11px !important; margin-bottom: 6px !important; }
        .contact-item i { width: 15px; }

        
        .section-pill { padding: 4px 15px !important; font-size: 13px !important; margin-top: 15px !important; margin-bottom: 10px !important; }
        
        .skill-block { 
            margin-bottom: 8px !important; 
            padding: 0 !important; 
            break-inside: avoid; 
            border: none !important; 
        }
        .skill-name { font-size: 11.5px !important; margin-bottom: 2px !important; border-bottom: none !important; padding-bottom: 0 !important; font-weight: bold !important; color: #222 !important;}
        .skill-dots { display: block !important; font-size: 9px !important; color: #dcdcdc !important;} 
        .skill-dots .active { color: #3b3d42 !important; }

        .about-text { font-size: 11.5px !important; margin-bottom: 10px !important; line-height: 1.4 !important; }

        
        .timeline-wrapper { 
            display: flex !important; 
            flex-wrap: wrap !important; 
            gap: 15px 10px; 
            border-left: none !important; 
            margin-left: 0 !important; 
            padding-left: 0 !important; 
            margin-top: 5px !important;
        }
        
        .timeline-item { 
            width: calc(50% - 5px) !important; 
            margin-bottom: 5px !important; 
            padding-left: 15px !important; 
            border-left: 2px dashed #999 !important; 
            break-inside: avoid; 
        }
        
        .timeline-item::before { 
            left: -6px !important; 
            width: 10px !important; 
            height: 10px !important; 
        }

        
        .act-grid { gap: 5px !important; margin-bottom: 5px !important; }
        .act-grid img { height: 50px !important; }
        .act-single-img { max-height: 60px !important; margin-bottom: 5px !important; }

        .act-title { font-size: 12px !important; margin-bottom: 2px !important; }
        .act-desc { font-size: 10.5px !important; line-height: 1.3 !important; }
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <nav class="top-navbar d-print-none">
            <div class="brand-section">
                <i class="fa-solid fa-bars d-md-none me-2" id="mobileMenuBtn" style="font-size: 24px; cursor: pointer;"></i>
                <img src="img/logo.png" alt="Logo" class="brand-logo" onerror="this.src='https://placehold.co/60x60?text=Logo'">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <span class="text-page-pill-btn mt-1">รายงาน E-portfolio</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                 <a href="index.php" style="text-decoration: none;">
                    <i class="fa-solid fa-circle-user ms-3" style="font-size: 40px; color: #333;"></i>
                </a>
            </div>
        </nav>

        <div class="main-wrapper">
            <aside class="sidebar d-print-none">
                 <a href="main_report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="main_e-portfolio.php" class="sidebar-item mb-3" style="background: #FDFDFD; color: var(--btn-blue);">
                    <i class="fa-solid fa-book-open"></i>
                    <span>รายงาน E-portfolio</span>
                </a>
            </aside>

            <main class="content-area">
                <div class="portfolio-container">

                    <div class="portfolio-left">
                        <div class="profile-img-wrapper">
                            <img src="<?php echo $profile_image_url; ?>" class="portfolio-profile-img" alt="Profile">
                        </div>
                        
                        <div class="portfolio-name"><?php echo htmlspecialchars($full_name); ?></div>
                        <div class="portfolio-edu">
                            <i class="fa-solid fa-graduation-cap mt-1"></i>
                            <div>
                                <strong>Education</strong><br>
                                <?php echo htmlspecialchars($user_profile['department'] ?? 'คณะวิทยาศาสตร์และเทคโนโลยี'); ?>
                            </div>
                        </div>

                        <div class="section-pill"><i class="fa-solid fa-address-card"></i> Contact</div>
                        <div class="contact-item">
                            <i class="fa-solid fa-location-dot"></i> <span>KPRU</span>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-id-badge"></i> <span><?php echo htmlspecialchars($user_profile['idstudent'] ?? '-'); ?></span>
                        </div>
                        <div class="contact-item">
                            <i class="fa-solid fa-envelope"></i> <span><?php echo htmlspecialchars($user_profile['email'] ?? '-'); ?></span>
                        </div>
                        <?php if(!empty($user_profile['phone'])): ?>
                        <div class="contact-item">
                            <i class="fa-solid fa-phone"></i> <span><?php echo htmlspecialchars($user_profile['phone']); ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="section-pill mt-5"><i class="fa-solid fa-bullseye"></i> Soft Skills</div>
                        
                        <?php if (count($saved_skills) > 0): ?>
                            <?php foreach($saved_skills as $skill_name => $level): ?>
                                <div class="skill-block">
                                    <div class="skill-name"><?php echo htmlspecialchars($skill_name); ?></div>
                                    <div class="skill-dots">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="fa-solid fa-circle <?php echo ($i <= $level) ? 'active' : ''; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted small mb-4">ไม่มีข้อมูลประเมินทักษะ</p>
                        <?php endif; ?>

                        <?php if(count($hard_skills_data) > 0): ?>
                            <div class="section-pill mt-5"><i class="fa-solid fa-laptop-code"></i> Hard Skills</div>
                            <?php foreach($hard_skills_data as $hs): ?>
                                <div class="skill-block" style="background:#fff; padding:10px; border-radius:8px; border: 1px solid #eee;">
                                    <div class="skill-name text-primary mb-1"><i class="fa-solid fa-desktop me-1"></i> <?php echo htmlspecialchars($hs['skill_name']); ?></div>
                                    <div class="small text-muted">ระดับ: <?php echo htmlspecialchars($hs['skill_level']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if(count($languages_data) > 0): ?>
                            <div class="section-pill mt-5" style="background-color: #2e7d32;"><i class="fa-solid fa-language"></i> Languages</div>
                            <?php foreach($languages_data as $lang): ?>
                                <div class="skill-block" style="background:#fff; padding:10px; border-radius:8px; border: 1px solid #eee;">
                                    <div class="skill-name text-success mb-1"><?php echo htmlspecialchars($lang['lang_name']); ?></div>
                                    <div style="font-size:12px; color:var(--text-muted); line-height: 1.5;">
                                        ฟัง: <?php echo htmlspecialchars($lang['lang_listen'] ?? '-'); ?> | พูด: <?php echo htmlspecialchars($lang['lang_speak'] ?? '-'); ?><br>
                                        อ่าน: <?php echo htmlspecialchars($lang['lang_read'] ?? '-'); ?> | เขียน: <?php echo htmlspecialchars($lang['lang_write'] ?? '-'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </div>

                    <div class="portfolio-right">
                        
                        <div class="mb-5" style="break-inside: avoid;">
                            <h5 class="fw-bold text-dark"><i class="fa-solid fa-user me-2"></i> About Me</h5>
                            <p class="small text-muted mt-2" style="line-height: 1.6; background: #fff; padding: 15px; border-left: 4px solid var(--top-bar-bg); box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
                                <?php 
                                    if(!empty($user_profile['about_me'])) {
                                        echo nl2br(htmlspecialchars($user_profile['about_me']));
                                    } else {
                                        echo "ไม่มีข้อมูลแนะนำตัวของนักศึกษา";
                                    }
                                ?>
                            </p>
                        </div>

                        <h5 class="fw-bold mb-4 text-dark"><i class="fa-solid fa-award me-2"></i> Activity Results</h5>

                        <div class="timeline-wrapper">
                            <?php foreach ($custom_activities as $c_act): ?>
                                <div class="timeline-item">
                                    <div class="fw-bold" style="font-size: 16px; color: var(--btn-blue);">
                                        <?php echo htmlspecialchars($c_act['title']); ?>
                                    </div>
                                    
                                    <?php if(!empty($c_act['image_path'])): ?>
                                        <div class="mt-2 mb-2">
                                            <img src="uploads/activities/<?php echo htmlspecialchars($c_act['image_path']); ?>" class="act-single-img" alt="Custom Activity">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="act-desc">
                                        <strong>บทบาท:</strong> <?php echo htmlspecialchars($c_act['role'] ?? '-'); ?>
                                        <?php if(!empty($c_act['description'])): ?>
                                            <br><?php echo nl2br(htmlspecialchars($c_act['description'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($portfolio_activities as $act): 
                                $cover_img = !empty($act['cover_image']) ? 'uploads/covers/' . $act['cover_image'] : 'https://placehold.co/400x300?text=Activity+Image';
                            ?>
                                <div class="timeline-item">
                                    <div class="act-grid">
                                        <img src="<?php echo $cover_img; ?>" alt="Activity Photo">
                                        <img src="<?php echo $cover_img; ?>" alt="Activity Photo" style="filter: brightness(0.9);">
                                        <img src="<?php echo $cover_img; ?>" alt="Activity Photo" style="filter: brightness(0.85);">
                                    </div>
                                    <div class="act-title"><?php echo htmlspecialchars($act['title']); ?></div>
                                    <div class="act-desc">
                                        บทบาท: <?php echo htmlspecialchars($act['task_name'] ?? 'ผู้เข้าร่วม'); ?> 
                                        (สะสมเวลา <?php echo $act['hours_count']; ?> ชั่วโมง)
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (count($portfolio_activities) == 0 && count($custom_activities) == 0): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-box-open mb-2" style="font-size: 30px; color: #ccc;"></i><br>
                                    ไม่มีข้อมูลผลงานกิจกรรม
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="btn-group-fixed d-print-none">
        <button type="button" onclick="window.print()" class="btn-pill" style="background:#2C3E50;">
            <i class="fa-solid fa-print me-2"></i>Export PDF
        </button>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
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
    </script>
</body>
</html>