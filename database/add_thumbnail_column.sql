-- Add thumbnail column to publications table
ALTER TABLE publications ADD COLUMN thumbnail VARCHAR(255) DEFAULT NULL;
