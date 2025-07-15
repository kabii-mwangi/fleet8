-- Products management schema for fleet management system
-- Run this to add products functionality

-- Create product categories table
CREATE TABLE IF NOT EXISTS product_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    quantity_purchased INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,
    order_number VARCHAR(100),
    supplier_name VARCHAR(255),
    notes TEXT,
    office_id INT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (office_id) REFERENCES offices(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_purchase_date (purchase_date),
    INDEX idx_category (category_id),
    INDEX idx_office (office_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default product categories
INSERT INTO product_categories (name, description) VALUES 
('Engine Oil', 'Various types of engine oils for vehicles'),
('2T Oil', 'Two-stroke engine oils'),
('Brake Fluid', 'Brake and hydraulic fluids'),
('Coolant', 'Engine coolants and antifreeze'),
('Transmission Oil', 'Transmission and gear oils'),
('Filters', 'Oil, air, and fuel filters'),
('Belts & Hoses', 'Drive belts and rubber hoses'),
('Spark Plugs', 'Ignition components'),
('Batteries', 'Vehicle batteries'),
('Tires', 'Vehicle tires and tubes'),
('Other', 'Miscellaneous automotive products')
ON DUPLICATE KEY UPDATE name = VALUES(name);