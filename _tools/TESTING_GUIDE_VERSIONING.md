# Content Versioning Testing Guide

## Goal
Test versioning system end-to-end: SPV → PIC → PROCEDURE
Verify each role creates new version rows with reviewer edits preserved in Audit Trail.

---

## Prerequisites

1. ✅ Database migration completed (`add_versioning_columns.sql`)
2. ✅ Backend changes deployed (`RequestModel.php`, `RequestController.php`)
3. ✅ Frontend fix deployed (`review.php` - handleBeforeInput enabled)
4. Choose a request for testing:
   - **Option A:** Use existing request in `APPROVED_SPV` or `APPROVED_PIC` status
   - **Option B:** Create new request from MAKER

---

## Test Scenario: Complete Workflow

### **Step 1: SPV Review & Approve**

**1.1 Login as SPV**
- Username: `spv01` (atau SPV user lain)
- Navigate to: **"Need to Approval"**

**1.2 Open Request**
- Click request yang status `SUBMIT_REQUEST` atau `APPROVED_MAKER`
- Note request ID (contoh: **Request 35**)

**1.3 Review & Add Red Text**
- Hard refresh (Ctrl+F5) untuk load latest code
- Click di dalam editor (table cell atau free input)
- **Ketik teks baru** atau **edit teks existing**
- **Verify:** Teks langsung berubah **MERAH**
- **Verify:** Draft card muncul di **sidebar kanan**

**1.4 Open Browser Console (F12)**
- Tab: Console
- Prepare untuk monitoring debug logs

**1.5 Click "Approve"**
- Confirm approval dialog
- **Watch Console Logs:**
  ```
  [DEBUG] Auto-committing drafts...
  [DEBUG] File Upload Mode - Panes found: X
  [DEBUG] Saved pane X for ID YYY
  [DEBUG] Final updatedContent: N items
  Backend response: {"success":true}
  ```
- **Screenshot Console logs**

**1.6 Verify Database**
Run Query 1 in SSMS:
```sql
SELECT 
    id, media, workflow_stage, created_by, created_at,
    LEN(CAST(content AS NVARCHAR(MAX))) as content_length,
    CASE 
        WHEN CAST(content AS NVARCHAR(MAX)) LIKE '%revision-span%' THEN 'Has Red Text'
        ELSE 'No Edits'
    END as has_edits
FROM script_preview_content
WHERE request_id = 35  -- ← GANTI dengan request ID yang di-test
ORDER BY created_at ASC, id ASC;
```

**Expected Result:**
- ✅ New rows with `workflow_stage = 'APPROVED_SPV'`
- ✅ `created_by = 'spv01'` (your SPV user ID)
- ✅ `created_at = [current timestamp]`
- ✅ `has_edits = 'Has Red Text'`

**📸 Screenshot:** SQL Result

---

### **Step 2: PIC Review & Approve**

**2.1 Login as PIC**
- Username: `pic01` (atau PIC user lain)
- Navigate to: **"Need to Approval"**

**2.2 Open Same Request**
- Request status should now be `APPROVED_SPV`
- Request ID: **35** (sama dengan Step 1)

**2.3 Review & Add More Red Text**
- Hard refresh (Ctrl+F5)
- **Add different edits** (to distinguish from SPV's edits)
- **Verify:** New red text appears
- **Verify:** New draft cards in sidebar

**2.4 Click "Approve"**
- Monitor Console logs (same as Step 1.5)
- **Screenshot Console logs**

**2.5 Verify Database**
Run same Query 1, should now see:
```
id    workflow_stage         created_by  created_at           has_edits
---   --------------------   ----------  -------------------  -------------
XXX   APPROVED_SPV           spv01       2026-02-05 19:30:00  Has Red Text   ← SPV version
YYY   APPROVED_PIC           pic01       2026-02-05 19:35:00  Has Red Text   ← PIC version (NEW!)
```

**Expected Result:**
- ✅ **Previous SPV rows still exist** (not overwritten!)
- ✅ **New PIC rows added** with `workflow_stage = 'APPROVED_PIC'`
- ✅ Total rows doubled (SPV rows + PIC rows)

**📸 Screenshot:** SQL Result

---

### **Step 3: PROCEDURE Review & Approve**

**3.1 Login as PROCEDURE**
- Username: `proc01` (atau PROCEDURE user lain)
- Navigate to: **"Need to Approval"**

**3.2 Open Same Request**
- Request status should now be `APPROVED_PIC`
- Request ID: **35**

**3.3 Review & Add Final Red Text**
- Hard refresh (Ctrl+F5)
- **Add final edits** (different from SPV and PIC)
- **Verify:** Red text appears
- **Verify:** Sidebar shows all accumulated comments

**3.4 Click "Approve"**
- Monitor Console logs
- **Screenshot Console logs**

**3.5 Verify Database**
Run Query 1, should now see:
```
id    workflow_stage              created_by  created_at           has_edits
---   -------------------------   ----------  -------------------  -------------
XXX   APPROVED_SPV                spv01       2026-02-05 19:30:00  Has Red Text
YYY   APPROVED_PIC                pic01       2026-02-05 19:35:00  Has Red Text
ZZZ   APPROVED_PROCEDURE          proc01      2026-02-05 19:40:00  Has Red Text  ← PROC version (NEW!)
```

**Expected Result:**
- ✅ **All previous versions preserved**
- ✅ **New PROCEDURE rows added**
- ✅ Total rows tripled (SPV + PIC + PROC)

**📸 Screenshot:** SQL Result

---

### **Step 4: Verify Audit Trail**

**4.1 Open Audit Trail**
- Navigate to: **Audit Trail** page
- Search for Request ID: **35**
- Click to open detail

**4.2 Check Version Timeline**
- **Verify:** Version selector buttons visible
- **Expected:** "Version 1 (SPV)", "Version 2 (PIC)", "Version 3 (PROCEDURE)"

**4.3 Test Version Switching**
- Click **"Version 1 (SPV)"**
  - Content should show: Original + SPV's red text only
  - Sidebar: SPV's review notes only
  
- Click **"Version 2 (PIC)"**
  - Content should show: Original + SPV + PIC's red text
  - Sidebar: SPV + PIC's review notes
  
- Click **"Version 3 (PROCEDURE)"**
  - Content should show: All edits (SPV + PIC + PROC)
  - Sidebar: All review notes

**4.4 Test Review Notes Click**
- Click any review note in sidebar
- **Verify:** Page scrolls to corresponding red text
- **Verify:** Red text gets highlighted

**📸 Screenshot:** Audit Trail with version selector & sidebar

---

## Verification Checklist

- [ ] SPV creates new rows with `workflow_stage = 'APPROVED_SPV'`
- [ ] PIC creates new rows with `workflow_stage = 'APPROVED_PIC'`
- [ ] PROCEDURE creates new rows with `workflow_stage = 'APPROVED_PROCEDURE'`
- [ ] Each version has correct `created_by` user ID
- [ ] Each version has correct `created_at` timestamp
- [ ] Red text (`<span class="revision-span">`) exists in content
- [ ] Audit Trail shows version selector buttons
- [ ] Switching versions changes content display
- [ ] Switching versions updates review notes sidebar
- [ ] Clicking review note scrolls to red text
- [ ] Old versions are NOT overwritten

---

## Troubleshooting

### Issue: Console shows `Panes found: 0`
**Solution:** 
- Check if File Upload mode
- Check if `unified-file-editor` exists in DOM
- Try Free Input mode instead

### Issue: No red text in database
**Solution:**
- Verify `handleBeforeInput` is attached (check Console for errors)
- Check if `autoCommitDrafts()` is called before submit
- Verify `applyModeColor()` is setting correct mode

### Issue: Audit Trail shows only 1 version
**Solution:**
- Check if `getRequestDetail()` query is executing correctly
- Verify `workflow_stage` grouping in SQL query
- Check browser cache (hard refresh)

---

## Success Criteria

✅ **3 distinct version rows** created in database  
✅ **Each version** has unique `workflow_stage`, `created_by`, `created_at`  
✅ **Red text preserved** in all versions  
✅ **Audit Trail renders** version timeline correctly  
✅ **Review notes** display per version  
✅ **No data loss** - all previous versions intact
