
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
    
    // Check if email is being changed to one that already exists
    if ($email !== $_SESSION['email']) {
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->bind_param("si", $email, $_SESSION['user_id']);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $_SESSION['error'] = "Email already in use by another account";
            header("Location: profile.php");
            exit();
        }
    }
    
    // Update query
    if ($password) {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $full_name, $email, $password, $_SESSION['user_id']);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $full_name, $email, $_SESSION['user_id']);
    }
    
    if ($stmt->execute()) {
        // Update session
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        
        $_SESSION['success'] = "Profile updated successfully";
    } else {
        $_SESSION['error'] = "Error updating profile: " . $conn->error;
    }
    
    header("Location: profile.php");
    exit();
}
?>