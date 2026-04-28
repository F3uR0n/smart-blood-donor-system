-- Smart Blood & Emergency Donor Network
-- Schema v2.0 — CSE370 DBMS Project (Revised)
-- Run in phpMyAdmin or MySQL CLI to rebuild the database

CREATE DATABASE IF NOT EXISTS smart_blood_donor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_blood_donor;

-- ─────────────────────────────────────────────────────────────
-- CORE USER TABLES  (user & recipient unchanged)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE user (
    userID      VARCHAR(36)  PRIMARY KEY,
    firstName   VARCHAR(50)  NOT NULL,
    surName     VARCHAR(50)  NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    phone       VARCHAR(20)  NOT NULL,
    nid         VARCHAR(17)  NOT NULL UNIQUE,
    bloodType   ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-'),
    role        ENUM('donor','recipient','admin') NOT NULL DEFAULT 'donor',
    password    VARCHAR(255) NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- UserLocation: multivalued, composite PK (userID, location)
CREATE TABLE user_location (
    userID   VARCHAR(36)  NOT NULL,
    location VARCHAR(255) NOT NULL,
    PRIMARY KEY (userID, location),
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- DONOR  (only stored attributes; points/rank/nextEligible derived)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE donor (
    userID       VARCHAR(36) PRIMARY KEY,
    bloodType    ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    isAvailable  TINYINT(1)  NOT NULL DEFAULT 1,
    lastDonated  DATE                 DEFAULT NULL,
    totalDonate  INT         NOT NULL DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES user(userID) ON DELETE CASCADE
);

-- HealthProfile: one row per donor, weightKg/haemoglobinLvl default NULL
CREATE TABLE health_profile (
    healthID       INT AUTO_INCREMENT PRIMARY KEY,
    userID         VARCHAR(36)  NOT NULL UNIQUE,
    weightKg       DECIMAL(5,1) DEFAULT NULL,
    haemoglobinLvl DECIMAL(4,1) DEFAULT NULL,
    FOREIGN KEY (userID) REFERENCES donor(userID) ON DELETE CASCADE
);

-- HealthStatus: derived via threshold; (weightKg, haemoglobinLvl) composite PK
--   Perfect : haemoglobin >= 13.5 AND weight >= 50
--   Good    : haemoglobin >= 12   AND weight >= 45
--   Bad     : anything below Good thresholds
CREATE TABLE health_status (
    weightKg       DECIMAL(5,1) NOT NULL,
    haemoglobinLvl DECIMAL(4,1) NOT NULL,
    healthStatus   ENUM('Perfect','Good','Bad') NOT NULL,
    PRIMARY KEY (weightKg, haemoglobinLvl)
);

-- Diseases: multivalued attribute of HealthProfile
CREATE TABLE diseases (
    healthID INT          NOT NULL,
    disease  VARCHAR(100) NOT NULL,
    PRIMARY KEY (healthID, disease),
    FOREIGN KEY (healthID) REFERENCES health_profile(healthID) ON DELETE CASCADE
);

CREATE TABLE recipient (
    recipientID VARCHAR(36) PRIMARY KEY,
    FOREIGN KEY (recipientID) REFERENCES user(userID) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- EMERGENCY REQUESTS & INTELLIGENT MATCHING
-- ─────────────────────────────────────────────────────────────

CREATE TABLE emergency_request (
    requestID         INT AUTO_INCREMENT PRIMARY KEY,
    bloodTypeNeeded   ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    unitsNeeded       INT          NOT NULL DEFAULT 1,
    urgencyLvl        ENUM('critical','high','medium','low') DEFAULT 'high',
    currentStatus     ENUM('pending','matched','fulfilled','cancelled') DEFAULT 'pending',
    createdAt         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    requestCity       VARCHAR(100) NOT NULL,
    reqHospitalCenter VARCHAR(200) NOT NULL,
    userID            VARCHAR(36)  DEFAULT NULL,
    FOREIGN KEY (userID) REFERENCES recipient(recipientID) ON DELETE SET NULL
);

-- Intelligent Match: composite PK (userID=donor, requestID)
CREATE TABLE intelligent_match (
    userID                  VARCHAR(36) NOT NULL,
    requestID               INT         NOT NULL,
    locationProximity       DECIMAL(10,2) DEFAULT NULL,
    donorAvailabilityStatus TINYINT(1)    DEFAULT 1,
    matchStatus             ENUM('pending','matched','declined','donated') DEFAULT 'pending',
    sendNotificationStatus  TINYINT(1)    DEFAULT 0,
    matched_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (userID, requestID),
    FOREIGN KEY (userID)    REFERENCES donor(userID) ON DELETE CASCADE,
    FOREIGN KEY (requestID) REFERENCES emergency_request(requestID) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- BLOOD BANKS & INVENTORY  (blood_bank unchanged)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE blood_bank (
    bankID   INT AUTO_INCREMENT PRIMARY KEY,
    bankName VARCHAR(200) NOT NULL,
    location VARCHAR(255) NOT NULL,
    city     VARCHAR(100) NOT NULL,
    isOpen   TINYINT(1)   DEFAULT 1
);

-- BloodInventory: composite PK (bankID, bloodType); no surrogate key, no maxCapacity
CREATE TABLE blood_inventory (
    bankID         INT         NOT NULL,
    bloodType      ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL,
    unitsAvailable INT         NOT NULL DEFAULT 0,
    expiryDate     DATE        DEFAULT NULL,
    PRIMARY KEY (bankID, bloodType),
    FOREIGN KEY (bankID) REFERENCES blood_bank(bankID) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- DONATION HISTORY  (unchanged)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE donation (
    donationID     INT AUTO_INCREMENT PRIMARY KEY,
    donorID        VARCHAR(36)  NOT NULL,
    donationDate   DATE         NOT NULL,
    hospitalCenter VARCHAR(200),
    unitsDonated   INT          NOT NULL DEFAULT 1,
    status         ENUM('completed','pending','cancelled') DEFAULT 'completed',
    FOREIGN KEY (donorID) REFERENCES donor(userID) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- CAMPAIGNS, COMPANIES & REWARDS (restructured)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE company (
    companyID    INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(200) NOT NULL,
    industryType VARCHAR(100) DEFAULT NULL,
    email        VARCHAR(100) DEFAULT NULL,
    websiteLink  VARCHAR(255) DEFAULT NULL
);

-- CompanyLocation: multivalued
CREATE TABLE company_location (
    companyID INT          NOT NULL,
    location  VARCHAR(255) NOT NULL,
    PRIMARY KEY (companyID, location),
    FOREIGN KEY (companyID) REFERENCES company(companyID) ON DELETE CASCADE
);

CREATE TABLE campaign (
    campaignID INT AUTO_INCREMENT PRIMARY KEY,
    budget     DECIMAL(12,2) DEFAULT NULL,
    tagLine    VARCHAR(500)  DEFAULT NULL,
    title      VARCHAR(200)  NOT NULL,
    hostPlace  VARCHAR(255)  DEFAULT NULL,
    startDate  DATE          NOT NULL,
    endDate    DATE          NOT NULL,
    companyID  INT           NOT NULL,
    FOREIGN KEY (companyID) REFERENCES company(companyID) ON DELETE CASCADE
);

-- Reward: rewardItem only (link to campaign via Offers)
CREATE TABLE reward (
    rewardID   INT AUTO_INCREMENT PRIMARY KEY,
    rewardItem VARCHAR(255) NOT NULL
);

-- Offers: ternary relationship (company, reward, campaign)
CREATE TABLE offers (
    companyID  INT NOT NULL,
    rewardID   INT NOT NULL,
    campaignID INT NOT NULL,
    PRIMARY KEY (companyID, rewardID, campaignID),
    FOREIGN KEY (companyID)  REFERENCES company(companyID)   ON DELETE CASCADE,
    FOREIGN KEY (rewardID)   REFERENCES reward(rewardID)     ON DELETE CASCADE,
    FOREIGN KEY (campaignID) REFERENCES campaign(campaignID) ON DELETE CASCADE
);

-- Accept: replaces donor_reward; adds eligibilityChecked
CREATE TABLE accept (
    rewardID           INT         NOT NULL,
    userID             VARCHAR(36) NOT NULL,
    claimedAt          TIMESTAMP   NULL DEFAULT NULL,
    claimedStatus      TINYINT(1)  DEFAULT 0,
    eligibilityChecked TINYINT(1)  DEFAULT 0,
    PRIMARY KEY (rewardID, userID),
    FOREIGN KEY (rewardID) REFERENCES reward(rewardID)    ON DELETE CASCADE,
    FOREIGN KEY (userID)   REFERENCES donor(userID)       ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- FEEDBACK  (linked via Donation, not directly to donor)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE feedback (
    feedbackID        INT AUTO_INCREMENT PRIMARY KEY,
    donationTrackerID INT     NOT NULL,
    rating            TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments          TEXT    DEFAULT NULL,
    FOREIGN KEY (donationTrackerID) REFERENCES donation(donationID) ON DELETE CASCADE
);

-- ─────────────────────────────────────────────────────────────
-- SAMPLE DATA
-- ─────────────────────────────────────────────────────────────

-- Users  (password = "12345678" stored as plain text for dev; DO NOT change)
INSERT INTO user (userID, firstName, surName, email, phone, nid, bloodType, role, password) VALUES
('u001', 'Rafiq',   'Karim',   'rafiq@test.com',   '+8801711000001', '1234567890', 'A+',  'donor',     '12345678'),
('u002', 'Sumaiya', 'Haque',   'sumaiya@test.com', '+8801711000002', '1234567891', 'O-',  'donor',     '12345678'),
('u003', 'Masud',   'Islam',   'masud@test.com',   '+8801711000003', '1234567892', 'B+',  'donor',     '12345678'),
('u004', 'Faisal',  'Nabi',    'faisal@test.com',  '+8801711000004', '1234567893', 'AB+', 'donor',     '12345678'),
('u005', 'Zahir',   'Alam',    'zahir@test.com',   '+8801711000005', '1234567894', 'O+',  'donor',     '12345678'),
('u006', 'Nadia',   'Rahman',  'nadia@test.com',   '+8801711000006', '1234567895', 'A-',  'donor',     '12345678'),
('u007', 'Tarek',   'Khan',    'tarek@test.com',   '+8801711000007', '1234567896', 'B-',  'donor',     '12345678'),
('u008', 'Polash',  'Mondal',  'polash@test.com',  '+8801711000008', '1234567897', 'AB-', 'donor',     '12345678'),
('u009', 'Ayesha',  'Begum',   'ayesha@test.com',  '+8801711000009', '1234567898', 'A+',  'recipient', '12345678'),
('u010', 'Jalal',   'Hossain', 'jalal@test.com',   '+8801711000010', '1234567899', 'B+',  'recipient', '12345678');

-- UserLocation (composite PK: userID + location string)
INSERT INTO user_location (userID, location) VALUES
('u001', 'Mirpur-10, Dhaka'),
('u002', 'Agrabad, Chittagong'),
('u003', 'Zindabazar, Sylhet'),
('u004', 'Ambarkhana, Sylhet'),
('u005', 'Dhanmondi, Dhaka'),
('u006', 'Boalia, Rajshahi'),
('u007', 'Uttara, Dhaka'),
('u008', 'KDA Avenue, Khulna'),
('u009', 'Gulshan, Dhaka'),
('u010', 'Banani, Dhaka');

-- Donor  (only stored columns; points/globalRank/nextEligible derived at query time)
INSERT INTO donor (userID, bloodType, isAvailable, lastDonated, totalDonate) VALUES
('u001', 'A+',  1, '2026-02-10', 18),
('u002', 'O-',  1, '2026-01-28', 15),
('u003', 'B+',  1, '2025-12-20', 13),
('u004', 'AB+', 0, '2025-11-05',  9),
('u005', 'O+',  1, '2026-03-01', 11),
('u006', 'A-',  1, '2026-04-02',  7),
('u007', 'B-',  1, '2025-10-18',  5),
('u008', 'AB-', 0, '2025-09-22',  4);

-- Recipients
INSERT INTO recipient (recipientID) VALUES ('u009'), ('u010');

-- HealthProfile (one per donor; NULL means not yet set → shows N/A in UI)
INSERT INTO health_profile (healthID, userID, weightKg, haemoglobinLvl) VALUES
(1, 'u001', 72.0, 14.5),
(2, 'u002', 58.0, 13.8),
(3, 'u003', 65.0, 13.2),
(4, 'u004', 48.0, 12.1),
(5, 'u005', 80.0, 15.0),
(6, 'u006', 55.0, 14.2),
(7, 'u007', 70.0, 13.5),
(8, 'u008', 62.0, 12.8);

-- HealthStatus (derived threshold table; unique (weightKg, haemoglobinLvl) combos)
-- Perfect: haemoglobin >= 13.5 AND weight >= 50
-- Good   : haemoglobin >= 12   AND weight >= 45
-- Bad    : below Good thresholds
INSERT INTO health_status (weightKg, haemoglobinLvl, healthStatus) VALUES
(72.0, 14.5, 'Perfect'),
(58.0, 13.8, 'Perfect'),
(65.0, 13.2, 'Good'),
(48.0, 12.1, 'Good'),
(80.0, 15.0, 'Perfect'),
(55.0, 14.2, 'Perfect'),
(70.0, 13.5, 'Perfect'),
(62.0, 12.8, 'Good');

-- Diseases (u004 had Anemia in old schema)
INSERT INTO diseases (healthID, disease) VALUES
(4, 'Anemia');

-- Blood banks (unchanged)
INSERT INTO blood_bank (bankID, bankName, location, city, isOpen) VALUES
(1, 'Dhaka Medical College Blood Bank',       'Bakshibazar, Dhaka',    'Dhaka',      1),
(2, 'Square Hospital Blood Bank',             'Panthapath, Dhaka',     'Dhaka',      1),
(3, 'Chittagong General Hospital Blood Bank', 'Anderkilla, Chittagong','Chittagong',  1),
(4, 'MAG Osmani Medical College Blood Bank',  'Sylhet Sadar, Sylhet',  'Sylhet',     0),
(5, 'Rajshahi Medical College Blood Bank',    'Boalia, Rajshahi',      'Rajshahi',   1),
(6, 'Khulna Medical College Blood Bank',      'KDA Avenue, Khulna',    'Khulna',     1);

-- BloodInventory (composite PK; no inventoryID, no maxCapacity)
INSERT INTO blood_inventory (bankID, bloodType, unitsAvailable, expiryDate) VALUES
(1,'A+',24,'2026-05-10'),(1,'A-',6,'2026-05-08'),(1,'B+',18,'2026-05-12'),
(1,'B-',3,'2026-05-05'),(1,'AB+',10,'2026-05-15'),(1,'AB-',2,'2026-05-03'),
(1,'O+',30,'2026-05-11'),(1,'O-',8,'2026-05-09'),
(2,'A+',12,'2026-05-08'),(2,'A-',4,'2026-05-06'),(2,'B+',20,'2026-05-10'),
(2,'B-',1,'2026-05-04'),(2,'AB+',5,'2026-05-12'),(2,'AB-',0,'2026-05-02'),
(2,'O+',22,'2026-05-09'),(2,'O-',5,'2026-05-07'),
(3,'A+',8,'2026-05-06'),(3,'A-',2,'2026-05-04'),(3,'B+',15,'2026-05-08'),
(3,'B-',6,'2026-05-06'),(3,'AB+',3,'2026-05-10'),(3,'AB-',1,'2026-05-02'),
(3,'O+',12,'2026-05-07'),(3,'O-',4,'2026-05-05'),
(4,'A+',16,'2026-05-10'),(4,'A-',5,'2026-05-08'),(4,'B+',9,'2026-05-11'),
(4,'B-',2,'2026-05-06'),(4,'AB+',7,'2026-05-13'),(4,'AB-',0,'2026-05-01'),
(4,'O+',18,'2026-05-10'),(4,'O-',3,'2026-05-07'),
(5,'A+',20,'2026-05-12'),(5,'A-',8,'2026-05-10'),(5,'B+',14,'2026-05-11'),
(5,'B-',4,'2026-05-08'),(5,'AB+',6,'2026-05-14'),(5,'AB-',3,'2026-05-06'),
(5,'O+',25,'2026-05-12'),(5,'O-',9,'2026-05-10'),
(6,'A+',10,'2026-05-08'),(6,'A-',3,'2026-05-06'),(6,'B+',7,'2026-05-09'),
(6,'B-',0,'2026-05-04'),(6,'AB+',4,'2026-05-11'),(6,'AB-',1,'2026-05-03'),
(6,'O+',14,'2026-05-09'),(6,'O-',2,'2026-05-05');

-- Emergency requests (new column names: urgencyLvl, createdAt, reqHospitalCenter; no patientName/contactNum)
INSERT INTO emergency_request (requestID, bloodTypeNeeded, unitsNeeded, urgencyLvl, currentStatus, requestCity, reqHospitalCenter, userID) VALUES
(1, 'A+', 2, 'critical', 'matched',   'Dhaka',       'Dhaka Medical College',          'u009'),
(2, 'O-', 1, 'high',     'pending',   'Dhaka',       'Square Hospital',                'u009'),
(3, 'B+', 3, 'medium',   'fulfilled', 'Chittagong',  'Chittagong General Hospital',    'u010');

-- Intelligent match (composite PK: userID + requestID; renamed donorID → userID)
INSERT INTO intelligent_match (userID, requestID, locationProximity, donorAvailabilityStatus, matchStatus, sendNotificationStatus) VALUES
('u001', 1, 1.2,  1, 'matched', 1),
('u005', 1, 3.4,  1, 'pending', 1),
('u002', 2, 2.0,  1, 'pending', 1),
('u003', 3, 5.0,  1, 'donated', 1);

-- Donation history (unchanged)
INSERT INTO donation (donorID, donationDate, hospitalCenter, unitsDonated, status) VALUES
('u001', '2026-02-10', 'Dhaka Medical College',       1, 'completed'),
('u001', '2025-10-12', 'Square Hospital',              1, 'completed'),
('u001', '2025-06-20', 'Labaid Hospital',              1, 'completed'),
('u002', '2026-01-28', 'Chittagong General Hospital',  1, 'completed'),
('u002', '2025-09-10', 'Chittagong General Hospital',  1, 'completed'),
('u003', '2025-12-20', 'MAG Osmani Medical',           1, 'completed'),
('u003', '2025-08-15', 'Sylhet MAG Osmani',            1, 'completed'),
('u005', '2026-03-01', 'Dhaka Medical College',        1, 'completed'),
('u005', '2025-11-05', 'Square Hospital',              1, 'completed'),
('u006', '2026-04-02', 'Rajshahi Medical College',     1, 'completed'),
('u007', '2025-10-18', 'Dhaka Medical College',        1, 'completed');

-- Companies (extracted from old campaign.companyName)
INSERT INTO company (companyID, name, industryType, email, websiteLink) VALUES
(1, 'RedCross Bangladesh',           'Non-Profit',   'info@redcross.org.bd', 'https://redcross.org.bd'),
(2, 'BRAC Bank',                     'Banking',      'info@bracbank.com',    'https://bracbank.com'),
(3, 'ACI Pharmaceuticals',           'Healthcare',   'info@aci-bd.com',      'https://aci-bd.com'),
(4, 'Chittagong Port Authority',     'Government',   'info@cpa.gov.bd',      'https://cpa.gov.bd'),
(5, 'Dhaka University',              'Education',    'info@du.ac.bd',        'https://du.ac.bd'),
(6, 'Rajshahi Chamber of Commerce',  'Commerce',     'info@rcci.org.bd',     'https://rcci.org.bd');

-- CompanyLocation
INSERT INTO company_location (companyID, location) VALUES
(1, 'Banani, Dhaka'),
(2, 'Gulshan, Dhaka'),
(3, 'Tejgaon, Dhaka'),
(4, 'Agrabad, Chittagong'),
(5, 'Nilkhet, Dhaka'),
(6, 'Shaheb Bazar, Rajshahi');

-- Campaigns (new schema: tagLine, hostPlace, companyID; no companyName/goalDonors/currentDonors/status)
INSERT INTO campaign (campaignID, budget, tagLine, title, hostPlace, startDate, endDate, companyID) VALUES
(1, 500000.00, 'Every drop counts, every life matters',    'Life Drop Drive 2026',     'Banani, Dhaka',          '2026-04-15', '2026-05-15', 1),
(2, 300000.00, 'Professionals uniting for life',           'Corporate Blood Pledge',    'Gulshan, Dhaka',         '2026-05-01', '2026-05-30', 2),
(3, 200000.00, 'Together stronger, together saving lives', 'Sylhet Summer Save',        'Zindabazar, Sylhet',     '2026-03-01', '2026-03-31', 3),
(4, 400000.00, 'Chittagong gives back',                    'Port City Blood Fest',      'Agrabad, Chittagong',    '2026-04-20', '2026-06-20', 4),
(5, 150000.00, 'Young hearts, big impact',                 'Youth Blood Champions',     'Nilkhet, Dhaka',         '2026-06-01', '2026-06-15', 5),
(6, 180000.00, 'Harvest season, save a life',              'Rajshahi Harvest of Hope',  'Shaheb Bazar, Rajshahi', '2026-04-10', '2026-04-25', 6);

-- Rewards (no campaignID; link goes through Offers)
INSERT INTO reward (rewardID, rewardItem) VALUES
(1, 'RedCross Appreciation Certificate'),
(2, '50 Bonus Points'),
(3, 'Free Health Checkup Voucher'),
(4, 'BRAC Bank Donation Badge'),
(5, 'Port City Hero T-Shirt'),
(6, '100 Bonus Points'),
(7, 'Harvest Hero Medal');

-- Offers (ternary: company + reward + campaign)
INSERT INTO offers (companyID, rewardID, campaignID) VALUES
(1, 1, 1),(1, 2, 1),(1, 3, 1),
(2, 4, 2),
(4, 5, 4),(4, 6, 4),
(6, 7, 6);

-- Accept (replaces donor_reward; adds eligibilityChecked)
INSERT INTO accept (rewardID, userID, claimedStatus, claimedAt, eligibilityChecked) VALUES
(1, 'u001', 1, '2026-04-20 10:00:00', 1),
(2, 'u001', 1, '2026-04-20 10:01:00', 1),
(5, 'u002', 0, NULL,                  0),
(7, 'u003', 1, '2026-04-15 09:00:00', 1);

-- Feedback (linked via donation.donationID, not directly to donor)
-- donationID 1 = u001@Dhaka Medical, 2 = u001@Square, 4 = u002@Chittagong
-- 6 = u003@MAG Osmani, 8 = u005@Dhaka Medical, 10 = u006@Rajshahi, 11 = u007@Dhaka Medical
INSERT INTO feedback (donationTrackerID, rating, comments) VALUES
(1,  5, 'Excellent staff and quick process.'),
(2,  4, 'Good experience, minor wait time.'),
(4,  5, 'Very professional and caring team.'),
(6,  4, 'Clean facility, would recommend.'),
(8,  3, 'Process was slow but staff were helpful.'),
(10, 5, 'Best donation experience I have had.'),
(11, 4, 'Good overall experience.');
