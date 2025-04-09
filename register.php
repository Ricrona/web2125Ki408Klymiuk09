<?php
session_start();

$db_host = getenv('DB_HOST') ?: 'sql303.infinityfree.com';
$db_user = getenv('DB_USER') ?: 'if0_38517244';
$db_pass = getenv('DB_PASS') ?: 'SPToatk2FrXDP';
$db_name = getenv('DB_NAME') ?: 'if0_38517244_web_labs_php';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) {
    die("Database connection error: " . $mysqli->connect_error);
}

$encryption_method = "AES-256-CBC";
$secret_key = "my_secret_key";
$secret_iv = "my_secret_iv";
$key = hash('sha256', $secret_key);
$iv = substr(hash('sha256', $secret_iv), 0, 16);

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? "");
    $password = trim($_POST['password'] ?? "");
    
    if (empty($username) || empty($password)) {
        $message = "Please fill in all fields.";
        $messageType = "danger";
    } else {
        // Check if a user with this username already exists
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $message = "A user with this username already exists.";
            $messageType = "danger";
        } else {
            // Adding user
            $stmt = $mysqli->prepare("INSERT INTO users (username) VALUES (?)");
            $stmt->bind_param('s', $username);
            if ($stmt->execute()) {
                $user_id = $mysqli->insert_id;
                
                // Generating password values
                $plain = $password;
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $encrypted = openssl_encrypt($password, $encryption_method, $key, 0, $iv);
                
                // Inserting data into user_passwords for each type
                $stmtPass = $mysqli->prepare("INSERT INTO user_passwords (user_id, password_type, password_value) VALUES (?, ?, ?)");
                foreach (['plain' => $plain, 'hash' => $hash, 'encrypted' => $encrypted] as $type => $passValue) {
                    $stmtPass->bind_param("iss", $user_id, $type, $passValue);
                    $stmtPass->execute();
                }
                
                $message = "User registered successfully.";
                $messageType = "success";
            } else {
                $message = "User registration error.";
                $messageType = "danger";
            }
        }
        $stmt->close();
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Registration</title>
  <link rel="stylesheet" href="styles/style.css">
</head>
<body>
  <div class="container">
    <h1>Registration</h1>
    <?php if ($message): ?>
      <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="">
      <label for="username">Username:</label>
      <input type="text" name="username" id="username" required>
      
      <label for="password">Password:</label>
      <input type="password" name="password" id="password" required>
      
      <button type="submit">Register</button>
    </form>
    <p>Go to home page <a href="index.php">Home page</a></p>
    <p>Already have an account? <a href="login.php">Login</a></p>
  </div>
</body>
</html>
