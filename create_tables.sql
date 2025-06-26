-- Invoice Generator Database Schema
-- This file creates all necessary tables for the invoice generator system

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS invoice_generator;
USE invoice_generator;

-- Drop tables if they exist (for clean installation)
DROP TABLE IF EXISTS invoice_items;
DROP TABLE IF EXISTS invoices;

-- Create invoices table
CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    invoice_date DATE NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT,
    status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_client_name (client_name),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create invoice_items table
CREATE TABLE invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_product_name (product_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create settings table for system configuration
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('company_name', 'Your Company Name', 'Company name to display on invoices'),
('company_address', 'Your Company Address', 'Company address to display on invoices'),
('company_phone', '+1 (555) 123-4567', 'Company phone number'),
('company_email', 'info@yourcompany.com', 'Company email address'),
('tax_rate', '18.00', 'Default tax rate percentage'),
('currency_symbol', '$', 'Currency symbol for invoices'),
('invoice_prefix', 'INV-', 'Prefix for invoice numbers'),
('next_invoice_number', '1001', 'Next available invoice number'),
('invoice_terms', 'Payment is due within 30 days of invoice date.', 'Default terms and conditions'),
('logo_path', 'assets/logo.png', 'Path to company logo');

-- Create a view for invoice summary
CREATE VIEW invoice_summary AS
SELECT 
    i.id,
    i.invoice_number,
    i.client_name,
    i.invoice_date,
    i.subtotal,
    i.tax_amount,
    i.discount_amount,
    i.grand_total,
    i.status,
    i.created_at,
    COUNT(ii.id) as total_items
FROM invoices i
LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
GROUP BY i.id, i.invoice_number, i.client_name, i.invoice_date, i.subtotal, i.tax_amount, i.discount_amount, i.grand_total, i.status, i.created_at;

-- Create stored procedure to generate invoice number
DELIMITER //
CREATE PROCEDURE GenerateInvoiceNumber(OUT new_invoice_number VARCHAR(50))
BEGIN
    DECLARE prefix VARCHAR(10);
    DECLARE next_num INT;
    DECLARE year_suffix VARCHAR(4);
    
    -- Get prefix and next number from settings
    SELECT setting_value INTO prefix FROM settings WHERE setting_key = 'invoice_prefix';
    SELECT CAST(setting_value AS UNSIGNED) INTO next_num FROM settings WHERE setting_key = 'next_invoice_number';
    SET year_suffix = YEAR(CURDATE());
    
    -- Generate invoice number
    SET new_invoice_number = CONCAT(prefix, year_suffix, '-', LPAD(next_num, 4, '0'));
    
    -- Update next invoice number
    UPDATE settings SET setting_value = next_num + 1 WHERE setting_key = 'next_invoice_number';
END //
DELIMITER ;

-- Create stored procedure to calculate invoice totals
DELIMITER //
CREATE PROCEDURE CalculateInvoiceTotals(IN invoice_id_param INT)
BEGIN
    DECLARE subtotal_val DECIMAL(10,2);
    DECLARE tax_rate_val DECIMAL(5,2);
    DECLARE tax_amount_val DECIMAL(10,2);
    DECLARE discount_val DECIMAL(10,2);
    DECLARE grand_total_val DECIMAL(10,2);
    
    -- Calculate subtotal
    SELECT COALESCE(SUM(total_amount), 0) INTO subtotal_val 
    FROM invoice_items 
    WHERE invoice_id = invoice_id_param;
    
    -- Get tax rate
    SELECT CAST(setting_value AS DECIMAL(5,2)) INTO tax_rate_val 
    FROM settings 
    WHERE setting_key = 'tax_rate';
    
    -- Calculate tax amount
    SET tax_amount_val = subtotal_val * (tax_rate_val / 100);
    
    -- Get discount amount
    SELECT discount_amount INTO discount_val 
    FROM invoices 
    WHERE id = invoice_id_param;
    
    -- Calculate grand total
    SET grand_total_val = subtotal_val + tax_amount_val - discount_val;
    
    -- Update invoice with calculated totals
    UPDATE invoices 
    SET subtotal = subtotal_val,
        tax_amount = tax_amount_val,
        grand_total = grand_total_val
    WHERE id = invoice_id_param;
END //
DELIMITER ;

-- Create trigger to update invoice totals when items are modified
DELIMITER //
CREATE TRIGGER after_invoice_item_insert
AFTER INSERT ON invoice_items
FOR EACH ROW
BEGIN
    CALL CalculateInvoiceTotals(NEW.invoice_id);
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER after_invoice_item_update
AFTER UPDATE ON invoice_items
FOR EACH ROW
BEGIN
    CALL CalculateInvoiceTotals(NEW.invoice_id);
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER after_invoice_item_delete
AFTER DELETE ON invoice_items
FOR EACH ROW
BEGIN
    CALL CalculateInvoiceTotals(OLD.invoice_id);
END //
DELIMITER ;

-- Create indexes for better performance
CREATE INDEX idx_invoices_created_at ON invoices(created_at);
CREATE INDEX idx_invoices_status_date ON invoices(status, invoice_date);
CREATE INDEX idx_invoice_items_total ON invoice_items(total_amount);

-- Add comments for documentation
ALTER TABLE invoices COMMENT = 'Main invoices table storing invoice header information';
ALTER TABLE invoice_items COMMENT = 'Invoice line items storing individual products/services';
ALTER TABLE settings COMMENT = 'System configuration settings';

-- Grant permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON invoice_generator.* TO 'your_user'@'localhost';
-- FLUSH PRIVILEGES;
