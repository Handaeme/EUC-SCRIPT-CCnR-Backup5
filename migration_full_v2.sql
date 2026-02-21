-- MIGRATION SCRIPT FULL V2 (INTEGRATED)
-- Run this in SQL Server Management Studio (SSMS)
-- This creates a NEW database [CITRA_V2] with the latest schema.

-- 1. Create Database (CITRA_V2)
IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'CITRA_V2')
BEGIN
    CREATE DATABASE [CITRA_V2]
    PRINT 'Database [CITRA_V2] Created'
END
ELSE
BEGIN
    PRINT 'Database [CITRA_V2] Exists'
END
GO

USE [CITRA_V2]
GO

-- 2. Table: tbluser (Standard Users)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[tbluser]') AND type in (N'U'))
BEGIN
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
        AKTIF INT DEFAULT 1
    )
    PRINT 'Table tbluser Created'
END
ELSE
BEGIN
    -- Add DIVISI column if missing (for existing databases)
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[tbluser]') AND name = 'DIVISI')
    BEGIN
        ALTER TABLE tbluser ADD DIVISI VARCHAR(100)
        PRINT 'Added DIVISI column to tbluser'
    END
END
GO

-- 3. Table: script_request (Main Request Table)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_request (
        id INT IDENTITY(1,1) PRIMARY KEY,
        ticket_id VARCHAR(20),      -- Format: SC-XXXX
        script_number VARCHAR(100), -- Format: KONV-WA-20/01/26-0001-01
        title VARCHAR(255),
        jenis VARCHAR(50),         -- Konvensional/Syariah
        produk VARCHAR(50),
        kategori VARCHAR(50),
        media VARCHAR(50),         -- WA, SMS, etc.
        mode VARCHAR(20),          -- FREE_INPUT or FILE_UPLOAD
        status VARCHAR(100),       -- CREATED, APPROVED_SPV, REVISION, etc.
        current_role VARCHAR(50),  -- Supervisor, PIC, Procedure
        version INT DEFAULT 1,     -- Version Tracking
        has_draft INT DEFAULT 0,   -- Draft Tracking (0/1)
        is_active INT DEFAULT 1,
        is_deleted INT DEFAULT 0,
        created_by VARCHAR(50),    -- UserID of creator
        selected_spv VARCHAR(50),  -- UserID of SPV
        selected_pic VARCHAR(50),  -- UserID of PIC (Added)
        start_date DATE,           -- Validity Start Date
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
    )
    PRINT 'Table script_request Created'
END
ELSE
BEGIN
    -- Check for start_date column
    IF NOT EXISTS (SELECT * FROM sys.columns WHERE object_id = OBJECT_ID(N'[dbo].[script_request]') AND name = 'start_date')
    BEGIN
        ALTER TABLE script_request ADD start_date DATE
        PRINT 'Added start_date column to script_request'
    END
END
GO

-- 4. Table: script_files (Uploaded Files)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_files]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_files (
        id INT IDENTITY(1,1) PRIMARY KEY,
        request_id INT,
        file_type VARCHAR(20),       -- TEMPLATE, LEGAL, CX, etc.
        original_filename VARCHAR(255),
        filepath VARCHAR(255),
        uploaded_by VARCHAR(50),
        uploaded_at DATETIME DEFAULT GETDATE()
    )
    PRINT 'Table script_files Created'
END
GO

-- 5. Table: script_templates (Master Templates)
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
GO

-- 6. Table: script_audit_trail (History Log)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_audit_trail]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_audit_trail (
        id INT IDENTITY(1,1) PRIMARY KEY,
        request_id INT,
        script_number VARCHAR(100),
        action VARCHAR(50),
        status_before VARCHAR(100),
        status_after VARCHAR(100),
        user_role VARCHAR(50),
        user_id VARCHAR(50),
        details NVARCHAR(MAX),
        created_at DATETIME DEFAULT GETDATE()
    )
    PRINT 'Table script_audit_trail Created'
END
GO

-- 7. Table: script_library (Final Published Scripts)
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[script_library]') AND type in (N'U'))
BEGIN
    CREATE TABLE script_library (
        id INT IDENTITY(1,1) PRIMARY KEY,
        request_id INT,
        script_number VARCHAR(100),
        media VARCHAR(50),
        content NVARCHAR(MAX),
        content NVARCHAR(MAX),
        version INT,
        is_active INT DEFAULT 0,
        start_date DATE,
        activated_at DATETIME,
        activated_by VARCHAR(50),
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

-- 8. Table: script_preview_content (Draft Content + Version History)
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
        
        -- V2 Columns (Version Tracking) - ALIGNED WITH CODEBASE
        workflow_stage VARCHAR(50),  -- MAKER, SPV, PIC, PROCEDURE
        created_by VARCHAR(50),
        created_at DATETIME DEFAULT GETDATE()
        -- action_type VARCHAR(20),  -- Optional (Future Use)
        -- version_number INT DEFAULT 1 -- Optional (Future Use)
    )
    
    -- Index for timeline performance (Updated to use valid columns)
    CREATE INDEX IX_script_preview_content_request_version 
    ON script_preview_content(request_id, workflow_stage, created_at);

    PRINT 'Table script_preview_content Created'
END
GO

-- 9. Seed Users (Default Accounts)
-- Role mapping: MAKER=JOB_FUNCTION 'DEPARTMENT HEAD', SPV=JOB_FUNCTION 'DIVISION HEAD', PIC=DEPT 'PIC', PROCEDURE=DIVISI

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'MAKER01')
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('MAKER01', 'MAKER01', '123', 0, NULL, 'DEPARTMENT HEAD', NULL, 'UNSECURED COLLECTION', GETDATE(), 1);

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'SPV01')
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('SPV01', 'SPV01', '123', 0, NULL, 'DIVISION HEAD', NULL, 'UNSECURED COLLECTION', GETDATE(), 1);

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'PIC01')
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('PIC01', 'PIC01', '123', 0, 'PIC', NULL, NULL, 'UNSECURED COLLECTION', GETDATE(), 1);

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'PROC01')
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('PROC01', 'PROC01', NULL, 1, NULL, NULL, 'Quality Analysis Monitoring & Procedure', 'CPMS', GETDATE(), 1);

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'PROC02')
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('PROC02', 'PROC02', NULL, 1, NULL, NULL, 'Quality Analysis Monitoring & Procedure', 'QPM', GETDATE(), 1);

IF NOT EXISTS (SELECT * FROM tbluser WHERE USERID = 'ADMIN01')
    INSERT INTO tbluser (USERID, FULLNAME, PASSWORD, LDAP, DEPT, JOB_FUNCTION, DIVISI, [GROUP], CREATED_DATE, AKTIF) 
    VALUES ('ADMIN01', 'Administrator', '123', 0, 'ADMIN', NULL, NULL, 'IT ADMIN', GETDATE(), 1);

-- UPDATE existing users to new role schema (safe for existing databases)
UPDATE tbluser SET JOB_FUNCTION = 'DEPARTMENT HEAD', DEPT = NULL WHERE USERID = 'MAKER01'
UPDATE tbluser SET JOB_FUNCTION = 'DIVISION HEAD', DEPT = NULL WHERE USERID = 'SPV01'
UPDATE tbluser SET DEPT = 'PIC', JOB_FUNCTION = NULL WHERE USERID = 'PIC01'
UPDATE tbluser SET DIVISI = 'Quality Analysis Monitoring & Procedure', DEPT = NULL, JOB_FUNCTION = NULL WHERE USERID = 'PROC01'
UPDATE tbluser SET DIVISI = 'Quality Analysis Monitoring & Procedure', DEPT = NULL, JOB_FUNCTION = NULL WHERE USERID = 'PROC02'

PRINT 'Seed Users Created/Updated'
GO

PRINT '========================================='
PRINT 'MIGRATION V2 COMPLETED SUCCESSFULLY'
PRINT 'Database: CITRA_V2'
PRINT '========================================='
