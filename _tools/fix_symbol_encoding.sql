-- Fix Symbol/Encoding Issue for Free Input
-- Switch key text columns from VARCHAR (ASCII) to NVARCHAR (Unicode)

-- 1. Script Request Title
ALTER TABLE script_request ALTER COLUMN title NVARCHAR(255);

-- 2. Script Preview Content (Free Input storage)
-- NOTE: If it was TEXT, change to NVARCHAR(MAX). If VARCHAR(MAX), change to NVARCHAR(MAX).
ALTER TABLE script_preview_content ALTER COLUMN content NVARCHAR(MAX);

-- 3. Script Library Content
ALTER TABLE script_library ALTER COLUMN content NVARCHAR(MAX);

-- 4. Audit Trail Details (just in case symbols are in log)
ALTER TABLE script_audit_trail ALTER COLUMN details NVARCHAR(MAX);
