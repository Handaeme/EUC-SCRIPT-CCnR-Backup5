-- MIGRATION SCRIPT FOR CITRA
-- Run this in SQL Server Management Studio (SSMS)

-- 1. Create Database (If Not Exists)
-- IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'EUC_CITRA')
IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'CITRA')
BEGIN
    CREATE DATABASE [CITRA]
    PRINT 'Database Created'
END
ELSE
BEGIN
    PRINT 'Database Exists'
END
GO

USE [CITRA]
GO

-- 2. Table: tbluser (Existing Table Check & Adapt)
IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbluser]') AND type in (N'U'))
BEGIN
    PRINT 'Table tbluser Exists - Adapting Schema'
    
    -- Check & Add ROLE_CODE if missing
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[tbluser]') AND name = 'ROLE_CODE')
    BEGIN
        ALTER TABLE tbluser ADD ROLE_CODE VARCHAR(20)
        PRINT 'Added ROLE_CODE column'
    END

    -- Check for LDAP column
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[tbluser]') AND name = 'LDAP')
    BEGIN
        ALTER TABLE tbluser ADD LDAP INT DEFAULT 0
        PRINT 'Added LDAP column'
    END

    -- Check & Add DIVISI if missing
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[tbluser]') AND name = 'DIVISI')
    BEGIN
        ALTER TABLE tbluser ADD DIVISI VARCHAR(100)
        PRINT 'Added DIVISI column'
    END
    
    -- Note: IS_ACTIVE corresponds to AKTIF (Existing)
    -- Note: GROUP_NAME corresponds to GROUP (Existing)
END
ELSE
BEGIN
    -- Fallback creation (If table doesn't exist at all, unlikely in this scenario)
    CREATE TABLE tbluser (
        USERID VARCHAR(50) PRIMARY KEY,
        FULLNAME VARCHAR(100),
        PASSWORD VARCHAR(255),
        LDAP INT DEFAULT 0, -- 0: Local, 1: LDAP
        DEPT VARCHAR(20), -- Only used for PIC role detection
        JOB_FUNCTION VARCHAR(50), -- DEPARTMENT HEAD → MAKER, DIVISION HEAD → SPV
        DIVISI VARCHAR(100), -- Quality Analysis Monitoring & Procedure → PROCEDURE
        [GROUP] VARCHAR(50), -- Department/Unit label (CPMS, QPM, etc)
        CREATED_DATE DATE DEFAULT GETDATE(),
        AKTIF INT DEFAULT 1 -- Changed from IS_ACTIVE to AKTIF
    )
    PRINT 'Table tbluser Created (New Schema)'
END
GO

-- 3. Table: script_request
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_request (
        id INT IDENTITY(1,1) PRIMARY KEY,
        ticket_id VARCHAR(20), -- CHANGED: Supports 'SC-0001' format
        script_number VARCHAR(100),
        title VARCHAR(255),
        jenis VARCHAR(50),
        produk VARCHAR(50),
        kategori VARCHAR(50),
        media VARCHAR(50),
        mode VARCHAR(20),
        status VARCHAR(100), -- Increased for safety
        current_role VARCHAR(50),
        version INT DEFAULT 1,
        has_draft INT DEFAULT 0, -- NEW: Track if request has active draft
        is_active INT DEFAULT 1,
        is_deleted INT DEFAULT 0,
        created_by VARCHAR(50),
        selected_spv VARCHAR(50),
        selected_pic VARCHAR(50), -- Added for SPV->PIC assignment
        start_date DATE, -- NEW: Validity start date
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME
    )
    PRINT 'Table script_request Created'
END
ELSE
BEGIN
    PRINT 'Table script_request Exists - Checking Columns'
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND name = 'ticket_id')
        ALTER TABLE script_request ADD ticket_id VARCHAR(20)
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND name = 'current_role')
        ALTER TABLE script_request ADD current_role VARCHAR(50)
     IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND name = 'version')
        ALTER TABLE script_request ADD version INT DEFAULT 1
     IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND name = 'has_draft')
        ALTER TABLE script_request ADD has_draft INT DEFAULT 0
     IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND name = 'selected_pic')
        ALTER TABLE script_request ADD selected_pic VARCHAR(50)
     IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND name = 'start_date')
        ALTER TABLE script_request ADD start_date DATE
END
GO

-- 4. Table: script_files
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_files]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_files (
        id INT IDENTITY(1,1) PRIMARY KEY,
        request_id INT,
        file_type VARCHAR(20),
        original_filename VARCHAR(255),
        filepath VARCHAR(255),
        uploaded_by VARCHAR(50),
        uploaded_at DATETIME DEFAULT GETDATE()
    )
    PRINT 'Table script_files Created'
END
GO

-- 5. Table: script_templates
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_templates]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_templates (
        id INT IDENTITY(1,1) PRIMARY KEY,
        title VARCHAR(100),
        filename VARCHAR(255),
        filepath VARCHAR(255),
        uploaded_by VARCHAR(50),
        description NVARCHAR(MAX),
        created_at DATETIME DEFAULT GETDATE()
    )
    PRINT 'Table script_templates Created'
END
ELSE
BEGIN
    -- Check Description Column (Added recently)
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_templates]') AND name = 'description')
        ALTER TABLE script_templates ADD description NVARCHAR(MAX)
END
GO

-- 6. Table: script_audit_trail
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_audit_trail]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_audit_trail (
        id INT IDENTITY(1,1) PRIMARY KEY,
        request_id INT,
        script_number VARCHAR(100),
        action VARCHAR(50),
        status_before VARCHAR(100), -- Increased for safety
        status_after VARCHAR(100),  -- Increased for safety
        user_role VARCHAR(50),
        user_id VARCHAR(50),
        details NVARCHAR(MAX),
        created_at DATETIME DEFAULT GETDATE()
    )
    PRINT 'Table script_audit_trail Created'
END
GO

-- 7. Table: script_library (Verified Used in DashboardController/RequestController)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_library]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_library (
        id INT IDENTITY(1,1) PRIMARY KEY,
        request_id INT,
        script_number VARCHAR(100),
        media VARCHAR(50),
        content NVARCHAR(MAX),
        version INT,
        is_active INT DEFAULT 0, -- NEW
        start_date DATE, -- NEW
        activated_at DATETIME, -- NEW
        activated_by VARCHAR(50), -- NEW
        created_at DATETIME DEFAULT GETDATE()
    )
    PRINT 'Table script_library Created'
END
ELSE
BEGIN
    PRINT 'Table script_library Exists - Checking Columns'
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_library]') AND name = 'is_active')
        ALTER TABLE script_library ADD is_active INT DEFAULT 0
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_library]') AND name = 'start_date')
        ALTER TABLE script_library ADD start_date DATE
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_library]') AND name = 'activated_at')
        ALTER TABLE script_library ADD activated_at DATETIME
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_library]') AND name = 'activated_by')
        ALTER TABLE script_library ADD activated_by VARCHAR(50)
END
GO

-- 8. Table: script_preview_content (Verified Used in RequestModel)
-- Integrated with latest columns for timeline tracking
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_preview_content]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_preview_content (
        id INT IDENTITY(1,1) PRIMARY KEY,
        request_id INT,
        media VARCHAR(50),
        content NVARCHAR(MAX),
        updated_by VARCHAR(50),
        updated_at DATETIME DEFAULT GETDATE(),
        
        -- Version Tracking
        workflow_stage VARCHAR(50) NULL,
        created_by VARCHAR(50) NULL,
        created_at DATETIME NULL
    )
    PRINT 'Table script_preview_content Created'
END
ELSE
BEGIN
    PRINT 'Table script_preview_content Exists - Checking Versioning Columns'
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_preview_content]') AND name = 'workflow_stage')
        ALTER TABLE script_preview_content ADD workflow_stage VARCHAR(50) NULL
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_preview_content]') AND name = 'created_by')
        ALTER TABLE script_preview_content ADD created_by VARCHAR(50) NULL
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_preview_content]') AND name = 'created_at')
        ALTER TABLE script_preview_content ADD created_at DATETIME NULL
END
GO

-- 9. Seed Users (tbluser)
-- Role mapping: MAKER=JOB_FUNCTION 'DEPARTMENT HEAD', SPV=JOB_FUNCTION 'DIVISION HEAD', PIC=DEPT 'PIC', PROCEDURE=DIVISI

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'MAKER01')
BEGIN
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('MAKER01', 'MAKER01', '123', 0, NULL, 'DEPARTMENT HEAD', NULL, 'UNSECURED COLLECTION', '2026-01-20', 1)
    PRINT 'User MAKER01 Created'
END

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'SPV01')
BEGIN
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('SPV01', 'SPV01', '123', 0, NULL, 'DIVISION HEAD', NULL, 'UNSECURED COLLECTION', '2026-01-20', 1)
    PRINT 'User SPV01 Created'
END

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'PIC01')
BEGIN
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('PIC01', 'PIC01', '123', 0, 'PIC', NULL, NULL, 'UNSECURED COLLECTION', '2026-01-20', 1)
    PRINT 'User PIC01 Created'
END

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'PROC01')
BEGIN
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('PROC01', 'PROC01', NULL, 1, NULL, NULL, 'Quality Analysis Monitoring & Procedure', 'CPMS', '2026-01-20', 1)
    PRINT 'User PROC01 Created'
END

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'PROC02')
BEGIN
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('PROC02', 'PROC02', NULL, 1, NULL, NULL, 'Quality Analysis Monitoring & Procedure', 'QPM', '2026-01-20', 1)
    PRINT 'User PROC02 Created'
END

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'ADMIN01')
BEGIN
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('ADMIN01', 'Administrator', '123', 0, 'ADMIN', NULL, NULL, 'IT ADMIN', '2026-01-20', 1)
    PRINT 'User ADMIN01 Created'
END

-- UPDATE existing users to new schema (for running on existing databases)
PRINT 'Updating existing users to new role schema...'
UPDATE tbluser SET JOB_FUNCTION = 'DEPARTMENT HEAD', DEPT = NULL WHERE USERID = 'MAKER01'
UPDATE tbluser SET JOB_FUNCTION = 'DIVISION HEAD', DEPT = NULL WHERE USERID = 'SPV01'
UPDATE tbluser SET DEPT = 'PIC', JOB_FUNCTION = NULL WHERE USERID = 'PIC01'
UPDATE tbluser SET DIVISI = 'Quality Analysis Monitoring & Procedure', DEPT = NULL, JOB_FUNCTION = NULL WHERE USERID = 'PROC01'
UPDATE tbluser SET DIVISI = 'Quality Analysis Monitoring & Procedure', DEPT = NULL, JOB_FUNCTION = NULL WHERE USERID = 'PROC02'
PRINT 'Existing users updated'

-- Drop old table if exists (Optional, depending on safety preference)
-- IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_users]') AND type in (N'U'))
-- BEGIN
--    DROP TABLE script_users
--    PRINT 'Table script_users Dropped'
-- END
