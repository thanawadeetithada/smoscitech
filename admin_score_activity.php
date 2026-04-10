<?php
session_start();
include 'db.php';

// กำหนดสิทธิ์ผู้เข้าถึง (คงไว้ตามเดิมของคุณ)
$allowed_roles = ['club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

// --- ดึงข้อมูลรูปโปรไฟล์สำหรับ Top Navbar ---
$user_id = $_SESSION['user_id'];
$stmt_profile = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ?");
$stmt_profile->bind_param("i", $user_id);
$stmt_profile->execute();
$res_profile = $stmt_profile->get_result();
$user_data = $res_profile->fetch_assoc();
// ถ้าไม่มีรูปให้ใช้ default.png
$profile_image = !empty($user_data['profile_image']) ? $user_data['profile_image'] : 'default.png';
$stmt_profile->close();
// ---------------------------------------------------------

// --- ข้อมูลจริงจากโค้ดเดิม ---
$sql = "SELECT a.*, 
        (SELECT SUM(capacity) FROM activity_tasks WHERE activity_id = a.activity_id) as total_capacity,
        (SELECT COUNT(*) FROM activity_registrations WHERE activity_id = a.activity_id AND registration_status != 'cancelled') as current_registrations
        FROM activities a 
        ORDER BY a.start_date DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#A37E5E">
    <title>หน้าคะแนนกิจกรรม - SMO SCITECH</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
    :root {
        --top-bar-bg: #A37E5E;
        --yellow-sidebar: #FEEFB3;
        --light-bg: #F8F9FC;
        --btn-blue: #6358E1;
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
        padding: 30px;
        display: flex;
        flex-direction: column;
    }

    
    .search-container {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
        margin-bottom: 30px;
        border: 1px solid #eee;
    }

    .activity-card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        background: white;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .activity-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .card-img-top-custom {
        height: 160px;
        background: linear-gradient(45deg, #A37E5E, #D7B79E);
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
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .status-open { background-color: #28a745; }
    .status-closed { background-color: #dc3545; }
    .status-upcoming { background-color: #ffc107; color: #000; }

    .card-body-custom {
        padding: 20px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .activity-title {
        font-weight: bold;
        font-size: 1.1rem;
        margin-bottom: 10px;
        color: #333;
    }

    .activity-info {
        font-size: 0.9rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .btn-purple-custom {
        background-color: var(--btn-blue);
        color: white;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
    }

    .btn-purple-custom:hover {
        background-color: #4a40bd;
        color: white;
    }

    .btn-warning-custom {
        background-color: #f6c23e;
        color: #fff;
        border: none;
        border-radius: 8px;
        transition: 0.3s;
        font-weight: bold;
    }

    .btn-warning-custom:hover {
        background-color: #dfa822;
        color: #fff;
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
        .content-area { padding: 20px 15px; }
        .logout-text { padding: 2px !important; font-size: 9px !important; }
        
        .header-section {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 15px;
        }
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
                    <span class="text-page-pill-btn mt-1">คะแนนกิจกรรม</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'ผู้ใช้งาน'); ?>
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
                <div class="d-flex justify-content-between align-items-center mb-4 header-section">
                    <div>
                        <h4 class="fw-bold mb-0" style="color: #333;">คะแนนกิจกรรม</h4>
                        <p class="text-muted mb-0">ค้นหาและจัดการคะแนนกิจกรรม</p>
                    </div>
                </div>

                <div class="search-container">
                    <div class="row g-3">
                        <div class="col-md-9 col-lg-10">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fa fa-search text-muted"></i></span>
                                <input type="text" id="searchInput" class="form-control bg-light border-start-0" placeholder="ค้นหากิจกรรม (ชื่อกิจกรรม)...">
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <select id="statusFilter" class="form-select bg-light">
                                <option value="all" selected>ทุกสถานะ</option>
                                <option value="Open">เปิดรับสมัคร</option>
                                <option value="Closed">ปิดรับสมัคร</option>
                                <option value="Finished">จบกิจกรรม</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): 
                            $cover_img = !empty($row['cover_image']) ? 'uploads/covers/' . $row['cover_image'] : '';
                            $status_class = 'status-open';
                            $status_text = 'เปิดรับสมัคร';
                            $filter_text = 'Open';

                            if($row['status'] == 'closed') {
                                $status_class = 'status-closed';
                                $status_text = 'ปิดรับสมัคร';
                                $filter_text = 'Closed';
                            }
                            if($row['status'] == 'completed') {
                                $status_class = 'bg-secondary';
                                $status_text = 'จบกิจกรรม';
                                $filter_text = 'Finished';
                            }

                            // กำหนดสีพื้นหลัง Default ให้สอดคล้องกับธีมหลัก
                            $gradients = [
                                'linear-gradient(45deg, #A37E5E, #C7A68C)',
                                'linear-gradient(45deg, #8E7057, #B4967C)',
                                'linear-gradient(45deg, #6358E1, #8B83E6)',
                                'linear-gradient(45deg, #FEEFB3, #F2D575)'
                            ];
                            $current_gradient = $gradients[$row['activity_id'] % 4];
                        ?>
                        <div class="col activity-item" data-status="<?php echo $filter_text; ?>">
                            <div class="activity-card">
                                <div class="card-img-top-custom" style="<?php echo $cover_img ? "background: url('$cover_img') center/cover;" : "background: $current_gradient;"; ?>">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </div>
                                <div class="card-body-custom">
                                    <div class="activity-info">
                                        <i class="far fa-calendar-alt text-primary me-1"></i>
                                        <?php echo date('d M Y', strtotime($row['start_date'])); ?>
                                    </div>
                                    <div class="activity-title text-truncate" title="<?php echo htmlspecialchars($row['title']); ?>">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </div>
                                    <p class="text-muted small mb-3" style="height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        <?php echo htmlspecialchars($row['description']); ?>
                                    </p>
                                    <div class="mt-auto">
                                        <hr class="mt-0 mb-3" style="opacity: 0.1;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold" style="font-size: 14px; color: #555;">
                                                <i class="fas fa-users me-1 text-primary"></i>
                                                <?php echo $row['current_registrations']; ?> / <?php echo ($row['total_capacity'] ?? 0); ?> คน
                                            </span>
                                            <div class="d-flex gap-2">
                                                <a href="admin_detail_score_activity.php?id=<?php echo $row['activity_id']; ?>" class="btn btn-warning-custom btn-sm px-3 shadow-sm py-2">
                                                    <i class="fa-regular fa-star me-1"></i> ให้คะแนน
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 w-100 d-flex flex-column justify-content-center align-items-center py-5 mt-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3 opacity-50"></i>
                            <h5 class="text-muted fw-bold">ยังไม่มีกิจกรรมในระบบ</h5>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle Sidebar สำหรับมือถือ
        $('#mobileMenuBtn').on('click', function(e) {
            e.stopPropagation();
            $('.sidebar').toggleClass('active');
        });

        // ปิด Sidebar หากคลิกพื้นที่อื่นบนหน้าจอ
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('#mobileMenuBtn').length) {
                    $('.sidebar').removeClass('active');
                }
            }
        });

        // ระบบค้นหาและ Filter ข้อมูล
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const activityItems = document.querySelectorAll('.activity-item');

        function filterActivities() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value;

            activityItems.forEach(item => {
                const titleElement = item.querySelector('.activity-title');
                const titleText = titleElement ? titleElement.innerText.toLowerCase() : '';
                const itemStatus = item.getAttribute('data-status');

                const matchesSearch = titleText.includes(searchTerm);
                const matchesStatus = (selectedStatus === 'all') || (itemStatus === selectedStatus);

                if (matchesSearch && matchesStatus) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('keyup', filterActivities);
        statusFilter.addEventListener('change', filterActivities);
    });
    </script>
</body>

</html>