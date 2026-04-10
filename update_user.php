<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST["user_id"];
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $idstudent = $_POST["idstudent"];
    $email = $_POST["email"];
    $department = $_POST["department"];
    $academic_year = $_POST["academic_year"];
    $year_level = $_POST["year_level"];
    $userrole = $_POST["userrole"];
    $membership_status = $_POST["membership_status"];

    // ดึงชื่อไฟล์รูปเก่าจากฐานข้อมูลมาไว้ก่อน เพื่อเตรียมลบ(ถ้ามีการอัปรูปใหม่)
    $sql_old = "SELECT profile_image FROM users WHERE user_id = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $user_id);
    $stmt_old->execute();
    $res_old = $stmt_old->get_result();
    $user_data = $res_old->fetch_assoc();
    $profile_image = $user_data['profile_image'];

    // ถ้ามีการอัปโหลดรูปภาพใหม่เข้ามา
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
        $filename = $_FILES["profile_image"]["name"];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (array_key_exists(strtolower($ext), $allowed)) {
            $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
            
            // 1. กำหนดโฟลเดอร์เป้าหมายให้ชัดเจน
            $target_dir = "uploads/profiles/";
            
            // 2. เช็คว่ามีโฟลเดอร์ uploads/profiles/ หรือยัง ถ้าไม่มีให้สร้าง
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // 3. ย้ายไฟล์ไปที่ uploads/profiles/
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_dir . $new_filename)) {
                
                // 4. ลบไฟล์รูปเก่าทิ้ง (ยกเว้นรูป default.png) โดยอ้างอิง path ใหม่
                if (!empty($profile_image) && $profile_image != 'default.png' && file_exists($target_dir . $profile_image)) {
                    unlink($target_dir . $profile_image);
                }
                
                // อัปเดตตัวแปรเพื่อนำไปบันทึกลงฐานข้อมูล
                $profile_image = $new_filename;
            }
        }
    }

    // อัปเดตข้อมูลลงฐานข้อมูล
    $sql = "UPDATE users SET first_name=?, last_name=?, idstudent=?, email=?, userrole=?, department=?, academic_year=?, year_level=?, profile_image=?, membership_status=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    
    $stmt->bind_param("ssssssssssi", 
        $first_name, 
        $last_name, 
        $idstudent, 
        $email, 
        $userrole, 
        $department, 
        $academic_year, 
        $year_level, 
        $profile_image, 
        $membership_status, 
        $user_id
    );

    if ($stmt->execute()) {
        header("Location: edit_user.php?user_id=$user_id&status=success");
    } else {
        header("Location: edit_user.php?user_id=$user_id&status=error");
    }
    exit();
}
?>