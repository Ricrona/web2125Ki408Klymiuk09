<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected $baseUrl;
    protected $usersFile = 'users.json';

    protected function setUp(): void
    {
        $this->baseUrl = getenv('BASE_URL') ?: 'http://localhost/web2125Ki408Klymiuk09';

        if (file_exists($this->usersFile)) {
            unlink($this->usersFile);
        }
    }

    protected function registerUser($username, $password)
    {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(['username' => $username, 'password' => $password]),
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
                'content' => http_build_query(['username' => '', 'password' => '']),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Please fill in both username and password.', $output);
    }

    public function testLoginNonExistentUser()
    {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(['username' => 'nonexistentuser', 'password' => 'any']),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('User does not exist.', $output);
    }

    public function testLoginIncorrectPassword()
    {
        $this->registerUser('testuser', 'correctpassword');

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(['username' => 'testuser', 'password' => 'wrongpassword']),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Incorrect password.', $output);
    }

    public function testLoginSuccessful()
    {
        $this->registerUser('testuser2', 'mypassword');

        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(['username' => 'testuser2', 'password' => 'mypassword']),
            ],
        ];
        $context = stream_context_create($options);
        $output = file_get_contents($this->baseUrl . '/login.php', false, $context);

        $this->assertStringContainsString('Login successful. Welcome, testuser2!', $output);
    }
}
