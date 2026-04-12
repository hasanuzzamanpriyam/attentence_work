# ZKTeco Device Integration - Setup Guide

## Overview
This guide explains how to set up and use the ZKTeco biometric device integration with device status monitoring.

## What Was Fixed

### 1. **500 Internal Server Error**
The error was caused by the `rats/zkteco` composer package not being properly installed. The package is now installed and autoloaded.

### 2. **Device Status Monitoring**
Added real-time device connectivity checking that shows whether the biometric device is connected or disconnected.

### 3. **Node.js Middleware**
Created an optional Node.js middleware service for advanced device management and real-time monitoring.

## Setup Instructions

### Step 1: Configure Environment Variables

Add the following to your `.env` file:

```env
# ZKTeco Device Configuration
ZKTECO_IP=192.168.1.201
ZKTECO_PORT=4370
```

**Important**: Replace `192.168.1.201` with your actual device IP address.

### Step 2: Install Composer Dependencies

Run the following command in your project root:

```bash
composer dump-autoload
```

If the `rats/zkteco` package is not installed, run:

```bash
composer require rats/zkteco
```

### Step 3: (Optional) Install Node.js Middleware

The Node.js middleware provides additional device management capabilities:

```bash
# Install Node.js dependencies
npm install express cors

# Or use the dedicated package file
cp device-middleware-package.json device-middleware/package.json
cd device-middleware
npm install
```

### Step 4: Start the Node.js Middleware (Optional)

If you want to use the Node.js middleware service:

```bash
node device-middleware.js
```

The middleware will start on port `8085` by default. You can change this by setting the `DEVICE_MIDDLEWARE_PORT` environment variable.

## Features

### 1. Device Status Indicator
- **Location**: Top-right corner of the Device Logs page
- **Green Badge**: Device is connected and reachable
- **Red Badge**: Device is disconnected or unreachable
- **Yellow Badge**: Unable to determine device status

### 2. Sync Device Now Button
- Click to manually sync attendance logs from the device
- Shows loading state during sync
- Displays success/error messages
- Automatically refreshes device status after sync

### 3. AJAX-Based Sync
- No page reload required
- Real-time feedback
- Better user experience

## API Endpoints

### Laravel Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/account/device-logs` | View device logs |
| POST | `/account/device-logs/sync` | Trigger device sync |
| GET | `/account/device-logs/status` | Check device connectivity |

### Node.js Middleware Endpoints (Optional)

| Method | URL | Description |
|--------|-----|-------------|
| GET | `http://localhost:8085/health` | Health check |
| POST | `http://localhost:8085/device/check` | Check single device |
| POST | `http://localhost:8085/device/check-multiple` | Check multiple devices |
| GET | `http://localhost:8085/devices/status` | Get all device statuses |

Example request to Node.js middleware:

```bash
curl -X POST http://localhost:8085/device/check \
  -H "Content-Type: application/json" \
  -d '{"ip": "192.168.1.201", "port": 4370}'
```

## Troubleshooting

### Device Shows as Disconnected

1. **Check Device Power**: Ensure the biometric device is powered on
2. **Verify IP Address**: Confirm the IP address in `.env` is correct
3. **Network Connectivity**: Ping the device from your server
   ```bash
   ping 192.168.1.201
   ```
4. **Port Availability**: Check if port 4370 is accessible
   ```bash
   telnet 192.168.1.201 4370
   ```
5. **Firewall Rules**: Ensure firewall allows traffic on port 4370

### Sync Fails with Error

1. **Check Laravel Logs**:
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verify Device Connection**:
   - Visit `/account/device-logs` and check the status indicator
   - It should show a green "Device Connected" badge

3. **Test Manual Sync**:
   ```bash
   php artisan zkteco:sync
   ```

4. **Common Errors**:
   - `Cannot connect to device`: Network issue or wrong IP
   - `Failed to establish connection`: SDK connection failed
   - `Library not found`: Run `composer require rats/zkteco`

### 500 Internal Server Error

If you still see a 500 error:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify composer autoload: `composer dump-autoload`
3. Clear Laravel cache:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

## Automatic Sync

The system automatically syncs with the device every minute via Laravel scheduler. Ensure your cron is set up:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

On Windows (using Task Scheduler):
```bash
php artisan schedule:run
```

## User Mapping

For attendance logs to be properly associated with users:

1. Each user in the system must have a `device_user_id` set
2. This ID should match the user ID configured in the ZKTeco device
3. Unmapped logs will be shown separately for manual review

To map a user:
```sql
UPDATE users SET device_user_id = 'DEVICE_USER_ID' WHERE id = USER_ID;
```

## Architecture

```
┌─────────────────┐
│   Laravel App   │
│                 │
│  ┌───────────┐  │
│  │ Controller│  │
│  └─────┬─────┘  │
│        │        │
│  ┌─────▼─────┐  │
│  │  Command  │  │
│  └─────┬─────┘  │
│        │        │
└────────┼────────┘
         │
         │ TCP/IP (port 4370)
         │
┌────────▼────────┐
│  ZKTeco Device  │
│  192.168.1.201  │
└─────────────────┘

Optional:
┌─────────────────┐
│  Node.js Middleware │
│  localhost:8085     │
└─────────────────┘
```

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review Laravel logs in `storage/logs/laravel.log`
3. Verify device network connectivity
4. Ensure all environment variables are set correctly

## Files Modified/Created

### Modified Files:
- `app/Http/Controllers/DeviceLogController.php` - Added device status check
- `routes/web.php` - Added device status route
- `resources/views/device-logs/index.blade.php` - Added status indicator

### Created Files:
- `device-middleware.js` - Node.js device middleware
- `device-middleware-package.json` - NPM package file
- `ZKTECO_SETUP_GUIDE.md` - This file

## Next Steps

1. ✅ Install composer dependencies
2. ✅ Configure `.env` with device IP
3. ✅ Test device connectivity
4. ✅ Verify sync functionality
5. ✅ (Optional) Set up Node.js middleware
6. ✅ (Optional) Configure automatic cron sync
