<?php
// Adjust path for includes
$base_path = '../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// Get system statistics
$stats = [
    'users' => executeQuery("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'owners' => executeQuery("SELECT COUNT(*) as count FROM owners")->fetch_assoc()['count'],
    'properties' => executeQuery("SELECT COUNT(*) as count FROM properties")->fetch_assoc()['count'],
    'contacts' => executeQuery("SELECT COUNT(*) as count FROM contact_logs")->fetch_assoc()['count'],
    'tasks' => executeQuery("SELECT COUNT(*) as count FROM tasks")->fetch_assoc()['count'],
    'pending_tasks' => executeQuery("SELECT COUNT(*) as count FROM tasks WHERE status = 'Pending'")->fetch_assoc()['count'],
    'completed_tasks' => executeQuery("SELECT COUNT(*) as count FROM tasks WHERE status = 'Completed'")->fetch_assoc()['count'],
];

// Get recent user activities
$recent_activities = executeQuery("SELECT u.username, u.name, u.created_at 
                                  FROM users u 
                                  ORDER BY u.created_at DESC 
                                  LIMIT 5");
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="../index.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Main Dashboard
        </a>
    </div>
</div>

<div class="row">
    <!-- System Statistics -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Users</h6>
                        <h2 class="mb-0"><?php echo $stats['users']; ?></h2>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between bg-primary-dark">
                <a href="users.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-arrow-circle-right text-white"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Owners</h6>
                        <h2 class="mb-0"><?php echo $stats['owners']; ?></h2>
                    </div>
                    <i class="fas fa-user-tie fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between bg-success-dark">
                <a href="../modules/owners/index.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-arrow-circle-right text-white"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Properties</h6>
                        <h2 class="mb-0"><?php echo $stats['properties']; ?></h2>
                    </div>
                    <i class="fas fa-home fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between bg-info-dark">
                <a href="../modules/properties/index.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-arrow-circle-right text-white"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Tasks</h6>
                        <h2 class="mb-0"><?php echo $stats['tasks']; ?></h2>
                    </div>
                    <i class="fas fa-tasks fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between bg-warning-dark">
                <a href="../modules/tasks/index.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-arrow-circle-right text-white"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Task Status -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-chart-pie"></i> Task Status
            </div>
            <div class="card-body">
                <canvas id="taskStatusChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent User Activities -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-history"></i> Recent User Activities
            </div>
            <div class="card-body">
                <?php if ($recent_activities->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Name</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['name']); ?></td>
                                        <td><?php echo formatDate($activity['created_at']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No recent activities found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Admin Quick Links -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="fas fa-link"></i> Admin Quick Links
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="users.php" class="btn btn-outline-primary w-100 p-3">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <div>Manage Users</div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="backup.php" class="btn btn-outline-primary w-100 p-3">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <div>Database Backup</div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="../profile.php" class="btn btn-outline-primary w-100 p-3">
                            <i class="fas fa-user-circle fa-2x mb-2"></i>
                            <div>My Profile</div>
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="../logout.php" class="btn btn-outline-danger w-100 p-3">
                            <i class="fas fa-sign-out-alt fa-2x mb-2"></i>
                            <div>Logout</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for Task Status Chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Task Status Chart
        var ctx = document.getElementById('taskStatusChart').getContext('2d');
        var taskStatusChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Pending', 'Completed'],
                datasets: [{
                    data: [<?php echo $stats['pending_tasks']; ?>, <?php echo $stats['completed_tasks']; ?>],
                    backgroundColor: ['#ffc107', '#28a745'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    });
</script>

<style>
    .bg-primary-dark {
        background-color: rgba(0, 0, 0, 0.15);
    }
    .bg-success-dark {
        background-color: rgba(0, 0, 0, 0.15);
    }
    .bg-info-dark {
        background-color: rgba(0, 0, 0, 0.15);
    }
    .bg-warning-dark {
        background-color: rgba(0, 0, 0, 0.15);
    }
</style>

<?php require_once $base_path . 'includes/footer.php'; ?>