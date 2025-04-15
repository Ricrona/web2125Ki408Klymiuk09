<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected $baseUrl;
    protected $mysqli;

    protected function setUp(): void
    {
        $this->baseUrl = getenv('BASE_URL') ?: 'http://localhost:8000';

        $db_host = getenv('DB_HOST') ?: 'localhost';
        $db_user = getenv('DB_USER') ?: 'root';
        $db_pass = getenv('DB_PASS') ?: 'root';
        $db_name = getenv('DB_NAME') ?: 'test_db';

        $this->mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($this->mysqli->connect_errno) {
            die("Database connection error: " . $this->mysqli->connect_error);
        }

        $this->mysqli->query("DELETE FROM users WHERE username LIKE 'testuser_%'");
    }

    protected function tearDown(): void
    {
        $this->mysqli->query("DELETE FROM users WHERE username LIKE 'testuser_%'");
        $this->mysqli->close();
    }

    protected function registerUser($username, $password)
    {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'username' => $username,
                    'password' => $password
                ]),
            ],
        ];
        $context = stream_context_create($options);
        return file_get_contents($this->baseUrl . '/register.php', false, $context);
    }

    public function testLoginEmptyFields()
    {
        $postData = [
            'username' => '',
            'password' => '',
            'method'   => 'plain'
        ];
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($postData),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Please fill in all fields.', $output);
    }

    public function testLoginNonExistentUser()
    {
        $postData = [
            'username' => 'nonexistentuser',
            'password' => 'any',
            'method'   => 'plain'
        ];
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($postData),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('User not found.', $output);
    }

    public function testLoginIncorrectPassword()
    {
        // Register a new user with a unique username.
        $uniqueUsername = 'testuser_' . uniqid();
        $this->registerUser($uniqueUsername, 'correctpassword');

        $postData = [
            'username' => $uniqueUsername,
            'password' => 'wrongpassword',
            'method'   => 'plain'
        ];
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($postData),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Incorrect password (plain password).', $output);
    }

    public function testLoginSuccessful()
    {
        // Register a new user and then attempt a correct login.
        $uniqueUsername = 'testuser2_' . uniqid();
        $this->registerUser($uniqueUsername, 'mypassword');

        $postData = [
            'username' => $uniqueUsername,
            'password' => 'mypassword',
            'method'   => 'plain'
        ];
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($postData),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Authentication successful (plain password).', $output);
    }
}
