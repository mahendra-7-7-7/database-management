
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$user_id = $_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete user's applications (if freelancer)
    $conn->query("DELETE FROM applications WHERE freelancer_id = $user_id");
    
    // Delete user's jobs and related applications (if client)
    $job_ids = $conn->query("SELECT id FROM jobs WHERE client_id = $user_id");
    if ($job_ids->num_rows > 0) {
        $job_ids_array = [];
        while ($row = $job_ids->fetch_assoc()) {
            $job_ids_array[] = $row['id'];
        }
        $job_ids_str = implode(',', $job_ids_array);
        $conn->query("DELETE FROM applications WHERE job_id IN ($job_ids_str)");
        $conn->query("DELETE FROM jobs WHERE client_id = $user_id");
    }
    
    // Delete payments related to user
    $conn->query("DELETE FROM payments WHERE freelancer_id = $user_id");
    
    // Finally, delete the user
    $conn->query("DELETE FROM users WHERE id = $user_id");
    
    // Commit transaction
    $conn->commit();
    
    // Logout and redirect
    session_destroy();
    header("Location: index.php?account_deleted=1");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Error deleting account: " . $e->getMessage();
    header("Location: profile.php");
    exit();
}
?>