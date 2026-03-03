<?php
session_start();
include 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}

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
    <meta name="theme-color" content="#96a1cd">
    <title>หน้าคะแนนกิจกรรม</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
        margin: 30px;
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
        color: #fff;
        font-weight: bold;
        float: right;
    }

    .edit-link {
        text-decoration: none;
        color: #000;
        font-weight: bold;
        float: right;
    }


    @media (max-width: 768px) {
        .btn-create-mobile {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50px;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
    }

    .btn-purple {
        background-color: #96a1cd;
        color: white;
        border: none;
        transition: 0.3s;
    }

    .btn-purple:hover {
        background-color: #7e89b3;
        color: white;
    }

    .bg-purple {
        background-color: #96a1cd !important;
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
                    [ <?php echo !empty($_SESSION['userrole']) ? $_SESSION['userrole'] : 'ตรวจสอบไม่พบ Role'; ?> ]
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
                <li><a href="admin_e-portfolio_transcript.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-address-book"></i> E-Portfolio / Transcript</a></li>
                <li><a href="admin_score_activity.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-regular fa-star"></i> คะแนนกิจกรรม</a></li>
                <li><a href="admin_user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>
    <div class="main-content">
        <div class="container-fluid">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-0">คะแนนกิจกรรมสโมสร</h4>
                    <p class="text-muted mb-0">ค้นหาและจัดการคะแนนกิจกรรม</p>
                </div>
            </div>

            <div class="search-container">
                <div class="row g-3">
                    <div class="col-md-12">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="fa fa-search text-muted"></i></span>
                            <input type="text" id="searchInput" class="form-control border-start-0"
                                placeholder="ค้นหากิจกรรม...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): 
                $cover_img = !empty($row['cover_image']) ? 'uploads/covers/' . $row['cover_image'] : '';
            $status_class = 'status-open';
            $status_text = 'เปิดรับสมัคร';
            $filter_text = 'Open'; // ใช้สำหรับ JS Filter

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

            $gradients = [
                'linear-gradient(45deg, #3a7bd5, #00d2ff)',
                'linear-gradient(45deg, #12c2e9, #c471ed)',
                'linear-gradient(45deg, #00b09b, #96c93d)',
                'linear-gradient(45deg, #f12711, #f5af19)'
            ];
            $current_gradient = $gradients[$row['activity_id'] % 4];
        ?>
                <div class="col activity-item" data-status="<?php echo $filter_text; ?>">
                    <div class="activity-card">
                        <div class="card-img-top-custom"
                            style="<?php echo $cover_img ? "background: url('$cover_img') center/cover;" : "background: $current_gradient;"; ?>">

                        </div>
                        <div class="card-body-custom">
                            <div class="activity-info">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('d M Y', strtotime($row['start_date'])); ?>
                            </div>
                            <div class="activity-title text-truncate" title="<?php echo $row['title']; ?>">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </div>
                            <p class="text-muted small"
                                style="height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </p>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">
                                    <i class="fas fa-users me-1 text-primary"></i>
                                    <?php echo $row['current_registrations']; ?> /
                                    <?php echo ($row['total_capacity'] ?? 0); ?> คน
                                </span>
                                <div class="redirect-page d-flex gap-2">
                                    <a href="admin_detail_score_activity.php?id=<?php echo $row['activity_id']; ?>"
                                        class="edit-link btn btn-warning btn-sm px-3">
                                        <i class="fa-regular fa-star me-1"></i> ให้คะแนน
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <p class="text-muted">ยังไม่มีกิจกรรมในระบบ</p>
                </div>
                <?php endif; ?>
            </div>
            <br>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
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