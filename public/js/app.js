/* ══════════════════════════════════════════════════════════════
   SAEC Cloud — App JS
   ══════════════════════════════════════════════════════════════ */

document.addEventListener('DOMContentLoaded', () => {
    initUploadZone();
    initFileActions();
    initDeleteConfirm();
});

/* ── Upload Zone ── */
function initUploadZone() {
    const zone = document.querySelector('.upload-zone');
    if (!zone) return;

    const input = zone.querySelector('input[type="file"]') || createFileInput(zone);
    const progress = zone.querySelector('.upload-progress');
    const progressFill = zone.querySelector('.progress-fill');
    const progressText = zone.querySelector('.progress-text');

    zone.addEventListener('click', () => input.click());

    zone.addEventListener('dragover', (e) => {
        e.preventDefault();
        zone.classList.add('dragover');
    });

    zone.addEventListener('dragleave', () => {
        zone.classList.remove('dragover');
    });

    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });

    input.addEventListener('change', () => {
        handleFiles(input.files);
    });

    function createFileInput(zone) {
        const inp = document.createElement('input');
        inp.type = 'file';
        inp.multiple = true;
        inp.style.display = 'none';
        zone.appendChild(inp);
        return inp;
    }

    function handleFiles(files) {
        if (!files.length) return;

        for (const file of files) {
            uploadFile(file);
        }
    }

    function uploadFile(file) {
        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/files/upload', true);
        if (typeof CSRF !== 'undefined') {
            xhr.setRequestHeader('X-CSRF-Token', CSRF);
        }

        if (progress) {
            progress.classList.add('active');
        }

        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable && progressFill) {
                const pct = Math.round((e.loaded / e.total) * 100);
                progressFill.style.width = pct + '%';
                if (progressText) progressText.textContent = pct + '%';
            }
        });

        xhr.addEventListener('load', () => {
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                if (data.success) {
                    showNotification('Fichier uploadé: ' + file.name, 'success');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showNotification(data.error || 'Upload failed', 'error');
                }
            } else {
                showNotification('Erreur upload (' + xhr.status + ')', 'error');
            }
            if (progress) {
                progress.classList.remove('active');
                if (progressFill) progressFill.style.width = '0%';
            }
        });

        xhr.addEventListener('error', () => {
            showNotification('Erreur réseau', 'error');
            if (progress) progress.classList.remove('active');
        });

        xhr.send(formData);
    }
}

/* ── File Actions ── */
function initFileActions() {
    document.querySelectorAll('[data-action="download"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            window.location.href = '/files/' + id + '/download';
        });
    });

    document.querySelectorAll('[data-action="delete"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            const name = btn.dataset.name || 'ce fichier';

            if (confirm('Supprimer "' + name + '" ?\n(Sera déplacé vers la corbeille)')) {
                fetch('/files/' + id, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Fichier supprimé', 'success');
                        const row = btn.closest('.file-row');
                        if (row) {
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        }
                    } else {
                        showNotification(data.error || 'Erreur', 'error');
                    }
                })
                .catch(() => showNotification('Erreur réseau', 'error'));
            }
        });
    });

    document.querySelectorAll('[data-action="restore"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const id = btn.dataset.id;

            fetch('/files/' + id + '/restore', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification('Fichier restauré', 'success');
                    const row = btn.closest('.file-row');
                    if (row) {
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                } else {
                    showNotification(data.error || 'Erreur', 'error');
                }
            })
            .catch(() => showNotification('Erreur réseau', 'error'));
        });
    });
}

/* ── Delete Confirm ── */
function initDeleteConfirm() {
    // Handled inline in initFileActions
}

/* ── Notifications ── */
function showNotification(message, type = 'success') {
    const existing = document.querySelector('.notification');
    if (existing) existing.remove();

    const el = document.createElement('div');
    el.className = 'notification notification-' + type;
    el.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
    el.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 9999;
        padding: 14px 20px; border-radius: 8px; font-size: 14px;
        display: flex; align-items: center; gap: 10px;
        animation: fadeIn 0.3s ease-out;
        font-family: var(--font-sans);
        ${type === 'success'
            ? 'background: rgba(16,185,129,0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3);'
            : 'background: rgba(239,68,68,0.15); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);'
        }
    `;

    document.body.appendChild(el);
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transition = 'opacity 0.3s';
        setTimeout(() => el.remove(), 300);
    }, 3000);
}

/* ── Copy Share Link ── */
function copyShareLink(token) {
    const url = window.location.origin + '/share/' + token;
    navigator.clipboard.writeText(url).then(() => {
        showNotification('Lien copié !', 'success');
    });
}
