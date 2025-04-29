
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Handle search
$search_query = "";
$location_filter = "";
$skills_filter = "";
// Update query to join with freelancers table
$query = "SELECT j.*, u.full_name as client_name, 
          (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
          FROM jobs j
          JOIN users u ON j.client_id = u.id
          WHERE j.status = 'open'";
if (isset($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $location_filter = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : "";
    $skills_filter = isset($_GET['skills']) ? $conn->real_escape_string($_GET['skills']) : "";
}

// Display any errors
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .job-card {
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .job-title {
            color: #3498db;
        }
        .budget-badge {
            font-size: 1rem;
            background-color: #2ecc71;
        }
        .search-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Available Jobs</h1>
        
        <?php if ($_SESSION['user_type'] === 'client'): ?>
            <a href="post_job.php" class="btn btn-primary mb-4">Post New Job</a>
        <?php endif; ?>
        
        <div class="search-card">
            <form method="get">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search jobs..." value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="location" class="form-control" placeholder="Location" value="<?= htmlspecialchars($location_filter) ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="skills" class="form-control" placeholder="Skills (comma separated)" value="<?= htmlspecialchars($skills_filter) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="row">
            <?php
            $query = "SELECT j.*, u.full_name as client_name 
                      FROM jobs j
                      JOIN users u ON j.client_id = u.id
                      WHERE j.status = 'open'";
            
            // Add search conditions
            if (!empty($search_query)) {
                $query .= " AND (j.title LIKE '%$search_query%' OR j.description LIKE '%$search_query%')";
            }
            
            if (!empty($location_filter)) {
                $query .= " AND j.location LIKE '%$location_filter%'";
            }
            
            if (!empty($skills_filter)) {
                $skills = explode(',', $skills_filter);
                $skills_condition = [];
                foreach ($skills as $skill) {
                    $trimmed_skill = trim($skill);
                    if (!empty($trimmed_skill)) {
                        $skills_condition[] = "j.skills LIKE '%$trimmed_skill%'";
                    }
                }
                if (!empty($skills_condition)) {
                    $query .= " AND (" . implode(' OR ', $skills_condition) . ")";
                }
            }
            
            $result = $conn->query($query);
            
            if ($result->num_rows > 0):
                while ($job = $result->fetch_assoc()):
            ?>
                <div class="col-md-6">
                    <div class="card job-card h-100">
                        <div class="card-body">
                            <h3 class="job-title"><?= htmlspecialchars($job['title']) ?></h3>
                            <p class="text-muted mb-2">Posted by: <?= htmlspecialchars($job['client_name']) ?></p>
                            <?php if (!empty($job['location'])): ?>
                                <p class="text-muted mb-2"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></p>
                            <?php endif; ?>
                            <p class="card-text"><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                            
                            <?php if (!empty($job['skills'])): ?>
                                <div class="mb-3">
                                    <?php 
                                    $skills = explode(',', $job['skills']);
                                    foreach ($skills as $skill): 
                                        $trimmed_skill = trim($skill);
                                        if (!empty($trimmed_skill)):
                                    ?>
                                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($trimmed_skill) ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="badge budget-badge">
                                    $<?= number_format($job['budget'], 2) ?>
                                </span>
                                
                                <?php if ($_SESSION['user_type'] === 'freelancer'): ?>
                                    <a href="apply_job.php?job_id=<?= $job['id'] ?>" 
                                       class="btn btn-success">
                                        Apply Now
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Your posted job</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer text-muted">
                            Posted on <?= date('M d, Y', strtotime($job['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <div class="col-12">
                    <div class="alert alert-info">No jobs available matching your criteria.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>