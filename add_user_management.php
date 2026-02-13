<?php
session_start();
require 'db.php';

$allowed_roles = ['executive', 'academic_officer', 'club_president'];

if (!isset($_SESSION['userrole']) || !in_array($_SESSION['userrole'], $allowed_roles)) {
    header("Location: index.php");
    exit();
}


$error_message = "";
$registration_success = false;
$profile_image = "default.png";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $academic_year = $_POST['academic_year'];
    $department = $_POST['department'];
    $userrole = 'club_member';
if (isset($_POST['existing_profile_image'])) {
        $profile_image = $_POST['existing_profile_image'];
    }
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
        $filename = $_FILES["profile_image"]["name"];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (!array_key_exists(strtolower($ext), $allowed)) {
            $error_message = "กรุณาเลือกไฟล์รูปภาพที่ถูกต้อง (jpg, jpeg, png)";
        }

        $maxsize = 2 * 1024 * 1024;
        if ($_FILES["profile_image"]["size"] > $maxsize) {
            $error_message = "ขนาดรูปภาพต้องไม่เกิน 2MB";
        }

        if (empty($error_message)) {
            $new_filename = $username . "_" . time() . "." . $ext;
            if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
            
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], "uploads/" . $new_filename)) {
                if ($profile_image != "default.png" && file_exists("uploads/" . $profile_image)) {
                    unlink("uploads/" . $profile_image);
                }
                $profile_image = $new_filename;
            }
        }
    }

    if ($password !== $confirmpassword) {
        $error_message = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    }

    if (empty($error_message)) {
    $check_duplicate_sql = "SELECT email, username 
                        FROM users 
                        WHERE (email = ? OR username = ?) 
                        AND deleted_at IS NULL
                        LIMIT 1";
        if ($stmt = $conn->prepare($check_duplicate_sql)) {
            $stmt->bind_param("ss", $email, $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($existing_email, $existing_username);
                $stmt->fetch();
                if ($email === $existing_email && $username === $existing_username) {
                    $error_message = "อีเมลและชื่อผู้ใช้งานนี้ถูกใช้ไปแล้ว";
                } elseif ($email === $existing_email) {
                    $error_message = "อีเมลนี้ถูกใช้ไปแล้ว กรุณาใช้อีเมลอื่น";
                } elseif ($username === $existing_username) {
                    $error_message = "ชื่อผู้ใช้งานนี้ถูกใช้ไปแล้ว กรุณาใช้ชื่อผู้ใช้งานอื่น";
                }
            }
            $stmt->close();
        } else {
            $error_message = "เกิดข้อผิดพลาดในการเตรียมคำสั่งตรวจสอบซ้ำ";
        }
    }

    if (empty($error_message)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (first_name, last_name, email, username, password, academic_year, department, profile_image, userrole) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssss", $first_name, $last_name, $email, $username, $hashed_password, $academic_year, $department, $profile_image, $userrole);
            $stmt->execute();
            $registration_success = true;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                 $error_message = "ชื่อผู้ใช้งานหรืออีเมลนี้ถูกใช้ไปแล้ว";
            } else {
                 $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
    $conn->close();
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
    <title>เพิ่มข้อมูลผู้ใช้งาน</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    body {
        font-family: 'Prompt', sans-serif;
        height: auto;
        background: url('bg/sky.png') no-repeat center center/cover;
        margin: 0;
        background: #cfd8e5;
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

    .container {
        background: rgba(255, 255, 255, 0.9);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 600px;
        margin: 20px;
    }

    h2 {
        margin-bottom: 20px;
        color: black;
        text-align: center;
        margin-top: 20px;
    }

    form {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    label {
        text-align: left;
        font-weight: bold;
        margin-top: 10px;
    }


    button {
        width: 48%;
        padding: 12px;
        font-size: 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s;
    }

    .submit-btn {
        background: #8c99bc;
        color: white;
    }

    .cancel-btn {
        background: #ccc;
        color: black;
        margin-left: 5px;
    }

    .form-control {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px;
    }

    .form-label {
        margin-top: 10px;
        margin-bottom: 0;
    }

    .container-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: calc(100vh - 56px);
    }

    .bg-purple {
        background-color: #8c99bc !important;
    }

    .btn-group-responsive {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        flex-wrap: nowrap;
    }

    .btn-group-responsive .btn {
        flex: 1 1 200px;
        max-width: 200px;
    }

    @media (max-width: 576px) {
        .btn-group-responsive .btn {
            width: 100%;
            max-width: none;
        }
    }

    .btn-purple {
        width: 20%;
        background-color: #8c99bc !important;
        color: white !important;
        border: none;
    }

    .btn-purple:hover {
        background-color: #9FA8DA !important;
    }

    .btn-cancel {
        width: 20%;
        background-color: #c7c5c5 !important;
        color: black !important;
    }

    .btn-cancel:hover {
        background-color: #E8E8E8 !important;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-dark bg-dark px-3">
        <div class="d-flex w-100 justify-content-between align-items-center">
            <i class="fa-solid fa-bars text-white" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"
                style="cursor: pointer;"></i>
            <div class="nav-item">
                <a class="nav-link" href="logout.php"><i class="fa-solid fa-user"></i>&nbsp;&nbsp;Logout</a>
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
    <div class="container-wrapper">
        <div class="container">
            <h2>เพิ่มข้อมูล</h2>
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            <form action="" method="POST" enctype="multipart/form-data">

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="first_name" class="form-label">ชื่อ</label>
                        <input type="text" id="first_name" name="first_name" placeholder="ชื่อ" required
                            class="form-control"
                            value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="last_name" class="form-label">นามสกุล</label>
                        <input type="text" id="last_name" name="last_name" placeholder="นามสกุล" required
                            class="form-control"
                            value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="username" class="form-label">ชื่อผู้ใช้งาน</label>
                        <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้งาน" required
                            class="form-control"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="E-mail" required class="form-control"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="academic_year" class="form-label">ปีการศึกษา</label>
                        <input type="number" name="academic_year" class="form-control" placeholder="ระบุปีการศึกษา"
                            required value="<?php echo @htmlspecialchars($_POST['academic_year']); ?>">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="department" class="form-label">สาขาวิชา</label>
                        <select class="form-control" id="department" name="department" required>
                            <option value="" disabled <?php echo !isset($_POST['department']) ? 'selected' : ''; ?>>--
                                เลือกสาขาวิชา --</option>
                            <?php
    $depts = ["วิทยาการคอมพิวเตอร์", "เทคโนโลยีสารสนเทศ", "นวัตกรรมและธุรกิจอาหาร", "สาธารณสุขศาสตร์", "เคมี (วท.บ.)", "วิทยาศาสตร์และเทคโนโลยีสิ่งแวดล้อม", "ฟิสิกส์", "เคมี (ค.บ.)", "ชีววิทยา", "คณิตศาสตร์ประยุกต์"];
    foreach ($depts as $d) {
        $selected = (isset($_POST['department']) && $_POST['department'] == $d) ? 'selected' : '';
        echo "<option value=\"$d\" $selected>$d</option>";
    }
    ?>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required
                            class="form-control">

                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="confirmpassword" class="form-label">ยืนยันรหัสผ่าน</label>
                        <input type="password" id="confirmpassword" name="confirmpassword" placeholder="ยืนยันรหัสผ่าน"
                            required class="form-control">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="profile_image" class="form-label">รูปโปรไฟล์</label>
                        <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/*"
                            onchange="previewImage(event)">

                        <input type="hidden" name="existing_profile_image"
                            value="<?php echo htmlspecialchars($profile_image); ?>">

                        <div class="mb-2 mt-4 text-center">
                            <?php 
                $display_img = "bg/default-profile.png";
                $preview_style = "display: none;";
                if ($profile_image != "default.png" && file_exists("uploads/" . $profile_image)) {
                    $display_img = "uploads/" . $profile_image;
                    $preview_style = "display: inline-block;";
                }
            ?>
                            <img id="preview" src="<?php echo $display_img; ?>" alt="Preview"
                                style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 2px solid #ddd; <?php echo $preview_style; ?>">
                        </div>
                    </div>
                </div>

                <div class="d-flex text-center btn-group-responsive mt-4">
                    <button type="submit" class="btn btn-purple">บันทึก</button>
                    <button type="button" onclick="window.location.href='admin_user_management.php'"
                        class="btn btn-cancel">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center">
                <div class="modal-header bg-purple text-white">
                    <h5 class="modal-title" id="statusModalLabel">อัปเดต</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="statusMessage">
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-purple" id="closeModalBtn" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');

        const isRegistrationSuccess = <?php echo $registration_success ? 'true' : 'false'; ?>;

        if (isRegistrationSuccess) {
            $("#statusMessage").html(
                "<i class='fa-solid fa-circle-check text-success fa-2x mb-2'></i><br>สมัครสมาชิกเรียบร้อยแล้ว"
                );
            $("#statusModal").modal("show");
        } else if (status === 'success') {
            $("#statusMessage").html(
                "<i class='fa-solid fa-circle-check text-success fa-2x mb-2'></i><br>อัปเดตข้อมูลเรียบร้อยแล้ว"
                );
            $("#statusModal").modal("show");
        } else if (status === 'error') {
            $("#statusMessage").html(
                "<i class='fa-solid fa-triangle-exclamation text-danger fa-2x mb-2'></i><br>เกิดข้อผิดพลาดในการดำเนินการ"
                );
            $("#statusModal").modal("show");
        }

        $("#statusModal").on('hidden.bs.modal', function() {
            window.location.href = 'admin_user_management.php';
        });
    });

    function previewImage(event) {
        const reader = new FileReader();
        const imageField = document.getElementById("preview");

        reader.onload = function() {
            if (reader.readyState === 2) {
                imageField.src = reader.result;
                imageField.style.display = "inline-block";
            }
        }

        if (event.target.files && event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        } else {
            imageField.src = "";
            imageField.style.display = "none";
        }
    }
    </script>
</body>

</html>