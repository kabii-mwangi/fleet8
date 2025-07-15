# Fleet Fuel Management System - Static PHP Version

A complete static website for managing vehicle fleet fuel consumption with PHP and MySQL.

## Features

- **Secure Login System**: Username: `Admin`, Password: `Admin123#`
- **Vehicle Management**: Add/manage cars, motorcycles, and trucks
- **Fuel Logging**: Track fuel consumption with cost analysis
- **Employee Management**: Assign vehicles to employees by department
- **Detailed Reports**: Generate reports with date filtering
- **Department Tracking**: Transport, Operations, Administration, Maintenance
- **Currency**: All costs displayed in Kenyan Shillings (KSH)

## Installation Instructions

### 1. Upload Files
Upload all files from the `static/` folder to your web hosting domain.

### 2. Database Setup
1. Create a MySQL database named `fleet_management`
2. Import the SQL file: `fleet_management_mysql.sql`
3. Update database credentials in `config.php`:
   ```php
   $db_host = 'localhost';  // Your MySQL host
   $db_name = 'fleet_management';  // Your database name
   $db_user = 'your_username';  // Your MySQL username
   $db_pass = 'your_password';  // Your MySQL password
   ```

### 3. File Permissions
Ensure your web server can read all PHP files (644 permissions).

## Login Credentials
- **Username**: Admin
- **Password**: Admin123#

## File Structure
```
static/
├── index.php           # Login page
├── config.php          # Database configuration
├── header.php          # Navigation header
├── dashboard.php       # Main dashboard
├── vehicles.php        # Vehicle management
├── fuel-logs.php       # Fuel log tracking
├── employees.php       # Employee management
├── reports.php         # Report generation
├── add-vehicle.php     # Add new vehicle
├── add-fuel-log.php    # Add fuel entry
├── logout.php          # Logout functionality
├── styles.css          # Complete styling
└── fleet_management_mysql.sql  # Database import file
```

## Database Schema
- **vehicle_categories**: Car, Motorcycle, Truck classifications
- **employees**: Staff with department assignments
- **vehicles**: Fleet vehicles with registration and assignments
- **fuel_logs**: Fuel consumption tracking with costs

## Sample Data Included
- 3 vehicle categories
- 5 sample employees
- 3 sample vehicles
- 3 sample fuel logs

## Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web hosting with PHP/MySQL support

## Support
The system includes comprehensive error handling and user-friendly interfaces for all operations.