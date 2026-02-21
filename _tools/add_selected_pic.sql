-- Migration: Add selected_pic column to script_request
-- Purpose: Allow SPV to select specific PIC before approving request
-- Date: 2026-02-08

-- Add column (NULL to preserve existing data)
IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'script_request' 
    AND COLUMN_NAME = 'selected_pic'
)
BEGIN
    ALTER TABLE script_request 
    ADD selected_pic VARCHAR(50) NULL;
    
    PRINT 'Column selected_pic added successfully';
END
ELSE
BEGIN
    PRINT 'Column selected_pic already exists';
END
GO
