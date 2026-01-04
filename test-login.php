<?php
/**
 * Login debugging page
 */

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/User.php';

echo "=== Login Debug ===\n\n";

$email = 'test@test.com';
$password = 'test1234';

try {
    $userModel = new User();

    echo "1. Attempting to find user by email: $email\n";
    $user = $userModel->findByEmail($email);

    if ($user) {
        echo "   ✓ User found!\n";
        echo "   ID: {$user['id']}\n";
        echo "   Name: {$user['name']}\n";
        echo "   Email: {$user['email']}\n";
        echo "   Password Hash: " . substr($user['password_hash'], 0, 20) . "...\n\n";

        echo "2. Verifying password: $password\n";
        $verified = password_verify($password, $user['password_hash']);

        if ($verified) {
            echo "   ✓ Password verified!\n\n";

            echo "3. Testing authenticate method\n";
            $authUser = $userModel->authenticate($email, $password);

            if ($authUser) {
                echo "   ✓ Authentication successful!\n";
            } else {
                echo "   ✗ Authentication failed!\n";
            }
        } else {
            echo "   ✗ Password verification failed!\n";
            echo "   Expected password: $password\n";
            echo "   Hash in DB: {$user['password_hash']}\n\n";

            // Test with known hash
            echo "4. Testing password_verify with known values\n";
            $testHash = '$2y$10$N9qo8uLOickgx2ZMRZoMye1IVI.9J6WqDqYZ3FqkMqZpVHXVKv6mO';
            $testPassword = 'test1234';
            $testResult = password_verify($testPassword, $testHash);
            echo "   password_verify('test1234', known_hash) = " . ($testResult ? 'true' : 'false') . "\n";
        }
    } else {
        echo "   ✗ User not found!\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
