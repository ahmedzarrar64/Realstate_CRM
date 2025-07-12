-- Create system_settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_description) VALUES
('company_name', 'Real Estate CRM', 'Company name displayed in the application'),
('company_logo', '', 'Path to company logo image'),
('default_task_duration', '7', 'Default number of days for task duration'),
('default_follow_up_days', '3', 'Default number of days for follow-up tasks'),
('contact_email', 'contact@example.com', 'Contact email address'),
('contact_phone', '+1234567890', 'Contact phone number'),
('address', '123 Real Estate Street, City, Country', 'Company address'),
('currency_symbol', '$', 'Currency symbol used in the application'),
('date_format', 'Y-m-d', 'Date format used in the application'),
('google_maps_api_key', '', 'Google Maps API Key for mapping features');