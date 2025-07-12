# Real Estate CRM

A comprehensive Customer Relationship Management (CRM) system designed specifically for real estate professionals to manage property owners, properties, contact logs, and tasks.

## Features

- **Dashboard**: Overview of key metrics, recent activities, and upcoming tasks
- **Owner Management**: Add, edit, view, and delete property owners
- **Property Management**: Track properties with details like type, address, area, price, and status
- **Contact Logs**: Record all communications with owners and regarding properties
- **Task Management**: Create and track tasks with due dates and completion status
- **Search & Filter**: Find information quickly with powerful search and filter options
- **Export to CSV**: Export data for external analysis or reporting
- **Responsive Design**: Works on desktop, tablet, and mobile devices

## Installation

### Prerequisites

- PHP 7.0 or higher
- MySQL 5.6 or higher
- Web server (Apache, Nginx, etc.)
- XAMPP, WAMP, MAMP, or similar local development environment

### Setup Instructions

1. **Clone or download** the repository to your web server's document root (e.g., `htdocs` for XAMPP)

2. **Database Configuration**:
   - The application will automatically create the database and required tables on first run
   - Default database name: `realstate_crm`
   - Default database user: `root` with no password (for local development)
   - If you need to change these settings, edit the `includes/config.php` file

3. **Access the Application**:
   - Navigate to `http://localhost/realState/` in your web browser
   - You will be redirected to the login page

4. **Login Credentials**:
   - Default username: `admin`
   - Default password: `admin123`
   - These credentials are automatically created on first run

## Usage

### Dashboard

The dashboard provides an overview of your real estate business with:
- Total number of owners and properties
- Properties by status (Available, Under Negotiation, Sold)
- Recent contact logs
- Today's tasks
- Quick action buttons

### Managing Owners

- View all owners in a sortable, searchable table
- Add new owners with contact details and preferences
- View detailed owner profiles including associated properties
- Edit or delete owner information

### Managing Properties

- Track all properties with detailed information
- Associate properties with specific owners
- Update property status as it moves through your sales pipeline
- Filter properties by status, owner, or other criteria

### Contact Logs

- Record all communications with property owners
- Track inquiries about specific properties
- Filter contact logs by date, owner, property, or contact type
- View communication history for better follow-up

### Task Management

- Create tasks related to owners or properties
- Set due dates and track completion status
- Get visual indicators for overdue tasks
- Mark tasks as completed when finished

## Security

- User authentication system to protect sensitive data
- Input validation to prevent SQL injection
- Data sanitization to prevent XSS attacks

## Customization

You can customize the application by:

- Modifying the CSS in `css/style.css`
- Adding new features by extending the existing modules
- Adding new user roles and permissions

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, feature requests, or bug reports, please open an issue on the project repository.