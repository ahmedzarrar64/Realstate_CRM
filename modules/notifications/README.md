# Notifications Module for Real Estate CRM

## Overview
The Notifications Module provides a comprehensive notification system for the Real Estate CRM, allowing users to receive and manage notifications about important events within the system.

## Features
- Real-time notification alerts
- Notification badge showing unread count
- Dropdown menu for quick access to recent notifications
- Dedicated notifications page for viewing all notifications
- Filtering options (All/Unread/Read)
- Mark as read functionality (individual and bulk)
- Delete functionality (individual and bulk)
- Toast notifications for new alerts
- Entity linking (notifications can link to tasks, properties, etc.)

## Notification Types
- **Task**: Notifications related to tasks (assignments, due dates, completions)
- **Payment**: Notifications related to payments and financial transactions
- **System**: General system notifications and announcements

## Files Structure
- `index.php` - Main notifications listing page
- `view.php` - View individual notification details
- `mark_read.php` - Mark individual notification as read
- `mark_all_read.php` - Mark all notifications as read
- `delete.php` - Delete individual notification
- `delete_all_read.php` - Delete all read notifications
- `check_new.php` - AJAX endpoint for checking new notifications
- `setup.php` - Setup script to create the notifications table

## Setup Instructions
1. Ensure you have admin access to the Real Estate CRM
2. Navigate to `/modules/notifications/setup.php` in your browser
3. The setup script will create the necessary database table
4. A welcome notification will be created automatically

## Integration Points
- **Header**: Notifications bell icon with dropdown in the top navigation
- **JavaScript**: Real-time notification checking via AJAX
- **Database**: Notifications table with relationships to users and entities

## Usage

### Creating Notifications
Use the `createNotification()` function from functions.php:

```php
createNotification(
    $userId,           // User ID to receive the notification
    'Notification Title',
    'Notification message with details',
    'Task',            // Type: 'Task', 'Payment', or 'System'
    'tasks',           // Entity type (optional)
    123                // Entity ID (optional)
);
```

### Retrieving Notifications
Use the `getUserNotifications()` function:

```php
$notifications = getUserNotifications(
    $userId,           // User ID
    10,                // Limit (number of notifications to retrieve)
    'all'              // Filter: 'all', 'read', or 'unread'
);
```

### Marking Notifications as Read
Use the `markNotificationAsRead()` or `markAllNotificationsAsRead()` functions.

### Deleting Notifications
Use the `deleteNotification()` or `deleteAllReadNotifications()` functions.

## JavaScript Integration
The notifications.js file provides client-side functionality:
- Polling for new notifications
- Updating the notification badge
- Displaying toast notifications
- Handling read status updates

## Future Enhancements
- Email notifications for important alerts
- User notification preferences
- More notification types
- Push notifications for mobile devices