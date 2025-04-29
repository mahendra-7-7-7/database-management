<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Get job ID from URL and validate it
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

if ($job_id <= 0) {
    $_SESSION['error'] = "Invalid job ID";
    header("Location: jobs.php");
    exit();
}

// Check if user is a freelancer
$freelancer_check = $conn->prepare("SELECT id FROM freelancers WHERE user_id = ?");
$freelancer_check->bind_param("i", $_SESSION['user_id']);
$freelancer_check->execute();
$freelancer = $freelancer_check->get_result()->fetch_assoc();

if (!$freelancer) {
    $_SESSION['error'] = "Only freelancers can apply for jobs";
    header("Location: job_details.php?id=$job_id");
    exit();
}

// Fetch job details with error handling
try {
    $stmt = $conn->prepare("SELECT j.*, u.full_name as client_name 
                          FROM jobs j
                          JOIN users u ON j.client_id = u.id
                          WHERE j.id = ?");
    $stmt->bind_param("i", $job_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
    
    if (!$job) {
        $_SESSION['error'] = "Job not found or you don't have permission to view it";
        header("Location: jobs.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching job details: " . $e->getMessage();
    header("Location: jobs.php");
    exit();
}

// Generate CSRF token for security
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job - <?= htmlspecialchars($job['title'] ?? 'Job Application') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles */
        :root {
            --primary-color: #4e73df;
            --primary-hover: #2e59d9;
            --secondary-color: #f8f9fc;
            --text-color: #5a5c69;
            --light-gray: #f8f9fa;
            --dark-gray: #d1d3e2;
        }
        
        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .job-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: var(--secondary-color);
            border-bottom: 1px solid var(--dark-gray);
            font-weight: 600;
            padding: 1.25rem 1.5rem;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .badge {
            padding: 0.5em 0.8em;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .skill-badge {
            background-color: #e9ecef;
            color: #495057;
            padding: 0.5em 0.8em;
            border-radius: 20px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
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
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            background-color: rgba(253, 126, 20, 0.1);
            color: #fd7e14;
            border: 1px solid #fd7e14;
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--dark-gray);
            border-radius: 8px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5c636a;
            border-color: #565e64;
            transform: translateY(-2px);
        }
        
        .detail-icon {
            margin-right: 8px;
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }
        
        textarea {
            min-height: 200px;
            resize: vertical;
        }
        
        @media (max-width: 768px) {
            .job-header {
                padding: 1.5rem;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        
        .form-group {
            transition: transform 0.3s ease;
        }
        
        .form-group:focus-within {
            transform: translateX(5px);
        }
        
        .file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload:hover {
            border-color: #4e73df;
            background-color: #f8f9fa;
        }
        
        #fileName {
            margin-top: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="job-header">
            <h1 class="mb-3"><i class="fas fa-briefcase detail-icon"></i> <?= htmlspecialchars($job['title'] ?? '') ?></h1>
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <p class="mb-1"><i class="fas fa-user-tie detail-icon"></i> <?= htmlspecialchars($job['client_name'] ?? 'Unknown') ?></p>
                    <?php if (!empty($job['location'])): ?>
                        <p class="mb-1"><i class="fas fa-map-marker-alt detail-icon"></i> <?= htmlspecialchars($job['location']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="budget-badge">
                        <i class="fas fa-dollar-sign"></i> 
                        Budget: $<?= number_format($job['budget'] ?? 0, 2) ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-info-circle detail-icon"></i>Job Details</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-3"><i class="fas fa-align-left detail-icon"></i>Description</h4>
                        <div class="bg-light p-3 rounded mb-4">
                            <?= nl2br(htmlspecialchars($job['description'] ?? 'No description provided')) ?>
                        </div>
                        
                        <?php if (!empty($job['skills'])): ?>
                            <h4 class="mb-3"><i class="fas fa-tools detail-icon"></i>Required Skills</h4>
                            <div class="mb-4">
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
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-paper-plane detail-icon"></i>Your Application</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                        <?php endif; ?>
                        
                        <form action="process_application.php" method="POST" id="applicationForm" enctype="multipart/form-data">
                            <input type="hidden" name="job_id" value="<?= $job_id ?>">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-4 form-group">
                                <label for="proposal" class="form-label fw-bold">Proposal</label>
                                <textarea class="form-control" id="proposal" name="proposal" rows="6" 
                                          placeholder="Explain why you're the best fit for this job..." required></textarea>
                                <small class="text-muted">Minimum 100 characters</small>
                            </div>
                            
                            <div class="mb-4 form-group">
                                <label for="bid_amount" class="form-label fw-bold">Bid Amount ($)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="bid_amount" name="bid_amount" 
                                           step="0.01" min="1" max="<?= $job['budget'] * 2 ?>" 
                                           placeholder="Enter your bid amount" required>
                                </div>
                                <small class="text-muted">Client's budget: $<?= number_format($job['budget'] ?? 0, 2) ?></small>
                            </div>
                            
                            <div class="mb-4 form-group">
                                <label for="timeline" class="form-label fw-bold">Timeline (days)</label>
                                <input type="number" class="form-control" id="timeline" name="timeline" 
                                       min="1" placeholder="Estimated days to complete" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Attachment (Optional)</label>
                                <div class="file-upload" onclick="document.getElementById('file').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x text-muted"></i>
                                    <p class="mb-1">Click to upload file</p>
                                    <p class="small text-muted">PDF, DOC, DOCX (Max 5MB)</p>
                                    <div id="fileName"></div>
                                </div>
                                <input type="file" id="file" name="file" style="display:none;" accept=".pdf,.doc,.docx">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Application
                                </button>
                                <a href="job_details.php?id=<?= $job_id ?>" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload display
        document.getElementById('file').addEventListener('change', function(e) {
            const fileName = document.getElementById('fileName');
            if (this.files.length > 0) {
                fileName.innerHTML = `<i class="fas fa-file me-2"></i>${this.files[0].name}`;
            } else {
                fileName.innerHTML = '';
            }
        });

        // Form submission handling
        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = document.getElementById('submitBtn');
            const originalBtnText = submitBtn.innerHTML;
            
            // Validate proposal length
            const proposal = document.getElementById('proposal');
            if (proposal.value.length < 100) {
                alert('Please write at least 100 characters in your proposal');
                proposal.focus();
                return;
            }
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';
            
            // Create FormData object
            const formData = new FormData(form);
            
            // Create AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action, true);
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    // Success - reload the page to show messages
                    window.location.reload();
                } else {
                    // Error handling
                    try {
                        const response = JSON.parse(xhr.responseText);
                        alert('Error: ' + (response.message || 'Submission failed'));
                    } catch (e) {
                        alert('Error: ' + xhr.responseText);
                    }
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
            };
            
            xhr.onerror = function() {
                alert('Network error occurred');
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            };
            
            xhr.send(formData);
        });

        // Character count for proposal
        document.getElementById('proposal').addEventListener('input', function() {
            const minLength = 100;
            const currentLength = this.value.length;
            const helpText = this.nextElementSibling;
            
            if (currentLength < minLength) {
                helpText.className = 'text-danger';
                helpText.textContent = `${minLength - currentLength} more characters required`;
            } else {
                helpText.className = 'text-success';
                helpText.textContent = `${currentLength} characters (minimum reached)`;
            }
        });
    </script>
</body>
</html>