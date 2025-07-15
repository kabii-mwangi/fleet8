-- Fleet Management System Database Improvements
-- Date: December 2024
-- Features: User management, roles, multi-office support

-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create offices/sections table
CREATE TABLE IF NOT EXISTS offices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    address TEXT,
    phone VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    office_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add office_id to existing tables
ALTER TABLE vehicles ADD COLUMN office_id INT NOT NULL DEFAULT 1 AFTER department;
ALTER TABLE employees ADD COLUMN office_id INT NOT NULL DEFAULT 1 AFTER department;
ALTER TABLE departments ADD COLUMN office_id INT NOT NULL DEFAULT 1 AFTER description;

-- Add foreign key constraints for office_id (after inserting default offices)
-- These will be added later after default data

-- Insert default roles
INSERT INTO roles (name, description, permissions) VALUES 
('Super Admin', 'Full system access', JSON_OBJECT(
    'vehicles_view', true, 'vehicles_edit', true, 'vehicles_delete', true,
    'fuel_logs_view', true, 'fuel_logs_edit', true, 'fuel_logs_delete', true,
    'employees_view', true, 'employees_edit', true, 'employees_delete', true,
    'departments_view', true, 'departments_edit', true, 'departments_delete', true,
    'users_view', true, 'users_edit', true, 'users_delete', true,
    'reports_view', true, 'system_settings', true, 'maintenance_view', true, 
    'maintenance_edit', true, 'maintenance_delete', true, 'view_all_offices', true
)),
('Admin', 'Administrative access with multi-office view', JSON_OBJECT(
    'vehicles_view', true, 'vehicles_edit', true, 'vehicles_delete', false,
    'fuel_logs_view', true, 'fuel_logs_edit', true, 'fuel_logs_delete', false,
    'employees_view', true, 'employees_edit', true, 'employees_delete', false,
    'departments_view', true, 'departments_edit', true, 'departments_delete', false,
    'users_view', true, 'users_edit', false, 'users_delete', false,
    'reports_view', true, 'system_settings', false, 'maintenance_view', true,
    'maintenance_edit', true, 'maintenance_delete', false, 'view_all_offices', true
)),
('User', 'Basic user access - view and add fuel logs only', JSON_OBJECT(
    'vehicles_view', true, 'vehicles_edit', false, 'vehicles_delete', false,
    'fuel_logs_view', true, 'fuel_logs_edit', true, 'fuel_logs_delete', false,
    'employees_view', true, 'employees_edit', false, 'employees_delete', false,
    'departments_view', true, 'departments_edit', false, 'departments_delete', false,
    'users_view', false, 'users_edit', false, 'users_delete', false,
    'reports_view', true, 'system_settings', false, 'maintenance_view', true,
    'maintenance_edit', false, 'maintenance_delete', false, 'view_all_offices', false
))
ON DUPLICATE KEY UPDATE permissions = VALUES(permissions);

-- Insert default offices
INSERT INTO offices (name, description, address, phone) VALUES 
('HQ', 'Headquarters Office', 'Main office location', '+254-XXX-XXX-XXX'),
('Maragua', 'Maragua Office', 'Maragua branch office', '+254-XXX-XXX-XXX')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Now add foreign key constraints for office_id
ALTER TABLE vehicles ADD FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE RESTRICT;
ALTER TABLE employees ADD FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE RESTRICT;
ALTER TABLE departments ADD FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE RESTRICT;

-- Create maintenance table
CREATE TABLE IF NOT EXISTS vehicle_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    maintenance_type ENUM('scheduled', 'repair', 'emergency') DEFAULT 'scheduled',
    description TEXT NOT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    maintenance_date DATE NOT NULL,
    mileage_at_maintenance INT,
    status ENUM('planned', 'in_progress', 'completed', 'cancelled') DEFAULT 'planned',
    mechanic_name VARCHAR(255),
    notes TEXT,
    office_id INT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create default admin user (password: Admin123#)
INSERT INTO users (username, email, password_hash, full_name, role_id, office_id) VALUES 
('admin', 'admin@fleet.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 1, 1)
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Add more vehicle categories
INSERT INTO vehicle_categories (name, description) VALUES 
('Personal Car', 'Personal vehicles assigned to staff'),
('Truck', 'Heavy trucks and commercial vehicles'),
('Van', 'Utility vans and light commercial vehicles')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Create indexes for better performance
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_users_office ON users(office_id);
CREATE INDEX idx_vehicles_office ON vehicles(office_id);
CREATE INDEX idx_employees_office ON employees(office_id);
CREATE INDEX idx_departments_office ON departments(office_id);

-- Add indexes for maintenance table
CREATE INDEX idx_maintenance_vehicle ON vehicle_maintenance(vehicle_id);
CREATE INDEX idx_maintenance_date ON vehicle_maintenance(maintenance_date);
CREATE INDEX idx_maintenance_office ON vehicle_maintenance(office_id);
CREATE INDEX idx_maintenance_created_by ON vehicle_maintenance(created_by);
CREATE INDEX idx_maintenance_status ON vehicle_maintenance(status);
CREATE INDEX idx_maintenance_type ON vehicle_maintenance(maintenance_type);

-- Create updated views that include office information
CREATE OR REPLACE VIEW vehicle_details_with_office AS
SELECT 
    v.*,
    vc.name as category_name,
    vc.description as category_description,
    e.name as employee_name,
    e.email as employee_email,
    e.department as employee_department,
    o.name as office_name
FROM vehicles v
LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
LEFT JOIN employees e ON v.assigned_employee_id = e.id
LEFT JOIN offices o ON v.office_id = o.id;

CREATE OR REPLACE VIEW fuel_log_details_with_office AS
SELECT 
    fl.*,
    v.registration_number,
    v.make,
    v.model,
    v.year,
    v.department as vehicle_department,
    vc.name as category_name,
    e.name as employee_name,
    o.name as office_name
FROM fuel_logs fl
JOIN vehicles v ON fl.vehicle_id = v.id
JOIN vehicle_categories vc ON v.category_id = vc.id
LEFT JOIN employees e ON v.assigned_employee_id = e.id
LEFT JOIN offices o ON v.office_id = o.id;