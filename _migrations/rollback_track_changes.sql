-- Rollback Track Changes Feature
-- Run this in SQL Server Management Studio to remove the revision_history table

-- Drop the table (this will delete all data inside)
IF OBJECT_ID('revision_history', 'U') IS NOT NULL
    DROP TABLE revision_history;

-- Verification: Check if table is gone
SELECT TABLE_NAME 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'revision_history';
-- Expected Result: No rows (empty result set)
