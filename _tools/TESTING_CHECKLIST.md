# Manual Testing & Debugging Guide

Gunakan checklist ini untuk memverifikasi fitur utama aplikasi EUC Script CCnR.

## 1. Create Request (Role: MAKER)

- [ ] **Create Form Validation**
    - [ ] Coba submit form kosong (harus ada error warning).
    - [ ] Coba submit tanpa mengisi "Media Channel" (harus error).

- [ ] **Mode: File Upload**
    - [ ] Upload file Excel (`.xlsx`) dengan **1 Sheet**.
        - [ ] Preview harus muncul.
        - [ ] Submit -> Cek di Dashboard SPV.
    - [ ] Upload file Excel dengan **Multi-Sheet** (misal: `Robo_CC`, `Robo_PL`).
        - [ ] Preview harus memunculkan Tab Button untuk setiap sheet.
        - [ ] Pindah tab harus lancar.
        - [ ] Pastikan tidak ada Tab Duplikat (misal `Sheet1` ada dua).

- [ ] **Mode: Free Input**
    - [ ] Pilih "Free Input".
    - [ ] Ketik di text area.
    - [ ] Masukkan karakter spesial (misal: `@`, `#`, emoji, atau Quote `' "`).
    - [ ] Save Draft -> Refresh halaman -> Pastikan konten tidak hilang.

## 2. Review Workflow (Role: SPV / PIC / PROCEDURE)

- [ ] **Review Page Load**
    - [ ] Login sebagai SPV/PIC.
    - [ ] Buka request yang statusnya `PENDING APPROVAL`.
    - [ ] Pastikan tombol "Approve" dan "Revision" muncul.
    - [ ] Sidebar History (kanan) harus tampil urut dari bawah ke atas.

- [ ] **Editing Content (Red Text)**
    - [ ] Klik tombol **"Revisi Maker"** (text area jadi editable).
    - [ ] **TEST PENTING:** Ketik 1 huruf (misal `a`).
        - [ ] Harusnya muncul `a` warna merah.
        - [ ] **JANGAN SAMPAI** muncul `aa` (double character bug).
    - [ ] **TEST PENTING:** Hapus teks (Backspace).
        - [ ] Text harus terhapus normal.

- [ ] **Review Notes (Komentar)**
    - [ ] Blok teks -> Klik **"Add Highlight"**.
    - [ ] Tulis komentar di popup.
    - [ ] Pastikan Note muncul di sidebar kanan.
    - [ ] **Grouping:** Note harus punya Header nama user (misal `SPV01`).

- [ ] **Navigasi Note**
    - [ ] Klik salah satu Note di sidebar.
    - [ ] Halaman harus **Scroll Otomatis** ke teks yang di-highlight.
    - [ ] Teks harus berkedip (kuning).
    - [ ] **Cross-Tab:** Jika note ada di tab lain (misal lagi buka `Robo_CC` tapi note di `Robo_PL`), klik note harus otomatis pindah tab lalu scroll.

## 3. Approval & Versioning

- [ ] **Action: Revision**
    - [ ] Klik tombol "Revision" (Kembalikan ke Maker).
    - [ ] Login sebagai Maker -> Buka request.
    - [ ] Pastikan status `REVISION`.
    - [ ] Maker harus bisa edit ulang.

- [ ] **Action: Approve**
    - [ ] SPV Approve -> Status harus naik ke PIC.
    - [ ] PIC Approve -> Status harus naik ke PROCEDURE.
    - [ ] PROCEDURE Approve -> Status `CLOSED / LIBRARY`.

- [ ] **Action: Library Clean** (Khusus PROCEDURE)
    - [ ] Saat Procedure Approve, cek menu **Script Library**.
    - [ ] Buka detail script.
    - [ ] Konten harus **BERSIH** (Hitam semua), tidak ada coretan merah/kuning.
    - [ ] Klik "View Revision History" -> Harus masuk ke Audit Trail yang masih ada coretannya.

## 4. Audit Trail (History)

- [ ] **Audit Trail List**
    - [ ] Buka menu Audit Trail.
    - [ ] Cek tabel, pastikan datanya lengkap (Maker, SPV, Action, Timestamp).
    - [ ] **Advanced Filter:**
        - [ ] Pilih Filter > 1 Kategori (misal: `Kartu Kredit` DAN `KTA`).
        - [ ] Klik Apply.
        - [ ] **JANGAN SAMPAI** ada baris duplikat untuk script yang sama.

- [ ] **Audit Trail Detail (Versi)**
    - [ ] Klik detail salah satu request.
    - [ ] Cek tombol Versi di atas preview (`MAKER`, `SPV`, `PIC`, `PROCEDURE`).
    - [ ] Klik tombol `MAKER` -> Harus muncul versi Original.
    - [ ] Klik tombol `SPV` -> Harus muncul versi coretan SPV.
    - [ ] Cek Sidebar Kanan (Review Notes): Harus sesuai dengan versi yang dipilih.

## 5. Script Library

- [ ] **Export Excel**
    - [ ] Buka detail Library.
    - [ ] Klik "Export to Excel".
    - [ ] Download file -> Buka di Excel.
    - [ ] Pastikan format rapi (tidak ada tag HTML `<div>` atau `<br>`).
