CREATE DATABASE IF NOT EXISTS member_system;
USE member_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    expiration_date DATETIME
);

CREATE TABLE IF NOT EXISTS codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(255) NOT NULL UNIQUE,
    duration INT NOT NULL
);

INSERT INTO users (username, password, is_admin) VALUES ('admin', PASSWORD('admin'), 1);
