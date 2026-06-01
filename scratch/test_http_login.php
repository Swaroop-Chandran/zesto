<?php
/**
 * Zesto — Test HTTP Register and Login exactly like the frontend AJAX drawer does.
 */

// We will make HTTP requests to localhost/zesto/
$baseUrl = 'http://localhost/zesto';

function make_request($url, $method = 'GET', $data = null, $cookies = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    $headers = [
        'Content-Type: application/json'
    ];
    if (!empty($data) && isset($data['csrf_token'])) {
        $headers[] = 'X-CSRF-Token: ' . $data['csrf_token'];
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($cookies) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookies);
    }
    
    $response = curl_exec($ch);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    // Extract cookies
    preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
    $new_cookies = [];
    foreach($matches[1] as $item) {
        parse_str($item, $cookie);
        $new_cookies = array_merge($new_cookies, $cookie);
    }
    
    curl_close($ch);
    return [
        'header' => $header,
        'body' => $body,
        'cookies' => $new_cookies
    ];
}

try {
    // Helper function to get fresh session & CSRF with cookie affinity
    $getFreshToken = function($cookies = '') use ($baseUrl) {
        $res = make_request($baseUrl . '/index.php', 'GET', null, $cookies);
        preg_match('/<meta name="csrf-token" content="([^"]+)"/', $res['body'], $matches);
        $csrfToken = $matches[1] ?? '';
        
        $cookieStr = '';
        foreach ($res['cookies'] as $name => $val) {
            $cookieStr .= "$name=$val; ";
        }
        if (empty($cookieStr)) {
            $cookieStr = $cookies;
        }
        return [$csrfToken, $cookieStr];
    };

    // 2. Test Customer Login (Existing)
    list($csrfToken, $cookieStr) = $getFreshToken();
    echo "\n=== 2. ATTEMPTING CUSTOMER LOGIN (swaroop@zyrops.in) ===\n";
    $loginData = [
        'email' => 'swaroop@zyrops.in',
        'password' => 'password',
        'role' => 'customer',
        'csrf_token' => $csrfToken
    ];
    $loginRes = make_request($baseUrl . '/api/auth/login.php', 'POST', $loginData, $cookieStr);
    echo "Login Body:\n" . $loginRes['body'] . "\n";

    // 3. Test Restaurant Owner Login (Existing)
    list($csrfToken, $cookieStr) = $getFreshToken();
    echo "\n=== 3. ATTEMPTING RESTAURANT OWNER LOGIN (achuboss80@gmail.com) ===\n";
    $loginData = [
        'email' => 'achuboss80@gmail.com',
        'password' => 'password',
        'role' => 'restaurant_owner',
        'csrf_token' => $csrfToken
    ];
    $loginRes = make_request($baseUrl . '/api/auth/login.php', 'POST', $loginData, $cookieStr);
    echo "Login Body:\n" . $loginRes['body'] . "\n";

    // 4. Test newly registered customer
    list($csrfToken, $cookieStr) = $getFreshToken();
    echo "\n=== 4. REGISTERING NEW CUSTOMER ===\n";
    $custEmail = "cust" . time() . "@example.com";
    $regData = [
        'name' => 'New Customer',
        'email' => $custEmail,
        'phone' => '+919999911111',
        'password' => 'Zesto@123',
        'role' => 'customer',
        'csrf_token' => $csrfToken
    ];
    $regRes = make_request($baseUrl . '/api/auth/register.php', 'POST', $regData, $cookieStr);
    echo "Register Body:\n" . $regRes['body'] . "\n";

    // Extract cookies from registration response
    $regCookies = '';
    foreach ($regRes['cookies'] as $name => $val) {
        $regCookies .= "$name=$val; ";
    }
    if (empty($regCookies)) {
        $regCookies = $cookieStr;
    }

    echo "\n=== 5. LOGGING IN NEW CUSTOMER ===\n";
    list($csrfToken, $sessionCookies) = $getFreshToken($regCookies);
    $loginData = [
        'email' => $custEmail,
        'password' => 'Zesto@123',
        'role' => 'customer',
        'csrf_token' => $csrfToken
    ];
    $loginRes = make_request($baseUrl . '/api/auth/login.php', 'POST', $loginData, $sessionCookies);
    echo "Login Body:\n" . $loginRes['body'] . "\n";

    // 5. Test newly registered restaurant owner
    list($csrfToken, $cookieStr) = $getFreshToken();
    echo "\n=== 6. REGISTERING NEW RESTAURANT OWNER ===\n";
    $ownerEmail = "owner" . time() . "@example.com";
    $regData = [
        'name' => 'New Owner',
        'email' => $ownerEmail,
        'phone' => '+919999922222',
        'password' => 'Zesto@123',
        'role' => 'restaurant_owner',
        'csrf_token' => $csrfToken
    ];
    $regRes = make_request($baseUrl . '/api/auth/register.php', 'POST', $regData, $cookieStr);
    echo "Register Body:\n" . $regRes['body'] . "\n";

    $regCookies = '';
    foreach ($regRes['cookies'] as $name => $val) {
        $regCookies .= "$name=$val; ";
    }
    if (empty($regCookies)) {
        $regCookies = $cookieStr;
    }

    echo "\n=== 7. LOGGING IN NEW RESTAURANT OWNER ===\n";
    list($csrfToken, $sessionCookies) = $getFreshToken($regCookies);
    $loginData = [
        'email' => $ownerEmail,
        'password' => 'Zesto@123',
        'role' => 'restaurant_owner',
        'csrf_token' => $csrfToken
    ];
    $loginRes = make_request($baseUrl . '/api/auth/login.php', 'POST', $loginData, $sessionCookies);
    echo "Login Body:\n" . $loginRes['body'] . "\n";

    // 6. Test newly registered delivery partner
    list($csrfToken, $cookieStr) = $getFreshToken();
    echo "\n=== 8. REGISTERING NEW DELIVERY PARTNER ===\n";
    $driverEmail = "driver" . time() . "@example.com";
    $regData = [
        'name' => 'New Driver',
        'email' => $driverEmail,
        'phone' => '+919999933333',
        'password' => 'Zesto@123',
        'role' => 'delivery_partner',
        'vehicle_type' => 'bike',
        'vehicle_number' => 'MH 02 AA 1111',
        'driving_license_number' => 'DL-1111111111111',
        'csrf_token' => $csrfToken
    ];
    $regRes = make_request($baseUrl . '/api/auth/register.php', 'POST', $regData, $cookieStr);
    echo "Register Body:\n" . $regRes['body'] . "\n";

    $regCookies = '';
    foreach ($regRes['cookies'] as $name => $val) {
        $regCookies .= "$name=$val; ";
    }
    if (empty($regCookies)) {
        $regCookies = $cookieStr;
    }

    echo "\n=== 9. LOGGING IN NEW DELIVERY PARTNER (PENDING APPROVAL) ===\n";
    list($csrfToken, $sessionCookies) = $getFreshToken($regCookies);
    $loginData = [
        'email' => $driverEmail,
        'password' => 'Zesto@123',
        'role' => 'delivery_partner',
        'csrf_token' => $csrfToken
    ];
    $loginRes = make_request($baseUrl . '/api/auth/login.php', 'POST', $loginData, $sessionCookies);
    echo "Login Body:\n" . $loginRes['body'] . "\n";

} catch (Exception $e) {
    echo "🔴 ERROR: " . $e->getMessage() . "\n";
}
