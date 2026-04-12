@echo off
title ZKTeco Device Setup - Network 192.168.0.x
color 0A

echo ================================================
echo    ZKTeco Device Configuration Tool
echo ================================================
echo.
echo Current Configuration:
echo   Your Computer IP: 192.168.0.75
echo   Device NEW IP:    192.168.0.201
echo   Device Port:      4370
echo.
echo ================================================
echo    STEP 1: Configure Physical Device
echo ================================================
echo.
echo You need to change the IP address on your ZKTeco device:
echo.
echo FROM: 192.168.1.201
echo TO:   192.168.0.201
echo.
echo How to change device IP:
echo.
echo Method 1 - On Device Screen:
echo   1. Press M/ESC to enter menu
echo   2. Navigate to: System Settings ^> Network Settings
echo   3. Change IP Address to: 192.168.0.201
echo   4. Subnet Mask: 255.255.255.0
echo   5. Gateway: 192.168.0.1
echo   6. Save and restart device
echo.
echo Method 2 - Via Web Browser:
echo   1. Connect to device at: http://192.168.1.201
echo      (You may need temporary IP 192.168.1.100 on your PC)
echo   2. Login (default: admin/admin)
echo   3. Go to: Network ^> TCP/IP Settings
echo   4. Change IP to: 192.168.0.201
echo   5. Save and reboot device
echo.
echo Method 3 - Via ZKTeco Software:
echo   1. Open ZKBioSecurity or ZKAccess software
echo   2. Use "Search Device" or "Device Manager"
echo   3. Find device at 192.168.1.201
echo   4. Change IP to: 192.168.0.201
echo   5. Apply settings
echo.
echo ================================================

choice /C YN /M "Have you changed the device IP to 192.168.0.201"

if errorlevel 2 goto :end
if errorlevel 1 goto :test

:test
echo.
echo ================================================
echo    STEP 2: Testing Connection
echo ================================================
echo.

echo [1/4] Pinging device at 192.168.0.201...
ping -n 3 192.168.0.201 | findstr /i "Reply TTL time"
echo.

echo [2/4] Testing TCP port 4370...
powershell -Command "Test-NetConnection -ComputerName 192.168.0.201 -Port 4370 | Select-Object RemotePort, TcpTestSucceeded"
echo.

echo [3/4] Running PHP diagnostic...
php test_device_connection.php
echo.

echo [4/4] Checking Laravel configuration...
php artisan config:clear >nul 2>&1
echo Configuration cleared.
echo.

echo ================================================
echo    SETUP COMPLETE
echo ================================================
echo.
echo If all tests show SUCCESS:
echo   1. Restart your Laravel server (Ctrl+C, then: php artisan serve)
echo   2. Visit: http://127.0.0.1:8000/account/device-logs
echo   3. Device should show: "Device Connected"
echo.
echo If tests FAILED:
echo   - Check device screen for correct IP
echo   - Verify ethernet cable is connected
echo   - Check router/firewall settings
echo   - Review storage/logs/laravel.log for errors
echo.
pause

:end
echo.
echo Setup cancelled. Run this script again when ready.
pause
