-- Track Changes Migration Script
-- Phase 1: Create revision_history table
-- Purpose: Store permanent history of all revision edits across SPV/PIC/Procedure/Maker roles

-- Drop existing table if re-running (CAUTION: This deletes data!)
-- IF OBJECT_ID('revision_history', 'U') IS NOT NULL
--     DROP TABLE revision_history;

CREATE TABLE revision_history (
    id INT PRIMARY KEY IDENTITY(1,1),
    request_id INT NOT NULL,
    revision_id VARCHAR(50) NOT NULL,
    version INT NOT NULL,
    action VARCHAR(10) NOT NULL, -- 'CREATE', 'UPDATE', 'DELETE'
    text_before NTEXT,
    text_after NTEXT,
    author_role VARCHAR(20) NOT NULL, -- 'SPV', 'PIC', 'PROCEDURE', 'MAKER'
    author_id INT,
    comment_text NTEXT, -- Optional: User's manual comment explaining the change
    created_at DATETIME DEFAULT GETDATE(),
    
    -- Foreign Keys
    CONSTRAINT FK_revision_history_request 
        FOREIGN KEY (request_id) REFERENCES script_request(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    CONSTRAINT IDX_revision_id 
        UNIQUE NONCLUSTERED (revision_id, version)
);

-- Create indexes for common queries
CREATE INDEX IDX_revision_history_request 
    ON revision_history(request_id);

CREATE INDEX IDX_revision_history_created 
    ON revision_history(created_at DESC);

-- Verification query
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    CHARACTER_MAXIMUM_LENGTH,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'revision_history'
ORDER BY ORDINAL_POSITION;
