<?php
session_start();

$admin_roles = ['executive', 'academic_officer', 'club_president'];

$isAdmin = (isset($_SESSION['userrole']) && in_array($_SESSION['userrole'], $admin_roles));

session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="th">
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ออกจากระบบ</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Prompt', sans-serif;
        }
        .modal-content {
            border-radius: 15px;
            text-align: center;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(-10px);}
            to {opacity: 1; transform: translateY(0);}
        }
        .btn-custom {
            background-color: #8c99bc;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            transition: 0.3s;
        }
        .btn-custom:hover {
            background-color: #6f7ca1;
        }
    </style>
</head>
<body>

    <!-- Modal -->
    <div class="modal fade show" id="logoutModal" tabindex="-1" role="dialog" style="display:block;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header justify-content-center border-0 pt-4">
                    <h5 class="modal-title font-weight-bold">✅ ออกจากระบบแล้ว</h5>
                </div>
                <div class="modal-body text-center px-4 pb-4 pt-2">
                    <p class="text-muted mb-3">ระบบจะนำคุณไปยังหน้าหลักโดยอัตโนมัติ</p>
                    <button id="confirmBtn" class="btn btn-custom px-4 py-2 font-weight-bold">ตกลง</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#confirmBtn').on('click', function() {
                redirectAfterLogout();
            });

            setTimeout(() => redirectAfterLogout(), 2500);

            function redirectAfterLogout() {
                <?php if ($isAdmin): ?>
                    window.location.href = "index.php";
                <?php else: ?>
                    var referrer = document.referrer;
                    if (referrer && referrer.includes('payment.php')) {
                        window.location.replace('index.php');
                    } else {
                        window.location.replace(referrer || 'index.php');
                    }
                <?php endif; ?>
            }
        });
    </script>

</body>
</html>
