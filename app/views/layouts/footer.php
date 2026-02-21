<!-- GLOBAL CENTER MODAL -->
<div id="globalModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:white; padding:30px; border-radius:8px; width:400px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,0.1); animation: popIn 0.3s ease;">
        <div id="modalIcon" style="font-size:40px; margin-bottom:15px;"></div>
        <h3 id="modalTitle" style="margin-bottom:10px; color:#333;">Title</h3>
        <p id="modalMessage" style="color:#666; margin-bottom:25px;">Message</p>
        <button class="btn btn-primary" onclick="closeGlobalModal()" style="width:100%;">OK</button>
    </div>
</div>

<script>
    function showGlobalModal(title, message, type = 'info') {
        const modal = document.getElementById('globalModal');
        const icon = document.getElementById('modalIcon');
        const titleEl = document.getElementById('modalTitle');
        const msgEl = document.getElementById('modalMessage');

        // Set Content
        titleEl.innerText = title;
        msgEl.innerHTML = message;
        
        // Set Icon & Color
        if (type === 'success') {
            icon.innerHTML = '✅';
            titleEl.style.color = '#10B981'; // Green
        } else if (type === 'error') {
            icon.innerHTML = '❌';
            titleEl.style.color = '#EF4444'; // Red
        } else {
            icon.innerHTML = 'ℹ️';
            titleEl.style.color = '#3B82F6'; // Blue
        }

        // Show (Flex to center)
        modal.style.display = 'flex';
    }

    function closeGlobalModal() {
        document.getElementById('globalModal').style.display = 'none';
        
        // If there was a redirect pending, we can handle it here via global var, 
        // but for now let's just close.
        if (window.pendingRedirect) {
            window.location.href = window.pendingRedirect;
        }
    }

    function filterTable(queryOrInputId, tableId) {
        // Support both calling conventions: (inputId, tableId) or (queryText, tableId)
        let filter;
        const el = document.getElementById(queryOrInputId);
        if (el && el.value !== undefined) {
            filter = el.value.trim().toUpperCase();
        } else {
            filter = (queryOrInputId || '').trim().toUpperCase();
        }
        
        const table = document.getElementById(tableId);
        if (table) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(function(tr) {
                if (!filter) { tr.style.display = ''; return; }
                let searchText = '';
                tr.querySelectorAll('td').forEach(function(td) {
                    searchText += ' ' + (td.innerText || '');
                });
                tr.style.display = searchText.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
            });
        }

        // Also filter Grid View cards if present
        const gridView = document.getElementById('view-grid');
        if (gridView) {
            const cards = gridView.children;
            for (let i = 0; i < cards.length; i++) {
                if (!filter) { cards[i].style.display = ''; continue; }
                const txt = cards[i].innerText || '';
                cards[i].style.display = txt.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
            }
        }
    }

    // Keyframes for animation
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    `;
    document.head.appendChild(style);
</script>

</body>
</html>
