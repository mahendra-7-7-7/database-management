<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Initialize variables
$error = '';
$title = '';
$description = '';
$budget = '';
$skills = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $budget = floatval($_POST['budget']);
    $skills = $conn->real_escape_string($_POST['skills']);
    $client_id = $_SESSION['user_id'];
    
    // Validate inputs
    if (empty($title) || empty($description) || $budget <= 0 || empty($skills)) {
        $error = "Please fill all fields with valid values";
    } else {
        // First, verify the client exists in the clients table
        $check_client = $conn->prepare("SELECT id FROM clients WHERE id = ?");
        $check_client->bind_param("i", $client_id);
        $check_client->execute();
        $check_client->store_result();
        
        if ($check_client->num_rows === 0) {
            $error = "Error: Your account is not properly registered as a client. Please contact support.";
        } else {
            // Client exists, proceed with job posting
            $stmt = $conn->prepare("INSERT INTO jobs (client_id, title, description, budget, skills) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issds", $client_id, $title, $description, $budget, $skills);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Job posted successfully!";
                header("Location: jobs.php");
                exit();
            } else {
                $error = "Error posting job: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--dark-gray);
        }
        
        .job-form-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2.5rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        
        .job-form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }
        
        .form-header {
            color: var(--secondary-color);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 12px 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }
        
        .btn-outline-secondary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-outline-secondary:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .skill-hint {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        
        .alert {
            border-radius: 8px;
        }
        
        /* Animation for form elements */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="job-form-container">
            <h2 class="form-header text-center">
                <i class="fas fa-briefcase me-2"></i>Post a New Job
            </h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="mb-4 form-group">
                    <label for="title" class="form-label">
                        <i class="fas fa-heading me-2"></i>Job Title
                    </label>
                    <input type="text" class="form-control" id="title" name="title" 
                           value="<?= htmlspecialchars($title) ?>" required
                           placeholder="E.g., Web Developer Needed for E-commerce Site">
                </div>
                
                <div class="mb-4 form-group">
                    <label for="description" class="form-label">
                        <i class="fas fa-align-left me-2"></i>Job Description
                    </label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="6" required
                              placeholder="Describe the job responsibilities, requirements, and expectations"><?= htmlspecialchars($description) ?></textarea>
                </div>
                
                <div class="mb-4 form-group">
                    <label for="budget" class="form-label">
                        <i class="fas fa-dollar-sign me-2"></i>Budget ($)
                    </label>
                    <input type="number" step="0.01" min="0.01" class="form-control" 
                           id="budget" name="budget" value="<?= htmlspecialchars($budget) ?>" required
                           placeholder="E.g., 500.00">
                </div>
                
                <div class="mb-4 form-group">
                    <label for="skills" class="form-label">
                        <i class="fas fa-tools me-2"></i>Required Skills
                    </label>
                    <input type="text" class="form-control" id="skills" name="skills" 
                           value="<?= htmlspecialchars($skills) ?>" required
                           placeholder="E.g., PHP, MySQL, JavaScript, HTML/CSS">
                    <div class="skill-hint">Separate multiple skills with commas</div>
                </div>
                
                <div class="d-grid gap-3 form-group">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>Post Job
                    </button>
                    <a href="jobs.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add character counter for description
        const description = document.getElementById('description');
        const charCounter = document.createElement('small');
        charCounter.className = 'text-muted d-block text-end mt-1';
        description.parentNode.appendChild(charCounter);
        
        description.addEventListener('input', function() {
            charCounter.textContent = `${this.value.length}/2000 characters`;
        });
        
        // Add input formatting for skills
        const skillsInput = document.getElementById('skills');
        skillsInput.addEventListener('blur', function() {
            this.value = this.value.split(',').map(skill => skill.trim()).join(', ');
        });
    </script>
</body>
</html>