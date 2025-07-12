<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get all tasks with owner and property information
$sql = "SELECT t.*, o.name as owner_name, p.address as property_address 
        FROM tasks t 
        LEFT JOIN owners o ON t.owner_id = o.id 
        LEFT JOIN properties p ON t.property_id = p.id 
        ORDER BY t.due_date ASC";
$result = executeQuery($sql);

$tasks = [];
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-tasks"></i> Tasks</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Task
        </a>
        <button id="export-btn" class="btn btn-success">
            <i class="fas fa-file-export"></i> Export CSV
        </button>
    </div>
</div>

<!-- Search and Filter -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-filter"></i> Search & Filter
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" id="search" class="form-control" placeholder="Search by description, owner or property...">
            </div>
            <div class="col-md-4 mb-3">
                <label for="status-filter" class="form-label">Status</label>
                <select id="status-filter" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="date-range" class="form-label">Due Date Range</label>
                <div class="input-group">
                    <input type="date" id="date-from" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="date-to" class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tasks Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-table"></i> Task List
    </div>
    <div class="card-body">
        <?php if (count($tasks) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="tasksTable">
                    <thead>
                        <tr>
                            <th>Due Date</th>
                            <th>Description</th>
                            <th>Owner</th>
                            <th>Property</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <?php 
                            // Determine if task is overdue
                            $is_overdue = ($task['status'] === 'Pending' && strtotime($task['due_date']) < time());
                            $row_class = $is_overdue ? 'table-danger' : '';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td data-sort="<?php echo strtotime($task['due_date']); ?>">
                                    <?php echo formatDate($task['due_date']); ?>
                                    <?php if ($is_overdue): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($task['task_description']); ?></td>
                                <td>
                                    <?php if ($task['owner_id']): ?>
                                        <a href="../owners/view.php?id=<?php echo $task['owner_id']; ?>">
                                            <?php echo htmlspecialchars($task['owner_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($task['property_id']): ?>
                                        <a href="../properties/view.php?id=<?php echo $task['property_id']; ?>">
                                            <?php echo htmlspecialchars($task['property_address']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($task['status'] === 'Pending') ? 'bg-warning' : 'bg-success'; ?>">
                                        <?php echo $task['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info text-white" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <?php if ($task['status'] === 'Pending'): ?>
                                        <a href="complete.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-success" title="Mark as Completed">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No tasks found. <a href="add.php">Add your first task</a>.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Table filtering
        const searchInput = document.getElementById('search');
        const statusFilter = document.getElementById('status-filter');
        const dateFromInput = document.getElementById('date-from');
        const dateToInput = document.getElementById('date-to');
        const table = document.getElementById('tasksTable');
        const rows = table ? table.getElementsByTagName('tbody')[0].getElementsByTagName('tr') : [];
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const status = statusFilter.value;
            const dateFrom = dateFromInput.value ? new Date(dateFromInput.value) : null;
            const dateTo = dateToInput.value ? new Date(dateToInput.value) : null;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                
                // Get values for filtering
                const dateCell = cells[0];
                const dateStr = dateCell.textContent.trim().split(' ')[0];
                const dateParts = dateStr.split('/');
                const rowDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
                
                const description = cells[1].textContent.toLowerCase();
                const owner = cells[2].textContent.toLowerCase();
                const property = cells[3].textContent.toLowerCase();
                const rowStatus = cells[4].textContent.trim();
                
                // Apply filters
                const matchesSearch = description.includes(searchTerm) || 
                                     owner.includes(searchTerm) || 
                                     property.includes(searchTerm);
                                     
                const matchesStatus = status === '' || rowStatus.includes(status);
                
                let matchesDate = true;
                if (dateFrom && dateTo) {
                    matchesDate = rowDate >= dateFrom && rowDate <= dateTo;
                } else if (dateFrom) {
                    matchesDate = rowDate >= dateFrom;
                } else if (dateTo) {
                    matchesDate = rowDate <= dateTo;
                }
                
                row.style.display = (matchesSearch && matchesStatus && matchesDate) ? '' : 'none';
            }
        }
        
        // Add event listeners
        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (statusFilter) statusFilter.addEventListener('change', filterTable);
        if (dateFromInput) dateFromInput.addEventListener('change', filterTable);
        if (dateToInput) dateToInput.addEventListener('change', filterTable);
        
        // Export to CSV
        const exportBtn = document.getElementById('export-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                // Get visible rows
                const visibleRows = [];
                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].style.display !== 'none') {
                        visibleRows.push(rows[i]);
                    }
                }
                
                // Create CSV content
                let csvContent = 'Due Date,Description,Owner,Property,Status\n';
                
                visibleRows.forEach(function(row) {
                    const cells = row.getElementsByTagName('td');
                    const rowData = [
                        cells[0].textContent.trim().split(' ')[0], // Due date without overdue badge
                        '"' + cells[1].textContent.trim().replace(/"/g, '""') + '"',
                        cells[2].textContent.trim(),
                        cells[3].textContent.trim(),
                        cells[4].textContent.trim()
                    ];
                    csvContent += rowData.join(',') + '\n';
                });
                
                // Create download link
                const encodedUri = encodeURI('data:text/csv;charset=utf-8,' + csvContent);
                const link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', 'tasks_export.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>