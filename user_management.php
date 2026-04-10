<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$msg_type = '';

// --- Logic การ Update ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $idstudent = trim($_POST['idstudent'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $academic_year = trim($_POST['academic_year'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    $check_sql = "SELECT user_id FROM users WHERE idstudent = ? AND user_id != ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("si", $idstudent, $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $message = "รหัสนักศึกษานี้มีผู้ใช้งานอื่นลงทะเบียนไว้แล้ว โปรดตรวจสอบอีกครั้ง";
        $msg_type = "warning";
    } else {
        $profile_image = $_POST['current_image'] ?? 'default.png';
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
                $upload_path = 'uploads/profiles/' . $new_filename; 
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $profile_image = $new_filename;
                }
            }
        }

        $sql = "UPDATE users SET idstudent = ?, first_name = ?, last_name = ?, email = ?, phone = ?, academic_year = ?, year_level = ?, department = ?, profile_image = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $idstudent, $first_name, $last_name, $email, $phone, $academic_year, $year_level, $department, $profile_image, $user_id);
        if ($stmt->execute()) {
            $message = "อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว";
            $msg_type = "success";
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
        }
        $stmt->close();
    }
    $stmt_check->close();
}

// ดึงข้อมูลจริงจาก DB
$sql_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// กำหนดตัวแปรรูปโปรไฟล์สำหรับแสดงบน Navbar
$profile_image_navbar = !empty($user_data['profile_image']) && $user_data['profile_image'] !== 'default.png' 
                        ? $user_data['profile_image'] 
                        : 'default.png';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลสมาชิกสโมสร - SMO SCITECH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --top-bar-bg: #A37E5E;
            --yellow-sidebar: #FEEFB3;
            --btn-gold: #E6A129;
            --text-dark: #333;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #fff;
            margin: 0;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .wrapper {
            display: flex;
            flex-direction: column;
            flex: 1;
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

        .text-page-pill-btn {
            background: white;
            color: black;
            padding: 3px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 13px;
            letter-spacing: 0.5px;
            font-weight: 500;
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
        .login-pill-btn:hover { background: #eee; color: black; }

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
            text-align: center;
        }

        
        .main-wrapper { display: flex; flex: 1; position: relative; }

        
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
            background: white; padding: 25px 10px; text-align: center;
            border-bottom: 1px solid #eee; text-decoration: none; color: #333; display: block;
            transition: all 0.3s ease;
        }
        .sidebar-item:hover { 
            background: #FDFDFD; 
            transform: translateX(5px); 
        }
        .sidebar-item i { font-size: 30px; display: block; margin-bottom: 8px; color: #000; }
        .sidebar-item span { font-weight: bold; font-size: 13px; }

        
        .content-area { flex-grow: 1; padding: 40px; background-color: white; }
        .info-card {
            background: #FAF3F3;
            border-radius: 20px;
            padding: 40px;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            position: relative;
        }

        .info-card h3 { text-align: center; margin-bottom: 40px; font-weight: bold; }

        .profile-flex { display: flex; gap: 40px; align-items: flex-start; }
        .profile-img-box {
            width: 200px; height: 200px; background: #fff;
            border-radius: 15px; border: 1px solid #ddd;
            overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        }
        .profile-img-box img { width: 100%; height: 100%; object-fit: cover; }

        .info-list { flex-grow: 1; list-style: none; padding: 0; margin: 0; }
        .info-list li { 
            display: flex; border-bottom: 1px solid #eee; 
            padding: 12px 0; font-size: 18px; 
        }
        .info-label { width: 150px; font-weight: bold; }
        .info-value { color: #555; }

        .btn-edit-gold {
            background-color: var(--btn-gold);
            color: #fff; border: none;
            padding: 10px 40px; border-radius: 30px;
            font-weight: bold; font-size: 18px;
            float: right; margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        
        @media (max-width: 992px) {
            .profile-flex { flex-direction: column; align-items: center; }
            .info-list { width: 100%; }
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
            .content-area { padding: 20px 10px; }
            .logout-text { padding: 2px !important; font-size: 9px !important; }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <nav class="top-navbar d-print-none">
            <div class="brand-section">
                <i class="fa-solid fa-bars d-md-none me-2" id="mobileMenuBtn" style="font-size: 24px; cursor: pointer;"></i>
                <img src="img/logo.png" alt="Logo" class="brand-logo">
                <div style="display: flex; flex-direction: column; align-items: flex-start; line-height: 1.2;">
                    <span class="brand-name">SMO SCITECH KPRU</span>
                    <span class="text-page-pill-btn mt-1">ข้อมูลสมาชิกสโมสร</span>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <span class="d-none d-sm-block fw-bold me-2 login-pill-btn">
                    <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'ผู้ใช้งาน'); ?>
                </span>
                
                <div class="logout-area">
                    <a href="user_management.php">
                        <img src="uploads/profiles/<?php echo htmlspecialchars($profile_image_navbar); ?>" alt="Profile"
                             style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    </a>
                    <a href="logout.php" class="logout-text mt-1">Log out</a>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            <aside class="sidebar d-print-none">
                <a href="report_activity.php" class="sidebar-item mt-3 mb-3">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>สถิติการเข้าร่วมกิจกรรม</span>
                </a>
                <a href="e-portfolio.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-book-open"></i>
                    <span>E-portfolio</span>
                </a>
                <a href="user_management.php" class="sidebar-item mb-3">
                    <i class="fa-solid fa-users"></i>
                    <span>ข้อมูลสมาชิก</span>
                </a>
                <a href="activity.php" class="sidebar-item">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>ข้อมูลกิจกรรม</span>
                </a>
            </aside>

            <main class="content-area">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show mx-auto" style="max-width:900px;" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="info-card">
                    <h3>ข้อมูลสมาชิกสโมสร</h3>
                    
                    <div class="profile-flex">
                        <div class="profile-img-box">
                            <?php 
                                $img_src = !empty($user_data['profile_image']) && $user_data['profile_image'] !== 'default.png' 
                                           ? 'uploads/profiles/' . $user_data['profile_image'] 
                                           : 'https://via.placeholder.com/150?text=No+Image';
                            ?>
                            <img src="<?php echo $img_src; ?>" alt="Profile">
                        </div>

                        <ul class="info-list">
                            <li>
                                <span class="info-label">รหัสนักศึกษา :</span>
                                <span class="info-value"><?php echo htmlspecialchars($user_data['idstudent'] ?? '-'); ?></span>
                            </li>
                            <li>
                                <span class="info-label">ชื่อ - นามสกุล :</span>
                                <span class="info-value"><?php echo htmlspecialchars($user_data['first_name'].' '.$user_data['last_name']); ?></span>
                            </li>
                            <li>
                                <span class="info-label">สาขาวิชา :</span>
                                <span class="info-value"><?php echo htmlspecialchars($user_data['department'] ?? '-'); ?></span>
                            </li>
                            <li>
                                <span class="info-label">ตำแหน่ง :</span>
                                <span class="info-value"><?php echo htmlspecialchars($user_data['userrole'] == 'club_president' ? 'นายกสโมสร' : 'สมาชิกสโมสร'); ?></span>
                            </li>
                            <li>
                                <span class="info-label">ชั้นปี :</span>
                                <span class="info-value"><?php echo htmlspecialchars($user_data['year_level'] ?? '-'); ?></span>
                            </li>
                            <li>
                                <span class="info-label">อีเมล :</span>
                                <span class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></span>
                            </li>
                            <li>
                                <span class="info-label">เบอร์โทร :</span>
                                <span class="info-value"><?php echo htmlspecialchars($user_data['phone'] ?? '-'); ?></span>
                            </li>
                        </ul>
                    </div>

                    <button class="btn-edit-gold" data-bs-toggle="modal" data-bs-target="#editModal">แก้ไขข้อมูล >></button>
                    <div class="clearfix"></div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="user_management.php" method="POST" enctype="multipart/form-data" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">แก้ไขข้อมูลส่วนตัว</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-3">
                    <div class="col-12 text-center mb-3">
                        <img id="preview" src="<?php echo $img_src; ?>" style="width:120px; height:120px; object-fit:cover; border-radius:50%;" class="border">
                        <input type="file" name="profile_image" class="form-control mt-2" onchange="previewImg(event)">
                        <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($user_data['profile_image'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">รหัสนักศึกษา</label>
                        <input type="text" name="idstudent" class="form-control" value="<?php echo htmlspecialchars($user_data['idstudent'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">อีเมล</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ชื่อ</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">นามสกุล</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">เบอร์โทร</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">สาขาวิชา</label>
                        <select name="department" class="form-select">
                            <option value="">-- เลือกสาขาวิชา --</option>
                            <?php 
                                $departments = ["วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "นวัตกรรมและธุรกิจอาหาร", "สาธารณสุขศาสตร์", "เคมี (วท.บ.)", "วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม", "ฟิสิกส์", "เคมี (ค.บ.)", "ชีววิทยา", "คณิตศาสตร์ประยุกต์"];
                                $current_dept = trim($user_data['department'] ?? '');
                                
                                foreach ($departments as $dept) {
                                    $selected = ($current_dept === $dept) ? 'selected' : '';
                                    echo "<option value=\"$dept\" $selected>$dept</option>";
                                }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">ชั้นปี</label>
                        <select name="year_level" class="form-select">
                            <?php $current_year = trim($user_data['year_level'] ?? ''); ?>
                            <option value="ชั้นปีที่ 1" <?php echo ($current_year === 'ชั้นปีที่ 1') ? 'selected' : ''; ?>>ชั้นปีที่ 1</option>
                            <option value="ชั้นปีที่ 2" <?php echo ($current_year === 'ชั้นปีที่ 2') ? 'selected' : ''; ?>>ชั้นปีที่ 2</option>
                            <option value="ชั้นปีที่ 3" <?php echo ($current_year === 'ชั้นปีที่ 3') ? 'selected' : ''; ?>>ชั้นปีที่ 3</option>
                            <option value="ชั้นปีที่ 4" <?php echo ($current_year === 'ชั้นปีที่ 4') ? 'selected' : ''; ?>>ชั้นปีที่ 4</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ปีการศึกษา</label>
                        <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($user_data['academic_year'] ?? ''); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" name="update_profile" class="btn btn-primary" style="background-color: var(--btn-gold); border: none;">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script สำหรับพรีวิวรูปภาพ
        function previewImg(e){
            var reader = new FileReader();
            reader.onload = function(){
                document.getElementById('preview').src = reader.result;
            }
            reader.readAsDataURL(e.target.files[0]);
        }

        $(document).ready(function() {
            // Toggle Sidebar สำหรับมือถือ
            $('#mobileMenuBtn').on('click', function(e) {
                e.stopPropagation();
                $('.sidebar').toggleClass('active');
            });

            // ปิด Sidebar หากคลิกพื้นที่อื่นบนหน้าจอ (เฉพาะในหน้าจอมือถือ)
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