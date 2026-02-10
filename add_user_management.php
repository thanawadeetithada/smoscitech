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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $userrole = $_POST['userrole'];

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
                    $error_message = "อีเมลและชื่อผู้ใช้งานนี้ถูกใช้ไปแล้ว กรุณาใช้อื่น";
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
        $sql = "INSERT INTO users (first_name, last_name, email, username, password, userrole) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        try {$stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $first_name, $last_name, $email, $username, $hashed_password, $userrole);
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

.modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fff;
        margin: 15% auto;
        padding: 30px;
        border-radius: 12px;
        text-align: center;
        width: 80%;
        max-width: 400px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .modal-button {
        background-color: #8c99bc;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        margin-top: 15px;
        font-size: 18px;
        width: fit-content;
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

    .form-control {
        padding: 12px;
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
                            class="fa-solid fa-list-check"></i> รายงานกิจกรรม</a></li>
                <li><a href="user_management.php" class="text-white text-decoration-none d-block py-2"><i
                            class="fa-solid fa-user-tie"></i> ข้อมูลผู้ใช้งาน</a></li>
            </ul>
        </div>
    </div>
    <div class="container-wrapper">
        <div class="container">
            <h2>เพิ่มข้อมูล</h2>
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <label for="first_name">ชื่อ</label>
                <input type="text" id="first_name" name="first_name" placeholder="ชื่อ" required class="form-control"
                    value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">

                <label for="last_name">นามสกุล</label>
                <input type="text" id="last_name" name="last_name" placeholder="นามสกุล" required class="form-control"
                    value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">

                <label for="username">ชื่อผู้ใช้งาน</label>
                <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้งาน" required
                    class="form-control"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" placeholder="E-mail" required class="form-control"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

                <label for="userrole">สถานะ</label>
                <select class="form-control" id="userrole" name="userrole" required>
                    <option value="">-- เลือกประเภท --</option>
                    <option value="executive"
                        <?= (isset($_POST['userrole']) && $_POST['userrole'] == 'executive') ? 'selected' : ''; ?>>
                        ผู้บริหาร
                    </option>
                    <option value="academic_officer"
                        <?= (isset($_POST['userrole']) && $_POST['userrole'] == 'academic_officer') ? 'selected' : ''; ?>>
                        นักวิชาการศึกษา</option>
                    <option value="club_president"
                        <?= (isset($_POST['userrole']) && $_POST['userrole'] == 'club_president') ? 'selected' : ''; ?>>
                        นายกสโมสรนักศึกษาและรองนายกสโมสรนักศึกษา</option>
                    <option value="club_member"
                        <?= (isset($_POST['userrole']) && $_POST['userrole'] == 'club_member') ? 'selected' : ''; ?>>
                        สมาชิกสโมสรนักศึกษาคณะวิทยาศาสตร์และเทคโนโลยี</option>
                </select>

                <label for="password">รหัสผ่าน</label>
                <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required
                    class="form-control">

                <label for="confirmpassword">ยืนยันรหัสผ่าน</label>
                <input type="password" id="confirmpassword" name="confirmpassword" placeholder="ยืนยันรหัสผ่าน" required
                    class="form-control">

                <div class="d-flex text-center btn-group-responsive mt-4">
                    <button type="submit" class="btn btn-purple">ตกลง</button>
                    <button onclick="window.location.href='user_management.php'" class="btn btn-cancel">ยกเลิก</button>
                </div>
            </form>
        </div>
    </div>

    <div id="successModal" class="modal text-center">
        <div class="modal-content">
            <h2>เพิ่มข้อมูลสำเร็จ!</h2>
            <p>คุณได้เพิ่มข้อมูลเรียบร้อยแล้ว</<p><br>
                <button class="modal-button" id="modalConfirmBtn">ตกลง</button>
        </div>
    </div>

    <script>
    const modal = document.getElementById("successModal");
    const confirmBtn = document.getElementById("modalConfirmBtn");

    <?php if ($registration_success): ?>
    modal.style.display = "block";
    const redirectToLogin = () => window.location.href = 'user_management.php';
    const autoRedirectTimeout = setTimeout(redirectToLogin, 3000);

    confirmBtn.onclick = function() {
        clearTimeout(autoRedirectTimeout);
        redirectToLogin();
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            clearTimeout(autoRedirectTimeout);
            redirectToLogin();
        }
    }
    <?php endif; ?>
    </script>
</body>

</html>