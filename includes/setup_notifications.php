<?php
/**
 * Automatic setup script for notifications table
 * This file is included in config.php to ensure the notifications table exists
 */

// Check if notifications table exists
$check_table = "SHOW TABLES LIKE 'notifications'";
$result = $conn->query($check_table);

if ($result->num_rows == 0) {
    // Table doesn't exist, create it
    $create_table_sql = "CREATE TABLE notifications (
      id int(11) NOT NULL AUTO_INCREMENT,
      user_id int(11) NOT NULL,
      title varchar(255) NOT NULL,
      message text NOT NULL,
      type enum('Task','Payment','System') NOT NULL DEFAULT 'System',
      is_read tinyint(1) NOT NULL DEFAULT 0,
      entity_type varchar(50) DEFAULT NULL,
      entity_id int(11) DEFAULT NULL,
      created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (id),
      KEY user_id (user_id),
      KEY is_read (is_read),
      KEY entity_type_entity_id (entity_type, entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_table_sql) !== TRUE) {
        // Log error but don't stop execution
        error_log("Error creating notifications table: " . $conn->error);
    }
}
?>