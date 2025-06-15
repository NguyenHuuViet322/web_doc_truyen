<?php
// This script tests file upload functionality
require_once 'includes/functions.php';

// Check if the upload directories exist
echo "<h1>Upload Directory Check</h1>";
$dirs = [
    'uploads',
    'uploads/avatars',
    'uploads/comics',
    'uploads/chapters'
];

foreach ($dirs as $dir) {
    echo "<p>Directory: $dir<br>";
    echo "Exists: " . (is_dir($dir) ? 'Yes' : 'No') . "<br>";
    
    if (is_dir($dir)) {
        echo "Writable: " . (is_writable($dir) ? 'Yes' : 'No');
        
        // Create a test file to verify write permissions
        $test_file = $dir . '/test_' . time() . '.txt';
        $result = @file_put_contents($test_file, 'test');
        
        if ($result !== false) {
            echo " (Verified by creating test file)<br>";
            @unlink($test_file); // Clean up test file
        } else {
            echo " (Failed to create test file)<br>";
        }
        
        // Get directory permissions
        echo "Permissions: " . substr(sprintf('%o', fileperms($dir)), -4) . "<br>";
    }
    
    echo "</p>";
}

// Create upload form for testing
?>

<h2>Test File Upload</h2>
<form action="test_upload.php" method="post" enctype="multipart/form-data">
    <div>
        <label for="fileToUpload">Select image to upload:</label>
        <input type="file" name="fileToUpload" id="fileToUpload">
    </div>
    <div>
        <label for="uploadDir">Upload directory:</label>
        <select name="uploadDir" id="uploadDir">
            <option value="uploads/">uploads/</option>
            <option value="uploads/avatars/">uploads/avatars/</option>
            <option value="uploads/comics/">uploads/comics/</option>
            <option value="uploads/chapters/">uploads/chapters/</option>
        </select>
    </div>
    <div>
        <input type="submit" value="Upload Image" name="submit">
    </div>
</form>

<?php
// Process the upload if the form was submitted
if (isset($_POST['submit']) && !empty($_FILES['fileToUpload']['name'])) {
    $target_dir = $_POST['uploadDir'];
    
    echo "<h2>Upload Results</h2>";
    echo "<pre>";
    print_r(debugFileUpload($_FILES['fileToUpload'], $target_dir));
    echo "</pre>";
    
    $upload_result = uploadFile($_FILES['fileToUpload'], $target_dir);
    
    if ($upload_result) {
        echo "<p style='color: green;'>File was successfully uploaded. Saved as: $upload_result</p>";
        echo "<img src='{$target_dir}{$upload_result}' style='max-width: 300px;'>";
    } else {
        echo "<p style='color: red;'>Sorry, there was an error uploading your file.</p>";
    }
}
?>
