-- _tools/manual_update_server_v2.sql
-- KASUS: Kolom DEPT sudah ada (tapi kosong), dan ROLE_CODE masih ada isinya.

USE [EUC_CITRA]; -- Sesuaikan nama DB Anda
GO

-- 1. Pindahkan isi Data dari ROLE_CODE ke DEPT
PRINT '1. Copying data from ROLE_CODE to DEPT...';
UPDATE tbluser 
SET DEPT = ROLE_CODE 
WHERE DEPT IS NULL OR DEPT = ''; -- Hanya isi yang kosong biar aman
GO

-- 2. Hapus kolom ROLE_CODE (Jika data sudah aman)
-- Pastikan step 1 sukses dulu sebelum menjalankan ini
IF EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[tbluser]') AND name = 'ROLE_CODE')
BEGIN
    PRINT '2. Dropping column ROLE_CODE...';
    ALTER TABLE tbluser DROP COLUMN ROLE_CODE;
    PRINT 'Success: Column ROLE_CODE deleted.';
END
GO

-- 3. Cek hasil akhir
SELECT TOP 5 USERID, FULLNAME, DEPT FROM tbluser;
GO
