<?php
namespace Medical\Core;

class Schema {
    public static function getTables() {
        return [
            'staff' => "CREATE TABLE IF NOT EXISTS staff (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL,
                specialization VARCHAR(255),
                access_code VARCHAR(10),
                permissions JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'patients' => "CREATE TABLE IF NOT EXISTS patients (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                birth_date DATE,
                phone VARCHAR(50),
                card_number VARCHAR(50),
                residence TEXT,
                treating_doctor VARCHAR(255),
                extra_info TEXT,
                history JSON,
                comments JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'procedures_directory' => "CREATE TABLE IF NOT EXISTS procedures_directory (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                duration INT,
                prep_time INT,
                work_start TIME,
                work_end TIME,
                price DECIMAL(10,2),
                is_paid TINYINT(1),
                default_cabinet VARCHAR(50),
                assigned_staff JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'appointments' => "CREATE TABLE IF NOT EXISTS appointments (
                id VARCHAR(50) PRIMARY KEY,
                patient_id VARCHAR(50),
                patient_name VARCHAR(255),
                procedure_id VARCHAR(50),
                procedure_name VARCHAR(255),
                date DATE,
                time TIME,
                cabinet_id VARCHAR(50),
                price DECIMAL(10,2),
                status VARCHAR(50),
                attended TINYINT(1) DEFAULT 0,
                attended_at DATETIME,
                performed_by VARCHAR(255),
                doctor VARCHAR(255),
                cancel_reason TEXT,
                cancelled_at DATETIME,
                cancelled_by VARCHAR(255),
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'activity_log' => "CREATE TABLE IF NOT EXISTS activity_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(50),
                user_name VARCHAR(255),
                role VARCHAR(50),
                action VARCHAR(255),
                details JSON,
                ip VARCHAR(50),
                timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'templates' => "CREATE TABLE IF NOT EXISTS templates (
                id VARCHAR(50) PRIMARY KEY,
                content MEDIUMTEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'settings' => "CREATE TABLE IF NOT EXISTS settings (
                name VARCHAR(255) PRIMARY KEY,
                value JSON
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'rooms' => "CREATE TABLE IF NOT EXISTS rooms (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(50),
                capacity INT,
                base_price DECIMAL(10,2),
                description TEXT,
                photos JSON
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'bookings' => "CREATE TABLE IF NOT EXISTS bookings (
                id VARCHAR(50) PRIMARY KEY,
                room_id VARCHAR(50),
                guest_name VARCHAR(255),
                guest_phone VARCHAR(50),
                check_in DATE,
                check_out DATE,
                total_cost DECIMAL(10,2),
                status VARCHAR(50),
                comment TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'pricing_rules' => "CREATE TABLE IF NOT EXISTS pricing_rules (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(255),
                start_date DATE,
                end_date DATE,
                type VARCHAR(50),
                value DECIMAL(10,2)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

            'announcements' => "CREATE TABLE IF NOT EXISTS announcements (
                id VARCHAR(50) PRIMARY KEY,
                title VARCHAR(255),
                content TEXT,
                author VARCHAR(255),
                expires_at DATE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
        ];
    }
}
