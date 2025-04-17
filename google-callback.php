<?php
session_start();

$client_id = '1043957684282-e3oc7don9hun53bmg23bqvk8ie6lr1tu.apps.googleusercontent.com';
$client_secret = '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';
$redirect_uri = 'http://alona-klymiuk.rf.gd/google-callback.php';

try {
    if (!isset($_GET['code']) || empty($_GET['code'])) {
        throw new Exception('Code parameter missing.');
    }
    $code = $_GET['code'];

    $token_url = 'https://oauth2.googleapis.com/token';
    $post_data = [
        'code' => $code,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    $token_data = json_decode($response, true);
    if (!isset($token_data['access_token'])) {
        throw new Exception('Unable to get access token: ' . ($token_data['error_description'] ?? 'Unknown error'));
    }
    $access_token = $token_data['access_token'];

    $user_info_url = 'https://www.googleapis.com/oauth2/v3/userinfo';
    $headers = ['Authorization: Bearer ' . $access_token];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $user_info_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $user_info_response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL error fetching user info: ' . curl_error($ch));
    }
    curl_close($ch);

    $user_info = json_decode($user_info_response, true);
    if (!isset($user_info['email'])) {
        throw new Exception('Failed to retrieve user information.');
    }

    $_SESSION['user'] = [
        'email' => $user_info['email'],
        'name' => $user_info['name'],
        'picture' => $user_info['picture'] ?? null
    ];
    $_SESSION['username'] = $user_info['email']; 
    $_SESSION['auth_method'] = 'google';

    $db_host = 'sql303.infinityfree.com';
    $db_user = 'if0_38517244';
    $db_pass = 'SPToatk2FrXDP';
    $db_name = 'if0_38517244_web_labs_php';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_errno) {
        throw new Exception('Database connection failed: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');

    $username = $user_info['email'];
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    if (!$stmt) {
        throw new Exception('Database query error: ' . $mysqli->error);
    }
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, auth_provider) VALUES (?, ?, 'google')");
        if (!$stmt) {
            throw new Exception('Database query error: ' . $mysqli->error);
        }
        $stmt->bind_param('ss', $username, $user_info['email']);
        if (!$stmt->execute()) {
            throw new Exception('Failed to create user: ' . $mysqli->error);
        }
        $user_id = $mysqli->insert_id;

        $stmt = $mysqli->prepare("INSERT INTO user_passwords (user_id, password_type, password_value) VALUES (?, 'google', 'N/A')");
        if (!$stmt) {
            throw new Exception('Database query error: ' . $mysqli->error);
        }
        $stmt->bind_param('i', $user_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to insert password entry: ' . $mysqli->error);
        }
        $stmt->close();
    }

    $mysqli->close();

    header('Location: index.php');
    exit;

} catch (Exception $e) {
    error_log('Google-callback.php error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    $_SESSION['error'] = 'Authentication failed: ' . htmlspecialchars($e->getMessage());
    header('Location: login.php');
    exit;
}