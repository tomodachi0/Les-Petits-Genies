-- Create database
CREATE DATABASE IF NOT EXISTS children_edu;
USE children_edu;

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create flags table
CREATE TABLE IF NOT EXISTS flags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(100) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    audio_path VARCHAR(255) NOT NULL
);

-- Create animals table
CREATE TABLE IF NOT EXISTS animals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    animal_name VARCHAR(100) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    audio_path VARCHAR(255) NOT NULL
);

-- Create jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(100) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    audio_path VARCHAR(255) NOT NULL
);

-- Create stories table
CREATE TABLE IF NOT EXISTS stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    youtube_link VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255) NOT NULL
);

-- Insert default admin user
INSERT INTO admin_users (username, password, email) 
VALUES ('admin', 'admin123', 'admin@example.com')
ON DUPLICATE KEY UPDATE id=id;

-- Insert sample users
INSERT INTO users (username, password, created_at) VALUES
('demo', '$2y$10$HvMaJ5.XdZQJYXCGO/BH4.KqzQvTEcsEe/F9p9HVNQGB.ZM.EUE.2', NOW()); -- password: demo123

-- Insert sample flags
INSERT INTO flags (country_name, image_path, audio_path) VALUES
('United States', 'images/flags/usa.jpg', 'audio/flags/usa.mp3'),
('United Kingdom', 'images/flags/uk.jpg', 'audio/flags/uk.mp3'),
('France', 'images/flags/france.jpg', 'audio/flags/france.mp3'),
('Japan', 'images/flags/japan.jpg', 'audio/flags/japan.mp3'),
('Brazil', 'images/flags/brazil.jpg', 'audio/flags/brazil.mp3');

-- Insert sample animals
INSERT INTO animals (animal_name, image_path, audio_path) VALUES
('Lion', 'images/animals/lion.jpg', 'audio/animals/lion.mp3'),
('Elephant', 'images/animals/elephant.jpg', 'audio/animals/elephant.mp3'),
('Giraffe', 'images/animals/giraffe.jpg', 'audio/animals/giraffe.mp3'),
('Dolphin', 'images/animals/dolphin.jpg', 'audio/animals/dolphin.mp3'),
('Penguin', 'images/animals/penguin.jpg', 'audio/animals/penguin.mp3');

-- Insert sample jobs
INSERT INTO jobs (job_name, image_path, audio_path) VALUES
('Doctor', 'images/jobs/doctor.jpg', 'audio/jobs/doctor.mp3'),
('Teacher', 'images/jobs/teacher.jpg', 'audio/jobs/teacher.mp3'),
('Firefighter', 'images/jobs/firefighter.jpg', 'audio/jobs/firefighter.mp3'),
('Astronaut', 'images/jobs/astronaut.jpg', 'audio/jobs/astronaut.mp3'),
('Chef', 'images/jobs/chef.jpg', 'audio/jobs/chef.mp3');

-- Insert sample stories
INSERT INTO stories (title, description, youtube_link, thumbnail_path) VALUES
('The Three Little Pigs', 'A classic tale about three pigs and their houses made of different materials.', 'https://www.youtube.com/embed/QLR2pLUsl-Y', 'images/stories/three_pigs.jpg'),
('Little Red Riding Hood', 'The story of a young girl who encounters a wolf on her way to visit her grandmother.', 'https://www.youtube.com/embed/0W86K1jBJFI', 'images/stories/red_riding_hood.jpg'),
('Cinderella', 'A young woman living in unfortunate circumstances finds true love with the help of a fairy godmother.', 'https://www.youtube.com/embed/F_hbG4hHMAA', 'images/stories/cinderella.jpg'),
('Jack and the Beanstalk', 'A boy climbs a magical beanstalk and discovers a giant\'s castle in the clouds.', 'https://www.youtube.com/embed/oyYh43hxSts', 'images/stories/beanstalk.jpg'),
('The Ugly Duckling', 'A duckling who is rejected by others because of his appearance, but later grows into a beautiful swan.', 'https://www.youtube.com/embed/m6YY9I9nJA0', 'images/stories/ugly_duckling.jpg'); 