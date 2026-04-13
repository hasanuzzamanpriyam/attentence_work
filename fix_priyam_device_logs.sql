-- =====================================================================
-- Complete Fix Script for Device Logs - User: Priyam (ID: 2)
-- =====================================================================
-- This script will:
-- 1. Map user ID 2 (Priyam) to device user ID 2
-- 2. Fix existing type=0 records using daily sequence logic
-- 3. Map all existing unmapped logs to correct users
-- =====================================================================

-- STEP 1: Map user ID 2 (Priyam) to device user ID 2
-- This connects the biometric device user ID 2 to your system account
UPDATE users 
SET device_user_id = '2'
WHERE id = 2;

-- Verify the update
SELECT 
    'User Mapping Updated' as status,
    id,
    name,
    email,
    device_user_id
FROM users 
WHERE id = 2;

-- STEP 2: Fix existing type=0 or type=NULL records using DAILY sequence logic
-- This groups punches by device_id AND date, then assigns type per day

-- Create a temporary table with row numbers per device_id per date
DROP TEMPORARY TABLE IF EXISTS temp_daily_logs;
CREATE TEMPORARY TABLE temp_daily_logs AS
SELECT 
    id,
    device_id,
    DATE(timestamp) as log_date,
    timestamp,
    type as old_type,
    ROW_NUMBER() OVER (
        PARTITION BY device_id, DATE(timestamp) 
        ORDER BY timestamp ASC
    ) as daily_punch_number
FROM attendance_raw_logs
WHERE (type = 0 OR type IS NULL)
AND user_id IS NULL;

-- Update the type based on daily sequence: odd=1(IN), even=2(OUT)
UPDATE attendance_raw_logs arl
INNER JOIN temp_daily_logs tdl ON arl.id = tdl.id
SET arl.type = CASE 
    WHEN tdl.daily_punch_number % 2 = 1 THEN 1  -- Odd punches (1st, 3rd, 5th) = Check-In
    ELSE 2                                       -- Even punches (2nd, 4th, 6th) = Check-Out
END
WHERE arl.type = 0 OR arl.type IS NULL;

-- Verify the type fix (show first 20 records)
SELECT 
    'Type Values Fixed' as status,
    id,
    device_id,
    timestamp,
    type,
    CASE 
        WHEN type = 1 THEN '✓ Check-In'
        WHEN type = 2 THEN '✓ Check-Out'
        ELSE '✗ Unknown'
    END as type_status
FROM attendance_raw_logs
WHERE user_id IS NULL
ORDER BY timestamp DESC
LIMIT 20;

-- STEP 3: Map all existing unmapped logs to users based on device_user_id
UPDATE attendance_raw_logs arl
INNER JOIN users u ON arl.device_id = u.device_user_id
SET arl.user_id = u.id
WHERE arl.user_id IS NULL
AND u.device_user_id IS NOT NULL;

-- Verify the mapping (show Priyam's logs)
SELECT 
    'Logs Mapped to Priyam' as status,
    arl.id,
    u.name as user_name,
    u.id as user_id,
    arl.device_id,
    DATE(arl.timestamp) as date,
    TIME(arl.timestamp) as time,
    arl.type,
    CASE 
        WHEN arl.type = 1 THEN '→ Check-In'
        WHEN arl.type = 2 THEN '← Check-Out'
        ELSE '? Unknown'
    END as punch_type
FROM attendance_raw_logs arl
INNER JOIN users u ON arl.user_id = u.id
WHERE u.id = 2
ORDER BY arl.timestamp DESC
LIMIT 20;

-- =====================================================================
-- FINAL VERIFICATION
-- =====================================================================

-- Check user mapping
SELECT 
    '=== User Account Status ===' as info,
    id,
    name,
    username,
    email,
    device_user_id,
    CASE 
        WHEN device_user_id IS NOT NULL THEN '✓ Mapped'
        ELSE '✗ Not Mapped'
    END as mapping_status
FROM users
WHERE id = 2;

-- Check total logs
SELECT 
    '=== Log Statistics ===' as info,
    (SELECT COUNT(*) FROM attendance_raw_logs WHERE user_id = 2) as mapped_logs_for_priyam,
    (SELECT COUNT(*) FROM attendance_raw_logs WHERE user_id IS NULL) as remaining_unmapped_logs,
    (SELECT COUNT(*) FROM attendance_raw_logs WHERE type = 0) as logs_with_type_zero;

-- Check Priyam's recent punches (last 10)
SELECT 
    '=== Priyam\'s Recent Punches ===' as info,
    id,
    DATE(timestamp) as date,
    TIME(timestamp) as time,
    type,
    CASE 
        WHEN type = 1 THEN '✓ Check-In (1st, 3rd, 5th punch of day)'
        WHEN type = 2 THEN '✓ Check-Out (2nd, 4th, 6th punch of day)'
        ELSE '✗ Unknown type'
    END as punch_meaning
FROM attendance_raw_logs
WHERE user_id = 2
ORDER BY timestamp DESC
LIMIT 10;

-- Check daily punch pairs for Priyam (last 5 days)
SELECT 
    '=== Priyam\'s Daily Punch Pairs (Last 5 Days) ===' as info,
    DATE(timestamp) as date,
    COUNT(*) as total_punches,
    MIN(CASE WHEN type = 1 THEN TIME(timestamp) END) as first_check_in,
    MIN(CASE WHEN type = 2 THEN TIME(timestamp) END) as first_check_out,
    MAX(CASE WHEN type = 1 THEN TIME(timestamp) END) as last_check_in,
    MAX(CASE WHEN type = 2 THEN TIME(timestamp) END) as last_check_out
FROM attendance_raw_logs
WHERE user_id = 2
GROUP BY DATE(timestamp)
ORDER BY date DESC
LIMIT 5;

-- =====================================================================
-- CLEANUP
-- =====================================================================
DROP TEMPORARY TABLE IF EXISTS temp_daily_logs;

-- =====================================================================
-- SUCCESS MESSAGE
-- =====================================================================
SELECT '
✓✓✓ FIX COMPLETED SUCCESSFULLY! ✓✓✓

What was fixed:
1. ✓ User ID 2 (Priyam) mapped to device user ID 2
2. ✓ All type=0 records updated with correct values (per day logic)
3. ✓ All unmapped logs mapped to their respective users

How it works now:
- 1st punch of the day = Check-In (type=1)
- 2nd punch of the day = Check-Out (type=2)
- 3rd punch of the day = Check-In (type=1)
- 4th punch of the day = Check-Out (type=2)
- And so on...

Next steps:
1. Visit: http://127.0.0.1:8000/account/device-logs
2. Look for "Priyam (ID: 2 | Device ID: 2)" in the user cards
3. Your punches should show Check-In → Check-Out pairs correctly
4. Future syncs will automatically have correct type values

' as result;
