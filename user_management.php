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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $idstudent = trim($_POST['idstudent']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $academic_year = trim($_POST['academic_year']);
    $year_level = $_POST['year_level'];
    $department = trim($_POST['department']);
    
    $check_sql = "SELECT user_id FROM users WHERE idstudent = ? AND user_id != ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("si", $idstudent, $user_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $message = "รหัสนักศึกษานี้มีผู้ใช้งานอื่นลงทะเบียนไว้แล้ว โปรดตรวจสอบอีกครั้ง";
        $msg_type = "warning";
    } else {
        $profile_image = $_POST['current_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array(strtolower($ext), $allowed)) {
                $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
                $upload_path = 'uploads/profiles/' . $new_filename; 
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $profile_image = $new_filename;
                } else {
                    $message = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ";
                    $msg_type = "danger";
                }
            } else {
                $message = "อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF) เท่านั้น";
                $msg_type = "warning";
            }
        }

        if (empty($message)) {
            $sql = "UPDATE users SET 
                    idstudent = ?,
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    academic_year = ?, 
                    year_level = ?, 
                    department = ?, 
                    profile_image = ? 
                    WHERE user_id = ?";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $idstudent, $first_name, $last_name, $email, $academic_year, $year_level, $department, $profile_image, $user_id);
            
            if ($stmt->execute()) {
                $message = "อัปเดตข้อมูลส่วนตัวเรียบร้อยแล้ว";
                $msg_type = "success";
                
                $_SESSION['idstudent'] = $idstudent;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['academic_year'] = $academic_year;
                $_SESSION['year_level'] = $year_level;
                $_SESSION['department'] = $department;
            } else {
                $message = "เกิดข้อผิดพลาดในการอัปเดตข้อมูล: " . $conn->error;
                $msg_type = "danger";
            }
            $stmt->close();
        }
    }
    $stmt_check->close();
}

$sql_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result = $stmt_user->get_result();
$user_data = $result->fetch_assoc();
$stmt_user->close();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="apple-mobile-web-app-title" content="App Premium">
    <meta name="application-name" content="App Premium">
    <meta name="theme-color" content="#96a1cd">
    <title>หน้าข้อมูลผู้ใช้งาน</title>
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
        margin: 30px auto;
        padding: 20px;
        max-width: 1000px;
    }

    .profile-card {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.05);
        border: none;
    }

    .profile-image-container {
        text-align: center;
        margin-bottom: 20px;
    }

    .profile-image {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #f8f9fc;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 15px;
    }

    input[type="file"] {
        display: none;
    }

    .custom-file-upload {
        border: 1px solid #ccc;
        display: inline-block;
        padding: 6px 12px;
        cursor: pointer;
        border-radius: 5px;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    
    .custom-file-upload:hover {
        background-color: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
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
        <div class="container-fluid">
            <h3 class="fw-bold mb-4">ตั้งค่าข้อมูลส่วนตัว</h3>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="profile-card">
                <form action="user_management.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-4 profile-image-container">
                            <?php 
                                $img_src = !empty($user_data['profile_image']) && $user_data['profile_image'] !== 'default.png' 
                                            ? 'uploads/profiles/' . $user_data['profile_image'] 
                                            : 'https://via.placeholder.com/150?text=No+Image';
                            ?>
                            <img id="imagePreview" src="<?php echo $img_src; ?>" alt="Profile Image" class="profile-image">
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($user_data['profile_image'] ?? 'default.png'); ?>">
                            
                            <label class="custom-file-upload mt-2">
                                <input type="file" name="profile_image" id="profile_image" accept="image/png, image/jpeg, image/jpg" onchange="previewImage(event)">
                                <i class="fa-solid fa-camera"></i> เปลี่ยนรูปโปรไฟล์
                            </label>
                            <div class="text-muted mt-2" style="font-size: 0.8rem;">ขนาดไฟล์สูงสุด 2MB (JPG, PNG)</div>
                        </div>

                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">รหัสนักศึกษา <span class="text-danger">*</span></label>
                                    <input type="text" name="idstudent" class="form-control" value="<?php echo htmlspecialchars($user_data['idstudent'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6"></div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">ชื่อ <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold">อีเมล <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold">สาขาวิชา</label>
                                    <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($user_data['department'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">ชั้นปี</label>
                                    <select name="year_level" class="form-select">
                                        <option value="">-- เลือกชั้นปี --</option>
                                        <option value="ชั้นปีที่ 1" <?php echo ($user_data['year_level'] == 'ชั้นปีที่ 1') ? 'selected' : ''; ?>>ชั้นปีที่ 1</option>
                                        <option value="ชั้นปีที่ 2" <?php echo ($user_data['year_level'] == 'ชั้นปีที่ 2') ? 'selected' : ''; ?>>ชั้นปีที่ 2</option>
                                        <option value="ชั้นปีที่ 3" <?php echo ($user_data['year_level'] == 'ชั้นปีที่ 3') ? 'selected' : ''; ?>>ชั้นปีที่ 3</option>
                                        <option value="ชั้นปีที่ 4" <?php echo ($user_data['year_level'] == 'ชั้นปีที่ 4') ? 'selected' : ''; ?>>ชั้นปีที่ 4</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">ปีการศึกษา</label>
                                    <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($user_data['academic_year'] ?? ''); ?>" placeholder="เช่น 2566">
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update_profile" class="btn btn-primary px-4"><i class="fa-solid fa-save"></i> บันทึกข้อมูล</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('imagePreview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>