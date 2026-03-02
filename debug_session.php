<?php
// Script untuk mengecek data Session dari Portal Utama
session_start();

echo "<h2>Debug Session CITRASF -> Script</h2>";
echo "Pastikan Anda <b>sudah login di portal utama CITRASF</b> sebelum membuka halaman ini.<br><br>";

echo "<h3>1. Isi variabel \$_SESSION:</h3>";
if (empty($_SESSION)) {
    echo "<p style='color:red;'><b>KOSONG.</b> Tidak ada data session yang masuk.</p>";
    echo "<p>Kemungkinan penyebab:<br>
          - Anda belum login di portal utama.<br>
          - Portal utama menggunakan nama session cookie yang berbeda (bukan PHPSESSID).<br>
          - Path Cookie dari portal utama membatasi session supaya tidak terbaca di luar foldernya sendiri.</p>";
} else {
    echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc; max-height:400px; overflow:auto;'>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Quick check for NIK indicator
    if (isset($_SESSION['NIK'])) {
        echo "<p style='color:green;'>✅ <b>\$_SESSION['NIK'] DITEMUKAN:</b> " . htmlspecialchars($_SESSION['NIK']) . "</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ <b>\$_SESSION['NIK'] TIDAK DITEMUKAN!</b><br>
              Silakan cek kotak abu-abu di atas. Bandingkan isinya, kira-kira portal utama menyimpan data <b>NIK/User ID</b> di nama key apa? (Contoh: mungkin <code>\$_SESSION['nik']</code>, atau <code>\$_SESSION['userid']</code>).</p>";
    }
}

echo "<h3>2. Isi variabel \$_COOKIE (Cookie Browser yang ditangkap):</h3>";
echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc;'>";
print_r($_COOKIE);
echo "</pre>";
?>
