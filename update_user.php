<?php
session_start();
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST["user_id"];
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $department = $_POST["department"];
    $academic_year = $_POST["academic_year"];
    $userrole = $_POST["userrole"];

    $sql_old = "SELECT profile_image FROM users WHERE user_id = ?";
    $stmt_old = $conn->prepare($sql_old);
    $stmt_old->bind_param("i", $user_id);
    $stmt_old->execute();
    $res_old = $stmt_old->get_result();
    $user_data = $res_old->fetch_assoc();
    $profile_image = $user_data['profile_image'];

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png");
        $filename = $_FILES["profile_image"]["name"];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (array_key_exists(strtolower($ext), $allowed)) {

            $new_filename = "user_" . $user_id . "_" . time() . "." . $ext;
            
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], "uploads/" . $new_filename)) {
                if (!empty($profile_image) && $profile_image != 'default.png' && file_exists("uploads/" . $profile_image)) {
                    unlink("uploads/" . $profile_image);
                }
                $profile_image = $new_filename;
            }
        }
    }

    $sql = "UPDATE users SET first_name=?, last_name=?, username=?, email=?, userrole=?, department=?, academic_year=?, profile_image=? WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $first_name, $last_name, $username, $email, $userrole, $department, $academic_year, $profile_image, $user_id);

    if ($stmt->execute()) {
        header("Location: edit_user.php?user_id=$user_id&status=success");
    } else {
        header("Location: edit_user.php?user_id=$user_id&status=error");
    }
    exit();
}
?>