-- ============================================
-- Versioning Verification Query
-- ============================================
-- Purpose: Check if versioning is working correctly
-- Shows all versions for each request with workflow stages
-- ============================================

USE EUC_CITRA;
GO

-- Query 1: Show all versions grouped by request
-- (Use this to see version progression for a specific request)
SELECT 
    request_id,
    id,
    media,
    workflow_stage,
    created_by,
    created_at,
    LEN(CAST(content AS NVARCHAR(MAX))) as content_length,
    LEFT(CAST(content AS NVARCHAR(MAX)), 150) as content_preview
FROM script_preview_content
WHERE request_id = 14  -- ← GANTI dengan request ID yang di-test
ORDER BY request_id, created_at ASC, id ASC;
GO

-- Query 2: Count versions per request per stage
-- (Use this to verify each role created their version)
SELECT 
    request_id,
    workflow_stage,
    COUNT(*) as version_count,
    STUFF((
        SELECT ', ' + CAST(id AS VARCHAR)
        FROM script_preview_content spc2
        WHERE spc2.request_id = spc.request_id 
          AND spc2.workflow_stage = spc.workflow_stage
        FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 2, '') as version_ids
FROM script_preview_content spc
WHERE request_id = 14  -- ← GANTI dengan request ID yang di-test
GROUP BY request_id, workflow_stage
ORDER BY request_id, 
    CASE workflow_stage 
        WHEN 'SUBMIT' THEN 1
        WHEN 'APPROVED_SPV' THEN 2
        WHEN 'APPROVED_PIC' THEN 3
        WHEN 'APPROVED_PROCEDURE' THEN 4
        ELSE 5
    END;
GO

-- Query 3: Show latest 5 requests with version counts
-- (Use this to see overview of all requests)
SELECT 
    r.id as request_id,
    r.script_number,
    r.status,
    COUNT(DISTINCT spc.id) as total_versions,
    COUNT(DISTINCT spc.workflow_stage) as unique_stages,
    STUFF((
        SELECT DISTINCT ' → ' + spc2.workflow_stage
        FROM script_preview_content spc2
        WHERE spc2.request_id = r.id
        ORDER BY ' → ' + spc2.workflow_stage
        FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 4, '') as workflow_progression
FROM script_request r
LEFT JOIN script_preview_content spc ON r.id = spc.request_id
WHERE r.id >= 10  -- Recent requests only
GROUP BY r.id, r.script_number, r.status
ORDER BY r.id DESC;
GO

-- Query 4: Detailed version timeline for a request
-- (Shows full progression with timestamps)
SELECT 
    ROW_NUMBER() OVER (PARTITION BY request_id ORDER BY created_at, id) as version_number,
    id,
    media,
    workflow_stage,
    created_by,
    FORMAT(created_at, 'yyyy-MM-dd HH:mm:ss') as created_at,
    CASE 
        WHEN CAST(content AS NVARCHAR(MAX)) LIKE '%revision-span%' THEN 'Has Red Text'
        ELSE 'No Edits'
    END as has_reviewer_edits
FROM script_preview_content
WHERE request_id = 14  -- ← GANTI dengan request ID yang di-test
ORDER BY created_at ASC, id ASC;
GO

PRINT '✓ Queries ready!';
PRINT 'Expected Result:';
PRINT '  - Query 1: Should show multiple rows per request with different workflow_stage';
PRINT '  - Query 2: Should show version_count for each stage (SPV, PIC, PROCEDURE)';
PRINT '  - Query 3: Overview of recent requests with version counts';
PRINT '  - Query 4: Detailed timeline with version numbers';
