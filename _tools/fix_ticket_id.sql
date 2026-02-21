USE CITRA;
GO

-- 1. Alter column to VARCHAR
ALTER TABLE script_request ALTER COLUMN ticket_id VARCHAR(20);
GO

-- 2. Update existing data to SC-XXXX format
-- We check NOT LIKE 'SC-%' to avoid double updating if run multiple times
UPDATE script_request 
SET ticket_id = 'SC-' + RIGHT('0000' + ticket_id, 4) 
WHERE ticket_id NOT LIKE 'SC-%';
GO
