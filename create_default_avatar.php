<?php
// Script to create a default avatar image

$avatarDir = 'uploads/avatars/';

// Create directory if it doesn't exist
if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0777, true);
    echo "Created avatar directory: $avatarDir<br>";
}

// Check if default avatar exists
if (file_exists($avatarDir . 'default.jpg')) {
    echo "Default avatar already exists<br>";
} else {
    // Create a simple colored square as default avatar
    $width = 200;
    $height = 200;
    $image = imagecreatetruecolor($width, $height);
    
    // Background color (blue)
    $bgcolor = imagecolorallocate($image, 59, 89, 152);
    imagefill($image, 0, 0, $bgcolor);
    
    // Draw a simple user icon or just use the first letter of "user"
    $textcolor = imagecolorallocate($image, 255, 255, 255);
    $font = 5; // Built-in font
    $text = "U";
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    
    // Center the text
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    imagestring($image, $font, $x, $y, $text, $textcolor);
    
    // Save the image
    imagejpeg($image, $avatarDir . 'default.jpg', 90);
    imagedestroy($image);
    
    echo "Created default avatar image<br>";
}

echo "<p>Default avatar path: {$avatarDir}default.jpg</p>";
echo "<p>Preview:</p>";
echo "<img src='{$avatarDir}default.jpg' style='max-width: 100px; border: 1px solid #ddd;'>";
?>
