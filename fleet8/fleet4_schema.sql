
-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL
);

-- Create roles table
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    permissions TEXT
);

-- Create vehicle_categories table
CREATE TABLE IF NOT EXISTS vehicle_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Create vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) NOT NULL,
    make VARCHAR(50),
    model VARCHAR(50),
    category_id INT,
    current_mileage INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (category_id) REFERENCES vehicle_categories(id)
);

-- Create fuel_logs table with new columns
CREATE TABLE IF NOT EXISTS fuel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    date DATE NOT NULL,
    mileage INT NOT NULL,
    fuel_quantity FLOAT NOT NULL,
    cost FLOAT NOT NULL,
    notes TEXT,
    detail_order VARCHAR(255),
    pump_image VARCHAR(255),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);
