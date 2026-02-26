<?php
// fix_offline.php
$jsUrl = "https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js";
$cssUrl = "https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css";

$message = "";

if (isset($_POST['download'])) {
    // 1. Pastikan folder Tujuan ada
    if (!is_dir('public/js')) mkdir('public/js', 0777, true);
    if (!is_dir('public/css')) mkdir('public/css', 0777, true);

    // 2. Download dan simpan File
    $jsSuccess = file_put_contents('public/js/xlsx.bundle.js', file_get_contents($jsUrl));
    $cssSuccess = file_put_contents('public/css/uicons-regular-rounded.css', file_get_contents($cssUrl));

    if ($jsSuccess !== false && $cssSuccess !== false) {
        $message = "<div style='color: green; margin-bottom: 20px;'><b>BERHASIL!</b> Kedua file berhasil didownload dan disimpan secara offline. Silakan cek aplikasi utama Anda.</div>";
    } else {
        $message = "<div style='color: red; margin-bottom: 20px;'><b>GAGAL!</b> Gagal menyimpan file. Pastikan folder public/js dan public/css memiliki izin penulisan (write permission).</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Unduh Aset Offline</title>
</head>
<body style="font-family: sans-serif; padding: 40px; text-align: center;">
    <h2>Solusi Download Aset Offline (CSS & JS)</h2>
    <p>Klik tombol di bawah ini untuk mendownload file <i>Flaticon</i> dan <i>SheetJS</i> ke dalam server lokal Anda.</p>
    
    <?php echo $message; ?>

    <form method="POST">
        <button type="submit" name="download" style="padding: 15px 30px; font-size: 16px; background: #dc2626; color: white; border: none; cursor: pointer; border-radius: 8px;">
            ⬇️ DOWNLOAD ASET SEKARANG ⬇️
        </button>
    </form>
</body>
</html>
