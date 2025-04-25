-- Create database
DROP DATABASE IF EXISTS kids_education;
CREATE DATABASE kids_education;
USE kids_education;

-- Drop old tables
DROP TABLE IF EXISTS stories;
DROP TABLE IF EXISTS colors_shapes;

-- Create new stories table for YouTube videos
CREATE TABLE stories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    youtube_url VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create new jobs table
CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_name VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    audio_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Keep existing tables
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS math_exercises (
    id INT PRIMARY KEY AUTO_INCREMENT,
    operation_type ENUM('addition', 'subtraction', 'multiplication') NOT NULL,
    number1 INT NOT NULL,
    number2 INT NOT NULL,
    correct_answer INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS countries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    flag_image VARCHAR(255) NOT NULL,
    audio_file VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS animals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    animal_name VARCHAR(100) NOT NULL,
    image_file VARCHAR(255) NOT NULL,
    audio_file VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user if not exists
INSERT IGNORE INTO users (username, password, email) 
VALUES ('admin', '$2y$10$YourHashedPasswordHere', 'admin@example.com'); 