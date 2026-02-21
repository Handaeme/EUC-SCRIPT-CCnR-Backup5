-- Script to rename columns in EUC_CITRA database to match CITRA server
-- Run this in SQL Server Management Studio

USE EUC_CITRA;
GO

-- 1. Rename GROUP_NAME to GROUP (with bracket for reserved word)
EXEC sp_rename 'tbluser.GROUP_NAME', 'GROUP', 'COLUMN';
GO

-- 2. Rename IS_ACTIVE to AKTIF
EXEC sp_rename 'tbluser.IS_ACTIVE', 'AKTIF', 'COLUMN';
GO

-- Verify the changes
SELECT COLUMN_NAME, DATA_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tbluser'
ORDER BY ORDINAL_POSITION;
GO
