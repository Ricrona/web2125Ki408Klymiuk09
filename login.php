<?php
session_start();

$db_host = getenv('DB_HOST') ?: 'sql303.infinityfree.com';
$db_user = getenv('DB_USER') ?: 'if0_38517244';    
$db_pass = getenv('DB_PASS') ?: 'SPToatk2FrXDP';
$db_name = getenv('DB_NAME') ?: 'if0_38517244_web_labs_php';  

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    die("Database connection error: " . $mysqli->connect_error);
}

$encryption_method = "AES-256-CBC";
$secret_key = "my_secret_key";   
$secret_iv  = "my_secret_iv";    
$key = hash('sha256', $secret_key);
$iv  = substr(hash('sha256', $secret_iv), 0, 16);

$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $method = $_POST['method'] ?? "";
    $username = trim($_POST['username'] ?? "");
    $password = trim($_POST['password'] ?? "");

    $client_pass = $password;
    if (empty($username) || empty($password)) {
         $message = "Please fill in all fields.";
         $messageType = "danger";
    } else {
         // Check if user exists
         $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
         $stmt->bind_param("s", $username);
         $stmt->execute();
         $result = $stmt->get_result();
         $user = $result->fetch_assoc();
         $stmt->close();
         if (!$user) {
             $message = "User not found.";
             $messageType = "danger";
         } else {
             $user_id = $user['id'];
             $stmt = $mysqli->prepare("SELECT password_value FROM user_passwords WHERE user_id = ? AND password_type = ?");
             $stmt->bind_param("is", $user_id, $method);
             $stmt->execute();
             $result = $stmt->get_result();
             $row = $result->fetch_assoc();
             $stmt->close();
             
             if (!$row) {
                 $message = "Undefined authentication method.";
                 $messageType = "danger";
             } else {
                 $stored_pass = $row['password_value'];
                 if ($method === "plain") {
                     if ($password === $stored_pass) {
                         $_SESSION['username'] = $username;
                         $message = "Authentication successful (plain password).<br>"
                           . "Password sent by client: " . htmlspecialchars($client_pass) . "<br>"
                           . "Password received by server: " . htmlspecialchars($stored_pass) . "<br>"
                           . "Password stored in database: " . htmlspecialchars($stored_pass);
                         $messageType = "success";
                     } else {
                         $message = "Incorrect password (plain password).";
                         $messageType = "danger";
                     }
                 } elseif ($method === "hash") {
                     if (password_verify($password, $stored_pass)) {
                         $_SESSION['username'] = $username;
                         $message = "Authentication successful (hashed password).<br>"
                           . "Password sent by client: " . htmlspecialchars($client_pass) . "<br>"
                           . "Password received by server: " . htmlspecialchars($stored_pass) . "<br>"
                           . "Password stored in database (hash): " . htmlspecialchars($stored_pass);
                         $messageType = "success";
                     } else {
                         $message = "Incorrect password (hashed password).";
                         $messageType = "danger";
                     }
                 } elseif ($method === "encrypted") {
                     $decrypted_pass = openssl_decrypt($stored_pass, $encryption_method, $key, 0, $iv);
                     if ($password === $decrypted_pass) {
                         $_SESSION['username'] = $username;
                         $message = "Authentication successful (encrypted password).<br>"
                           . "Password sent by client: " . htmlspecialchars($client_pass) . "<br>"
                           . "Password received by server: " . htmlspecialchars($stored_pass) . "<br>"
                           . "Password stored in database (encrypted): " . htmlspecialchars($client_pass);
                         $messageType = "success";
                     } else {
                         $message = "Incorrect password (encrypted password).";
                         $messageType = "danger";
                     }
                 } else {
                     $message = "Undefined authentication method.";
                     $messageType = "danger";
                 }
             }
         }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authentication (MySQL DB)</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <h1>Authentication</h1>
        <p>Go to home page <a href="index.php">Home page</a></p>
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['username'])): ?>
         <div class="message success">
                <p>You are logged in as <?php echo htmlspecialchars($_SESSION['username']); ?>.</p>
                <p><a href="logout.php">Logout</a></p>
            </div>
        <?php endif; ?>
       
        <h2>Login with Google</h2>
        <a href="google-login.php" class="google-login-btn">Login with Google</a>

            <h2>Login with plain password</h2>
            <form method="POST">
                <input type="hidden" name="method" value="plain">
                <label for="username_plain">Username:</label>
                <input type="text" name="username" id="username_plain" required>
                <label for="password_plain">Password:</label>
                <input type="password" name="password" id="password_plain" required>
                <button type="submit">Login (plain password)</button>
            </form>

            <h2>Login with hashed password</h2>
            <form method="POST">
                <input type="hidden" name="method" value="hash">
                <label for="username_hash">Username:</label>
                <input type="text" name="username" id="username_hash" required>
                <label for="password_hash">Password:</label>
                <input type="password" name="password" id="password_hash" required>
                <button type="submit">Login (hashed password)</button>
            </form>

            <h2>Login with encrypted password</h2>
            <form method="POST">
                <input type="hidden" name="method" value="encrypted">
                <label for="username_enc">Username:</label>
                <input type="text" name="username" id="username_enc" required>
                <label for="password_enc">Password:</label>
                <input type="password" name="password" id="password_enc" required>
                <button type="submit">Login (encrypted password)</button>
            </form>
    </div>
</body>
</html>
