-- Create user_profiles table to store profile information
-- This table will be populated from registration data and allow profile management

CREATE TABLE IF NOT EXISTS user_profiles (
    profileID INT AUTO_INCREMENT PRIMARY KEY,
    studentID VARCHAR(20) NOT NULL UNIQUE,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    contactNumber VARCHAR(11) NOT NULL,
    emailAddress VARCHAR(100) NOT NULL,
    currentAddress VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    profileImage VARCHAR(255) DEFAULT 'uploads/profile.png',
    theme_preference VARCHAR(10) DEFAULT 'light',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (studentID) REFERENCES registration(studentID) ON DELETE CASCADE
);

-- Index for faster lookups
CREATE INDEX idx_studentID ON user_profiles(studentID);
CREATE INDEX idx_is_public ON user_profiles(is_public);
