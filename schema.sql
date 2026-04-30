-- EWU Vehicle Parking Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS ewu_parking_system;
USE ewu_parking_system;

-- Users/Login Table
CREATE TABLE Users (
    User_id INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password_hash VARCHAR(255) NOT NULL,
    Role ENUM('Admin', 'Security', 'Student', 'Faculty', 'Staff') NOT NULL
);

-- Student Table
CREATE TABLE Student (
    Student_id VARCHAR(20) PRIMARY KEY,
    Student_name VARCHAR(100) NOT NULL,
    Department VARCHAR(50) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Contact_number VARCHAR(15) NOT NULL
);

-- Faculty Table
CREATE TABLE Faculty (
    Faculty_id VARCHAR(20) PRIMARY KEY,
    Faculty_name VARCHAR(100) NOT NULL,
    Department VARCHAR(50) NOT NULL,
    E_mail VARCHAR(100) UNIQUE NOT NULL,
    Contact_Number VARCHAR(15) NOT NULL
);

-- Staff Table
CREATE TABLE Staff (
    Staff_id VARCHAR(20) PRIMARY KEY,
    Staff_name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Contact_number VARCHAR(15) NOT NULL
);

-- Security Staff Table
CREATE TABLE Security_Staff (
    Security_id INT AUTO_INCREMENT PRIMARY KEY,
    security_Name VARCHAR(100) NOT NULL,
    Shift VARCHAR(20) NOT NULL,
    Contact_Number VARCHAR(15) NOT NULL
);

-- Parking Slot Table
CREATE TABLE Parking_Slot (
    Parking_slot_id INT AUTO_INCREMENT PRIMARY KEY,
    Slot_number VARCHAR(10) UNIQUE NOT NULL,
    Slot_type ENUM('Car', 'Bike') NOT NULL,
    Is_occupied ENUM('yes', 'no') DEFAULT 'no',
    Location VARCHAR(50) NOT NULL
);

-- Vehicle Table
CREATE TABLE Vehicle (
    Vehicle_number VARCHAR(20) PRIMARY KEY,
    Vehicle_Type VARCHAR(20) NOT NULL,
    Owner_type ENUM('Student', 'Faculty', 'Staff', 'Visitor') NOT NULL,
    Owner_id VARCHAR(20)
);

-- Parking Log Table (Inferred from Payment table FK)
CREATE TABLE Parking_Log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(20) NOT NULL,
    parking_slot_id INT NOT NULL,
    entry_time DATETIME NOT NULL,
    exit_time DATETIME NULL,
    status ENUM('active', 'deactive') DEFAULT 'active',
    FOREIGN KEY (vehicle_number) REFERENCES Vehicle(Vehicle_number) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (parking_slot_id) REFERENCES Parking_Slot(Parking_slot_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Payment Table
CREATE TABLE Payment (
    Payment_id INT AUTO_INCREMENT PRIMARY KEY,
    Log_id INT NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    Payment_time DATETIME NOT NULL,
    Payment_method VARCHAR(20) NOT NULL,
    FOREIGN KEY (Log_id) REFERENCES Parking_Log(log_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Vehicle Requests Table (for approval workflow)
CREATE TABLE Vehicle_Request (
    Request_id INT AUTO_INCREMENT PRIMARY KEY,
    Vehicle_number VARCHAR(20) NOT NULL,
    Vehicle_Type VARCHAR(20) NOT NULL,
    Owner_type ENUM('Student', 'Faculty', 'Staff') NOT NULL,
    Owner_id VARCHAR(20) NOT NULL,
    Request_type ENUM('Add', 'Delete') NOT NULL,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    Request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Processed_date TIMESTAMP NULL,
    Processed_by INT NULL,
    FOREIGN KEY (Processed_by) REFERENCES Users(User_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Slot Requests Table (for approval workflow)
CREATE TABLE Slot_Request (
    Request_id INT AUTO_INCREMENT PRIMARY KEY,
    Slot_number VARCHAR(10) NOT NULL,
    Slot_type ENUM('Car', 'Bike') NOT NULL,
    Location VARCHAR(50) NOT NULL,
    Request_type ENUM('Add') NOT NULL,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    Request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Processed_date TIMESTAMP NULL,
    Processed_by INT NULL,
    FOREIGN KEY (Processed_by) REFERENCES Users(User_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Insert sample data
INSERT INTO Users (Username, Password_hash, Role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin'),
('security1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Security');

INSERT INTO Student (Student_id, Student_name, Department, Email, Contact_number) VALUES
('2021001', 'John Doe', 'CSE', 'john.doe@ewu.edu', '1234567890');

INSERT INTO Faculty (Faculty_id, Faculty_name, Department, E_mail, Contact_Number) VALUES
('F001', 'Dr. Smith', 'CSE', 'smith@ewu.edu', '0987654321');

INSERT INTO Staff (Staff_id, Staff_name, Email, Contact_number) VALUES
('S001', 'Jane Admin', 'jane@ewu.edu', '1122334455');

INSERT INTO Security_Staff (security_Name, Shift, Contact_Number) VALUES
('Bob Guard', 'Morning', '5566778899');

INSERT INTO Parking_Slot (Slot_number, Slot_type, Is_occupied, Location) VALUES
('A001', 'Car', 'no', 'Block A'),
('B001', 'Bike', 'no', 'Basement');

INSERT INTO Vehicle (Vehicle_number, Vehicle_Type, Owner_type, Owner_id) VALUES
('ABC123', 'Sedan', 'Student', '2021001');

INSERT INTO Parking_Log (vehicle_number, parking_slot_id, entry_time, status) VALUES
('ABC123', 1, NOW(), 'active');

INSERT INTO Payment (Log_id, Amount, Payment_time, Payment_method) VALUES
(1, 50.00, NOW(), 'Cash');
