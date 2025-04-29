<?php
session_start();
include 'db.php';

if (isset($_POST['register'])) {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];

    // First check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        // Email already exists
        $_SESSION['error'] = "This email is already registered. Please use a different email or login.";
        header("Location: register.php");
        exit();
    }

    // Email doesn't exist, proceed with registration
    $sql = "INSERT INTO users (full_name, email, password, user_type) 
            VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $full_name, $email, $password, $user_type);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful. Please login.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
        header("Location: register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .registration-form {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            background: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2 class="text-center mb-4">Create Account</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Account Type</label>
                    <select name="user_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="freelancer">Freelancer</option>
                        <option value="client">Client</option>
                    </select>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary w-100">Register</button>
                
                <div class="mt-3 text-center">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>