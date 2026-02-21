-- ============================================
-- Content Versioning Migration
-- ============================================
-- Purpose: Add versioning columns to script_preview_content
-- to track which workflow stage created each version
-- ============================================

USE EUC_CITRA;
GO

-- Step 1: Add new columns (nullable for backward compatibility)
ALTER TABLE script_preview_content 
ADD 
    workflow_stage VARCHAR(50) NULL,
    created_by VARCHAR(50) NULL,
    created_at DATETIME NULL;
GO

-- Step 2: Backfill created_at for existing rows (set to current time as approximation)
UPDATE script_preview_content 
SET created_at = GETDATE()
WHERE created_at IS NULL;
GO

-- Step 3: Backfill workflow_stage for existing rows
-- Assume existing rows are final approved versions
UPDATE script_preview_content 
SET workflow_stage = 'APPROVED_PROCEDURE'
WHERE workflow_stage IS NULL;
GO

-- Step 4: Backfill created_by for existing rows
UPDATE script_preview_content 
SET created_by = 'SYSTEM'
WHERE created_by IS NULL;
GO

-- Verification: Check sample data
SELECT TOP 10 
    id, 
    request_id, 
    media, 
    workflow_stage, 
    created_by, 
    created_at,
    LEN(CAST(content AS NVARCHAR(MAX))) as content_length
FROM script_preview_content
ORDER BY request_id DESC, id DESC;
GO

PRINT '✓ Migration completed successfully!';
PRINT '✓ Added columns: workflow_stage, created_by, created_at';
PRINT '✓ Backfilled existing data with default values';
