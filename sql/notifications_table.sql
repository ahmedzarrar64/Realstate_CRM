-- SQL Script to create notifications table
-- This should be run once to set up the notifications system

-- Create notifications table if it doesn't exist
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('Task','Payment','System') NOT NULL DEFAULT 'System',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `entity_type_entity_id` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add foreign key constraint if users table exists
-- ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

-- Sample notifications for testing (optional)
-- INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `entity_type`, `entity_id`) VALUES
-- (1, 'Welcome to the system', 'Welcome to the Real Estate CRM. This is your first notification.', 'System', NULL, NULL),
-- (1, 'New task assigned', 'You have been assigned a new task: Follow up with client John Doe', 'Task', 'tasks', 1),
-- (1, 'Payment received', 'Payment of $1,500 received for property listing #123', 'Payment', 'property_sales', 1);