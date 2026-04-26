<style>
/* Custom animations & design for Guru AI Widget */
#guru-ai-widget { z-index: 9999; }
.guru-msg-bot { background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); color: #1e293b; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px; }
.guru-msg-user { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #ffffff; border-bottom-right-radius: 4px; }
.guru-typing-dot { animation: guru-bounce 1.4s infinite ease-in-out both; }
.guru-typing-dot:nth-child(1) { animation-delay: -0.32s; }
.guru-typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes guru-bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
@keyframes guru-slide-up { 0% { opacity: 0; transform: translateY(20px) scale(0.95); } 100% { opacity: 1; transform: translateY(0) scale(1); } }
.guru-animate-slide-up { animation: guru-slide-up 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
.markdown-body ul { list-style-type: disc; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
.markdown-body ol { list-style-type: decimal; padding-left: 1.5rem; margin-top: 0.5rem; margin-bottom: 0.5rem; }
.markdown-body strong { font-weight: 700; color: #0a1628; }
.guru-msg-user .markdown-body strong { color: white; }
</style>

<!-- Floating Action Button -->
<button id="guruAiFab" onclick="toggleGuruAi()" class="fixed bottom-[100px] right-6 h-14 px-6 bg-navy-950 text-gold-400 rounded-full shadow-2xl shadow-navy-900/40 flex items-center justify-center gap-3 text-lg font-bold hover:scale-105 transition-all duration-300 z-[9998] border-2 border-gold-500/30 overflow-hidden group">
    <div class="absolute inset-0 bg-gradient-to-tr from-gold-500/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
    <i class="fas fa-robot animate-pulse text-xl" id="guruFabIcon"></i>
    <span id="guruFabText" class="tracking-wide text-sm whitespace-nowrap">Chat Guru AI</span>
</button>

<!-- Chat Window -->
<div id="guruAiWindow" class="fixed bottom-[164px] right-4 sm:right-6 w-[350px] max-w-[calc(100vw-32px)] h-[500px] max-h-[70vh] bg-white border border-slate-200 shadow-2xl rounded-3xl overflow-hidden flex flex-col hidden guru-animate-slide-up z-[9999]" style="box-shadow: 0 25px 50px -12px rgba(10, 22, 40, 0.3);">
    
    <!-- Header -->
    <div class="bg-navy-950 p-4 border-b-4 border-gold-500 relative shrink-0">
        <div class="absolute top-0 right-0 w-32 h-32 bg-gold-400/10 rounded-full blur-2xl"></div>
        <div class="flex items-center justify-between relative z-10">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-10 h-10 bg-gradient-to-br from-gold-400 to-amber-600 rounded-2xl flex items-center justify-center text-white text-xl shadow-lg shadow-gold-500/30">
                        <i class="fas fa-robot"></i>
                    </div>
                    <span class="absolute -top-1 -right-1 w-3 h-3 bg-emerald-500 border-2 border-navy-950 rounded-full animate-pulse"></span>
                </div>
                <div>
                    <h3 class="font-bold text-white text-base leading-tight">Guru AI</h3>
                    <p class="text-[10px] uppercase font-bold tracking-widest text-gold-400">Asisten Pintar SIPANDA</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="clearGuruHistory()" class="text-slate-400 hover:text-white transition-colors" title="Hapus Riwayat Asisten">
                    <i class="fas fa-trash-alt text-xs"></i>
                </button>
                <button onclick="toggleGuruAi()" class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-rose-500 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Limit Info (Jika Tamu) -->
    <?php if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])): ?>
    <div id="guruLimitInfo" class="bg-amber-100 text-amber-800 text-[10px] font-bold text-center py-1.5 uppercase tracking-widest shrink-0 border-b border-amber-200">
        Mode Tamu: <span id="guruSisaLimit"><?= 5 - ($_SESSION['ai_chat_count'] ?? 0) ?></span> pesan tersisa
    </div>
    <?php endif; ?>

    <!-- Chat Messages Area -->
    <div id="guruAiMessages" class="flex-1 overflow-y-auto p-4 space-y-4 scroll-smooth bg-slate-50">
        <!-- Initial Greeting (Selalu muncul saat pertama load tanpa history) -->
        <div class="flex items-end gap-2 pr-8" id="guruInitialMsg">
            <div class="w-6 h-6 rounded-full bg-navy-900 flex items-center justify-center text-[10px] text-gold-400 shrink-0"><i class="fas fa-robot"></i></div>
            <div class="p-3.5 rounded-2xl guru-msg-bot text-sm whitespace-pre-line shadow-sm">
                Halo! Saya Guru AI 👋 Asisten cerdas Anda di lingkungan SIPANDA.
                
                Ada yang bisa saya bantu hari ini? Anda bisa mengeklik tombol cepat di bawah atau langsung mengetik pesan.
            </div>
        </div>
        
        <!-- Quick Actions Buttons -->
        <div class="grid gap-2 pr-8 mt-2" id="guruQuickActions">
            <button onclick="sendGuruMsgStr('Halo! Saya ingin bertanya tentang PPDB (Pendaftaran Siswa Baru). Bisa info prosedurnya dan admin TU yang bisa dihubungi?')" class="text-left bg-white hover:bg-gold-50 border border-slate-200 hover:border-gold-300 text-xs text-navy-800 font-semibold p-2.5 rounded-xl shadow-sm transition-all focus:outline-none">
                <i class="fas fa-user-graduate text-gold-500 mr-1.5"></i> Tanya Admin PPDB
            </button>
            <button onclick="sendGuruMsgStr('Saya butuh bantuan untuk PR / Materi Pelajaran (misalnya Matematika, IPA, dll).')" class="text-left bg-white hover:bg-gold-50 border border-slate-200 hover:border-gold-300 text-xs text-navy-800 font-semibold p-2.5 rounded-xl shadow-sm transition-all focus:outline-none">
                <i class="fas fa-book text-blue-500 mr-1.5"></i> Tanya PR / Pelajaran Akademik
            </button>
            <button onclick="sendGuruMsgStr('Berapa rincian biaya / SPP di sekolah ini? Siapa yang bisa saya hubungi tentang keuangan?')" class="text-left bg-white hover:bg-gold-50 border border-slate-200 hover:border-gold-300 text-xs text-navy-800 font-semibold p-2.5 rounded-xl shadow-sm transition-all focus:outline-none">
                <i class="fas fa-file-invoice-dollar text-emerald-500 mr-1.5"></i> Info Keuangan / Biaya
            </button>
        </div>
    </div>

    <!-- Input Area -->
    <div class="p-3 bg-white border-t border-slate-100 shrink-0">
        <form id="guruAiForm" class="flex items-end gap-2 relative">
            <textarea id="guruAiInput" rows="1" placeholder="Ketik pertanyaan Anda..." class="w-full bg-slate-100/80 border-transparent focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 text-sm rounded-2xl py-2.5 px-4 resize-none transition-all outline-none text-slate-700" oninput="this.style.height='auto';this.style.height=(this.scrollHeight < 120 ? this.scrollHeight : 120)+'px';" onkeydown="if(event.key === 'Enter' && !event.shiftKey){ event.preventDefault(); sendGuruMsg(); }"></textarea>
            
            <button type="submit" id="guruAiBtnSend" class="w-10 h-10 bg-blue-600 hover:bg-blue-500 text-white rounded-xl flex items-center justify-center shrink-0 shadow-md shadow-blue-600/20 transition-transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-paper-plane text-sm"></i>
            </button>
        </form>
    </div>
</div>

<script>
    const elWindow = document.getElementById('guruAiWindow');
    const elFab = document.getElementById('guruAiFab');
    const elIcon = document.getElementById('guruFabIcon');
    const elMessages = document.getElementById('guruAiMessages');
    const elInput = document.getElementById('guruAiInput');
    const elForm = document.getElementById('guruAiForm');
    const elBtnSend = document.getElementById('guruAiBtnSend');
    
    // State
    const guruUserId = '<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'tamu' ?>';
    const guruStorageKey = 'guru_ai_history_' + guruUserId;
    let isGuruOpen = false;
    let guruHistory = []; // {role: 'user'|'model', text: '...'}
    
    // Load history dari sessionStorage berdasarkan ID user
    function loadGuruHistory() {
        const h = sessionStorage.getItem(guruStorageKey);
        if (h) {
            try {
                guruHistory = JSON.parse(h);
                // Jika ada history, sembunyikan kotak salam & aksi cepat default
                if (guruHistory.length > 0) {
                    document.getElementById('guruInitialMsg').style.display = 'none';
                    document.getElementById('guruQuickActions').style.display = 'none';
                    
                    // Render ulang riwayat
                    guruHistory.forEach(msg => {
                        renderMsgHTML(msg.text, msg.role);
                    });
                    scrollToBottom();
                }
            } catch(e) { console.error('Gagal load history'); }
        }
    }
    
    function saveGuruHistory() {
        sessionStorage.setItem(guruStorageKey, JSON.stringify(guruHistory));
    }

    function toggleGuruAi() {
        isGuruOpen = !isGuruOpen;
        const elText = document.getElementById('guruFabText');
        if (isGuruOpen) {
            elWindow.classList.remove('hidden');
            elIcon.classList.remove('fa-robot');
            elIcon.classList.add('fa-times');
            if(elText) elText.innerText = 'Tutup';
            elInput.focus();
            if(guruHistory.length === 0) loadGuruHistory();
        } else {
            elWindow.classList.add('hidden');
            elIcon.classList.remove('fa-times');
            elIcon.classList.add('fa-robot');
            if(elText) elText.innerText = 'Chat Guru AI';
        }
    }

    function clearGuruHistory() {
        if(confirm('Hapus riwayat obrolan?')) {
            guruHistory = [];
            saveGuruHistory();
            elMessages.innerHTML = ''; // Hapus semua
            
            // Kembalikan tampilan awal
            const initialHtml = `
                <div class="flex items-end gap-2 pr-8" id="guruInitialMsg">
                    <div class="w-6 h-6 rounded-full bg-navy-900 flex items-center justify-center text-[10px] text-gold-400 shrink-0"><i class="fas fa-robot"></i></div>
                    <div class="p-3.5 rounded-2xl guru-msg-bot text-sm whitespace-pre-line shadow-sm">
                        Riwayat obrolan dihapus. Ada yang bisa saya bantu kembali?
                    </div>
                </div>
            `;
            elMessages.insertAdjacentHTML('beforeend', initialHtml);
        }
    }
    
    // Simple Markdown Parser untuk Bold dan List
    function parseMarkdown(text) {
        let html = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n\s*-\s(.*)/g, '<li>$1</li>')
            .replace(/\n\s*\*\s(.*)/g, '<li>$1</li>');
        
        // Bungkus baris <li> berurutan menjadi <ul>. Sedikit hacky tapi cukup untuk chatbot simple
        html = html.replace(/(<li>.*<\/li>)/s, '<ul class="pl-4 space-y-1 my-2">$1</ul>');
        
        // Handle newlines
        html = html.replace(/\n/g, '<br>');
        return `<div class="markdown-body font-medium leading-relaxed">${html}</div>`;
    }

    function renderMsgHTML(text, role) {
        const isUser = role === 'user';
        const justify = isUser ? 'justify-end' : 'justify-start';
        const pad = isUser ? 'pl-8' : 'pr-8';
        const msgStyle = isUser ? 'guru-msg-user' : 'guru-msg-bot';
        const parsedText = parseMarkdown(text);
        
        const botIcon = !isUser ? `<div class="w-6 h-6 rounded-full bg-navy-900 flex items-center justify-center text-[10px] text-gold-400 shrink-0 animate-pulse"><i class="fas fa-robot"></i></div>` : '';
        
        const html = `
            <div class="flex items-end gap-2 ${justify} ${pad} guru-animate-slide-up">
                ${botIcon}
                <div class="p-3.5 rounded-2xl ${msgStyle} text-sm shadow-sm inline-block max-w-[90%]">
                    ${parsedText}
                </div>
            </div>
        `;
        elMessages.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }
    
    function showTyping() {
        const html = `
            <div id="guruTyping" class="flex items-end gap-2 pr-12 guru-animate-slide-up">
                <div class="w-6 h-6 rounded-full bg-navy-900 flex items-center justify-center text-[10px] text-gold-400 shrink-0"><i class="fas fa-robot"></i></div>
                <div class="p-3.5 rounded-2xl guru-msg-bot flex gap-1 items-center">
                    <div class="w-2 h-2 bg-slate-400 rounded-full guru-typing-dot"></div>
                    <div class="w-2 h-2 bg-slate-400 rounded-full guru-typing-dot"></div>
                    <div class="w-2 h-2 bg-slate-400 rounded-full guru-typing-dot"></div>
                </div>
            </div>
        `;
        elMessages.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }
    
    function hideTyping() {
        const typing = document.getElementById('guruTyping');
        if(typing) typing.remove();
    }
    
    function scrollToBottom() {
        elMessages.scrollTop = elMessages.scrollHeight;
    }

    function sendGuruMsgStr(str) {
        elInput.value = str;
        sendGuruMsg();
        
        // Hide initial panels if exist
        if(document.getElementById('guruInitialMsg')) document.getElementById('guruInitialMsg').style.display = 'none';
        if(document.getElementById('guruQuickActions')) document.getElementById('guruQuickActions').style.display = 'none';
    }

    function sendGuruMsg() {
        const text = elInput.value.trim();
        if (!text) return;
        
        // Hide initial panels if exist
        if(document.getElementById('guruInitialMsg')) document.getElementById('guruInitialMsg').style.display = 'none';
        if(document.getElementById('guruQuickActions')) document.getElementById('guruQuickActions').style.display = 'none';

        // Render user msg
        renderMsgHTML(text, 'user');
        
        // Disable input
        elInput.value = '';
        elInput.style.height = 'auto'; // reset textarea height
        elInput.disabled = true;
        elBtnSend.disabled = true;
        
        showTyping();
        
        // Call API
        fetch('<?= BASE_URL ?>api/chat_guru_ai.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: text,
                history: guruHistory
            })
        })
        .then(res => res.json())
        .then(data => {
            hideTyping();
            if (data.status === 'success') {
                // Update history
                guruHistory.push({role: 'user', text: text});
                guruHistory.push({role: 'model', text: data.data});
                saveGuruHistory();
                
                renderMsgHTML(data.data, 'model');
                
                // Update sisa limit (if exists string)
                if (data.sisa_limit !== undefined && document.getElementById('guruSisaLimit')) {
                    document.getElementById('guruSisaLimit').innerText = data.sisa_limit;
                }
            } else if (data.status === 'limit') {
                renderMsgHTML("⚠️ " + data.message, 'model');
            } else {
                renderMsgHTML("❌ Error: " + data.message, 'model');
            }
        })
        .catch(err => {
            hideTyping();
            renderMsgHTML("Jaringan bermasalah. Server AI mungkin sibuk.", 'model');
        })
        .finally(() => {
            elInput.disabled = false;
            elBtnSend.disabled = false;
            elInput.focus();
        });
    }

    elForm.addEventListener('submit', (e) => {
        e.preventDefault();
        sendGuruMsg();
    });
    
    // Auto load history on mount but stay closed
    loadGuruHistory();
</script>
