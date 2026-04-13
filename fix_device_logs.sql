-- =====================================================================
-- Fix Device Logs: Map Users and Correct Type Values
-- =====================================================================
-- Run this script to fix the following issues:
-- 1. Map unmapped device user IDs to system users
-- 2. Fix type=0 records to proper Check-In (1) or Check-Out (2) values
-- =====================================================================

-- STEP 1: Find your device user ID from attendance_raw_logs
-- Run this query first to see what device IDs are being recorded
SELECT 
    device_id,
    COUNT(*) as punch_count,
    MIN(timestamp) as first_punch,
    MAX(timestamp) as last_punch
FROM attendance_raw_logs
WHERE user_id IS NULL
GROUP BY device_id
ORDER BY punch_count DESC;

-- STEP 2: Map user ID 2 (Priyam) to their device user ID
-- IMPORTANT: Replace 'YOUR_DEVICE_ID' with the actual device_id from STEP 1
-- Example: If your device_id is '123', then use: WHERE id = 2 SET device_user_id = '123'

UPDATE users 
SET device_user_id = 'YOUR_DEVICE_ID'
WHERE id = 2 AND email LIKE '%priyam%';

-- Verify the update
SELECT id, name, email, device_user_id 
FROM users 
WHERE id = 2;

-- STEP 3: Fix existing type=0 records based on punch sequence
-- This will update unmapped logs (user_id IS NULL) with type=0
-- It calculates type based on chronological order per device_id

-- Create a temporary table to calculate correct types
DROP TEMPORARY TABLE IF EXISTS temp_log_fix;
CREATE TEMPORARY TABLE temp_log_fix AS
SELECT 
    id as log_id,
    device_id,
    timestamp,
    type as old_type,
    ROW_NUMBER() OVER (PARTITION BY device_id ORDER BY timestamp) as punch_order
FROM attendance_raw_logs
WHERE type = 0;

-- Update the records: odd punch_order = 1 (Check-In), even = 2 (Check-Out)
UPDATE attendance_raw_logs arl
INNER JOIN temp_log_fix tlf ON arl.id = tlf.log_id
SET arl.type = CASE 
    WHEN tlf.punch_order % 2 = 1 THEN 1  -- Odd = Check-In
    ELSE 2                                 -- Even = Check-Out
END
WHERE arl.type = 0;

-- Verify the fix
SELECT 
    device_id,
    timestamp,
    type,
    CASE 
        WHEN type = 1 THEN 'Check In'
        WHEN type = 2 THEN 'Check Out'
        ELSE 'Unknown'
    END as type_label
FROM attendance_raw_logs
WHERE user_id IS NULL
ORDER BY timestamp DESC
LIMIT 20;

-- STEP 4: After mapping your user, re-run sync to map existing logs
-- This will map all existing unmapped logs to your user account
-- Run this AFTER you set device_user_id in STEP 2

UPDATE attendance_raw_logs arl
INNER JOIN users u ON arl.device_id = u.device_user_id
SET arl.user_id = u.id
WHERE arl.user_id IS NULL AND u.device_user_id IS NOT NULL;

-- Verify mapped logs
SELECT 
    arl.id,
    u.name as user_name,
    u.email,
    arl.device_id,
    arl.timestamp,
    arl.type,
    CASE 
        WHEN arl.type = 1 THEN 'Check In'
        WHEN arl.type = 2 THEN 'Check Out'
        ELSE 'Unknown'
    END as type_label
FROM attendance_raw_logs arl
INNER JOIN users u ON arl.user_id = u.id
WHERE u.id = 2
ORDER BY arl.timestamp DESC
LIMIT 20;

-- =====================================================================
-- CLEANUP: Drop temporary table
-- =====================================================================
DROP TEMPORARY TABLE IF EXISTS temp_log_fix;

-- =====================================================================
-- VERIFICATION: Check your user mapping and logs
-- =====================================================================
SELECT 
    'User Mapping' as info,
    id,
    name,
    email,
    device_user_id
FROM users
WHERE id = 2;

SELECT 
    'Total Mapped Logs' as info,
    COUNT(*) as total_logs
FROM attendance_raw_logs
WHERE user_id = 2;

SELECT 
    'Total Unmapped Logs' as info,
    COUNT(*) as total_unmapped
FROM attendance_raw_logs
WHERE user_id IS NULL;
