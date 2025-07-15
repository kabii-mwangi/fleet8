-- Fleet Management System - Complete Demo Data
-- Created: December 2024
-- This file contains comprehensive demo data for all tables
-- IMPORTANT: Run this AFTER importing the main database schema files

-- Use your database name (update as needed)
USE maggie_fleet;

-- Clear existing data (optional - uncomment if you want to start fresh)
-- SET FOREIGN_KEY_CHECKS = 0;
-- DELETE FROM fuel_logs;
-- DELETE FROM vehicles;
-- DELETE FROM employees;
-- DELETE FROM users;
-- DELETE FROM departments;
-- DELETE FROM offices;
-- DELETE FROM roles;
-- DELETE FROM vehicle_categories;
-- SET FOREIGN_KEY_CHECKS = 1;

-- Insert roles
INSERT INTO roles (name, description, permissions) VALUES 
('Super Admin', 'Full system access with all permissions', JSON_OBJECT(
    'vehicles_view', true, 'vehicles_edit', true, 'vehicles_delete', true,
    'fuel_logs_view', true, 'fuel_logs_edit', true, 'fuel_logs_delete', true,
    'employees_view', true, 'employees_edit', true, 'employees_delete', true,
    'departments_view', true, 'departments_edit', true, 'departments_delete', true,
    'users_view', true, 'users_edit', true, 'users_delete', true,
    'reports_view', true, 'system_settings', true
)),
('Admin', 'Administrative access with limited delete permissions', JSON_OBJECT(
    'vehicles_view', true, 'vehicles_edit', true, 'vehicles_delete', false,
    'fuel_logs_view', true, 'fuel_logs_edit', true, 'fuel_logs_delete', false,
    'employees_view', true, 'employees_edit', true, 'employees_delete', false,
    'departments_view', true, 'departments_edit', true, 'departments_delete', false,
    'users_view', true, 'users_edit', false, 'users_delete', false,
    'reports_view', true, 'system_settings', false
)),
('User', 'Basic user access - can view and add fuel logs', JSON_OBJECT(
    'vehicles_view', true, 'vehicles_edit', false, 'vehicles_delete', false,
    'fuel_logs_view', true, 'fuel_logs_edit', true, 'fuel_logs_delete', false,
    'employees_view', true, 'employees_edit', false, 'employees_delete', false,
    'departments_view', true, 'departments_edit', false, 'departments_delete', false,
    'users_view', false, 'users_edit', false, 'users_delete', false,
    'reports_view', true, 'system_settings', false
)),
('Fleet Manager', 'Fleet management with full vehicle and fuel access', JSON_OBJECT(
    'vehicles_view', true, 'vehicles_edit', true, 'vehicles_delete', true,
    'fuel_logs_view', true, 'fuel_logs_edit', true, 'fuel_logs_delete', true,
    'employees_view', true, 'employees_edit', true, 'employees_delete', false,
    'departments_view', true, 'departments_edit', false, 'departments_delete', false,
    'users_view', false, 'users_edit', false, 'users_delete', false,
    'reports_view', true, 'system_settings', false
))
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert offices
INSERT INTO offices (name, description, address, phone, status) VALUES 
('HQ', 'Headquarters Office', 'Main Office Building, Nairobi, Kenya', '+254-700-123-456', 'active'),
('Maragua', 'Maragua Branch Office', 'Maragua Town, Murang\'a County', '+254-700-123-457', 'active'),
('Thika', 'Thika Regional Office', 'Thika Industrial Area, Kiambu County', '+254-700-123-458', 'active'),
('Kiambu', 'Kiambu County Office', 'Kiambu Town Center, Kiambu County', '+254-700-123-459', 'active'),
('Nakuru', 'Nakuru Branch', 'Nakuru Town, Nakuru County', '+254-700-123-460', 'active')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert vehicle categories
INSERT INTO vehicle_categories (name, description) VALUES 
('Car', 'Standard passenger cars and sedans'),
('Motorcycle', 'Motorcycles and scooters for quick transport'),
('Truck', 'Heavy trucks and commercial vehicles'),
('Van', 'Utility vans and light commercial vehicles'),
('Pick-up', 'Pick-up trucks and double cab vehicles'),
('Bus', 'Buses and minibuses for passenger transport'),
('SUV', 'Sport utility vehicles for rough terrain')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert departments
INSERT INTO departments (name, description, office_id) VALUES 
-- HQ departments
('Transport', 'Main transport and logistics department', 1),
('Operations', 'Field operations and utility services', 1),
('Administration', 'Administrative and management functions', 1),
('Maintenance', 'Vehicle and equipment maintenance', 1),
('Finance', 'Financial management and budgeting', 1),
-- Maragua departments
('Transport', 'Maragua transport department', 2),
('Operations', 'Maragua field operations', 2),
('Customer Service', 'Maragua customer relations', 2),
-- Thika departments
('Transport', 'Thika transport department', 3),
('Operations', 'Thika field operations', 3),
('Technical', 'Technical support and maintenance', 3),
-- Kiambu departments
('Transport', 'Kiambu transport department', 4),
('Operations', 'Kiambu field operations', 4),
-- Nakuru departments
('Transport', 'Nakuru transport department', 5),
('Operations', 'Nakuru field operations', 5)
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert demo users for each role
-- Password for all users: Demo123# (hashed with PHP password_hash())
INSERT INTO users (username, email, password_hash, full_name, role_id, office_id, status) VALUES 
-- Super Admin users
('superadmin', 'superadmin@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Administrator', 1, 1, 'active'),
('admin', 'admin@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 1, 1, 'active'),

-- Admin users for each office
('admin_hq', 'admin.hq@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'HQ Administrator', 2, 1, 'active'),
('admin_maragua', 'admin.maragua@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maragua Administrator', 2, 2, 'active'),
('admin_thika', 'admin.thika@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Thika Administrator', 2, 3, 'active'),
('admin_kiambu', 'admin.kiambu@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kiambu Administrator', 2, 4, 'active'),

-- Fleet Manager users
('fleet_manager1', 'fleet.manager1@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Fleet Manager', 4, 1, 'active'),
('fleet_manager2', 'fleet.manager2@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Fleet Manager', 4, 2, 'active'),
('fleet_manager3', 'fleet.manager3@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Peter Fleet Manager', 4, 3, 'active'),

-- Regular users (drivers and operators)
('user1', 'user1@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Driver', 3, 1, 'active'),
('user2', 'user2@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary Driver', 3, 1, 'active'),
('user3', 'user3@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Peter Transport', 3, 2, 'active'),
('user4', 'user4@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Grace Operations', 3, 3, 'active'),
('user5', 'user5@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Field', 3, 4, 'active'),
('user6', 'user6@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lucy Transport', 3, 5, 'active'),
('user7', 'user7@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James Driver', 3, 1, 'active'),
('user8', 'user8@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rose Operations', 3, 2, 'active')
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Insert employees
INSERT INTO employees (name, email, phone, department, office_id) VALUES 
-- HQ employees
('John Smith', 'john.smith@fleet.com', '+254-700-111-001', 'Transport', 1),
('Maria Garcia', 'maria.garcia@fleet.com', '+254-700-111-002', 'Transport', 1),
('David Johnson', 'david.johnson@fleet.com', '+254-700-111-003', 'Operations', 1),
('Sarah Wilson', 'sarah.wilson@fleet.com', '+254-700-111-004', 'Administration', 1),
('Michael Brown', 'michael.brown@fleet.com', '+254-700-111-005', 'Maintenance', 1),
('Jennifer Davis', 'jennifer.davis@fleet.com', '+254-700-111-006', 'Transport', 1),
('Robert Miller', 'robert.miller@fleet.com', '+254-700-111-007', 'Operations', 1),
('Linda Taylor', 'linda.taylor@fleet.com', '+254-700-111-008', 'Finance', 1),
('William Anderson', 'william.anderson@fleet.com', '+254-700-111-009', 'Administration', 1),
('Elizabeth Thomas', 'elizabeth.thomas@fleet.com', '+254-700-111-010', 'Maintenance', 1),

-- Maragua employees
('Peter Kamau', 'peter.kamau@fleet.com', '+254-700-222-001', 'Transport', 2),
('Grace Wanjiku', 'grace.wanjiku@fleet.com', '+254-700-222-002', 'Transport', 2),
('James Mwangi', 'james.mwangi@fleet.com', '+254-700-222-003', 'Operations', 2),
('Lucy Njeri', 'lucy.njeri@fleet.com', '+254-700-222-004', 'Operations', 2),
('Stephen Kariuki', 'stephen.kariuki@fleet.com', '+254-700-222-005', 'Customer Service', 2),
('Ann Wangui', 'ann.wangui@fleet.com', '+254-700-222-006', 'Transport', 2),

-- Thika employees
('Samuel Kiprotich', 'samuel.kiprotich@fleet.com', '+254-700-333-001', 'Transport', 3),
('Rose Akinyi', 'rose.akinyi@fleet.com', '+254-700-333-002', 'Transport', 3),
('Francis Mutua', 'francis.mutua@fleet.com', '+254-700-333-003', 'Operations', 3),
('Catherine Muthoni', 'catherine.muthoni@fleet.com', '+254-700-333-004', 'Technical', 3),
('Paul Mbugua', 'paul.mbugua@fleet.com', '+254-700-333-005', 'Operations', 3),

-- Kiambu employees
('Daniel Ochieng', 'daniel.ochieng@fleet.com', '+254-700-444-001', 'Transport', 4),
('Mary Wambui', 'mary.wambui@fleet.com', '+254-700-444-002', 'Transport', 4),
('Joseph Gitau', 'joseph.gitau@fleet.com', '+254-700-444-003', 'Operations', 4),
('Agnes Nyambura', 'agnes.nyambura@fleet.com', '+254-700-444-004', 'Operations', 4),

-- Nakuru employees
('Simon Kiplimo', 'simon.kiplimo@fleet.com', '+254-700-555-001', 'Transport', 5),
('Joyce Wairimu', 'joyce.wairimu@fleet.com', '+254-700-555-002', 'Transport', 5),
('Vincent Rotich', 'vincent.rotich@fleet.com', '+254-700-555-003', 'Operations', 5)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert demo vehicles
INSERT INTO vehicles (registration_number, make, model, year, category_id, assigned_employee_id, department, office_id, status, current_mileage) VALUES 
-- HQ vehicles
('KCA-001A', 'Toyota', 'Hilux', 2020, 5, 1, 'Transport', 1, 'active', 45000),
('KCA-002B', 'Honda', 'CB125', 2021, 2, 2, 'Transport', 1, 'active', 12500),
('KCA-003C', 'Toyota', 'Corolla', 2019, 1, 3, 'Operations', 1, 'active', 67000),
('KCA-004D', 'Nissan', 'Navara', 2020, 5, 4, 'Administration', 1, 'active', 38000),
('KCA-005E', 'Isuzu', 'D-Max', 2021, 5, 5, 'Maintenance', 1, 'active', 28000),
('KCA-006F', 'Toyota', 'Prado', 2019, 7, 6, 'Transport', 1, 'active', 55000),
('KCA-007G', 'Mitsubishi', 'Canter', 2020, 3, 7, 'Operations', 1, 'active', 72000),
('KCA-008H', 'Toyota', 'Camry', 2021, 1, 8, 'Finance', 1, 'active', 15000),
('KCA-009I', 'Ford', 'Transit', 2019, 4, 9, 'Administration', 1, 'active', 84000),
('KCA-010J', 'Yamaha', 'FZ150', 2020, 2, 10, 'Maintenance', 1, 'active', 18000),

-- Maragua vehicles
('KBJ-101A', 'Toyota', 'Vitz', 2018, 1, 11, 'Transport', 2, 'active', 89000),
('KBJ-102B', 'Honda', 'CRF150', 2020, 2, 12, 'Transport', 2, 'active', 15000),
('KBJ-103C', 'Ford', 'Ranger', 2019, 5, 13, 'Operations', 2, 'active', 62000),
('KBJ-104D', 'Suzuki', 'Alto', 2017, 1, 14, 'Operations', 2, 'active', 95000),
('KBJ-105E', 'Nissan', 'Matatu', 2018, 6, 15, 'Customer Service', 2, 'active', 125000),
('KBJ-106F', 'Toyota', 'Probox', 2019, 4, 16, 'Transport', 2, 'active', 78000),

-- Thika vehicles
('KCE-201A', 'Toyota', 'Hiace', 2020, 4, 17, 'Transport', 3, 'active', 35000),
('KCE-202B', 'Nissan', 'Patrol', 2019, 7, 18, 'Transport', 3, 'active', 48000),
('KCE-203C', 'Isuzu', 'NPR', 2018, 3, 19, 'Operations', 3, 'active', 78000),
('KCE-204D', 'Toyota', 'Fielder', 2020, 1, 20, 'Technical', 3, 'active', 32000),
('KCE-205E', 'Mitsubishi', 'L200', 2019, 5, 21, 'Operations', 3, 'active', 54000),

-- Kiambu vehicles
('KCF-301A', 'Toyota', 'Fielder', 2019, 1, 22, 'Transport', 4, 'active', 42000),
('KCF-302B', 'Honda', 'Fit', 2020, 1, 23, 'Transport', 4, 'active', 25000),
('KCF-303C', 'Nissan', 'Hardbody', 2018, 5, 24, 'Operations', 4, 'active', 98000),
('KCF-304D', 'Suzuki', 'Ertiga', 2021, 4, 25, 'Operations', 4, 'active', 12000),

-- Nakuru vehicles
('KCG-401A', 'Toyota', 'Land Cruiser', 2020, 7, 26, 'Transport', 5, 'active', 38000),
('KCG-402B', 'Isuzu', 'Truck', 2019, 3, 27, 'Transport', 5, 'active', 145000),
('KCG-403C', 'Honda', 'Civic', 2018, 1, 28, 'Operations', 5, 'active', 87000)
ON DUPLICATE KEY UPDATE make = VALUES(make);

-- Insert comprehensive fuel logs for the last 6 months
INSERT INTO fuel_logs (vehicle_id, date, mileage, fuel_quantity, cost, notes) VALUES 
-- HQ Vehicle logs (KCA-001A to KCA-010J)
(1, '2024-12-15', 45000, 45.5, 7280.00, 'Regular refuel at Shell Station'),
(1, '2024-12-01', 44800, 42.0, 6720.00, 'Monthly fuel allowance'),
(1, '2024-11-15', 44500, 48.2, 7712.00, 'Long distance trip refuel'),
(1, '2024-11-01', 44200, 46.0, 7360.00, 'Regular monthly fuel'),
(1, '2024-10-15', 43900, 44.5, 7120.00, 'Mid-month fuel top-up'),

(2, '2024-12-14', 12500, 8.2, 1312.00, 'Motorcycle fuel top-up'),
(2, '2024-12-01', 12350, 7.5, 1200.00, 'Regular motorcycle refuel'),
(2, '2024-11-20', 12200, 8.0, 1280.00, 'Monthly motorcycle fuel'),
(2, '2024-11-05', 12050, 7.8, 1248.00, 'Mid-month motorcycle fuel'),
(2, '2024-10-20', 11900, 8.5, 1360.00, 'October motorcycle fuel'),

(3, '2024-12-13', 67000, 38.0, 6080.00, 'Operations vehicle fuel'),
(3, '2024-11-28', 66750, 35.5, 5680.00, 'Regular fuel top-up'),
(3, '2024-11-10', 66500, 40.0, 6400.00, 'Field operations fuel'),
(3, '2024-10-25', 66200, 37.0, 5920.00, 'Monthly operations fuel'),

(4, '2024-12-12', 38000, 52.0, 8320.00, 'Administration vehicle fuel'),
(4, '2024-11-25', 37600, 48.5, 7760.00, 'Regular admin fuel'),
(4, '2024-11-08', 37300, 50.0, 8000.00, 'Monthly fuel allocation'),

(5, '2024-12-10', 28000, 55.0, 8800.00, 'Maintenance vehicle fuel'),
(5, '2024-11-22', 27700, 50.0, 8000.00, 'Regular maintenance fuel'),
(5, '2024-11-05', 27400, 52.0, 8320.00, 'Monthly fuel for maintenance'),

-- Maragua Vehicle logs (KBJ-101A to KBJ-106F)
(11, '2024-12-08', 89000, 28.0, 4480.00, 'Maragua office vehicle fuel'),
(11, '2024-11-24', 88750, 30.0, 4800.00, 'Regular fuel for small car'),
(11, '2024-11-10', 88500, 32.0, 5120.00, 'Monthly fuel allocation'),
(11, '2024-10-28', 88200, 29.0, 4640.00, 'Regular monthly fuel'),

(12, '2024-12-09', 15000, 6.5, 1040.00, 'Motorcycle fuel - Maragua'),
(12, '2024-11-26', 14800, 7.0, 1120.00, 'Monthly motorcycle fuel'),
(12, '2024-11-12', 14600, 6.8, 1088.00, 'Mid-month motorcycle fuel'),

(13, '2024-12-07', 62000, 48.0, 7680.00, 'Ford Ranger fuel - Operations'),
(13, '2024-11-21', 61600, 45.0, 7200.00, 'Field operations fuel'),
(13, '2024-11-05', 61300, 47.0, 7520.00, 'Monthly operations fuel'),

-- Thika Vehicle logs (KCE-201A to KCE-205E)
(17, '2024-12-06', 35000, 65.0, 10400.00, 'Hiace van fuel - Transport'),
(17, '2024-11-19', 34500, 60.0, 9600.00, 'Monthly transport fuel'),
(17, '2024-11-03', 34000, 62.0, 9920.00, 'Regular van fuel'),

(18, '2024-12-05', 48000, 55.0, 8800.00, 'Nissan Patrol fuel'),
(18, '2024-11-18', 47600, 52.0, 8320.00, 'Monthly patrol fuel'),
(18, '2024-11-02', 47300, 54.0, 8640.00, 'Regular fuel top-up'),

(19, '2024-12-04', 78000, 85.0, 13600.00, 'Isuzu NPR truck fuel'),
(19, '2024-11-17', 77400, 80.0, 12800.00, 'Monthly truck fuel'),
(19, '2024-11-01', 77000, 82.0, 13120.00, 'Regular truck refuel'),

-- Kiambu Vehicle logs (KCF-301A to KCF-304D)
(22, '2024-12-04', 42000, 32.0, 5120.00, 'Fielder fuel - Transport'),
(22, '2024-11-17', 41700, 35.0, 5600.00, 'Monthly fuel allowance'),
(22, '2024-11-01', 41400, 33.0, 5280.00, 'Regular monthly fuel'),

(23, '2024-12-03', 25000, 28.0, 4480.00, 'Honda Fit fuel'),
(23, '2024-11-16', 24750, 30.0, 4800.00, 'Regular fuel top-up'),
(23, '2024-10-30', 24500, 29.0, 4640.00, 'Monthly fuel allocation'),

-- Nakuru Vehicle logs (KCG-401A to KCG-403C)
(26, '2024-12-02', 38000, 70.0, 11200.00, 'Land Cruiser fuel - Long distance'),
(26, '2024-11-15', 37500, 65.0, 10400.00, 'Monthly SUV fuel'),
(26, '2024-11-01', 37200, 68.0, 10880.00, 'Regular fuel for SUV'),

(27, '2024-12-01', 145000, 120.0, 19200.00, 'Isuzu truck fuel - Heavy duty'),
(27, '2024-11-14', 144200, 115.0, 18400.00, 'Monthly truck fuel'),
(27, '2024-10-29', 143500, 118.0, 18880.00, 'Regular truck refuel'),

(28, '2024-11-30', 87000, 35.0, 5600.00, 'Honda Civic fuel'),
(28, '2024-11-13', 86700, 38.0, 6080.00, 'Monthly civic fuel'),
(28, '2024-10-28', 86400, 36.0, 5760.00, 'Regular monthly fuel')
ON DUPLICATE KEY UPDATE notes = VALUES(notes);

-- Update vehicle mileage based on latest fuel logs
UPDATE vehicles v 
SET current_mileage = (
    SELECT MAX(fl.mileage) 
    FROM fuel_logs fl 
    WHERE fl.vehicle_id = v.id
)
WHERE id IN (SELECT DISTINCT vehicle_id FROM fuel_logs);

-- Display comprehensive summary
SELECT '=== FLEET MANAGEMENT DEMO DATA SUMMARY ===' as Info;

SELECT 
    'Users Created' as Category,
    COUNT(*) as Total,
    CONCAT(
        SUM(CASE WHEN role_id = 1 THEN 1 ELSE 0 END), ' Super Admin, ',
        SUM(CASE WHEN role_id = 2 THEN 1 ELSE 0 END), ' Admin, ',
        SUM(CASE WHEN role_id = 3 THEN 1 ELSE 0 END), ' User, ',
        SUM(CASE WHEN role_id = 4 THEN 1 ELSE 0 END), ' Fleet Manager'
    ) as Breakdown
FROM users;

SELECT 
    'Vehicles Created' as Category,
    COUNT(*) as Total,
    CONCAT(COUNT(*), ' vehicles across ', COUNT(DISTINCT office_id), ' offices') as Breakdown
FROM vehicles;

SELECT 
    'Fuel Logs Created' as Category,
    COUNT(*) as Total,
    CONCAT(COUNT(*), ' fuel entries for ', COUNT(DISTINCT vehicle_id), ' vehicles') as Breakdown
FROM fuel_logs;

SELECT 
    'Employees Created' as Category,
    COUNT(*) as Total,
    CONCAT(COUNT(*), ' employees across ', COUNT(DISTINCT office_id), ' offices') as Breakdown
FROM employees;

SELECT 
    'Offices Created' as Category,
    COUNT(*) as Total,
    GROUP_CONCAT(name SEPARATOR ', ') as Office_Names
FROM offices;

SELECT 
    'Departments Created' as Category,
    COUNT(*) as Total,
    CONCAT(COUNT(*), ' departments across all offices') as Breakdown
FROM departments;

-- LOGIN CREDENTIALS FOR TESTING
SELECT '=== LOGIN CREDENTIALS FOR TESTING ===' as Info;
SELECT 
    'ROLE' as Role,
    'USERNAME' as Username,
    'PASSWORD' as Password,
    'OFFICE' as Office
UNION ALL
SELECT 'Super Admin', 'admin', 'Demo123#', 'HQ'
UNION ALL
SELECT 'Admin', 'admin_hq', 'Demo123#', 'HQ'
UNION ALL
SELECT 'Admin', 'admin_maragua', 'Demo123#', 'Maragua'
UNION ALL
SELECT 'Fleet Manager', 'fleet_manager1', 'Demo123#', 'HQ'
UNION ALL
SELECT 'User', 'user1', 'Demo123#', 'HQ'
UNION ALL
SELECT 'User', 'user3', 'Demo123#', 'Maragua';

SELECT '=== DEMO DATA IMPORT COMPLETE ===' as Status;