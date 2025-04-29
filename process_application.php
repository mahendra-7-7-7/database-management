<?php
session_start();
include 'db.php';

// Check if user is a freelancer
$user_check = $conn->prepare("SELECT id FROM freelancers WHERE user_id = ?");
$user_check->bind_param("i", $_SESSION['user_id']);
$user_check->execute();
$freelancer = $user_check->get_result()->fetch_assoc();

if (!$freelancer) {
    $_SESSION['error'] = "Only freelancers can apply for jobs";
    header("Location: job_details.php?id=" . ($_POST['job_id'] ?? ''));
    exit();
}

$freelancer_id = $freelancer['id'];

// Rest of your validation code...

try {
    // Check if already applied
    $check_stmt = $conn->prepare("SELECT id FROM job_applications WHERE job_id = ? AND freelancer_id = ?");
    $check_stmt->bind_param("ii", $_POST['job_id'], $freelancer_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['error'] = "You've already applied to this job";
        header("Location: job_details.php?id=" . $_POST['job_id']);
        exit();
    }

    // Insert application
    $stmt = $conn->prepare("INSERT INTO job_applications 
                          (job_id, freelancer_id, proposal, bid_amount, status, created_at) 
                          VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt->bind_param("iisd", 
        $_POST['job_id'],
        $freelancer_id,
        $_POST['proposal'],
        $_POST['bid_amount']
    );
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Application submitted successfully!";
        header("Location: job_details.php?id=" . $_POST['job_id']);
        exit();
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error submitting application: " . $e->getMessage();
    header("Location: apply_job.php?job_id=" . $_POST['job_id']);
    exit();
}
?>