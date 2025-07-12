<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Get all contact logs with owner and property information
$sql = "SELECT cl.*, o.name as owner_name, p.address as property_address 
        FROM contact_logs cl 
        LEFT JOIN owners o ON cl.owner_id = o.id 
        LEFT JOIN properties p ON cl.property_id = p.id 
        ORDER BY cl.contact_date DESC";
$result = executeQuery($sql);

$contact_logs = [];
while ($row = $result->fetch_assoc()) {
    $contact_logs[] = $row;
}
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-address-book"></i> Contact Logs</h2>
    </div>
    <div class="col-md-4 text-end">
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Contact Log
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
                <input type="text" id="search" class="form-control" placeholder="Search by owner or description...">
            </div>
            <div class="col-md-4 mb-3">
                <label for="contact-type" class="form-label">Contact Type</label>
                <select id="contact-type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Call">Call</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Email">Email</option>
                    <option value="Visit">Visit</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label for="date-range" class="form-label">Date Range</label>
                <div class="input-group">
                    <input type="date" id="date-from" class="form-control">
                    <span class="input-group-text">to</span>
                    <input type="date" id="date-to" class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Logs Table -->
<div class="card">
    <div class="card-header">
        <i class="fas fa-table"></i> Contact History
    </div>
    <div class="card-body">
        <?php if (count($contact_logs) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="contactsTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Owner</th>
                            <th>Property</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contact_logs as $log): ?>
                            <tr>
                                <td data-sort="<?php echo strtotime($log['contact_date']); ?>">
                                    <?php echo formatDateTime($log['contact_date']); ?>
                                </td>
                                <td>
                                    <?php if ($log['owner_id']): ?>
                                        <a href="../owners/view.php?id=<?php echo $log['owner_id']; ?>">
                                            <?php echo htmlspecialchars($log['owner_name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['property_id']): ?>
                                        <a href="../properties/view.php?id=<?php echo $log['property_id']; ?>">
                                            <?php echo htmlspecialchars($log['property_address']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Not specified</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    switch ($log['contact_type']) {
                                        case 'Call':
                                            $badge_class = 'bg-primary';
                                            break;
                                        case 'WhatsApp':
                                            $badge_class = 'bg-success';
                                            break;
                                        case 'Email':
                                            $badge_class = 'bg-info';
                                            break;
                                        case 'Visit':
                                            $badge_class = 'bg-warning';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $log['contact_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $description = $log['description'];
                                    echo (strlen($description) > 100) ? 
                                        htmlspecialchars(substr($description, 0, 100)) . '...' : 
                                        htmlspecialchars($description); 
                                    ?>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $log['id']; ?>" class="btn btn-sm btn-info text-white" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $log['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?php echo $log['id']; ?>" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                No contact logs found. <a href="add.php">Add your first contact log</a>.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Table filtering
        const searchInput = document.getElementById('search');
        const contactTypeSelect = document.getElementById('contact-type');
        const dateFromInput = document.getElementById('date-from');
        const dateToInput = document.getElementById('date-to');
        const table = document.getElementById('contactsTable');
        const rows = table ? table.getElementsByTagName('tbody')[0].getElementsByTagName('tr') : [];
        
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const contactType = contactTypeSelect.value;
            const dateFrom = dateFromInput.value ? new Date(dateFromInput.value) : null;
            const dateTo = dateToInput.value ? new Date(dateToInput.value) : null;
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                
                // Get values for filtering
                const dateCell = cells[0];
                const dateStr = dateCell.textContent.trim();
                const dateParts = dateStr.split(' ')[0].split('/');
                const rowDate = new Date(dateParts[2], dateParts[1] - 1, dateParts[0]);
                
                const ownerName = cells[1].textContent.toLowerCase();
                const propertyAddress = cells[2].textContent.toLowerCase();
                const rowContactType = cells[3].textContent.trim();
                const description = cells[4].textContent.toLowerCase();
                
                // Apply filters
                const matchesSearch = ownerName.includes(searchTerm) || 
                                     propertyAddress.includes(searchTerm) || 
                                     description.includes(searchTerm);
                                     
                const matchesType = contactType === '' || rowContactType === contactType;
                
                let matchesDate = true;
                if (dateFrom && dateTo) {
                    matchesDate = rowDate >= dateFrom && rowDate <= dateTo;
                } else if (dateFrom) {
                    matchesDate = rowDate >= dateFrom;
                } else if (dateTo) {
                    matchesDate = rowDate <= dateTo;
                }
                
                row.style.display = (matchesSearch && matchesType && matchesDate) ? '' : 'none';
            }
        }
        
        // Add event listeners
        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (contactTypeSelect) contactTypeSelect.addEventListener('change', filterTable);
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
                let csvContent = 'Date,Owner,Property,Type,Description\n';
                
                visibleRows.forEach(function(row) {
                    const cells = row.getElementsByTagName('td');
                    const rowData = [
                        cells[0].textContent.trim(),
                        cells[1].textContent.trim(),
                        cells[2].textContent.trim(),
                        cells[3].textContent.trim(),
                        '"' + cells[4].textContent.trim().replace(/"/g, '""') + '"'
                    ];
                    csvContent += rowData.join(',') + '\n';
                });
                
                // Create download link
                const encodedUri = encodeURI('data:text/csv;charset=utf-8,' + csvContent);
                const link = document.createElement('a');
                link.setAttribute('href', encodedUri);
                link.setAttribute('download', 'contact_logs_export.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        }
    });
</script>

<?php require_once $base_path . 'includes/footer.php'; ?>