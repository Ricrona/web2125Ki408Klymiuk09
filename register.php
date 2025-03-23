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
    if (!is_array($users)) {
        $users = [];
    }
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
    } elseif (isset($users[$username])) {
        $message = "Username already exists.";
        $messageType = "danger";
    } else {
        $password_plain = $password;
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $password_encrypted = openssl_encrypt($password, $encryption_method, $key, 0, $iv);

        $users[$username] = [
            'username'           => $username,
            'password_plain'     => $password_plain,
            'password_hash'      => $password_hash,
            'password_encrypted' => $password_encrypted
        ];

        file_put_contents($usersFile, json_encode($users));
        $message = "User registered successfully.";
        $messageType = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Registration</title>
  <link rel="stylesheet" type="text/css" href="styles/style.css">
</head>
<body>
  <div class="container">
    <h1>User Registration</h1>
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
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
    <p>Go to home page <a href="index.php">Home page</a></p>
  </div>
</body>
</html>
