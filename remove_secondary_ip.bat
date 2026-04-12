@echo off
echo Removing secondary IP address...
echo.

for /f "tokens=*" %%a in ('powershell -Command "(Get-NetAdapter | Where-Object {$_.Status -eq 'Up' -and $_.InterfaceDescription -notlike '*Bluetooth*'} | Select-Object -First 1).Name"') do (
    set ADAPTER=%%a
)

if not defined ADAPTER (
    echo ERROR: Could not find active network adapter
    pause
    exit /b 1
)

echo Removing 192.168.1.100 from adapter: %ADAPTER%
echo.

netsh interface ip delete address "%ADAPTER%" 192.168.1.100

echo.
if %ERRORLEVEL% EQU 0 (
    echo SUCCESS! Secondary IP removed.
) else (
    echo FAILED or IP was not found.
)

echo.
pause
