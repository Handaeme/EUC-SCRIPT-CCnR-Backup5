-- Manual SQL Fix for script_files.uploaded_by column
-- Run this directly in SQL Server Management Studio or sqlcmd

-- Make sure we're using the right database
USE CITRA;
GO

-- Step 1: Drop FK constraint
ALTER TABLE dbo.script_files DROP CONSTRAINT FK__script_fi__uploa__59FA5E80;
GO

-- Step 2: Alter column type
ALTER TABLE dbo.script_files ALTER COLUMN uploaded_by VARCHAR(50);
GO

-- Done! Now file upload should work.
