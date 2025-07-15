-- Fleet Fuel Management System Database Migration for MySQL
-- Created: December 19, 2024
-- Compatible with MySQL 5.7+ and MariaDB 10.2+

-- Create database (uncomment if needed)
-- CREATE DATABASE IF NOT EXISTS fleet_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE fleet_management;

-- Create database tables
CREATE TABLE IF NOT EXISTS vehicle_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    category_id INT NOT NULL,
    assigned_employee_id INT NULL,
    department VARCHAR(100) NOT NULL DEFAULT 'Transport',
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    current_mileage INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_employee_id) REFERENCES employees(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fuel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    date DATE NOT NULL,
    mileage INT NOT NULL,
    fuel_quantity DECIMAL(8,2) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default vehicle categories
INSERT INTO vehicle_categories (name, description) VALUES 
('Car', 'Passenger cars and sedans'),
('Motorcycle', 'Motorcycles and scooters'),
('Truck', 'Trucks and commercial vehicles')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert default departments
INSERT INTO departments (name, description) VALUES 
('Transport', 'Main transport and logistics department'),
('Operations', 'Field operations and utility services'),
('Administration', 'Administrative and management functions'),
('Maintenance', 'Vehicle and equipment maintenance')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Insert default employees
INSERT INTO employees (name, email, phone, department) VALUES 
('John Smith', 'john.smith@waterutility.com', '+254-234-567-8901', 'Transport'),
('Maria Garcia', 'maria.garcia@waterutility.com', '+254-234-567-8902', 'Transport'),
('David Johnson', 'david.johnson@waterutility.com', '+254-234-567-8903', 'Transport'),
('Sarah Wilson', 'sarah.wilson@waterutility.com', '+254-234-567-8904', 'Transport'),
('Michael Brown', 'michael.brown@waterutility.com', '+254-234-567-8905', 'Transport')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert sample vehicles
INSERT INTO vehicles (registration_number, make, model, year, category_id, assigned_employee_id, department, status, current_mileage) VALUES 
('KCA-001A', 'Toyota', 'Hilux', 2020, 3, 1, 'Transport', 'active', 45000),
('KBA-002B', 'Honda', 'CB125', 2021, 2, 2, 'Operations', 'active', 12500),
('KAA-003C', 'Toyota', 'Corolla', 2019, 1, 3, 'Administration', 'active', 67000)
ON DUPLICATE KEY UPDATE make = VALUES(make);

-- Insert sample fuel logs
INSERT INTO fuel_logs (vehicle_id, date, mileage, fuel_quantity, cost, notes) VALUES 
(1, '2024-12-15', 45000, 45.5, 7280.00, 'Regular refuel at Shell Station'),
(2, '2024-12-14', 12500, 8.2, 1312.00, 'Motorcycle fuel top-up'),
(3, '2024-12-13', 67000, 38.0, 6080.00, 'Monthly fuel for administration vehicle')
ON DUPLICATE KEY UPDATE notes = VALUES(notes);

-- Create indexes for better performance
CREATE INDEX idx_vehicles_category ON vehicles(category_id);
CREATE INDEX idx_vehicles_employee ON vehicles(assigned_employee_id);
CREATE INDEX idx_vehicles_department ON vehicles(department);
CREATE INDEX idx_fuel_logs_vehicle ON fuel_logs(vehicle_id);
CREATE INDEX idx_fuel_logs_date ON fuel_logs(date);
CREATE INDEX idx_fuel_logs_vehicle_date ON fuel_logs(vehicle_id, date);
CREATE INDEX idx_employees_department ON employees(department);
CREATE INDEX idx_departments_name ON departments(name);

-- Create views for common queries
CREATE OR REPLACE VIEW vehicle_details AS
SELECT 
    v.*,
    vc.name as category_name,
    vc.description as category_description,
    e.name as employee_name,
    e.email as employee_email,
    e.department as employee_department
FROM vehicles v
LEFT JOIN vehicle_categories vc ON v.category_id = vc.id
LEFT JOIN employees e ON v.assigned_employee_id = e.id;

CREATE OR REPLACE VIEW fuel_log_details AS
SELECT 
    fl.*,
    v.registration_number,
    v.make,
    v.model,
    v.year,
    v.department as vehicle_department,
    vc.name as category_name,
    e.name as employee_name
FROM fuel_logs fl
JOIN vehicles v ON fl.vehicle_id = v.id
JOIN vehicle_categories vc ON v.category_id = vc.id
LEFT JOIN employees e ON v.assigned_employee_id = e.id;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE GetMonthlyFuelStats(IN report_year INT, IN report_month INT)
BEGIN
    SELECT 
        COUNT(DISTINCT fl.vehicle_id) as total_vehicles,
        COUNT(fl.id) as total_logs,
        SUM(fl.fuel_quantity) as total_fuel,
        SUM(fl.cost) as total_cost,
        AVG(fl.cost / fl.fuel_quantity) as avg_cost_per_liter
    FROM fuel_logs fl
    WHERE YEAR(fl.date) = report_year 
    AND MONTH(fl.date) = report_month;
END //

CREATE PROCEDURE GetVehicleEfficiency(IN vehicle_id INT, IN days_back INT)
BEGIN
    SELECT 
        v.registration_number,
        v.make,
        v.model,
        COUNT(fl.id) as fuel_logs,
        SUM(fl.fuel_quantity) as total_fuel,
        SUM(fl.cost) as total_cost,
        MAX(fl.mileage) - MIN(fl.mileage) as distance_covered,
        CASE 
            WHEN SUM(fl.fuel_quantity) > 0 THEN 
                ROUND((MAX(fl.mileage) - MIN(fl.mileage)) / SUM(fl.fuel_quantity), 2)
            ELSE 0 
        END as efficiency_km_per_liter
    FROM vehicles v
    JOIN fuel_logs fl ON v.id = fl.vehicle_id
    WHERE v.id = vehicle_id 
    AND fl.date >= DATE_SUB(CURDATE(), INTERVAL days_back DAY)
    GROUP BY v.id, v.registration_number, v.make, v.model;
END //

DELIMITER ;

-- Grant typical permissions (adjust username as needed)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON fleet_management.* TO 'fleet_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE fleet_management.GetMonthlyFuelStats TO 'fleet_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE fleet_management.GetVehicleEfficiency TO 'fleet_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Database setup complete
-- Remember to update config.php with your database credentials:
-- $db_host = 'localhost';
-- $db_name = 'fleet_management';
-- $db_user = 'your_db_username';
-- $db_pass = 'your_db_password';