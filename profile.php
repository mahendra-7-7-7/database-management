<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

function getOSMGeocode($address) {
    $url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($address) . "&format=json";

    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: FreelanceHub/1.0\r\n"
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if (!empty($data)) {
        return [
            'latitude' => $data[0]['lat'],
            'longitude' => $data[0]['lon']
        ];
    } else {
        return null;
    }
}

// Get user's location automatically (if possible)
$user_location = null;
if (isset($_GET['location'])) {
    $location = trim($_GET['location']);
    if (!empty($location)) {
        $user_location = getOSMGeocode($location);
    }
}

// Get user details
$user_id = $_SESSION['user_id'];
$user_stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Initialize variables for all user types
$applications = [];
$payments = [];
$posted_jobs = [];

// Get available jobs with location filtering
$search_term = isset($_GET['search']) ? "%" . $conn->real_escape_string($_GET['search']) . "%" : "%";
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base query
$query = "SELECT * FROM jobs WHERE status = 'open' AND title LIKE ?";
$params = [$search_term];
$types = "s";

// Add location filter if provided
if (isset($_GET['location']) && !empty($_GET['location'])) {
    $query .= " AND location LIKE ?";
    $params[] = "%" . $conn->real_escape_string($_GET['location']) . "%";
    $types .= "s";
}

$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$jobs_stmt = $conn->prepare($query);
$jobs_stmt->bind_param($types, ...$params);
$jobs_stmt->execute();
$available_jobs = $jobs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user-specific data based on type
if ($_SESSION['user_type'] === 'freelancer') {
    $apps_stmt = $conn->prepare("SELECT a.*, j.title, j.client_id FROM applications a 
                                JOIN jobs j ON a.job_id = j.id 
                                WHERE a.freelancer_id = ?");
    $apps_stmt->bind_param("i", $user_id);
    $apps_stmt->execute();
    $applications = $apps_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $pay_stmt = $conn->prepare("SELECT * FROM payments WHERE freelancer_id = ? ORDER BY created_at DESC");
    $pay_stmt->bind_param("i", $user_id);
    $pay_stmt->execute();
    $payments = $pay_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // For clients
    $jobs_stmt = $conn->prepare("SELECT * FROM jobs WHERE client_id = ? ORDER BY created_at DESC");
    $jobs_stmt->bind_param("i", $user_id);
    $jobs_stmt->execute();
    $posted_jobs = $jobs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $pay_stmt = $conn->prepare("SELECT p.*, u.full_name as freelancer_name FROM payments p
                               JOIN users u ON p.freelancer_id = u.id
                               WHERE p.job_id IN (SELECT id FROM jobs WHERE client_id = ?)
                               ORDER BY p.created_at DESC");
    $pay_stmt->bind_param("i", $user_id);
    $pay_stmt->execute();
    $payments = $pay_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($_SESSION['user_type']) ?> Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
            --light-text: #6c757d;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
        }
        
        .profile-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .profile-btn:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .container {
            margin-top: 2rem;
            max-width: 1200px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .job-card {
            border-left: 4px solid var(--accent-color);
            transition: all 0.3s ease;
        }
        
        .job-card:hover {
            border-left: 4px solid var(--secondary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            padding: 0.5rem 1.5rem;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-success {
            background-color: #4cc9f0;
            border: none;
            padding: 0.5rem 1.5rem;
        }
        
        .search-bar {
            margin-bottom: 1.5rem;
        }
        
        .profile-section {
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .profile-pic {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }
        
        .stats-card {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stats-label {
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .pagination {
            justify-content: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar with Profile Button -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="profile.php">
                <h3 class="fw-bold" style="color: var(--primary-color);">FreelanceHub</h3>
            </a>
            <div class="d-flex align-items-center">
                <button class="btn profile-btn me-3" onclick="window.location.href='#profile-section'">
                    <i class="fas fa-user-circle me-2"></i>My Profile
                </button>
                <a href="logout.php" class="btn btn-outline-danger">
    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Available Jobs Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Available Jobs</h3>
                <form method="get" class="d-flex search-bar mt-3">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search jobs..." 
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    
                    <input type="text" name="location" class="form-control me-2" placeholder="Location (city, zip)" 
                           value="<?= isset($_GET['location']) ? htmlspecialchars($_GET['location']) : '' ?>">
                    
                    <select name="category" class="form-select me-2" style="max-width: 200px;">
                        <option value="">All Categories</option>
                        <option value="web" <?= (isset($_GET['category']) && $_GET['category'] == 'web') ? 'selected' : '' ?>>Web Development</option>
                        <option value="design" <?= (isset($_GET['category']) && $_GET['category'] == 'design') ? 'selected' : '' ?>>Design</option>
                        <option value="writing" <?= (isset($_GET['category']) && $_GET['category'] == 'writing') ? 'selected' : '' ?>>Writing</option>
                        <option value="marketing" <?= (isset($_GET['category']) && $_GET['category'] == 'marketing') ? 'selected' : '' ?>>Marketing</option>
                    </select>
                    
                    <button type="button" class="btn btn-outline-primary me-2" onclick="getLocation()">
                        <i class="fas fa-location-arrow"></i>
                    </button>
                    
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            <div class="card-body">
                <?php if (empty($available_jobs)): ?>
                    <div class="alert alert-info">
                        No jobs available at the moment. Check back later!
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($available_jobs as $job): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card job-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($job['title']) ?></h5>
                                        <p class="card-text text-muted"><?= htmlspecialchars(substr($job['description'], 0, 120)) ?>...</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted">Posted: <?= date('M d, Y', strtotime($job['created_at'])) ?></small>
                                                <?php if (!empty($job['location'])): ?>
                                                    <br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <a href="job_details.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-primary">View</a>
                                                <?php if ($_SESSION['user_type'] === 'freelancer'): ?>
                                                    <a href="apply_job.php?job_id=<?= $job['id'] ?>" class="btn btn-sm btn-success">Apply</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="page-item active"><a class="page-link" href="#"><?= $page ?></a></li>
                            
                            <?php if (count($available_jobs) == $limit): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Section -->
        <div id="profile-section" class="profile-section mt-5">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center mb-4">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['full_name']) ?>&background=4361ee&color=fff" 
                             alt="Profile Picture" class="profile-pic mb-3">
                        <h4><?= htmlspecialchars($user['full_name']) ?></h4>
                        <p class="text-muted"><?= ucfirst($user['user_type']) ?></p>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-number"><?= $_SESSION['user_type'] === 'freelancer' ? count($applications) : count($posted_jobs) ?></div>
                        <div class="stats-label"><?= $_SESSION['user_type'] === 'freelancer' ? 'Jobs Applied' : 'Jobs Posted' ?></div>
                    </div>
                    
                    <div class="stats-card">
                        <div class="stats-number">$<?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></div>
                        <div class="stats-label"><?= $_SESSION['user_type'] === 'freelancer' ? 'Total Earnings' : 'Total Spent' ?></div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <h4 class="mb-4">Profile Details</h4>
                    <div class="mb-4">
                        <h6>Personal Information</h6>
                        <hr>
                        <p><strong>Full Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p><strong>Account Type:</strong> <?= ucfirst($user['user_type']) ?></p>
                        <?php if (!empty($user['skills'])): ?>
                            <p><strong>Skills:</strong> <?= htmlspecialchars($user['skills']) ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($_SESSION['user_type'] === 'freelancer'): ?>
                        <div class="mb-4">
                            <h6>Recent Applications</h6>
                            <hr>
                            <?php if (!empty($applications)): ?>
                                <ul class="list-group">
                                    <?php foreach (array_slice($applications, 0, 3) as $app): ?>
                                        <li class="list-group-item border-0 ps-0">
                                            <strong><?= htmlspecialchars($app['title']) ?></strong>
                                            <span class="badge bg-<?= $app['status'] === 'pending' ? 'warning' : ($app['status'] === 'accepted' ? 'success' : 'danger') ?> float-end">
                                                <?= ucfirst($app['status']) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">Applied on <?= date('M d, Y', strtotime($app['applied_at'])) ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">No recent applications</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="mb-4">
                            <h6>Recent Jobs Posted</h6>
                            <hr>
                            <?php if (!empty($posted_jobs)): ?>
                                <ul class="list-group">
                                    <?php foreach (array_slice($posted_jobs, 0, 3) as $job): ?>
                                        <li class="list-group-item border-0 ps-0">
                                            <strong><?= htmlspecialchars($job['title']) ?></strong>
                                            <span class="badge bg-<?= $job['status'] === 'open' ? 'success' : 'secondary' ?> float-end">
                                                <?= ucfirst($job['status']) ?>
                                            </span>
                                            <br>
                                            <small class="text-muted">Posted on <?= date('M d, Y', strtotime($job['created_at'])) ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">No jobs posted yet</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex gap-2">
                        <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                        <?php if ($_SESSION['user_type'] === 'client'): ?>
                            <a href="post_job.php" class="btn btn-success">Post New Job</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}`)
                        .then(response => response.json())
                        .then(data => {
                            let location = '';
                            if (data.address) {
                                if (data.address.city) location = data.address.city;
                                else if (data.address.town) location = data.address.town;
                                else if (data.address.village) location = data.address.village;
                                else if (data.address.county) location = data.address.county;
                            }
                            document.querySelector('input[name="location"]').value = location;
                        });
                },
                function(error) {
                    alert("Unable to get your location. Please enter it manually.");
                },
                {
                    enableHighAccuracy: true,
                    timeout: 5000,
                    maximumAge: 0
                }
            );
        } else {
            alert("Geolocation is not supported by this browser. Please enter your location manually.");
        }
    }
    </script>
</body>
</html>