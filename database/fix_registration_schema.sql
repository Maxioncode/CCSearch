-- Fix Registration Database Schema Issues
-- This script addresses the foreign key and data type inconsistencies in the registration system

USE research_repository;

-- Step 1: Drop problematic foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;

-- Drop foreign key from registration table
ALTER TABLE registration DROP FOREIGN KEY registration_ibfk_1;

-- Drop foreign key from publications table
ALTER TABLE publications DROP FOREIGN KEY publications_ibfk_1;

-- Drop foreign keys from library table
ALTER TABLE library DROP FOREIGN KEY library_ibfk_1;
ALTER TABLE library DROP FOREIGN KEY library_ibfk_2;

-- Step 2: Update table schemas to be consistent

-- Update student_information table to use consistent length
ALTER TABLE student_information MODIFY COLUMN studentID VARCHAR(20) NOT NULL;

-- Update registration table to use consistent length
ALTER TABLE registration MODIFY COLUMN studentID VARCHAR(20) NOT NULL;

-- Update publications table studentID length (already VARCHAR(20))
-- No change needed for publications.studentID

-- Update library table studentID length (already VARCHAR(20))
-- No change needed for library.studentID

-- Step 3: Recreate foreign key constraints properly

-- Registration table: studentID should reference student_information.studentID
ALTER TABLE registration ADD CONSTRAINT fk_registration_studentID
FOREIGN KEY (studentID) REFERENCES student_information(studentID) ON DELETE CASCADE ON UPDATE CASCADE;

-- Publications table: studentID should reference registration.studentID
ALTER TABLE publications ADD CONSTRAINT fk_publications_studentID
FOREIGN KEY (studentID) REFERENCES registration(studentID) ON DELETE CASCADE ON UPDATE CASCADE;

-- Library table: studentID should reference registration.studentID (not student_information)
ALTER TABLE library DROP FOREIGN KEY library_ibfk_1; -- Remove old constraint
ALTER TABLE library ADD CONSTRAINT fk_library_studentID
FOREIGN KEY (studentID) REFERENCES registration(studentID) ON DELETE CASCADE ON UPDATE CASCADE;

-- Library table: publicationID should reference publications.publicationID
ALTER TABLE library ADD CONSTRAINT fk_library_publicationID
FOREIGN KEY (publicationID) REFERENCES publications(publicationID) ON DELETE CASCADE ON UPDATE CASCADE;

-- Step 4: Update user_profiles foreign key
ALTER TABLE user_profiles ADD CONSTRAINT fk_user_profiles_studentID
FOREIGN KEY (studentID) REFERENCES registration(studentID) ON DELETE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Step 5: Update existing data to match new schema
-- Update student_information table to use longer student IDs if needed
UPDATE student_information SET studentID = CONCAT('2023-', LPAD(SUBSTRING(studentID, 6), 5, '0'))
WHERE LENGTH(studentID) < 10;

-- Update registration table to match
UPDATE registration SET studentID = CONCAT('2023-', LPAD(SUBSTRING(studentID, 6), 5, '0'))
WHERE LENGTH(studentID) < 10;



