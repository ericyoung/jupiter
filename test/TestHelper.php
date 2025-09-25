<?php
// test/TestHelper.php

class TestHelper {
    private static $baseUrl = "http://localhost:8000"; // Should match SITE_URL from config
    
    /**
     * Make an HTTP request to the application
     */
    public static function makeRequest($path, $method = "GET", $data = [], $headers = []) {
        $url = self::$baseUrl . "/" . ltrim($path, "/");
        
        $options = [
            "http" => [
                "method" => $method,
                "header" => array_merge([
                    "Content-Type: application/x-www-form-urlencoded",
                    "User-Agent: Jupiter-PHP-Test-Framework/1.0"
                ], $headers),
                "ignore_errors" => true
            ]
        ];
        
        if ($method === "POST" && !empty($data)) {
            $options["http"]["content"] = http_build_query($data);
        }
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        return [
            "status" => $http_response_header[0] ?? "HTTP/1.1 200 OK",
            "headers" => $http_response_header ?? [],
            "body" => $result
        ];
    }
    
    /**
     * Check if a response contains expected content
     */
    public static function assertContains($response, $expected) {
        return strpos($response["body"], $expected) !== false;
    }
    
    /**
     * Check if a response has a specific status code
     */
    public static function assertStatusCode($response, $expectedCode) {
        return strpos($response["status"], (string)$expectedCode) !== false;
    }
    
    /**
     * Helper to extract CSRF token from HTML response
     */
    public static function extractCSRFToken($response) {
        preg_match("/name=\"csrf_token\" value=\"([^\"]+)\"/", $response["body"], $matches);
        return $matches[1] ?? null;
    }
    
    /**
     * Login helper function
     */
    public static function login($email, $password) {
        // First get the login page to extract CSRF token
        $getLogin = self::makeRequest("auth/login.php");
        $token = self::extractCSRFToken($getLogin);
        
        if (!$token) {
            return false;
        }
        
        // Submit login form
        $loginData = [
            "email" => $email,
            "password" => $password,
            "csrf_token" => $token
        ];
        
        return self::makeRequest("auth/login.php", "POST", $loginData);
    }
    
    // Static properties to track test results
    private static $testCount = 0;
    private static $passedCount = 0;
    private static $failedCount = 0;
    private static $failedTests = [];

    /**
     * Simple test runner
     */
    public static function runTest($name, $testFunction) {
        self::$testCount++;
        echo "Running test: $name\n";
        $result = $testFunction();
        if ($result === true) {
            echo "✓ PASSED\n\n";
            self::$passedCount++;
            return true;
        } else {
            echo "✗ FAILED: $result\n\n";
            self::$failedCount++;
            self::$failedTests[] = [$name, $result];
            return false;
        }
    }

    /**
     * Get test summary
     */
    public static function getSummary() {
        return [
            'total' => self::$testCount,
            'passed' => self::$passedCount,
            'failed' => self::$failedCount,
            'failed_tests' => self::$failedTests
        ];
    }

    /**
     * Print test results summary
     */
    public static function printSummary() {
        $summary = self::getSummary();
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        echo "Total Tests: " . $summary['total'] . "\n";
        echo "Passed:      " . $summary['passed'] . "\n";
        echo "Failed:      " . $summary['failed'] . "\n";
        echo str_repeat("-", 50) . "\n";
        
        if (!empty($summary['failed_tests'])) {
            echo "FAILED TESTS:\n";
            foreach ($summary['failed_tests'] as $failedTest) {
                echo "  - " . $failedTest[0] . ": " . $failedTest[1] . "\n";
            }
        } elseif ($summary['total'] > 0) {
            echo "All tests passed! ✓\n";
        }
        
        echo str_repeat("=", 50) . "\n";
    }
}
