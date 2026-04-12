<?php

/**
 * ZKTeco Device Connectivity Diagnostic Script
 * 
 * Usage: php test_device_connection.php
 */

$ip = getenv('ZKTECO_IP') ?: '192.168.0.201';
$port = getenv('ZKTECO_PORT') ?: 4370;

echo "========================================\n";
echo "ZKTeco Device Connectivity Test\n";
echo "========================================\n\n";

echo "Testing connection to: {$ip}:{$port}\n\n";

// Test 1: Ping the device
echo "[Test 1] Ping Test\n";
echo "----------------------------------------\n";
exec("ping -n 2 {$ip}", $pingOutput, $pingReturn);
echo implode("\n", $pingOutput) . "\n";
if ($pingReturn === 0) {
    echo "✓ Ping: SUCCESS\n";
} else {
    echo "✗ Ping: FAILED - Device is not reachable at {$ip}\n";
    echo "\nPossible causes:\n";
    echo "  - Device IP address is incorrect\n";
    echo "  - Device is on a different network subnet\n";
    echo "  - Firewall is blocking ICMP requests\n";
    echo "  - Device is powered off\n";
}
echo "\n";

// Test 2: TCP Port Check
echo "[Test 2] TCP Port Test (Port {$port})\n";
echo "----------------------------------------\n";
$connection = @fsockopen($ip, $port, $errno, $errstr, 5);
if ($connection) {
    echo "✓ TCP Connection: SUCCESS\n";
    echo "  Port {$port} is open and accepting connections\n";
    fclose($connection);
} else {
    echo "✗ TCP Connection: FAILED\n";
    echo "  Error Code: {$errno}\n";
    echo "  Error Message: {$errstr}\n";
    echo "\nPossible causes:\n";
    echo "  - Device is not powered on\n";
    echo "  - Firewall is blocking port {$port}\n";
    echo "  - Device service is not running\n";
    echo "  - Network routing issue\n";
}
echo "\n";

// Test 3: Check PHP Sockets Extension
echo "[Test 3] PHP Extensions\n";
echo "----------------------------------------\n";
if (extension_loaded('sockets')) {
    echo "✓ PHP sockets extension: ENABLED\n";
} else {
    echo "✗ PHP sockets extension: NOT ENABLED\n";
    echo "  Fix: Enable 'extension=sockets' in php.ini\n";
}

if (function_exists('socket_create')) {
    echo "✓ socket_create() function: AVAILABLE\n";
} else {
    echo "✗ socket_create() function: NOT AVAILABLE\n";
}
echo "\n";

// Test 4: Check ZKTeco Library
echo "[Test 4] ZKTeco Library\n";
echo "----------------------------------------\n";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    
    if (class_exists(\Rats\Zkteco\Lib\ZKTeco::class)) {
        echo "✓ ZKTeco Library: LOADED\n";
        echo "  Location: " . (new ReflectionClass(\Rats\Zkteco\Lib\ZKTeco::class))->getFileName() . "\n";
        
        // Try to instantiate and connect
        echo "\n[Test 5] ZKTeco SDK Connection\n";
        echo "----------------------------------------\n";
        try {
            $zk = new \Rats\Zkteco\Lib\ZKTeco($ip, $port);
            $connectResult = @$zk->connect();
            
            if ($connectResult) {
                echo "✓ SDK Connection: SUCCESS\n";

                // Get device info
                echo "\n[Device Information]\n";
                echo "----------------------------------------\n";
                echo "Platform: " . @$zk->platform() . "\n";
                echo "Serial Number: " . @$zk->serialNumber() . "\n";
                echo "Device Name: " . @$zk->deviceName() . "\n";
                echo "Firmware Version: " . @$zk->fmVersion() . "\n";
                echo "OS Version: " . @$zk->osVersion() . "\n";
                echo "Device Time: " . @$zk->getTime() . "\n";

                @$zk->disconnect();
            } else {
                echo "✗ SDK Connection: FAILED\n";
                echo "  The device is reachable but SDK protocol failed\n";
                echo "  Possible causes:\n";
                echo "    - Device protocol not compatible\n";
                echo "    - Device requires authentication\n";
                echo "    - Communication protocol error\n";
            }
        } catch (Exception $e) {
            echo "✗ SDK Connection: ERROR\n";
            echo "  Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ ZKTeco Library: NOT FOUND\n";
        echo "  Fix: Run 'composer require rats/zkteco'\n";
    }
} else {
    echo "✗ Vendor autoload not found\n";
    echo "  Fix: Run 'composer install'\n";
}
echo "\n";

// Test 5: Network Configuration
echo "[Test 6] Network Configuration\n";
echo "----------------------------------------\n";
$localIP = getHostByName(getHostName());
echo "Your Computer IP: {$localIP}\n";
echo "Device IP: {$ip}\n";

// Check if on same subnet
$localParts = explode('.', $localIP);
$deviceParts = explode('.', $ip);

if ($localParts[0] === $deviceParts[0] && $localParts[1] === $deviceParts[1]) {
    echo "✓ Network: Devices appear to be on the same subnet\n";
} else {
    echo "✗ Network: Devices are on DIFFERENT subnets!\n";
    echo "  Your network: {$localParts[0]}.{$localParts[1]}.x.x\n";
    echo "  Device network: {$deviceParts[0]}.{$deviceParts[1]}.x.x\n";
    echo "\n  SOLUTION: Either:\n";
    echo "    1. Change your computer IP to {$deviceParts[0]}.{$deviceParts[1]}.x.x\n";
    echo "    2. Change device IP to {$localParts[0]}.{$localParts[1]}.x.x\n";
    echo "    3. Configure network routing between subnets\n";
}
echo "\n";

echo "========================================\n";
echo "Diagnostic Complete\n";
echo "========================================\n";
