USE CITRA;
IF NOT EXISTS (
  SELECT * FROM sys.columns 
  WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') 
  AND name = 'has_draft'
)
BEGIN
    ALTER TABLE [dbo].[script_request] ADD [has_draft] BIT DEFAULT 0;
END
GO
