@echo off
echo Adding secondary IP address to communicate with ZKTeco device...
echo.
echo This will add 192.168.1.100 as a secondary IP on your Wi-Fi adapter.
echo Your main IP (192.168.0.75) will remain active for internet.
echo.
pause
echo.

REM Find the Wi-Fi adapter name and add secondary IP
for /f "tokens=*" %%a in ('powershell -Command "(Get-NetAdapter | Where-Object {$_.Status -eq 'Up' -and $_.InterfaceDescription -notlike '*Bluetooth*'} | Select-Object -First 1).Name"') do (
    set ADAPTER=%%a
)

if not defined ADAPTER (
    echo ERROR: Could not find active network adapter
    pause
    exit /b 1
)

echo Found adapter: %ADAPTER%
echo Adding secondary IP: 192.168.1.100
echo.

netsh interface ip add address "%ADAPTER%" 192.168.1.100 255.255.255.0

echo.
if %ERRORLEVEL% EQU 0 (
    echo SUCCESS! Secondary IP added.
    echo.
    echo Testing connection to device...
    ping -n 2 192.168.1.201
    echo.
    echo You can now access the device at 192.168.1.201:4370
    echo.
    echo To remove this IP later, run: remove_secondary_ip.bat
) else (
    echo FAILED! Make sure you run this as Administrator.
    echo.
    echo Manual method:
    echo 1. Press Win+R, type: ncpa.cpl
    echo 2. Right-click your Wi-Fi adapter - Properties
    echo 3. Select IPv4 - Properties - Advanced
    echo 4. Click Add under IP addresses
    echo 5. Enter: IP: 192.168.1.100, Mask: 255.255.255.0
)

echo.
pause
