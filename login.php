<?php
session_start();

$encryption_method = "AES-256-CBC";
$secret_key = "my_secret_key"; 
$secret_iv = "my_secret_iv";   
$key = hash('sha256', $secret_key);
$iv = substr(hash('sha256', $secret_iv), 0, 16);

$usersFile = 'users.json';
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true);
} else {
    $users = [];
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $message = "Please fill in both username and password.";
        $messageType = "danger";
    } elseif (!isset($users[$username])) {
        $message = "User does not exist.";
        $messageType = "danger";
    } else {
        $user = $users[$username];
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['username'] = $username;
            $message = "Login successful. Welcome, " . htmlspecialchars($username) . "!";
            $messageType = "success";
        } else {
            $message = "Incorrect password.";
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Login</title>
  <link rel="stylesheet" type="text/css" href="styles/style.css">
</head>
<body>
  <div class="container">
    <h1>User Login</h1>
    <?php if ($message): ?>
      <div class="message <?php echo $messageType; ?>">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
    <?php if (!isset($_SESSION['username'])): ?>
      <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" required>
        
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        
        <button type="submit">Login</button>
      </form>
      <p>Don't have an account? <a href="register.php">Register here</a>.</p>
    <?php else: ?>
      <div class="message">
        <p>You are logged in as <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>
        <p><a href="logout.php">Logout</a></p>
      </div>
    <?php endif; ?>
    <p>Go to home page <a href="index.php">Home page</a></p>
  </div>
</body>
</html>
