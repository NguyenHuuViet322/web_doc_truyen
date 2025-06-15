<?php
// This script creates the necessary upload directories

// Define the directories
$directories = [
    'uploads',
    'uploads/avatars',
    'uploads/comics',
    'uploads/chapters'
];

echo "Creating upload directories...\n";

// Create each directory
foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    
    if (!is_dir($path)) {
        if (mkdir($path, 0777, true)) {
            echo "Created: $path\n";
        } else {
            echo "Failed to create: $path\n";
        }
    } else {
        echo "Already exists: $path\n";
        
        // Make sure directory is writable
        if (!is_writable($path)) {
            if (chmod($path, 0777)) {
                echo "Made writable: $path\n";
            } else {
                echo "Failed to make writable: $path\n";
            }
        }
    }
}

echo "Done.\n";
