-- _tools/manual_update_server.sql
-- Run this script in SQL Server Management Studio (SSMS) on your PC Server

USE [EUC_CITRA]; -- Pastikan nama database sesuai dengan yang ada di Server Anda
GO

-- 1. Cek apakah kolom ROLE_CODE ada
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[tbluser]') AND name = 'ROLE_CODE')
BEGIN
    PRINT 'Found ROLE_CODE. Renaming to DEPT...';
    EXEC sp_rename 'tbluser.ROLE_CODE', 'DEPT', 'COLUMN';
    PRINT 'Success: Renamed to DEPT.';
END
ELSE
BEGIN
    PRINT 'Column ROLE_CODE not found. It might be already renamed to DEPT.';
END
GO

-- 2. Cek hasilnya
SELECT TOP 5 USERID, FULLNAME, DEPT FROM tbluser;
GO
