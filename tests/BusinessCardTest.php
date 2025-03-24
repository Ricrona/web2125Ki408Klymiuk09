<?php
use PHPUnit\Framework\TestCase;

class BusinessCardTest extends TestCase
{
    public function testGetPageLoads()
    {
        $baseUrl = getenv('BASE_URL') ?: 'http://localhost/web2125Ki408Klymiuk09';
        $output = file_get_contents($baseUrl . '/get_page.php');

        $this->assertStringContainsString('GET Request Page', $output);
    }

    public function testPostPageLoads()
    {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(['info' => 'Test data']),
            ],
        ];
        $context  = stream_context_create($options);
        $baseUrl = getenv('BASE_URL') ?: 'http://localhost/web2125Ki408Klymiuk09';
        $output = file_get_contents($baseUrl . '/post_page.php', false, $context);

        $this->assertStringContainsString('POST Request Page', $output);
    }

    public function testAjaxGet()
    {
        $baseUrl = getenv('BASE_URL') ?: 'http://localhost/web2125Ki408Klymiuk09';
        $response = file_get_contents($baseUrl . '/process_get.php?data=test');

        $this->assertStringContainsString('GET AJAX Response: test', $response);
    }

    public function testAjaxPost()
    {
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(['data' => 'test']),
            ],
        ];
        $context  = stream_context_create($options);
        $baseUrl = getenv('BASE_URL') ?: 'http://localhost/web2125Ki408Klymiuk09';
        $response = file_get_contents($baseUrl . '/process_post.php', false, $context);

        $this->assertStringContainsString('POST AJAX Response: test', $response);
    }
}
