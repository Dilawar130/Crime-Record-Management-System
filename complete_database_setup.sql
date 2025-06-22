-- Pakistani Crime Management System - Complete Database Setup for XAMPP
-- Run this file in phpMyAdmin or MySQL command line
-- This creates the complete database with all tables and comprehensive sample data

-- Create database
CREATE DATABASE IF NOT EXISTS crime_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE crime_management;

-- Drop existing tables (if any) in correct order to handle foreign key constraints
DROP TABLE IF EXISTS patrol_notes;
DROP TABLE IF EXISTS response_information;
DROP TABLE IF EXISTS police_information;
DROP TABLE IF EXISTS cases;
DROP TABLE IF EXISTS evidence;
DROP TABLE IF EXISTS criminal;
DROP TABLE IF EXISTS crime_information;
DROP TABLE IF EXISTS fir;
DROP TABLE IF EXISTS crime_category;
DROP TABLE IF EXISTS resident_credential;
DROP TABLE IF EXISTS resident;
DROP TABLE IF EXISTS police_station;
DROP TABLE IF EXISTS location;
DROP TABLE IF EXISTS user;

-- Create Location table
CREATE TABLE location (
    LocationID INT PRIMARY KEY AUTO_INCREMENT,
    AreaName VARCHAR(100) NOT NULL,
    City VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create User table (Police Officers)
CREATE TABLE user (
    ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Role ENUM('admin', 'ig', 'sp', 'dsp', 'asi', 'constable') NOT NULL,
    CNIC VARCHAR(15) UNIQUE NOT NULL,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Police Station table
CREATE TABLE police_station (
    StationID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    LocationID INT NOT NULL,
    Phone VARCHAR(20),
    Address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (LocationID) REFERENCES location(LocationID) ON DELETE CASCADE
);

-- Create Resident table (Public Users)
CREATE TABLE resident (
    ResidentID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    CNIC VARCHAR(15) UNIQUE NOT NULL,
    Address TEXT NOT NULL,
    Contact VARCHAR(20) NOT NULL,
    Email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Resident Credential table
CREATE TABLE resident_credential (
    CredentialID INT PRIMARY KEY AUTO_INCREMENT,
    ResidentID INT NOT NULL,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ResidentID) REFERENCES resident(ResidentID) ON DELETE CASCADE
);

-- Create Crime Category table
CREATE TABLE crime_category (
    CategoryID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(50) NOT NULL,
    Description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create FIR (First Information Report) table
CREATE TABLE fir (
    FIRID INT PRIMARY KEY AUTO_INCREMENT,
    Title VARCHAR(200) NOT NULL,
    Description TEXT NOT NULL,
    Date DATE NOT NULL,
    FiledBy INT NOT NULL,
    Status ENUM('submitted', 'under_review', 'investigating', 'resolved', 'closed') DEFAULT 'submitted',
    PoliceStationID INT NOT NULL,
    ReferenceNumber VARCHAR(20) UNIQUE NOT NULL,
    Priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (FiledBy) REFERENCES resident(ResidentID) ON DELETE RESTRICT,
    FOREIGN KEY (PoliceStationID) REFERENCES police_station(StationID) ON DELETE RESTRICT
);

-- Create Crime Information table
CREATE TABLE crime_information (
    CrimeID INT PRIMARY KEY AUTO_INCREMENT,
    CategoryID INT NOT NULL,
    Description TEXT NOT NULL,
    Date DATETIME NOT NULL,
    LocationID INT NOT NULL,
    FIRID INT,
    Severity ENUM('minor', 'moderate', 'major', 'critical') DEFAULT 'moderate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (CategoryID) REFERENCES crime_category(CategoryID) ON DELETE RESTRICT,
    FOREIGN KEY (LocationID) REFERENCES location(LocationID) ON DELETE RESTRICT,
    FOREIGN KEY (FIRID) REFERENCES fir(FIRID) ON DELETE SET NULL
);

-- Create Criminal table
CREATE TABLE criminal (
    CriminalID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    CrimeType VARCHAR(100) NOT NULL,
    FIRID INT NOT NULL,
    Age INT,
    Description TEXT,
    Status ENUM('suspected', 'arrested', 'convicted', 'acquitted') DEFAULT 'suspected',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (FIRID) REFERENCES fir(FIRID) ON DELETE CASCADE
);

-- Create Evidence table
CREATE TABLE evidence (
    EvidenceID INT PRIMARY KEY AUTO_INCREMENT,
    FIRID INT NOT NULL,
    Description TEXT NOT NULL,
    SubmittedBy INT NOT NULL,
    EvidenceType ENUM('physical', 'digital', 'witness', 'document', 'photo', 'video') NOT NULL,
    FilePath VARCHAR(500),
    CollectedDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FIRID) REFERENCES fir(FIRID) ON DELETE CASCADE,
    FOREIGN KEY (SubmittedBy) REFERENCES user(ID) ON DELETE RESTRICT
);

-- Create Case table
CREATE TABLE cases (
    CaseID INT PRIMARY KEY AUTO_INCREMENT,
    FIRID INT NOT NULL,
    AssignedTo INT NOT NULL,
    Status ENUM('open', 'investigating', 'pending', 'closed', 'transferred') DEFAULT 'open',
    Priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    Notes TEXT,
    DeadlineDate DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (FIRID) REFERENCES fir(FIRID) ON DELETE CASCADE,
    FOREIGN KEY (AssignedTo) REFERENCES user(ID) ON DELETE RESTRICT
);

-- Create Police Information table
CREATE TABLE police_information (
    PoliceID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Rank ENUM('IG', 'SP', 'DSP', 'ASI', 'Constable') NOT NULL,
    StationID INT NOT NULL,
    UserID INT NOT NULL,
    BadgeNumber VARCHAR(20) UNIQUE,
    JoinDate DATE,
    Specialization VARCHAR(100),
    JurisdictionDistrict VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (StationID) REFERENCES police_station(StationID) ON DELETE RESTRICT,
    FOREIGN KEY (UserID) REFERENCES user(ID) ON DELETE CASCADE
);

-- Create Patrol Notes table
CREATE TABLE patrol_notes (
    NoteID INT PRIMARY KEY AUTO_INCREMENT,
    FIRID INT NOT NULL,
    OfficerID INT NOT NULL,
    Notes TEXT NOT NULL,
    PatrolDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FIRID) REFERENCES fir(FIRID) ON DELETE CASCADE,
    FOREIGN KEY (OfficerID) REFERENCES police_information(PoliceID) ON DELETE RESTRICT
);

-- Create Response Information table
CREATE TABLE response_information (
    ResponseID INT PRIMARY KEY AUTO_INCREMENT,
    FIRID INT NOT NULL,
    OfficerID INT NOT NULL,
    ResponseDetails TEXT NOT NULL,
    Date DATETIME DEFAULT CURRENT_TIMESTAMP,
    ResponseType ENUM('initial', 'follow_up', 'investigation', 'closure') DEFAULT 'initial',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (FIRID) REFERENCES fir(FIRID) ON DELETE CASCADE,
    FOREIGN KEY (OfficerID) REFERENCES police_information(PoliceID) ON DELETE RESTRICT
);

-- Insert Pakistani Locations (Major Cities and Areas)
INSERT INTO location (AreaName, City) VALUES
-- Karachi Areas
('Downtown', 'Karachi'),
('Gulshan-e-Iqbal', 'Karachi'),
('DHA Phase 1', 'Karachi'),
('DHA Phase 2', 'Karachi'),
('Clifton', 'Karachi'),
('North Nazimabad', 'Karachi'),
('Saddar', 'Karachi'),
('Korangi', 'Karachi'),
('Malir', 'Karachi'),
('Shah Faisal Colony', 'Karachi'),

-- Lahore Areas
('Model Town', 'Lahore'),
('Johar Town', 'Lahore'),
('DHA Lahore', 'Lahore'),
('Gulberg', 'Lahore'),
('Anarkali', 'Lahore'),
('Liberty Market', 'Lahore'),
('Fortress Stadium', 'Lahore'),
('Iqbal Town', 'Lahore'),

-- Islamabad Areas
('Blue Area', 'Islamabad'),
('F-8 Sector', 'Islamabad'),
('F-6 Sector', 'Islamabad'),
('F-7 Sector', 'Islamabad'),
('G-9 Sector', 'Islamabad'),
('I-8 Sector', 'Islamabad'),

-- Rawalpindi Areas
('Saddar', 'Rawalpindi'),
('Cantonment', 'Rawalpindi'),
('Committee Chowk', 'Rawalpindi'),
('Commercial Market', 'Rawalpindi'),

-- Faisalabad Areas
('Ghulam Muhammad Abad', 'Faisalabad'),
('Peoples Colony', 'Faisalabad'),
('Susan Road', 'Faisalabad'),

-- Multan Areas
('Cantt Area', 'Multan'),
('Gulgasht Colony', 'Multan'),

-- Peshawar Areas
('University Town', 'Peshawar'),
('Hayatabad', 'Peshawar'),

-- Quetta Areas
('Cantt Area', 'Quetta'),
('Satellite Town', 'Quetta');

-- Insert Crime Categories with Urdu descriptions
INSERT INTO crime_category (Name, Description) VALUES
('Theft', 'چوری - Stealing of personal or public property without force'),
('Assault', 'حملہ - Physical attack or threat of violence against a person'),
('Vandalism', 'توڑ پھوڑ - Willful destruction or defacement of property'),
('Fraud', 'دھوکہ دہی - Financial crimes involving deception for monetary gain'),
('Burglary', 'چوری - Breaking and entering premises with intent to steal'),
('Drug Offense', 'منشیات - Illegal drug possession, distribution, or manufacturing'),
('Traffic Violation', 'ٹریفک خلاف ورزی - Road safety and traffic law violations'),
('Domestic Violence', 'گھریلو تشدد - Violence within domestic or family relationships'),
('Cyber Crime', 'سائبر کرائم - Internet and computer related criminal activities'),
('Kidnapping', 'اغوا - Unlawful abduction or restraint of persons'),
('Murder', 'قتل - Unlawful killing of another human being'),
('Robbery', 'ڈکیتی - Theft involving force, violence, or threat thereof'),
('Sexual Assault', 'جنسی تشدد - Non-consensual sexual contact or behavior'),
('Extortion', 'بھتہ خوری - Obtaining money through coercion or threats'),
('Corruption', 'بدعنوانی - Misuse of public office for private gain'),
('Terrorism', 'دہشت گردی - Acts intended to create fear and intimidate civilians'),
('Money Laundering', 'منی لانڈرنگ - Concealing the origins of illegally obtained money'),
('Other', 'دیگر - Other criminal activities not listed above');

-- Insert Pakistani Police Officers with realistic names and hierarchy
INSERT INTO user (Name, Role, CNIC, Username, Password) VALUES
-- Admin Level
('Muhammad Tariq Shah', 'admin', '42101-1234567-1', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),

-- IG Level (Inspector General)
('IG Muhammad Tariq Shah', 'ig', '42101-1234568-1', 'ig.tariq', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('IG Ayesha Siddique', 'ig', '42101-1234569-1', 'ig.ayesha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),

-- SP Level (Superintendent of Police)
('SP Ahmed Ali Khan', 'sp', '42101-2345678-2', 'sp.ahmed', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('SP Rashid Ahmed', 'sp', '42101-6789012-6', 'sp.rashid', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('SP Zainab Malik', 'sp', '42101-2345679-2', 'sp.zainab', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),

-- DSP Level (Deputy Superintendent of Police)
('DSP Fatima Nawaz', 'dsp', '42101-3456789-3', 'dsp.fatima', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('DSP Ayesha Siddique', 'dsp', '42101-7890123-7', 'dsp.ayesha', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('DSP Hassan Iqbal', 'dsp', '42101-3456790-3', 'dsp.hassan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),

-- ASI Level (Assistant Sub Inspector)
('ASI Hassan Malik', 'asi', '42101-4567890-4', 'asi.hassan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('ASI Muhammad Iqbal', 'asi', '42101-8901234-8', 'asi.iqbal', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('ASI Sana Khan', 'asi', '42101-4567891-4', 'asi.sana', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('ASI Omar Farooq', 'asi', '42101-4567892-4', 'asi.omar', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),

-- Constable Level
('Constable Usman Khan', 'constable', '42101-5678901-5', 'const.usman', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Constable Nadia Ali', 'constable', '42101-9012345-9', 'const.nadia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Constable Ahmed Hassan', 'constable', '42101-5678902-5', 'const.ahmed', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Constable Fatima Zara', 'constable', '42101-5678903-5', 'const.fatima', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Constable Ali Raza', 'constable', '42101-5678904-5', 'const.ali', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Constable Maria Khan', 'constable', '42101-5678905-5', 'const.maria', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert Police Stations across Pakistan
INSERT INTO police_station (Name, LocationID, Phone, Address) VALUES
-- Karachi Police Stations
('Central Police Station Karachi', 1, '+92-21-99201234', 'Main Street, Downtown, Karachi'),
('Gulshan Police Station', 2, '+92-21-99205678', 'Block 2, Gulshan-e-Iqbal, Karachi'),
('DHA Police Station', 3, '+92-21-99209012', 'DHA Phase 1, Main Clifton, Karachi'),
('Clifton Police Station', 5, '+92-21-99203456', 'Main Clifton Road, Karachi'),
('North Nazimabad Police Station', 6, '+92-21-99204567', 'Block L, North Nazimabad, Karachi'),
('Saddar Police Station Karachi', 7, '+92-21-99205678', 'Abdullah Haroon Road, Saddar, Karachi'),

-- Lahore Police Stations
('Model Town Police Station', 11, '+92-42-99203456', 'Model Town Extension, Lahore'),
('Johar Town Police Station', 12, '+92-42-99204567', 'Block J, Johar Town, Lahore'),
('Gulberg Police Station', 14, '+92-42-99205678', 'Main Gulberg, Lahore'),
('Anarkali Police Station', 15, '+92-42-99206789', 'Anarkali Bazaar, Lahore'),

-- Islamabad Police Stations
('Blue Area Police Station', 19, '+92-51-99207890', 'Jinnah Avenue, Blue Area, Islamabad'),
('F-8 Police Station', 20, '+92-51-99201122', 'F-8 Markaz, Islamabad'),
('F-6 Police Station', 21, '+92-51-99208901', 'F-6 Super Market, Islamabad'),

-- Rawalpindi Police Stations
('Saddar Police Station Rawalpindi', 25, '+92-51-99209012', 'Committee Chowk, Saddar, Rawalpindi'),
('Cantonment Police Station', 26, '+92-51-99210123', 'Mall Road, Cantonment, Rawalpindi'),

-- Other Cities
('Faisalabad Central Police Station', 29, '+92-41-99211234', 'Ghulam Muhammad Abad, Faisalabad'),
('Multan Police Station', 31, '+92-61-99212345', 'Cantt Area, Multan'),
('Peshawar Police Station', 33, '+92-91-99213456', 'University Town, Peshawar'),
('Quetta Police Station', 35, '+92-81-99214567', 'Cantt Area, Quetta');

-- Insert Sample Pakistani Residents with realistic data
INSERT INTO resident (Name, CNIC, Address, Contact, Email) VALUES
-- Karachi Residents
('Ali Ahmad', '42101-1111111-1', 'House 123, Block A, Gulshan-e-Iqbal, Karachi', '+92-300-1234567', 'ali.ahmad@email.com'),
('Fatima Sheikh', '42101-2222222-2', 'Flat 45, DHA Phase 2, Karachi', '+92-301-2345678', 'fatima.sheikh@email.com'),
('Muhammad Asif', '42101-5555555-5', 'Flat 12, Blue Area, Islamabad', '+92-304-5678901', 'asif.muhammad@email.com'),
('Sana Malik', '42101-6666666-6', 'House 67, North Nazimabad, Karachi', '+92-305-6789012', 'sana.malik@email.com'),
('Omar Farooq', '42101-7777777-7', 'Apartment 89, Clifton, Karachi', '+92-306-7890123', 'omar.farooq@email.com'),

-- Lahore Residents
('Hassan Malik', '42101-3333333-3', 'House 67, Model Town, Lahore', '+92-302-3456789', 'hassan.malik@email.com'),
('Zainab Khan', '42101-8888888-8', 'House 234, Johar Town, Lahore', '+92-307-8901234', 'zainab.khan@email.com'),
('Ahmed Raza', '42101-9999999-9', 'Flat 156, Gulberg, Lahore', '+92-308-9012345', 'ahmed.raza@email.com'),

-- Islamabad Residents
('Ayesha Khan', '42101-4444444-4', 'House 89, F-8/3, Islamabad', '+92-303-4567890', 'ayesha.khan@email.com'),
('Usman Ali', '42101-1010101-0', 'House 345, F-6/2, Islamabad', '+92-309-0123456', 'usman.ali@email.com'),

-- Other Cities
('Nadia Shah', '42101-1111122-1', 'House 789, Cantonment, Rawalpindi', '+92-310-1234567', 'nadia.shah@email.com'),
('Bilal Ahmed', '42101-2222233-2', 'House 456, Peoples Colony, Faisalabad', '+92-311-2345678', 'bilal.ahmed@email.com'),
('Mariam Khan', '42101-3333344-3', 'House 123, Gulgasht Colony, Multan', '+92-312-3456789', 'mariam.khan@email.com'),
('Tariq Mahmood', '42101-4444455-4', 'House 567, Hayatabad, Peshawar', '+92-313-4567890', 'tariq.mahmood@email.com'),
('Saira Baloch', '42101-5555566-5', 'House 890, Satellite Town, Quetta', '+92-314-5678901', 'saira.baloch@email.com');

-- Insert Resident Credentials (All passwords are 'password123')
INSERT INTO resident_credential (ResidentID, Username, Password) VALUES
(1, 'ali.ahmad', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(2, 'fatima.sheikh', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(3, 'hassan.malik', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(4, 'ayesha.khan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(5, 'asif.muhammad', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(6, 'sana.malik', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(7, 'omar.farooq', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(8, 'zainab.khan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(9, 'ahmed.raza', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(10, 'usman.ali', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(11, 'nadia.shah', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(12, 'bilal.ahmed', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(13, 'mariam.khan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(14, 'tariq.mahmood', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
(15, 'saira.baloch', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert Police Information with Pakistani Ranks and Badge Numbers
INSERT INTO police_information (Name, Rank, StationID, UserID, BadgeNumber, JoinDate, Specialization, JurisdictionDistrict) VALUES
('IG Muhammad Tariq Shah', 'IG', 1, 2, 'PK-IG-001', '2015-01-15', 'Provincial Administration', 'Sindh Province'),
('SP Ahmed Ali Khan', 'SP', 1, 4, 'PK-SP-002', '2017-06-20', 'District Operations', 'Karachi District'),
('DSP Fatima Nawaz', 'DSP', 1, 7, 'PK-DSP-003', '2018-03-10', 'Station Management', 'Karachi Central'),
('ASI Hassan Malik', 'ASI', 1, 10, 'PK-ASI-004', '2019-08-25', 'Investigation & Evidence', 'Karachi Central'),
('Constable Usman Khan', 'Constable', 1, 13, 'PK-CONST-005', '2020-02-10', 'Patrol & First Response', 'Karachi Central'),
('SP Rashid Ahmed', 'SP', 8, 5, 'PK-SP-006', '2016-09-12', 'District Operations', 'Lahore District'),
('DSP Ayesha Siddique', 'DSP', 11, 8, 'PK-DSP-007', '2019-01-18', 'Station Management', 'Islamabad'),
('ASI Muhammad Iqbal', 'ASI', 2, 11, 'PK-ASI-008', '2020-05-22', 'Investigation & Evidence', 'Karachi Gulshan'),
('Constable Nadia Ali', 'Constable', 3, 14, 'PK-CONST-009', '2021-03-15', 'Patrol & First Response', 'Karachi DHA');

-- Insert Sample FIRs with varied statuses and realistic scenarios
INSERT INTO fir (Title, Description, Date, FiledBy, PoliceStationID, ReferenceNumber, Priority, Status, created_at, updated_at) VALUES
('Mobile Phone Theft at Gulshan Market', 'My mobile phone (iPhone 12) was stolen while I was shopping at Gulshan Market. The suspect was a young man wearing a blue shirt who snatched it from my hand and ran away on a motorcycle.', '2024-01-15', 1, 2, 'FIR-2024-0001', 'medium', 'resolved', '2024-01-15 10:30:00', '2024-01-20 16:45:00'),

('House Burglary in DHA Phase 2', 'Unknown persons broke into my house during the night when we were out of town. They stole jewelry worth approximately PKR 500,000, cash PKR 50,000, and damaged the main door lock.', '2024-01-18', 2, 3, 'FIR-2024-0002', 'high', 'investigating', '2024-01-18 08:15:00', '2024-01-19 14:20:00'),

('Road Accident at Main Shahrah-e-Faisal', 'A serious road accident occurred involving two vehicles. One person was injured and was taken to the hospital. The other driver fled the scene. Vehicle number KHI-1234 was involved.', '2024-01-20', 3, 1, 'FIR-2024-0003', 'urgent', 'under_review', '2024-01-20 14:30:00', '2024-01-20 14:30:00'),

('Fraud Case - Online Banking', 'I received a fake call claiming to be from my bank asking for my PIN and card details. After providing the information, PKR 100,000 was withdrawn from my account without authorization.', '2024-01-22', 4, 4, 'FIR-2024-0004', 'high', 'investigating', '2024-01-22 11:00:00', '2024-01-23 09:15:00'),

('Domestic Violence Incident', 'Reporting domestic violence case where the victim requires immediate protection and medical attention. Multiple witnesses available. Urgent police intervention required.', '2024-01-25', 5, 1, 'FIR-2024-0005', 'urgent', 'resolved', '2024-01-25 16:45:00', '2024-01-28 10:30:00'),

('Cyber Crime - Social Media Harassment', 'Someone is using fake profiles to harass and threaten me on social media platforms. They are posting my personal information and making threats to my safety and reputation.', '2024-01-28', 6, 2, 'FIR-2024-0006', 'medium', 'under_review', '2024-01-28 13:20:00', '2024-01-28 13:20:00'),

('Vehicle Theft - Car Stolen from Parking', 'My car (Toyota Corolla, Registration: ABC-123) was stolen from the parking area of a shopping mall. I have the original documents and keys were with me. CCTV footage may be available.', '2024-02-01', 7, 8, 'FIR-2024-0007', 'high', 'investigating', '2024-02-01 19:30:00', '2024-02-02 08:45:00'),

('Shoplifting at Local Store', 'A customer stole items worth PKR 5,000 from my general store. The incident was captured on CCTV camera. The suspect is a regular customer and can be identified.', '2024-02-03', 8, 9, 'FIR-2024-0008', 'low', 'submitted', '2024-02-03 12:15:00', '2024-02-03 12:15:00'),

('Assault Case in F-8 Sector', 'I was physically assaulted by unknown persons near F-8 Markaz. They demanded money and when I refused, they attacked me. I have visible injuries and medical report available.', '2024-02-05', 9, 12, 'FIR-2024-0009', 'medium', 'investigating', '2024-02-05 20:00:00', '2024-02-06 11:30:00'),

('Drug Dealing in Residential Area', 'Suspected drug dealing activities in our residential area. Multiple people coming and going at odd hours from a specific house. Strong smell of drugs and suspicious behavior observed.', '2024-02-08', 10, 14, 'FIR-2024-0010', 'high', 'under_review', '2024-02-08 15:45:00', '2024-02-08 15:45:00'),

('Kidnapping Attempt Near School', 'An attempt was made to kidnap a child near the local school. The suspect was driving a white van without number plates. School security and parents intervened and the suspect fled.', '2024-02-10', 11, 15, 'FIR-2024-0011', 'urgent', 'investigating', '2024-02-10 07:30:00', '2024-02-10 09:15:00'),

('Extortion by Local Gang', 'Local shopkeepers are being threatened and forced to pay protection money by a gang. They are demanding PKR 10,000 monthly from each shop. Several shop owners are afraid to report.', '2024-02-12', 12, 16, 'FIR-2024-0012', 'high', 'under_review', '2024-02-12 14:20:00', '2024-02-12 14:20:00'),

('Vandalism in Public Park', 'Public property in the local park has been vandalized. Benches broken, walls covered with graffiti, and flower beds destroyed. This happened during the night and no security was present.', '2024-02-15', 13, 17, 'FIR-2024-0013', 'low', 'submitted', '2024-02-15 09:00:00', '2024-02-15 09:00:00'),

('Robbery at ATM', 'I was robbed while withdrawing money from an ATM. Two men on a motorcycle threatened me with weapons and took PKR 25,000 cash and my ATM card. Incident occurred at night.', '2024-02-18', 14, 18, 'FIR-2024-0014', 'high', 'investigating', '2024-02-18 22:30:00', '2024-02-19 08:00:00'),

('Corruption in Government Office', 'Government official demanding bribe for processing legal documents. The officer is asking for PKR 20,000 for a service that should be free. I have recorded evidence of the conversation.', '2024-02-20', 15, 11, 'FIR-2024-0015', 'medium', 'closed', '2024-02-20 11:45:00', '2024-02-25 16:30:00');

-- Insert corresponding Crime Information for each FIR
INSERT INTO crime_information (CategoryID, Description, Date, LocationID, FIRID, Severity) VALUES
-- Mobile Phone Theft
(1, 'Mobile phone theft at Gulshan Market during daytime shopping hours. Suspect described as young male, approximately 20-25 years old, wearing blue shirt. Escaped on motorcycle with accomplice.', '2024-01-15 15:45:00', 2, 1, 'moderate'),

-- House Burglary
(5, 'Residential burglary in DHA Phase 2. Entry gained by breaking front door lock. Multiple valuable items stolen including gold jewelry, cash, and electronics. No witnesses available.', '2024-01-18 02:30:00', 4, 2, 'major'),

-- Road Accident
(7, 'Two-vehicle collision on main Shahrah-e-Faisal. One vehicle fled the scene (hit and run). Injured party transported to hospital. Vehicle registration KHI-1234 identified.', '2024-01-20 14:30:00', 1, 3, 'major'),

-- Online Banking Fraud
(4, 'Online banking fraud through social engineering. Victim received fraudulent call impersonating bank official. Personal banking details compromised resulting in unauthorized transactions.', '2024-01-22 09:15:00', 5, 4, 'major'),

-- Domestic Violence
(8, 'Domestic violence incident requiring immediate intervention. Victim sustained injuries and requires medical attention. Multiple witnesses available to provide statements.', '2024-01-25 20:00:00', 1, 5, 'critical'),

-- Cyber Crime
(9, 'Cyber harassment through social media platforms. Fake profiles created to threaten and harass victim. Personal information published without consent. Digital evidence available.', '2024-01-28 10:30:00', 2, 6, 'moderate'),

-- Vehicle Theft
(1, 'Motor vehicle theft from shopping mall parking area. Toyota Corolla stolen with registration ABC-123. Owner has original documents. CCTV footage being reviewed.', '2024-02-01 19:30:00', 11, 7, 'major'),

-- Shoplifting
(1, 'Retail theft from general store. Items worth PKR 5,000 stolen by known customer. Incident captured on store CCTV system. Suspect can be identified from previous visits.', '2024-02-03 16:45:00', 12, 8, 'minor'),

-- Assault
(2, 'Physical assault near F-8 Markaz during robbery attempt. Victim sustained injuries requiring medical treatment. Multiple attackers involved in the incident.', '2024-02-05 20:00:00', 20, 9, 'moderate'),

-- Drug Dealing
(6, 'Suspected drug distribution in residential area. Multiple individuals observed visiting specific location at unusual hours. Strong odor and suspicious activities reported by neighbors.', '2024-02-08 23:30:00', 14, 10, 'major'),

-- Kidnapping Attempt
(10, 'Attempted child abduction near educational institution. Suspect operating white van without visible registration. School security and parents intervened preventing the crime.', '2024-02-10 07:30:00', 25, 11, 'critical'),

-- Extortion
(14, 'Organized extortion targeting local businesses. Gang demanding monthly protection payments from shop owners. Multiple victims afraid to report due to threats of violence.', '2024-02-12 18:00:00', 29, 12, 'major'),

-- Vandalism
(3, 'Destruction of public property in community park. Benches damaged, graffiti on walls, landscaping destroyed. Incident occurred during overnight hours without security presence.', '2024-02-15 03:00:00', 31, 13, 'minor'),

-- ATM Robbery
(12, 'Armed robbery at automated teller machine. Two suspects on motorcycle threatened victim with weapons. Cash and ATM card stolen during late-night transaction.', '2024-02-18 22:30:00', 33, 14, 'major'),

-- Corruption
(15, 'Government official soliciting bribe for document processing. Officer demanding payment for legally free service. Victim has audio recording evidence of bribery attempt.', '2024-02-20 14:15:00', 19, 15, 'moderate');

-- Insert some sample cases for active FIRs
INSERT INTO cases (FIRID, AssignedTo, Status, Priority, Notes, DeadlineDate) VALUES
(2, 4, 'investigating', 'high', 'House burglary case - collecting fingerprints and interviewing neighbors. CCTV footage from nearby shops being reviewed.', '2024-02-15'),
(4, 7, 'investigating', 'high', 'Banking fraud case - coordinating with bank security team and cyber crime unit. Tracing unauthorized transactions.', '2024-02-10'),
(7, 5, 'investigating', 'high', 'Vehicle theft case - checking with all major car dealers and scrap yards. CCTV footage from shopping mall obtained.', '2024-02-20'),
(9, 8, 'investigating', 'medium', 'Assault case - medical reports collected, interviewing witnesses. Checking CCTV footage from nearby establishments.', '2024-02-12'),
(11, 4, 'investigating', 'urgent', 'Kidnapping attempt - coordinating with school authorities and parents. Increasing security around educational institutions.', '2024-02-05'),
(14, 6, 'investigating', 'high', 'ATM robbery case - reviewing bank security footage, interviewing security guards. Checking similar incidents in the area.', '2024-02-25');

-- Create indexes for better performance
CREATE INDEX idx_fir_status ON fir(Status);
CREATE INDEX idx_fir_created_at ON fir(created_at);
CREATE INDEX idx_fir_reference ON fir(ReferenceNumber);
CREATE INDEX idx_resident_cnic ON resident(CNIC);
CREATE INDEX idx_crime_info_date ON crime_information(Date);
CREATE INDEX idx_location_city ON location(City);

-- Display completion message
SELECT 'Pakistani Crime Management System database setup completed successfully!' as 'Status',
       COUNT(*) as 'Total_Locations' FROM location
UNION ALL
SELECT 'Police Officers Created:', COUNT(*) FROM user
UNION ALL  
SELECT 'Police Stations Created:', COUNT(*) FROM police_station
UNION ALL
SELECT 'Residents Registered:', COUNT(*) FROM resident
UNION ALL
SELECT 'Crime Categories:', COUNT(*) FROM crime_category
UNION ALL
SELECT 'Sample FIRs Created:', COUNT(*) FROM fir;