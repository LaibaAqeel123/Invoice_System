-- Invoice Management System Database
-- Run this SQL in phpMyAdmin or MySQL

CREATE DATABASE IF NOT EXISTS invoice_system;
USE invoice_system;

-- Admin/User Table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com');

-- Company Settings Table (for logo and company info)
CREATE TABLE company_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    logo_path VARCHAR(255) DEFAULT 'uploads/logo.png',
    company_name VARCHAR(100) DEFAULT 'NEPTA SOLUTION',
    company_email VARCHAR(100) DEFAULT 'info@neptasolutions.co.uk',
    company_mobile VARCHAR(50) DEFAULT '07482197889',
    company_website VARCHAR(100) DEFAULT 'www.neptasolutions.co.uk',
    bank_name VARCHAR(100) DEFAULT 'Nepta Solution LTD',
    account_number VARCHAR(50) DEFAULT '01100919',
    sort_code VARCHAR(20) DEFAULT '04-00-03',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default company settings
INSERT INTO company_settings (id) VALUES (1);

-- Customers Table
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    company VARCHAR(100),
    address TEXT,
    email VARCHAR(100),
    phone VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices Table
CREATE TABLE invoices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    terms VARCHAR(100) DEFAULT 'Due on receipt',
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    total DECIMAL(10,2) DEFAULT 0.00,
    balance_due DECIMAL(10,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Invoice Items Table
CREATE TABLE invoice_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_id INT NOT NULL,
    description TEXT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
);