<?php
// Adjust path for includes
$base_path = '../../';
require_once $base_path . 'includes/header.php';
require_once $base_path . 'includes/functions.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

// Get contact log data with owner and property information
$sql = "SELECT cl.*, o.name as owner_name, o.phone as owner_phone, o.email as owner_email, 
               p.address as property_address, p.property_type, p.status as property_status 
        FROM contact_logs cl 
        LEFT JOIN owners o ON cl.owner_id = o.id 
        LEFT JOIN properties p ON cl.property_id = p.id 
        WHERE cl.id = $id";
$result = executeQuery($sql);

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$contact_log = $result->fetch_assoc();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="fas fa-address-book"></i> Contact Log Details</h2>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Contact Logs</a></li>
                <li class="breadcrumb-item active">View Contact Log</li>
            </ol>
        </nav>
    </div>
    <div class="col-md-4 text-end">
        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Contact Log
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card mb-4">
            <div class="card-header">
                <?php 
                $icon = '';
                $badge_class = '';
                switch ($contact_log['contact_type']) {
                    case 'Call':
                        $icon = '<i class="fas fa-phone-alt"></i>';
                        $badge_class = 'bg-primary';
                        break;
                    case 'WhatsApp':
                        $icon = '<i class="fab fa-whatsapp"></i>';
                        $badge_class = 'bg-success';
                        break;
                    case 'Email':
                        $icon = '<i class="fas fa-envelope"></i>';
                        $badge_class = 'bg-info';
                        break;
                    case 'Visit':
                        $icon = '<i class="fas fa-home"></i>';
                        $badge_class = 'bg-warning';
                        break;
                }
                ?>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php echo $icon; ?> Contact Log <span class="badge <?php echo $badge_class; ?> ms-2"><?php echo $contact_log['contact_type']; ?></span>
                    </div>
                    <div>
                        <?php echo formatDateTime($contact_log['contact_date']); ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <h5 class="card-title">Contact Details</h5>
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($contact_log['description'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <h5 class="card-title">Owner Information</h5>
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($contact_log['owner_id']): ?>
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Name:</strong>
                                    </div>
                                    <div class="col-md-8">
                                        <a href="../owners/view.php?id=<?php echo $contact_log['owner_id']; ?>">
                                            <?php echo htmlspecialchars($contact_log['owner_name']); ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <strong>Phone:</strong>
                                    </div>
                                    <div class="col-md-8">
                                        <a href="tel:<?php echo htmlspecialchars($contact_log['owner_phone']); ?>">
                                            <?php echo htmlspecialchars($contact_log['owner_phone']); ?>
                                        </a>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <strong>Email:</strong>
                                    </div>
                                    <div class="col-md-8">
                                        <a href="mailto:<?php echo htmlspecialchars($contact_log['owner_email']); ?>">
                                            <?php echo htmlspecialchars($contact_log['owner_email']); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    Owner information not available.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($contact_log['property_id']): ?>
                <h5 class="card-title">Property Information</h5>
                <div class="mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Address:</strong>
                                </div>
                                <div class="col-md-8">
                                    <a href="../properties/view.php?id=<?php echo $contact_log['property_id']; ?>">
                                        <?php echo htmlspecialchars($contact_log['property_address']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <strong>Property Type:</strong>
                                </div>
                                <div class="col-md-8">
                                    <?php echo htmlspecialchars($contact_log['property_type']); ?>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <strong>Status:</strong>
                                </div>
                                <div class="col-md-8">
                                    <?php 
                                    $status_class = '';
                                    switch ($contact_log['property_status']) {
                                        case 'Available':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'Under Negotiation':
                                            $status_class = 'bg-info';
                                            break;
                                        case 'Sold':
                                            $status_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo $contact_log['property_status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <div>
                        <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete.php?id=<?php echo $id; ?>" class="btn btn-danger">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once $base_path . 'includes/footer.php'; ?>