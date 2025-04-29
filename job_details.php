<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Get job ID from URL
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch job details
$stmt = $conn->prepare("SELECT j.*, u.full_name as client_name 
                       FROM jobs j
                       JOIN users u ON j.client_id = u.id
                       WHERE j.id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    $_SESSION['error'] = "Job not found";
    header("Location: jobs.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details - <?= htmlspecialchars($job['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-color: #5a5c69;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .job-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .job-details-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .job-details-card:hover {
            transform: translateY(-5px);
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .skill-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 8px 12px;
            border-radius: 20px;
            margin-right: 8px;
            margin-bottom: 8px;
            display: inline-block;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .skill-badge:hover {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .budget-badge {
            font-size: 1.1rem;
            padding: 10px 20px;
            border-radius: 30px;
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .action-btns .btn {
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            margin-right: 15px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .description-text {
            line-height: 1.8;
            color: #4a4a4a;
        }
        
        .detail-icon {
            margin-right: 8px;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .job-header {
                padding: 1.5rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="job-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1 class="mb-3"><?= htmlspecialchars($job['title']) ?></h1>
                    <p class="mb-0">
                        <i class="fas fa-user-tie detail-icon"></i> 
                        Posted by: <?= htmlspecialchars($job['client_name']) ?>
                    </p>
                </div>
                <div class="mt-3 mt-md-0">
                    <span class="budget-badge">
                        <i class="fas fa-dollar-sign"></i> 
                        Budget: $<?= number_format($job['budget'], 2) ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($job['location'])): ?>
                <div class="mt-3 d-flex align-items-center">
                    <i class="fas fa-map-marker-alt detail-icon"></i>
                    <span><?= htmlspecialchars($job['location']) ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card job-details-card mb-4">
            <div class="card-body">
                <h3 class="mb-4"><i class="fas fa-align-left detail-icon"></i>Description</h3>
                <div class="description-text">
                    <?= nl2br(htmlspecialchars($job['description'])) ?>
                </div>
                
                <?php if (!empty($job['skills'])): ?>
                    <div class="mt-5">
                        <h4><i class="fas fa-tools detail-icon"></i>Required Skills</h4>
                        <div class="mt-3">
                            <?php 
                            $skills = explode(',', $job['skills']);
                            foreach ($skills as $skill): 
                                $trimmed_skill = trim($skill);
                                if (!empty($trimmed_skill)):
                            ?>
                                <span class="skill-badge">
                                    <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($trimmed_skill) ?>
                                </span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="action-btns d-flex flex-wrap">
            <?php if ($_SESSION['user_type'] === 'freelancer'): ?>
                <a href="apply_job.php?job_id=<?= $job['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Apply Now
                </a>
            <?php endif; ?>
            <a href="jobs.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Jobs
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>