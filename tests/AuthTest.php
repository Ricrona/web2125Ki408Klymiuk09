<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected $baseUrl;

    protected function setUp(): void
    {
        // Use the correct BASE_URL for testing (make sure your GitHub Action or local setup sets this)
        $this->baseUrl = getenv('BASE_URL') ?: 'http://localhost:8000';
        // Optionally, you may add code here to clean out test records from the database.
    }

    /**
     * Helper function to register a user using registration.php.
     */
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
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'username' => '',
                    'password' => '',
                    'method'   => 'plain'
                ]),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Please fill in all fields.', $output);
    }

    public function testLoginNonExistentUser()
    {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'username' => 'nonexistentuser',
                    'password' => 'any',
                    'method'   => 'plain'
                ]),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('User not found.', $output);
    }

    public function testLoginIncorrectPassword()
    {
        // Register a unique test user.
        $uniqueUsername = 'testuser_' . uniqid();
        $this->registerUser($uniqueUsername, 'correctpassword');

        // Attempt to login with an incorrect password using the "plain" method.
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'username' => $uniqueUsername,
                    'password' => 'wrongpassword',
                    'method'   => 'plain'
                ]),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Incorrect password (plain password).', $output);
    }

    public function testLoginSuccessful()
    {
        // Register a unique test user.
        $uniqueUsername = 'testuser2_' . uniqid();
        $this->registerUser($uniqueUsername, 'mypassword');

        // Attempt to login with the correct credentials using the "plain" method.
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query([
                    'username' => $uniqueUsername,
                    'password' => 'mypassword',
                    'method'   => 'plain'
                ]),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Authentication successful (plain password).', $output);
    }
}
