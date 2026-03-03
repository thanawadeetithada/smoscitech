<?php
// ส่ง HTTP Header 503 เพื่อบอก Search Engine (เช่น Google) ว่าเว็บแค่กำลังปรับปรุงชั่วคราว ไม่ได้ล่ม
header('HTTP/1.1 503 Service Temporarily Unavailable');
header('Retry-After: 3600'); // บอกให้กลับมาเช็คใหม่ใน 1 ชั่วโมง
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Updating...</title>
    <style>
        /* จัดการขอบและระยะห่างพื้นฐาน */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* จัดหน้าให้อยู่กึ่งกลางหน้าจอ (Flexbox) */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #1e1e2f; /* พื้นหลังสีกรมท่าเข้ม ดูทันสมัย */
            color: #ffffff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* กล่องคอนเทนเนอร์ตรงกลาง */
        .container {
            margin: 20px;
            text-align: center;
            padding: 50px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        p {
            font-size: 1.1rem;
            color: #a9a9b3;
            margin-bottom: 10px;
        }

        /* --- ส่วนของเอฟเฟกต์กระพริบ --- */
        .blink {
            /* เรียกใช้ keyframes ชื่อ blinker นาน 1.5 วินาที สลับไปมาเรื่อยๆ */
            animation: blinker 1.5s ease-in-out infinite alternate;
            color: #ffcc00; /* สีเหลืองทองให้ดูเด่นขึ้นตอนกระพริบ */
        }

        /* กำหนดการกระพริบ (ให้ลดความทึบแสงลง แต่ไม่ถึงกับหายไปหมดเพื่อให้อ่านง่าย) */
        @keyframes blinker {
            0% { opacity: 1; text-shadow: 0 0 10px rgba(255, 204, 0, 0.5); }
            100% { opacity: 0.2; text-shadow: none; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="icon">⚙️</div>
        <h1 class="blink">SYSTEM UPDATING</h1>
        <p>ระบบกำลังดำเนินการอัปเดตเพื่อประสบการณ์ใช้งานที่ดียิ่งขึ้น</p>
        <p>กรุณารอสักครู่...</p>
    </div>

</body>
</html>