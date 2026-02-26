<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EUC Script CCnR</title>
    <link rel="stylesheet" href="public/css/style.css?v=9">
    <!-- Flaticon Local -->
    <link rel='stylesheet' href='public/css/uicons-regular-rounded.css'>
	
    <!-- Custom Local Modal (Replaces SweetAlert2 for Offline Compatibility) -->
    <script>
        // Inject Custom Styles for Local Modal
        const swalLocalStyle = document.createElement('style');
        swalLocalStyle.innerHTML = `
            .swal-fallback-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; display:flex; justify-content:center; align-items:center; opacity:0; transition:opacity 0.2s; }
            .swal-fallback-overlay.show { opacity: 1; }
            .swal-fallback-box { background:white; padding:25px; border-radius:8px; width:90%; max-width:400px; box-shadow:0 4px 15px rgba(0,0,0,0.2); text-align:center; transform:scale(0.9); transition:transform 0.2s; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .swal-fallback-overlay.show .swal-fallback-box { transform:scale(1); }
            .swal-fallback-title { font-size:18px; font-weight:bold; margin-bottom:10px; color:#333; }
            .swal-fallback-text { font-size:14px; color:#555; margin-bottom:20px; line-height:1.5; }
            .swal-fallback-buttons { display:flex; justify-content:center; gap:10px; }
            .swal-btn { padding:8px 20px; border:none; border-radius:4px; font-weight:bold; cursor:pointer; font-size:14px; transition: background 0.2s; }
            .swal-btn-confirm { background:#dc2626; color:white; }
            .swal-btn-confirm:hover { background:#b91c1c; }
            .swal-btn-cancel { background:#e5e7eb; color:#374151; }
            .swal-btn-cancel:hover { background:#d1d5db; }
            .swal-spinner { border: 4px solid #f3f3f3; border-top: 4px solid #dc2626; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 10px auto; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        `;
        document.head.appendChild(swalLocalStyle);

        // Generic Swal-compatible API
        window.Swal = {
            currentOverlay: null,
            
            fire: function(config) {
                // Close existing if open
                if (this.currentOverlay) this.close();

                return new Promise((resolve) => {
                    let title = '', text = '', showCancel = false;
                    let confirmText = 'OK', cancelText = 'Cancel';
                    let confirmColor = '#dc2626';
                    let iconType = ''; 
                    let showConfirm = true;
                    let timer = null;

                    if (typeof config === 'string') {
                        title = config;
                        text = arguments[1] || '';
                        iconType = arguments[2] || '';
                    } else {
                        title = config.title || '';
                        text = config.text || config.html || '';
                        showCancel = config.showCancelButton || false;
                        iconType = config.icon || '';
                        if(config.confirmButtonText) confirmText = config.confirmButtonText;
                        if(config.cancelButtonText) cancelText = config.cancelButtonText;
                        if(config.confirmButtonColor) confirmColor = config.confirmButtonColor;
                        if(typeof config.showConfirmButton !== 'undefined') showConfirm = config.showConfirmButton;
                        if(config.timer) timer = config.timer;
                    }
                    
                    // Icons
                    const icons = {
                        success: '<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:15px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                        error: '<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:15px;"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
                        warning: '<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:15px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                        info: '<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:15px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
                        question: '<svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:15px;"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>'
                    };
                    
                    const iconHtml = icons[iconType] || '';
                    
                    // Create Elements
                    const overlay = document.createElement('div');
                    overlay.className = 'swal-fallback-overlay';
                    this.currentOverlay = overlay;
                    
                    const box = document.createElement('div');
                    box.className = 'swal-fallback-box';
                    
                    const safeText = text.replace(/<[^>]*>?/gm, ''); 

                    let buttonsHtml = '<div class="swal-fallback-buttons">';
                    if (showCancel) {
                        buttonsHtml += '<button class="swal-btn swal-btn-cancel" id="swal-cancel">' + cancelText + '</button>';
                    }
                    if (showConfirm) {
                        buttonsHtml += '<button class="swal-btn swal-btn-confirm" id="swal-confirm" style="background:' + confirmColor + '">' + confirmText + '</button>';
                    }
                    buttonsHtml += '</div>';

                    box.innerHTML = iconHtml
                        + '<div class="swal-fallback-title">' + title + '</div>'
                        + '<div class="swal-fallback-text">' + safeText + '</div>'
                        + buttonsHtml;
                    
                    overlay.appendChild(box);
                    document.body.appendChild(overlay);

                    requestAnimationFrame(() => overlay.classList.add('show'));

                    let timerId = null;

                    // Inner Close
                    const cleanup = (isConfirmed) => {
                        if (timerId) clearTimeout(timerId);
                        overlay.classList.remove('show');
                        setTimeout(() => {
                            if(document.body.contains(overlay)) document.body.removeChild(overlay);
                            resolve({ isConfirmed: isConfirmed, isDismissed: !isConfirmed });
                        }, 200);
                        this.currentOverlay = null;
                    };

                    const confirmBtn = document.getElementById('swal-confirm');
                    if (confirmBtn) confirmBtn.onclick = () => cleanup(true);
                    
                    if (showCancel) {
                        document.getElementById('swal-cancel').onclick = () => cleanup(false);
                    }

                    if (timer) {
                        timerId = setTimeout(() => {
                            cleanup(true);
                        }, timer);
                    }
                });
            },
            
            showLoading: function() {
                 if (this.currentOverlay) this.close();
                 
                 const overlay = document.createElement('div');
                 overlay.className = 'swal-fallback-overlay show';
                 this.currentOverlay = overlay;

                 const box = document.createElement('div');
                 box.className = 'swal-fallback-box';
                 box.innerHTML = `
                    <div class="swal-spinner"></div>
                    <div class="swal-fallback-text" style="margin-bottom:0;">Loading...</div>
                 `;
                 
                 overlay.appendChild(box);
                 document.body.appendChild(overlay);
            },
            
            close: function() {
                if (this.currentOverlay) {
                    const el = this.currentOverlay;
                    el.classList.remove('show');
                    setTimeout(() => {
                        if(document.body.contains(el)) document.body.removeChild(el);
                    }, 200);
                    this.currentOverlay = null;
                }
            }
        };
    </script>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <div style="display:flex;align-items:center;gap:15px;">
            <!-- Hamburger Menu for Desktop & Mobile -->
            <button id="sidebar-toggle" onclick="toggleSidebar()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:8px; display:inline-flex; align-items:center; justify-content:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
        <div>
            <?php 
            // Handle session check loosely here if not strictly enforced elsewhere
            if(session_status() == PHP_SESSION_NONE) session_start();
            $user = $_SESSION['user'] ?? ['fullname' => 'Guest', 'job_function' => 'Visitor'];
            ?>
            <span class="header-user-info">
                <strong>Selamat Datang!</strong> <?php echo htmlspecialchars($user['fullname'] ?: $user['userid']); ?> 
                <span>(<?php echo htmlspecialchars($user['role_label'] ?? $user['dept'] ?? 'USER'); ?>)</span>
            </span>
            <a href="javascript:void(0);" onclick="confirmLogout()" style="color:white;margin-left:15px;text-decoration:none;font-weight:bold;">
                <span class="logout-text">Logout</span>
                <!-- Mobile Logout Icon -->
                <svg class="logout-icon" style="display:none;" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </a>
        </div>
    </div>
    
    <!-- Overlay for Mobile Sidebar -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        const body = document.body;
        
        // Mobile: use 'active' class
        // Desktop: use 'collapsed' class
        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        } else {
            // Desktop mode
            sidebar.classList.toggle('collapsed');
            body.classList.toggle('sidebar-collapsed');
        }
    }

    function confirmLogout() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Anda akan keluar dari sesi ini.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#6e7881',
                confirmButtonText: 'Ya, Logout!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?action=logout';
                }
            });
        } else {
            // Fallback for offline/missing assets
            if (confirm("Apakah Anda yakin ingin logout?")) {
                window.location.href = 'index.php?action=logout';
            }
        }
    }
    </script>
    <style>
        /* Mobile Logout Icon Style */
        @media (max-width: 768px) {
            .logout-text { display: none; }
            .logout-icon { display: inline-block !important; }
        }
    </style>

