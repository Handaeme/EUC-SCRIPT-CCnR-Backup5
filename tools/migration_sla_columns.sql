-- =====================================================
-- Migration: Add SLA & Aging Tracking Columns
-- Date: 2026-03-30
-- Description: Adds columns for SLA deadline tracking,
--              status timestamp tracking, and On Hold flag
-- =====================================================

-- 1. Add status_updated_at: Records WHEN the status last changed
--    This is the "stopwatch start" for SLA calculation
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('script_request') AND name = 'status_updated_at')
BEGIN
    ALTER TABLE script_request ADD status_updated_at DATETIME NULL;
    PRINT 'Added column: status_updated_at';
END
ELSE
    PRINT 'Column status_updated_at already exists. Skipping.';
GO

-- 2. Add sla_deadline: The exact datetime when SLA expires
--    PHP will calculate this using addWorkingDays() and store it here
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('script_request') AND name = 'sla_deadline')
BEGIN
    ALTER TABLE script_request ADD sla_deadline DATETIME NULL;
    PRINT 'Added column: sla_deadline';
END
ELSE
    PRINT 'Column sla_deadline already exists. Skipping.';
GO

-- 3. Add is_on_hold: Flag (0/1) for SLA Pause when waiting external (Legal/CX)
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('script_request') AND name = 'is_on_hold')
BEGIN
    ALTER TABLE script_request ADD is_on_hold TINYINT NOT NULL DEFAULT 0;
    PRINT 'Added column: is_on_hold';
END
ELSE
    PRINT 'Column is_on_hold already exists. Skipping.';
GO

-- 4. Add sla_paused_at: Records when SLA was paused (for resume calculation)
IF NOT EXISTS (SELECT 1 FROM sys.columns WHERE object_id = OBJECT_ID('script_request') AND name = 'sla_paused_at')
BEGIN
    ALTER TABLE script_request ADD sla_paused_at DATETIME NULL;
    PRINT 'Added column: sla_paused_at';
END
ELSE
    PRINT 'Column sla_paused_at already exists. Skipping.';
GO

-- 5. Backfill existing data: Set status_updated_at = updated_at for existing records
UPDATE script_request 
SET status_updated_at = COALESCE(updated_at, created_at) 
WHERE status_updated_at IS NULL;
PRINT 'Backfilled status_updated_at for existing records.';
GO

PRINT '=== Migration Complete: SLA columns added successfully ===';
GO
