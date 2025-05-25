-- Add slug column to comics table
ALTER TABLE comics ADD COLUMN slug VARCHAR(255) AFTER title;

-- Update existing comics with a slug based on their title
UPDATE comics SET slug = LOWER(REPLACE(REPLACE(title, ' ', '-'), '.', '-'));
