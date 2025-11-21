CREATE DATABASE crossfit_gym;
USE crossfit_gym;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'trainer', 'member') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Members table
CREATE TABLE members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    dob DATE,
    gender ENUM('male', 'female', 'other'),
    phone VARCHAR(20),
    address TEXT,
    join_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Trainers table
CREATE TABLE trainers (
    trainer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),
    bio TEXT,
    certification VARCHAR(100),
    experience_years INT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Packages table
CREATE TABLE packages (
    package_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_months INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    features TEXT
);

-- Bookings table
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    trainer_id INT,
    package_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(package_id) ON DELETE CASCADE
);

-- Supplements table
CREATE TABLE supplements (
    supplement_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255)
);

-- Supplement Orders table
CREATE TABLE supplement_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    supplement_id INT NOT NULL,
    quantity INT NOT NULL,
    pickup_date DATE NOT NULL,
    status ENUM('pending', 'ready', 'collected') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (supplement_id) REFERENCES supplements(supplement_id) ON DELETE CASCADE
);

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@crossfit.com', 'admin');

-- Insert sample trainer user (password: trainer123)
INSERT INTO users (username, password, email, role) VALUES 
('trainer1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer@crossfit.com', 'trainer');

-- Insert sample member user (password: member123)
INSERT INTO users (username, password, email, role) VALUES 
('member1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'member@crossfit.com', 'member');

-- Complete the member and trainer records
INSERT INTO members (user_id, full_name, dob, gender, phone, address, join_date) VALUES
(3, 'John Doe', '1990-05-15', 'male', '1234567890', '123 Gym Street', CURDATE());

INSERT INTO trainers (user_id, full_name, specialization, bio, certification, experience_years) VALUES
(2, 'Jane Smith', 'Weight Training', 'Certified personal trainer with 5 years experience', 'NASM Certified', 5);

-- Sample packages
INSERT INTO packages (name, description, duration_months, price, features) VALUES
('Basic Membership', 'Access to gym facilities during standard hours', 1, 50.00, 'Gym access, Locker room'),
('Premium Membership', 'Full access including classes and personal training', 3, 180.00, 'Gym access, All classes, 1 personal training session'),
('Annual Membership', 'Best value for long-term commitment', 12, 500.00, 'Gym access, All classes, 5 personal training sessions');

-- Sample supplements
INSERT INTO supplements (name, description, price, stock, image) VALUES
('Whey Protein', 'High quality whey protein isolate', 29.99, 50, 'whey.jpg'),
('Creatine Monohydrate', 'Pure creatine for strength and performance', 19.99, 30, 'creatine.jpg'),
('BCAA Powder', 'Branch chain amino acids for recovery', 24.99, 40, 'bcaa.jpg');
-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id)
);

-- Trainer availability
CREATE TABLE trainer_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    booked BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id)
);

-- Training sessions
CREATE TABLE training_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    member_id INT NOT NULL,
    session_time DATETIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id)
);

-- Fitness advice
CREATE TABLE fitness_advice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    member_id INT NULL,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id)
);

-- Advice requests
CREATE TABLE advice_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    member_id INT NOT NULL,
    question TEXT NOT NULL,
    answered BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id)
);
ALTER TABLE users MODIFY username VARCHAR(50) NULL;
ALTER TABLE users ADD UNIQUE (username);