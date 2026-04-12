# ZKTeco K50-A Biometric Device Integration

This document provides complete setup and usage instructions for the ZKTeco K50-A biometric attendance system integration with Worksuite.

## Overview

The integration allows automatic synchronization of attendance data from ZKTeco biometric devices to the Worksuite attendance management system. The system fetches raw punch logs every minute and processes them into daily attendance records, accessible through the existing web interface.

## Architecture Components

### 1. Package Integration ✅
- **Package**: `rats/zkteco` (already installed via Composer)
- **Version**: Compatible with v1.0, v2.0, or dev-master
- **Purpose**: Provides UDP/TCP communication with ZKTeco devices

### 2. Database Schema ✅

#### Users Table Extension
- **Column**: `device_user_id` (string, nullable, unique)
- **Purpose**: Maps Worksuite users to ZKTeco device User IDs

#### Attendance Raw Logs Table
- **Columns**:
  - `id` (primary key)
  - `user_id` (nullable foreign key to users)
  - `device_id` (device user ID from ZKTeco)
  - `timestamp` (datetime of punch)
  - `type` (1=Check In, 2=Check Out, etc.)
- **Purpose**: Stores every raw fingerprint/card scan

#### Attendance Table (Existing)
- Used to store processed daily attendance records
- Contains clock_in_time, clock_out_time, working hours, etc.

### 3. Data Synchronization ✅

#### Cron Job Setup
- **Command**: `php artisan zkteco:sync`
- **Schedule**: Every minute via Laravel Scheduler
- **Functionality**:
  - Connects to ZKTeco device at configured IP/port
  - Fetches new attendance logs
  - Prevents duplicate entries
  - Clears logs from device after successful sync
  - Processes logs into attendance records

#### Configuration (.env)
```env
ZKTECO_IP=192.168.1.201
ZKTECO_PORT=4370
```

### 4. Business Logic ✅

#### AttendanceService Class
**Location**: `app/Services/AttendanceService.php`

**Key Methods**:
- `processAttendanceLogs()`: Processes raw logs into attendance records
- `getDailyAttendanceSummary()`: Returns today's attendance for all users
- `getWeeklyAttendanceSummary()`: Returns weekly summary per user
- `getMonthlyAttendanceSummary()`: Returns detailed monthly report

**Logic**:
- First punch of day = Clock In
- Last punch of day = Clock Out
- Multiple punches between are treated as breaks
- Working hours calculated as difference between first and last punch

### 5. Web Interface ✅

#### Device Logs Page
- **URL**: `/account/device-logs`
- **Controller**: `DeviceLogController` (updated to use AttendanceService)
- **Features**:
  - View today's attendance summary for all users
  - Display raw device logs with user mapping
  - Manual sync button
  - Working days configuration from attendance settings

#### Sync Functionality
- **URL**: `POST /account/device-logs/sync`
- **Purpose**: Manually trigger device synchronization
- **Response**: Success/error messages via web interface

## Setup Instructions

### 1. Device Configuration
1. **Static IP Setup**: Configure ZKTeco device with static IP `192.168.1.201`
2. **Port Configuration**: Ensure communication port is set to `4370`
3. **Network Access**: Ensure device and server are on same network segment
4. **DHCP Disabled**: Disable DHCP to prevent IP changes

### 2. User Mapping
1. **Register Users**: Add users to ZKTeco device with User ID (e.g., "1", "2", etc.)
2. **Map in Database**: Update `device_user_id` column in users table:
   ```sql
   UPDATE users SET device_user_id = '1' WHERE id = 123;
   ```

### 3. Environment Configuration
Update your `.env` file:
```env
ZKTECO_IP=192.168.1.201
ZKTECO_PORT=4370
```

### 4. Cron Job Setup
Ensure Laravel scheduler is running:
```bash
# Add to crontab (run every minute)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Firewall Configuration
Allow port 4370 through firewall:
```powershell
# Windows Firewall
netsh advfirewall firewall add rule name="ZKTeco Port 4370" dir=out action=allow protocol=tcp remoteip=192.168.1.201 remoteport=4370
```

## Troubleshooting

### Connection Issues (Error 10060)
1. **Network Connectivity**: Ensure device is reachable via ping
2. **Firewall**: Check Windows Firewall settings
3. **IP Configuration**: Verify device IP hasn't changed
4. **Port Availability**: Confirm port 4370 is open on device

### No Data Syncing
1. **User Mapping**: Ensure `device_user_id` is set for users
2. **Device Time**: Check device clock is accurate
3. **Attendance Data**: Verify users have punched attendance on device

### Duplicate Records
- The system automatically prevents duplicates based on `device_id` + `timestamp`
- Manual cleanup may be needed if duplicates exist

## Testing

### Manual Sync
```bash
php artisan zkteco:sync
```

### Web Interface Testing
1. **Device Logs Page**: Visit `/account/device-logs` in your browser
2. **Manual Sync**: Click the sync button on the device logs page
3. **View Reports**: Check the attendance reports in the existing attendance module

## Files Modified/Created

### Modified Files
- `app/Console/Kernel.php`: Added SyncZktecoLogs command and scheduling
- `app/Http/Controllers/DeviceLogController.php`: Updated to use AttendanceService for cleaner code
- `routes/api.php`: Removed unnecessary API routes (not using React.js)

### New Files
- `app/Services/AttendanceService.php`: Business logic for attendance processing

### Removed Files
- `app/Http/Controllers/AttendanceApiController.php`: Removed as API routes are not needed

## Security Notes

- API endpoints should be protected with authentication middleware in production
- Device communication uses plain TCP/UDP - consider VPN for secure networks
- Store device credentials securely if device requires authentication

## Support

For issues with:
- **Device connectivity**: Check network configuration and firewall
- **Data processing**: Review AttendanceService logic
- **API responses**: Check controller methods and error logs
- **Package issues**: Ensure `rats/zkteco` package is properly installed</content>
<parameter name="filePath">c:\laragon\www\worksuite v5.5.20\worksuite\worksuite-new-5.5.20\script\ZKTECO_INTEGRATION.md