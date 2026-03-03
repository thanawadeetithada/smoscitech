<?php
session_start();

$admin_roles = ['executive', 'academic_officer', 'club_president'];
$isAdmin = (isset($_SESSION['userrole']) && in_array($_SESSION['userrole'], $admin_roles));

session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออกจากระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Prompt', sans-serif;
        }
        .modal-content {
            border-radius: 15px;
            text-align: center;
            animation: fadeIn 0.4s ease-in-out;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .btn-custom {
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 30px;
            transition: 0.3s;
        }
        .btn-custom:hover {
            background-color: #2e59d9;
            color: white;
        }
        .check-icon {
            font-size: 55px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

    <div class="modal d-block" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 p-4">
                <div class="modal-body">
                    <div class="check-icon">✅</div>
                    <h4 class="fw-bold mb-3">ออกจากระบบสำเร็จ</h4>
                    <p class="text-muted mb-4">ระบบจะนำคุณไปยังหน้าหลักโดยอัตโนมัติ...</p>
                    <button id="confirmBtn" class="btn btn-custom fw-bold">ตกลง</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const confirmBtn = document.getElementById('confirmBtn');
            const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
            const fallbackUrl = 'index.php';
            
            function redirectAfterLogout() {
                if (isAdmin) {
                    window.location.replace(fallbackUrl);
                } else {
                    let referrer = document.referrer;
                    if (referrer && referrer.includes('payment.php')) {
                        window.location.replace(fallbackUrl);
                    } else {
                        window.location.replace(referrer || fallbackUrl);
                    }
                }
            }

            confirmBtn.addEventListener('click', redirectAfterLogout);
            setTimeout(redirectAfterLogout, 2500);
        });
    </script>

</body>
</html>