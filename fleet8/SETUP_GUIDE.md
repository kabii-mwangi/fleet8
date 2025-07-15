# Fleet Management System - Setup Guide

## 🚀 Quick Start

Your fleet management system has been enhanced with all the requested features! Follow these steps to deploy the updates:

## 📋 Prerequisites

- MySQL database access
- Web server with PHP support
- Existing fleet management system database

## 🗄️ Database Setup

### Step 1: Run Database Updates
Execute the enhanced database schema:

```bash
mysql -u your_username -p your_database_name < database_improvements.sql
```

Or use phpMyAdmin/MySQL Workbench to import the `database_improvements.sql` file.

### Step 2: Verify Tables
Ensure these tables exist:
- ✅ `roles` (updated with new permissions)
- ✅ `offices` (HQ and Maragua)
- ✅ `users` (enhanced user management)
- ✅ `vehicle_maintenance` (new maintenance system)
- ✅ `vehicles` (updated with status and office_id)

## 👥 User Roles & Permissions

### Current Role Structure:

#### **Super Admin**
- Full system access
- Edit/delete users
- View all offices (HQ + Maragua)
- Manage vehicle status
- Full maintenance management

#### **Admin** 
- View all offices (HQ + Maragua)
- Manage vehicle status
- Add/edit maintenance records
- Cannot delete users

#### **User**
- View-only access (no action columns)
- Office-restricted data
- Can add fuel logs
- Cannot edit/delete records

## 🔧 Configuration

### Update config.php
Ensure your database credentials are correct in `config.php`:

```php
$db_host = 'localhost:3306';
$db_name = 'your_database_name';
$db_user = 'your_username';
$db_pass = 'your_password';
```

### Default Login
- **Username**: admin
- **Password**: Admin123#
- **Role**: Super Admin

## ✨ New Features Available

### 1. **Vehicle Status Management**
- Navigate to Vehicles page
- Admin/Super Admin can change status via dropdown
- Status options: Active, Under Maintenance, Inactive

### 2. **Maintenance System**
- New "Maintenance" menu item
- Add/edit/view maintenance records
- Cost tracking and reporting
- Vehicle-specific maintenance history

### 3. **Enhanced User Management**
- Super Admin can edit user details
- Change usernames, passwords, roles
- Delete users (except own account)
- Assign users to different offices

### 4. **Multi-Office Support**
- Admin and Super Admin see both HQ and Maragua data
- Users see only their office data
- Office filtering throughout system

## 🎯 Testing Your Setup

### Test Super Admin Features:
1. Login as admin
2. Go to Users page
3. Try editing a user (should work)
4. Try deleting a user (should work)
5. Check vehicles from both offices appear

### Test Admin Features:
1. Create an admin user
2. Login as admin
3. Verify you can see both offices
4. Test vehicle status changes
5. Add maintenance records

### Test User Restrictions:
1. Create a regular user
2. Login as user
3. Verify action columns are hidden
4. Confirm office restrictions work

## 🐛 Troubleshooting

### Common Issues:

#### "Permission denied" errors
- Check user roles are properly assigned
- Verify database updates were applied
- Clear browser cache

#### "Action column still visible for users"
- Ensure role permissions were updated in database
- Check user session is refreshed (logout/login)

#### "Office filtering not working"
- Verify office_id columns exist in tables
- Check foreign key relationships
- Ensure users have proper office assignments

#### "Maintenance page not accessible"
- Verify maintenance table was created
- Check user has maintenance_view permission
- Ensure navigation link appears

## 📞 Support

If you encounter any issues:
1. Check browser console for JavaScript errors
2. Review PHP error logs
3. Verify database table structure matches schema
4. Test with different user roles
5. Ensure all files are uploaded correctly

## 🎉 You're Ready!

Your enhanced fleet management system now includes:
- ✅ Role-based access control
- ✅ Vehicle status management  
- ✅ Comprehensive maintenance tracking
- ✅ Multi-office support
- ✅ Enhanced user management
- ✅ Improved security

Enjoy your upgraded fleet management system!