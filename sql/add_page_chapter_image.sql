-- Add page_chapter_image column to chapter_images table
ALTER TABLE chapter_images ADD COLUMN page_chapter_image VARCHAR(255) DEFAULT NULL;
