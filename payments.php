<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 15px;
        }
        
        .page-header {
            color: var(--secondary-color);
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .payment-card {
            border-left: 4px solid var(--primary-color);
            border-radius: 5px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        
        .payment-card:hover {
            transform: translateX(5px);
        }
        
        .payment-amount {
            font-weight: 600;
            color: var(--success-color);
        }
        
        .payment-date {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .payment-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 3rem;
        }
        
        .btn-pay {
            background-color: var(--warning-color);
            border: none;
            font-weight: 600;
            padding: 10px 25px;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <h1 class="page-header">Payment History</h1>
        
        <?php
        $payments = $conn->query("SELECT * FROM payments ORDER BY payment_date DESC");
        while ($p = $payments->fetch_assoc()):
        ?>
        <div class="card payment-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Job #<?= $p['job_id'] ?></h5>
                        <p class="card-text">Freelancer ID: <?= $p['freelancer_id'] ?></p>
                    </div>
                    <div class="text-end">
                        <p class="payment-amount">$<?= number_format($p['amount'], 2) ?></p>
                        <p class="payment-date"><?= date('M j, Y g:i A', strtotime($p['payment_date'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        
        <div class="payment-form">
            <h3>Record New Payment</h3>
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Job ID</label>
                        <input type="number" name="job_id" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Freelancer ID</label>
                        <input type="number" name="freelancer_id" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" name="pay" class="btn btn-pay">Record Payment</button>
                </div>
            </form>
            
            <?php
            if (isset($_POST['pay'])) {
                $job_id = intval($_POST['job_id']);
                $freelancer_id = intval($_POST['freelancer_id']);
                $amount = floatval($_POST['amount']);
                
                $stmt = $conn->prepare("INSERT INTO payments (job_id, freelancer_id, amount) VALUES (?, ?, ?)");
                $stmt->bind_param("iid", $job_id, $freelancer_id, $amount);
                
                if ($stmt->execute()) {
                    echo '<div class="alert alert-success mt-3">Payment recorded successfully!</div>';
                    echo '<script>setTimeout(() => window.location.reload(), 1500);</script>';
                } else {
                    echo '<div class="alert alert-danger mt-3">Error: ' . $conn->error . '</div>';
                }
            }
            ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>