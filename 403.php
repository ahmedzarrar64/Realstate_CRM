<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Forbidden | Real Estate CRM</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            max-width: 600px;
            width: 100%;
            padding: 15px;
            text-align: center;
        }

        .error-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="card error-card">
            <div class="card-body">
                <i class="fas fa-lock error-icon"></i>
                <h1 class="display-4">403</h1>
                <h2>Access Forbidden</h2>
                <p class="lead">You don't have permission to access this resource.</p>
                <hr>
                <div class="mt-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Back to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Go to Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">&copy; <?php echo date('Y'); ?> Real Estate CRM</small>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>