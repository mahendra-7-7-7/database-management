<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['user_type'] === 'client' ? 'jobs.php' : 'apply_job.php'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelance Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .hero-section {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .hero-title {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .hero-description {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        
        .btn-action {
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 50px;
            margin: 0 10px;
            transition: all 0.3s;
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
        }
        
        .btn-register {
            background-color: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-register:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <h1 class="hero-title">Welcome to Freelance Platform</h1>
            <p class="hero-description">Connect with top freelancers or find your next project opportunity</p>
            <div class="d-flex justify-content-center">
                <a href="login.php" class="btn btn-action btn-login">Login</a>
                <a href="register.php" class="btn btn-action btn-register">Register</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>