<?php
session_start();

$db_host = 'sql303.infinityfree.com';
$db_user = 'if0_38517244';
$db_pass = 'SPToatk2FrXDP';
$db_name = 'if0_38517244_web_labs_php';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
    $_SESSION['error'] = 'Database connection failed: ' . $mysqli->connect_error;
}
$mysqli->set_charset('utf8mb4');

$phone_number = null;
$verified = 0;
$pending_verification = false;
$pending_phone_number = null;
$user_id = null;
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $stmt = $mysqli->prepare("SELECT id, phone_number, verified FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $user_id = $user['id'];
        $phone_number = $user['phone_number'] ?? null;
        $verified = $user['verified'] ?? 0;

        if (!$verified && !$phone_number) {
            $stmt = $mysqli->prepare("SELECT phone_number FROM verification_codes WHERE user_id = ? AND expires_at > NOW()");
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $pending = $result->fetch_assoc();
                    $pending_verification = true;
                    $pending_phone_number = $pending['phone_number'];
                }
                $stmt->close();
            }
        }
    }
}

function sendWhatsAppMessage($to, $from, $message_body) {
    global $twilio_sid, $twilio_token;
    $url = "https://api.twilio.com/2010-04-01/Accounts/$twilio_sid/Messages.json";
    $auth = base64_encode("$twilio_sid:$twilio_token");
    $post_data = http_build_query([
        'From' => $from,
        'To' => $to,
        'Body' => $message_body
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('cURL error: ' . $error);
    }

    $response_data = json_decode($response, true);
    if ($http_code >= 200 && $http_code < 300 && isset($response_data['sid'])) {
        return $response_data['sid'];
    } elseif (isset($response_data['code'], $response_data['message'])) {
        throw new Exception('Twilio error [' . $response_data['code'] . ']: ' . $response_data['message']);
    } else {
        throw new Exception('Unexpected response: HTTP ' . $http_code);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number']) && isset($_SESSION['username']) && !$pending_verification) {
    $new_phone_number = trim($_POST['phone_number']);
    if (!preg_match('/^\+\d{10,15}$/', $new_phone_number)) {
        $_SESSION['error'] = 'Invalid phone number format (e.g., +12345678901).';
    } else {
        try {
            $code = sprintf("%06d", mt_rand(0, 999999));
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $stmt = $mysqli->prepare("INSERT INTO verification_codes (user_id, phone_number, code, expires_at) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('isss', $user_id, $new_phone_number, $code, $expires_at);
            if (!$stmt->execute()) {
                throw new Exception('Failed to store verification code: ' . $mysqli->error);
            }
            $stmt->close();

            try {
                $message_body = "Your verification code for alona-klymiuk.rf.gd is: $code. It expires in 10 minutes.";
                $message_id = sendWhatsAppMessage("whatsapp:$new_phone_number", $twilio_whatsapp_number, $message_body);
                $_SESSION['success'] = 'Verification code sent to your WhatsApp number.';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Failed to send verification code: ' . htmlspecialchars($e->getMessage()) . '. Ensure you joined the WhatsApp sandbox by sending "' . $twilio_sandbox_code . '" to ' . $twilio_whatsapp_number . '.';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to process phone number: ' . htmlspecialchars($e->getMessage());
        }
    }
    header('Location: index.php');
    exit;
}

// Handle verification code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code']) && isset($_SESSION['username']) && $pending_verification) {
    $submitted_code = trim($_POST['verification_code']);
    try {
        // Verify code
        $stmt = $mysqli->prepare("SELECT code FROM verification_codes WHERE user_id = ? AND phone_number = ? AND code = ? AND expires_at > NOW()");
        if (!$stmt) {
            throw new Exception('Database prepare failed: ' . $mysqli->error);
        }
        $stmt->bind_param('iss', $user_id, $pending_phone_number, $submitted_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $code_match = $result->num_rows > 0;
        $stmt->close();

        if (!$code_match) {
            $_SESSION['error'] = 'Invalid or expired verification code.';
        } else {
            $stmt = $mysqli->prepare("UPDATE users SET phone_number = ?, verified = 1 WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('si', $pending_phone_number, $user_id);
            if (!$stmt->execute()) {
                if ($mysqli->errno === 1062) {
                    throw new Exception('This phone number is already in use by another account.');
                }
                throw new Exception('Failed to update phone number: ' . $mysqli->error);
            }
            $stmt->close();

            $stmt = $mysqli->prepare("DELETE FROM verification_codes WHERE user_id = ? AND phone_number = ?");
            if (!$stmt) {
                throw new Exception('Database prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('is', $user_id, $pending_phone_number);
            $stmt->execute();
            $stmt->close();

            try {
                $message_body = "Hello, your account at alona-klymiuk.rf.gd has been verified! Email: $username, Phone: $pending_phone_number.";
                $message_id = sendWhatsAppMessage("whatsapp:$pending_phone_number", $twilio_whatsapp_number, $message_body);
                $_SESSION['success'] = 'Phone number verified successfully.';
            } catch (Exception $e) {
                $_SESSION['success'] = 'Phone number verified, but confirmation message failed to send: ' . htmlspecialchars($e->getMessage());
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Verification failed: ' . htmlspecialchars($e->getMessage());
    }
    header('Location: index.php');
    exit;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Business Card & Authentication</title>
  <link rel="stylesheet" type="text/css" href="styles/style.css">
</head>
<body>
  <div class="container">
    <h1>My Business Card</h1>
    <p>Name: Alona Klymiuk</p>
    <p>Position: Student of KI</p>
    <p>Task repo: <a href="https://github.com/Ricrona/web2125Ki408Klymiuk09/tree/main" target="_blank">click here!</a></p>
    <p>Contact: alyona.klymiyk.ki.2021@lpnu.ua</p>

    <!-- Verification Form -->
    <?php if (isset($_SESSION['username']) && (!$verified || !$phone_number)): ?>
      <h2>Please Verify Your Account</h2>
      <?php if (isset($_SESSION['error'])): ?>
        <div class="message danger"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['success'])): ?>
        <div class="message success"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
      <?php endif; ?>
      <?php if ($pending_verification): ?>
        <p>Enter the verification code sent to <?php echo htmlspecialchars($pending_phone_number); ?> via WhatsApp.</p>
        <form method="post">
          <label for="verification_code">Verification Code</label>
          <input type="text" id="verification_code" name="verification_code" placeholder="Enter 6-digit code" required>
          <button type="submit">Verify Code</button>
        </form>
        <p><a href="index.php">Resend code</a></p>
      <?php else: ?>
        <p>Enter your WhatsApp phone number to receive a verification code. <strong>First, join our WhatsApp sandbox by sending "<?php echo htmlspecialchars($twilio_sandbox_code); ?>" to <?php echo htmlspecialchars($twilio_whatsapp_number); ?>.</strong></p>
        <form method="post">
          <label for="phone_number">Phone Number (e.g., +12345678901)</label>
          <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number ?? ''); ?>" placeholder="+12345678901" required>
          <button type="submit">Send Verification Code</button>
        </form>
      <?php endif; ?>
      <hr>
    <?php endif; ?>

    <!-- Auth section -->
    <?php if (isset($_SESSION['username'])): ?>
      <div class="auth-message">
        <p>Logged in as "<?php echo htmlspecialchars($_SESSION['username']); ?>". 
          <?php if ($verified && $phone_number): ?>
            (Verified: <?php echo htmlspecialchars($phone_number); ?>)
          <?php endif; ?>
          <a href="logout.php">Logout</a>.
        </p>
        <p>Also you can <a href="login.php">Login</a> by use another method</p>
      </div>
    <?php else: ?>
      <div class="auth-message">
        <p>You are not logged in. <a href="login.php">Login</a> or <a href="register.php">Register</a></p>
      </div>
    <?php endif; ?>

    <hr>

    <!-- Non-AJAX Forms -->
    <h2>Non-AJAX Forms</h2>
    <h3>GET Form (Non-AJAX)</h3>
    <form method="GET" action="">
      <label for="non_ajax_get">Enter GET value:</label>
      <input type="text" name="non_ajax_get" id="non_ajax_get">
      <button type="submit">Submit GET</button>
    </form>
    
    <h3>POST Form (Non-AJAX)</h3>
    <form method="POST" action="">
      <label for="non_ajax_post">Enter POST value:</label>
      <input type="text" name="non_ajax_post" id="non_ajax_post">
      <button type="submit">Submit POST</button>
    </form>

    <?php
    if (isset($_GET['non_ajax_get'])) {
        echo "<p>Non-AJAX GET value: " . htmlspecialchars($_GET['non_ajax_get']) . "</p>";
    }
    if (isset($_POST['non_ajax_post'])) {
        echo "<p>Non-AJAX POST value: " . htmlspecialchars($_POST['non_ajax_post']) . "</p>";
    }
    ?>

    <hr>

    <!-- AJAX Forms -->
    <h2>AJAX Forms</h2>
    <h3>AJAX POST Form</h3>
    <form id="ajaxPostForm">
      <input type="text" name="data" placeholder="Enter some data">
      <button type="submit">Submit POST</button>
    </form>
    <div id="postResponse"></div>
    
    <h3>AJAX GET Form</h3>
    <form id="ajaxGetForm">
      <input type="text" name="data" placeholder="Enter some data">
      <button type="submit">Submit GET</button>
    </form>
    <div id="getResponse"></div>
    
    <script>
      document.getElementById('ajaxPostForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var formData = new FormData(this);
        fetch('process_post.php', { method: 'POST', body: formData })
          .then(response => response.text())
          .then(data => {
            document.getElementById('postResponse').innerHTML = data;
          })
          .catch(error => console.error('Error:', error));
      });

      document.getElementById('ajaxGetForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var data = new URLSearchParams(new FormData(this));
        fetch('process_get.php?' + data.toString(), { method: 'GET' })
          .then(response => response.text())
          .then(data => {
            document.getElementById('getResponse').innerHTML = data;
          })
          .catch(error => console.error('Error:', error));
      });
    </script>

    <footer>
      <p>Page loaded at: <?php echo date("Y-m-d H:i:s"); ?></p>
    </footer>
  </div>
</body>
</html>