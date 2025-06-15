<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kiểm tra biến SESSION</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .session-data {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        h1 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .avatar-preview {
            max-width: 100px;
            max-height: 100px;
            border-radius: 50%;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background-color: #0056b3;
        }
        .not-set {
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>Kiểm tra SESSION Variables</h1>
    
    <?php if(isset($_SESSION) && !empty($_SESSION)): ?>
        <div class="session-data">
            <h2>SESSION Data:</h2>
            <table>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                    <th>Type</th>
                </tr>
                <?php foreach($_SESSION as $key => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td>
                            <?php if($key === 'password' || $key === 'access_key' || $key === 'auth_token'): ?>
                                [HIDDEN FOR SECURITY]
                            <?php elseif($key === 'avatar'): ?>
                                <?php if(!empty($value)): ?>
                                    <img src="uploads/avatars/<?php echo htmlspecialchars($value); ?>" 
                                         alt="Avatar" class="avatar-preview"><br>
                                    Path: <?php echo htmlspecialchars($value); ?>
                                <?php else: ?>
                                    <span class="not-set">Not set</span>
                                <?php endif; ?>
                            <?php elseif(is_array($value) || is_object($value)): ?>
                                <pre><?php print_r($value); ?></pre>
                            <?php else: ?>
                                <?php echo !empty($value) ? htmlspecialchars($value) : '<span class="not-set">Empty</span>'; ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo gettype($value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php else: ?>
        <p>No SESSION data found. Please <a href="login.php">login</a> first.</p>
    <?php endif; ?>
    
    <h2>Database Test</h2>
    <?php
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if($conn) {
            echo "<p style='color:green;'>✓ Database connection successful!</p>";
            
            // Test if the users table exists and has the expected columns
            $stmt = $conn->query("SHOW COLUMNS FROM users");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<p>Users table columns: " . implode(", ", $columns) . "</p>";
            
            // If logged in, fetch current user data directly from DB
            if(isset($_SESSION['user_id'])) {
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if($user) {
                    echo "<h3>Current User Data from Database:</h3>";
                    echo "<table>";
                    echo "<tr><th>Column</th><th>Value</th></tr>";
                    foreach($user as $col => $val) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($col) . "</td>";
                        if($col === 'password') {
                            echo "<td>[HIDDEN]</td>";
                        } else {
                            echo "<td>" . (is_null($val) ? "<span class='not-set'>NULL</span>" : htmlspecialchars($val)) . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>Current user not found in database. User ID: " . $_SESSION['user_id'] . "</p>";
                }
            }
        } else {
            echo "<p style='color:red;'>✗ Database connection failed!</p>";
        }
    } catch(PDOException $e) {
        echo "<p style='color:red;'>✗ Database Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
    
    <a href="index.php" class="back-link">Back to Homepage</a>
</body>
</html>
