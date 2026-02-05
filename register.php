<?php
require 'db.php';

$error_message = "";
$registration_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $userrole = 'user';

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
    <title>สมัครสมาชิก</title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="icons/icon-192.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
    body {
        font-family: 'Prompt', sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background: url('bg/sky.png') no-repeat center center/cover;
        margin: 0;
        background: #cfd8e5;
    }

    .container {
        background: rgba(255, 255, 255, 0.9);
        padding: 45px;
        border-radius: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 500px;
        margin: 30px;
        border: 1px solid #ccc;
    }

    h2 {
        margin-bottom: 20px;
        color: black;
        text-align: center;
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

    input {
        width: calc(100% - 20px);
        padding: 12px;
        margin: 5px 0;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 16px;
    }

    button {
        width: 100%;
        padding: 12px;
        background: #8c99bc;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        cursor: pointer;
        transition: background 0.3s;
        margin-top: 15px;
    }

    a {
        display: block;
        text-align: center;
        margin-top: 10px;
        color: #007BFF;
        text-decoration: none;
        font-weight: bold;
    }

    .alert {
        padding: 20px;
        margin-bottom: 15px;
        border-radius: 5px;
        text-align: center;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        font-size: 16px;
        font-weight: bold;
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

    .form-control {
        padding: 12px;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>สมัครสมาชิก</h2>
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
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
            <input type="text" id="username" name="username" placeholder="ชื่อผู้ใช้งาน" required class="form-control"
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="E-mail" required class="form-control"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

            <label for="password">รหัสผ่าน</label>
            <input type="password" id="password" name="password" placeholder="รหัสผ่าน" required class="form-control">

            <label for="confirmpassword">ยืนยันรหัสผ่าน</label>
            <input type="password" id="confirmpassword" name="confirmpassword" placeholder="ยืนยันรหัสผ่าน" required
                class="form-control">

            <button type="submit">สมัครสมาชิก</button>
            <br>
            <a href="index.php">เข้าสู่ระบบ</a>
        </form>
    </div>

    <div id="successModal" class="modal">
        <div class="modal-content">
            <h2>สมัครสมาชิกสำเร็จ!</h2>
            <label>คุณได้สมัครสมาชิกเรียบร้อยแล้ว</<label><br>
                <button class="modal-button" id="modalConfirmBtn">ตกลง</button>
        </div>
    </div>

    <script>
    const modal = document.getElementById("successModal");
    const confirmBtn = document.getElementById("modalConfirmBtn");

    <?php if ($registration_success): ?>
    modal.style.display = "block";
    const redirectToLogin = () => window.location.href = 'index.php';
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