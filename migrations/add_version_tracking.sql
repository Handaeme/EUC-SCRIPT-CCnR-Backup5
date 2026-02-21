-- Migration Script: Add Version History Tracking to script_preview_content
-- Created: 2026-01-23
-- Purpose: Enable version tracking for audit trail timeline feature

USE CITRA;
GO

-- 1. Add new columns for version tracking
PRINT 'Checking for new columns in script_preview_content...';

-- Add workflow_stage column
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_NAME = 'script_preview_content' 
               AND COLUMN_NAME = 'workflow_stage')
BEGIN
    ALTER TABLE script_preview_content ADD workflow_stage VARCHAR(20);
    PRINT '[OK] Added column workflow_stage';
END
ELSE
    PRINT '[SKIP] Column workflow_stage already exists';

-- Add created_by column
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_NAME = 'script_preview_content' 
               AND COLUMN_NAME = 'created_by')
BEGIN
    ALTER TABLE script_preview_content ADD created_by VARCHAR(100);
    PRINT '[OK] Added column created_by';
END
ELSE
    PRINT '[SKIP] Column created_by already exists';

-- Add version_number column
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_NAME = 'script_preview_content' 
               AND COLUMN_NAME = 'version_number')
BEGIN
    ALTER TABLE script_preview_content ADD version_number INT;
    PRINT '[OK] Added column version_number';
END
ELSE
    PRINT '[SKIP] Column version_number already exists';

-- Add action_type column
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_NAME = 'script_preview_content' 
               AND COLUMN_NAME = 'action_type')
BEGIN
    ALTER TABLE script_preview_content ADD action_type VARCHAR(20);
    PRINT '[OK] Added column action_type';
END
ELSE
    PRINT '[SKIP] Column action_type already exists';

-- Add created_at column (separate from updated_at)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_NAME = 'script_preview_content' 
               AND COLUMN_NAME = 'created_at')
BEGIN
    ALTER TABLE script_preview_content ADD created_at DATETIME DEFAULT GETDATE();
    PRINT '[OK] Added column created_at';
END
ELSE
    PRINT '[SKIP] Column created_at already exists';

GO

-- 2. Migrate existing data with default values
PRINT '';
PRINT 'Migrating existing data...';

UPDATE script_preview_content
SET 
    workflow_stage = 'MAKER_SUBMIT',
    version_number = 1,
    action_type = 'SUBMIT',
    created_by = ISNULL(updated_by, 'system'),
    created_at = ISNULL(updated_at, GETDATE())
WHERE workflow_stage IS NULL;

DECLARE @rowCount INT = @@ROWCOUNT;
PRINT CONCAT('[OK] Migrated ', @rowCount, ' existing rows with default values');

GO

-- 3. Create index for better query performance
IF NOT EXISTS (SELECT * FROM sys.indexes 
               WHERE name = 'IX_script_preview_content_request_version' 
               AND object_id = OBJECT_ID('script_preview_content'))
BEGIN
    CREATE INDEX IX_script_preview_content_request_version 
    ON script_preview_content(request_id, version_number, created_at);
    PRINT '[OK] Created index on request_id, version_number, created_at';
END
ELSE
    PRINT '[SKIP] Index already exists';

GO

-- 4. Verify migration
PRINT '';
PRINT 'Verification:';
SELECT 
    COUNT(*) as total_rows,
    COUNT(DISTINCT request_id) as unique_requests,
    MAX(version_number) as max_version
FROM script_preview_content;

PRINT '';
PRINT '=================================';
PRINT 'Migration completed successfully!';
PRINT '=================================';
GO
