    </div><!-- end content -->
    <!-- Footer -->
    <footer class="px-6 py-4 border-t border-white/5 text-center text-xs text-slate-600">
        &copy; <?= date('Y') ?> <?= clean($setting['nama_sekolah'] ?? APP_NAME) ?> — <?= APP_NAME ?> <?= APP_VERSION ?>
    </footer>
</main><!-- end main -->
<?php require_once __DIR__ . '/pwa_bottomnav.php'; ?>
</div><!-- end flex -->

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('hidden');
}

// Close sidebar on link click (mobile)
document.querySelectorAll('.sidebar-link').forEach(l => l.addEventListener('click', () => {
    if (window.innerWidth < 768) toggleSidebar();
}));

// Animate counters
document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count);
    let current = 0;
    const step = Math.ceil(target / 40);
    const timer = setInterval(() => {
        current += step;
        if (current >= target) { current = target; clearInterval(timer); }
        el.textContent = current.toLocaleString('id-ID');
    }, 30);
});

// Confirm delete
function confirmDelete(url, name) {
    if (confirm('Yakin hapus ' + (name||'data ini') + '?')) window.location = url;
}

// Modal
function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); }
// --- PWA Background Push Notification Tracker ---
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('<?= BASE_URL ?>service-worker.js');
}

if ('Notification' in window) {
    if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
        Notification.requestPermission();
    }
    
    // Sinkronisasi radar notif setiap 5 detik
    setInterval(() => {
        if (Notification.permission === 'granted') {
            fetch('<?= BASE_URL ?>api/get_pwa_notif.php')
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok' && data.data && data.data.length > 0) {
                    data.data.forEach(notif => {
                        // Tampilkan Pop-Up Native (Push Notification)
                        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                            navigator.serviceWorker.ready.then(function(registration) {
                                registration.showNotification(notif.judul, {
                                    body: notif.pesan,
                                    icon: '<?= BASE_URL ?>assets/img/icon-192.png',
                                    badge: '<?= BASE_URL ?>assets/img/icon-192.png',
                                    vibrate: [200, 100, 200, 100, 200, 100, 200]
                                });
                            });
                        } else {
                            new Notification(notif.judul, {
                                body: notif.pesan,
                                icon: '<?= BASE_URL ?>assets/img/icon-192.png'
                            });
                        }
                    });
                }
            }).catch(e => {});
        }
    }, 5000);
}

// ─── WEBAUTHN REGISTRATION PROMPT ───
if (window.PublicKeyCredential !== undefined && '<?= $_SESSION['username'] ?? '' ?>' !== '') {
    setTimeout(async () => {
        const username = '<?= $_SESSION['username'] ?? '' ?>';
        const prompted = localStorage.getItem('sipanda_webauthn_prompted_' + username);
        
        if (!prompted) {
            try {
                // Check if already registered
                const res = await fetch(`<?= BASE_URL ?>api/webauthn.php?action=check&username=${encodeURIComponent(username)}`);
                const data = await res.json();
                
                if (!data.registered) {
                    Swal.fire({
                        title: 'Aktifkan Sidik Jari?',
                        text: 'Login lebih cepat dan aman tanpa mengetik password.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, Aktifkan',
                        cancelButtonText: 'Nanti Saja'
                    }).then(async (result) => {
                        localStorage.setItem('sipanda_webauthn_prompted_' + username, 'true');
                        
                        if (result.isConfirmed) {
                            // Run registration flow
                            try {
                                const formData = new FormData();
                                formData.append('action', 'register_options');
                                
                                const optRes = await fetch('<?= BASE_URL ?>api/webauthn.php', { method: 'POST', body: formData });
                                const options = await optRes.json();
                                
                                if (options.status !== 'ok') throw new Error(options.message);
                                
                                // Helper decode
                                const bufferDecode = (val) => Uint8Array.from(atob(val.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
                                const bufferEncode = (val) => btoa(String.fromCharCode.apply(null, new Uint8Array(val))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
                                
                                const createOptions = {
                                    publicKey: {
                                        rp: options.rp,
                                        user: {
                                            id: bufferDecode(options.user.id),
                                            name: options.user.name,
                                            displayName: options.user.displayName
                                        },
                                        challenge: bufferDecode(options.challenge),
                                        pubKeyCredParams: options.pubKeyCredParams,
                                        timeout: options.timeout,
                                        authenticatorSelection: options.authenticatorSelection,
                                        attestation: options.attestation,
                                        excludeCredentials: options.excludeCredentials.map(c => ({
                                            type: c.type,
                                            id: bufferDecode(c.id)
                                        }))
                                    }
                                };
                                
                                const credential = await navigator.credentials.create(createOptions);
                                
                                const regData = {
                                    id: credential.id,
                                    device_name: navigator.userAgent.substring(0, 50), // simple device name
                                    response: {
                                        attestationObject: bufferEncode(credential.response.attestationObject),
                                        clientDataJSON: bufferEncode(credential.response.clientDataJSON)
                                    }
                                };
                                
                                const verifyRes = await fetch('<?= BASE_URL ?>api/webauthn.php?action=register_verify', {
                                    method: 'POST',
                                    headers: {'Content-Type': 'application/json'},
                                    body: JSON.stringify(regData)
                                });
                                
                                const verifyResult = await verifyRes.json();
                                if (verifyResult.status === 'success') {
                                    Swal.fire('Berhasil!', verifyResult.message, 'success');
                                } else {
                                    throw new Error(verifyResult.message);
                                }
                                
                            } catch (e) {
                                console.error(e);
                                Swal.fire('Gagal', e.message || 'Pendaftaran sidik jari dibatalkan.', 'error');
                            }
                        }
                    });
                }
            } catch (e) {
                console.error('WebAuthn check error:', e);
            }
        }
    }, 2000); // Wait 2s after page load
}
</script>
<?= alert_flash('interactive') ?>
</body>
</html>
