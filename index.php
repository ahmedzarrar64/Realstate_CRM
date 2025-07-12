<?php
// Set base path for root directory
$base_path = '';
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent contact logs
$recent_logs = getRecentContactLogs(5);

// Get today's tasks
$today_tasks = getTodaysTasks();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
        <hr>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon text-primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number"><?php echo $stats['total_owners']; ?></div>
                <div class="stats-label">Total Property Owners</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon text-info">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stats-number"><?php echo $stats['total_clients']; ?></div>
                <div class="stats-label">Total Clients</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon text-success">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stats-number"><?php echo $stats['active_listings']; ?></div>
                <div class="stats-label">Active Property Listings</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon text-warning">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="stats-number"><?php echo $stats['todays_tasks']; ?></div>
                <div class="stats-label">Today's Follow-ups</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Contact Logs -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-history"></i> Recent Contact Logs</span>
                <a href="modules/contacts/index.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Owner</th>
                                <th>Type</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recent_logs) > 0): ?>
                                <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td><?php echo formatDateTime($log['contact_date']); ?></td>
                                        <td><?php echo htmlspecialchars($log['owner_name']); ?></td>
                                        <td>
                                            <?php
                                            $icon = '';
                                            switch ($log['contact_type']) {
                                                case 'Call':
                                                    $icon = '<i class="fas fa-phone-alt text-primary"></i>';
                                                    break;
                                                case 'WhatsApp':
                                                    $icon = '<i class="fab fa-whatsapp text-success"></i>';
                                                    break;
                                                case 'Email':
                                                    $icon = '<i class="fas fa-envelope text-info"></i>';
                                                    break;
                                                case 'Visit':
                                                    $icon = '<i class="fas fa-home text-warning"></i>';
                                                    break;
                                            }
                                            echo $icon . ' ' . $log['contact_type'];
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(substr($log['description'], 0, 50)) . (strlen($log['description']) > 50 ? '...' : ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">No recent contact logs found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Tasks -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-day"></i> Today's Follow-ups</span>
                <a href="modules/tasks/index.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($today_tasks) > 0): ?>
                    <ul class="list-group">
                        <?php foreach ($today_tasks as $task): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($task['task_description']); ?></strong>
                                    <br>
                                    <small>
                                        <?php if ($task['owner_name']): ?>
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($task['owner_name']); ?>
                                        <?php endif; ?>
                                        <?php if ($task['property_address']): ?>
                                            <i class="fas fa-building ml-2"></i> <?php echo htmlspecialchars($task['property_address']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div>
                                    <a href="modules/tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit Task">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="modules/tasks/mark_done.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Mark as Done">
                                        <i class="fas fa-check"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> No tasks scheduled for today.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="fas fa-link"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 col-md-3 mb-3">
                        <a href="modules/owners/add.php" class="btn btn-primary w-100">
                            <i class="fas fa-user-plus"></i> Add Owner
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <a href="modules/clients/add.php" class="btn btn-info w-100 text-white">
                            <i class="fas fa-user-tie"></i> Add Client
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <a href="modules/properties/add.php" class="btn btn-success w-100">
                            <i class="fas fa-plus-circle"></i> Add Property
                        </a>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <a href="modules/tasks/add.php" class="btn btn-warning w-100">
                            <i class="fas fa-clipboard-list"></i> Add Task
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>