<?php

namespace Tests\Integration;

class AdminUiTest
{
    private string $baseUrl = 'http://localhost';

    private function requestRaw(string $method, string $path, array $data = [], ?string $cookieFile = null): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        if ($cookieFile) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        }

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        return [
            'status' => $status,
            'headers' => $headers,
            'body' => $body
        ];
    }

    private function assertTrue(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \Exception("Assertion failed: {$message}");
        }
    }

    private function assertContains(string $needle, string $haystack, string $message): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new \Exception("Assertion failed: {$message}");
        }
    }

    private function getHeaderValue(string $headers, string $headerName): string
    {
        foreach (explode("\r\n", $headers) as $line) {
            if (stripos($line, $headerName . ':') === 0) {
                return trim(substr($line, strlen($headerName) + 1));
            }
        }
        return '';
    }

    public function test_admin_requires_login(): void
    {
        echo "\n✓ TEST: Admin Requires Login\n";
        $response = $this->requestRaw('GET', '/admin/dashboard');
        $location = $this->getHeaderValue($response['headers'], 'Location');

        $this->assertTrue($response['status'] === 302, 'Should redirect to login');
        $this->assertContains('/admin/login', $location, 'Should redirect to /admin/login');
    }

    public function test_admin_login_invalid(): void
    {
        echo "\n✓ TEST: Admin Login Invalid\n";
        $response = $this->requestRaw('POST', '/admin/login', [
            'username' => 'admin',
            'password' => 'wrong'
        ]);

        $this->assertTrue($response['status'] === 200, 'Should return login page');
        $this->assertContains('Lathos', $response['body'], 'Should show error message');
    }

    public function test_admin_login_success(): void
    {
        echo "\n✓ TEST: Admin Login Success\n";
        $cookieFile = sys_get_temp_dir() . '/tera_admin_cookie.txt';

        $response = $this->requestRaw('POST', '/admin/login', [
            'username' => 'admin',
            'password' => 'admin'
        ], $cookieFile);

        $location = $this->getHeaderValue($response['headers'], 'Location');
        $this->assertTrue($response['status'] === 302, 'Should redirect after login');
        $this->assertContains('/admin/dashboard', $location, 'Should redirect to dashboard');
    }
}
