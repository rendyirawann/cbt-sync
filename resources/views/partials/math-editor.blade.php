{{-- Editor rumus VISUAL (WYSIWYG, seperti Word) berbasis MathLive, self-hosted/offline. --}}
{{-- Setiap kolom .math-input otomatis dapat tombol "ƒx" untuk membuka editor; hasilnya --}}
{{-- disisipkan sebagai $LaTeX$ lalu dirender oleh KaTeX (partials.katex). --}}
<script defer id="rdevMlScript" src="{{ asset('assets/plugins/mathlive/mathlive.min.js') }}"></script>
<style>
    .math-eq-open{--c:#4F46E5}
    button.math-eq-open{white-space:nowrap}
    button.math-eq-open .mfx{font-style:italic;font-weight:700}
    textarea.math-input + button.math-eq-open{margin-top:6px}
    /* Overlay editor */
    .rdev-mfe{position:fixed;inset:0;z-index:3100;display:none;align-items:center;justify-content:center;background:rgba(15,23,42,.55);padding:16px}
    .rdev-mfe.show{display:flex}
    .rdev-mfe-card{background:var(--bs-body-bg,#fff);color:var(--bs-body-color,#181c32);border-radius:14px;
        box-shadow:0 24px 70px rgba(0,0,0,.35);width:min(600px,96vw);padding:20px}
    .rdev-mfe-card h4{font-size:16px;font-weight:700;margin:0 0 2px}
    .rdev-mfe-card .sub{font-size:12px;color:#9aa0ac;margin:0 0 14px}
    #rdevMfHost math-field{width:100%;min-height:70px;font-size:24px;
        border:1px solid var(--bs-border-color,#e4e6ef);border-radius:10px;padding:10px 12px;background:var(--bs-body-bg,#fff)}
    .rdev-mfe-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}
    /* Keyboard virtual MathLive harus di atas overlay & modal */
    .ML__keyboard{z-index:3300 !important}
</style>

<div id="rdevMathEditor" class="rdev-mfe" aria-hidden="true">
    <div class="rdev-mfe-card">
        <h4>Editor Rumus</h4>
        <p class="sub">Tulis rumus seperti di Word — pangkat, akar, pecahan, dll. Gunakan tombol keyboard <b>⌨</b> di kanan untuk simbol. Selesai, klik <b>Sisipkan</b>.</p>
        <div id="rdevMfHost"></div>
        <div class="rdev-mfe-actions">
            <button type="button" id="rdevMfCancel" class="btn btn-light">Batal</button>
            <button type="button" id="rdevMfInsert" class="btn btn-primary"><i class="ki-outline ki-check fs-5"></i> Sisipkan</button>
        </div>
    </div>
</div>

<script>
(function(){
    var overlay, host, mf = null, configured = false, target = null, selS = 0, selE = 0;
    var FONTS = "{{ asset('assets/plugins/mathlive/fonts') }}";

    function whenReady(cb){
        if (window.MathfieldElement) return cb();
        var s = document.getElementById('rdevMlScript');
        if (s) s.addEventListener('load', cb, {once:true});
        else { var t = setInterval(function(){ if (window.MathfieldElement){ clearInterval(t); cb(); } }, 50); }
    }

    function ensureField(cb){
        whenReady(function(){
            if (!configured){
                try { window.MathfieldElement.fontsDirectory = FONTS; window.MathfieldElement.soundsDirectory = null; } catch(e){}
                configured = true;
            }
            if (!mf){
                mf = document.createElement('math-field');
                mf.style.display = 'block';
                host.appendChild(mf);
                mf.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)){ e.preventDefault(); doInsert(); }
                });
            }
            cb();
        });
    }

    // Tentukan kolom .math-input yang jadi sasaran tombol ƒx (deterministik).
    function resolveTarget(btn){
        // 1) tombol hasil "dekorasi": punya referensi id langsung
        if (btn.dataset.mfFor){ var byId = document.getElementById(btn.dataset.mfFor); if (byId) return byId; }
        // 2) .math-input di dalam input-group yang sama (opsi PG)
        var grp = btn.closest('.input-group');
        if (grp){ var f = grp.querySelector('.math-input'); if (f) return f; }
        // 3) kolom utama di scope (soal/essay = yang punya data-preview)
        var scope = btn.closest('.rdev-math-scope');
        if (scope){ return scope.querySelector('.math-input[data-preview]') || scope.querySelector('.math-input'); }
        return null;
    }

    function openFor(btn){
        target = resolveTarget(btn);
        if (!target) return;
        selS = target.selectionStart || 0;
        selE = target.selectionEnd || 0;
        var sel = (target.value || '').slice(selS, selE).trim().replace(/^\$+/, '').replace(/\$+$/, '');
        ensureField(function(){
            mf.value = sel || '';
            overlay.classList.add('show');
            try { mf.focus(); } catch(e){}
            setTimeout(function(){ try { mf.focus(); } catch(e){} }, 80);
        });
    }
    function close(){ overlay.classList.remove('show'); }

    function doInsert(){
        var latex = (mf && mf.value ? mf.value : '').trim();
        close();
        if (!target || !latex) return;
        var wrapped = '$' + latex + '$';
        var v = target.value || '';
        target.value = v.slice(0, selS) + wrapped + v.slice(selE);
        var pos = selS + wrapped.length;
        try { target.focus(); target.selectionStart = target.selectionEnd = pos; } catch(e){}
        target.dispatchEvent(new Event('input', {bubbles:true}));  // pemicu pratinjau KaTeX
    }

    // Tambahkan tombol ƒx di samping setiap .math-input (sekali per kolom).
    var uid = 0;
    function decorate(field){
        if (field.dataset.mfDecorated) return;
        field.dataset.mfDecorated = '1';
        if (!field.id) field.id = 'mfin_' + (++uid);
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm btn-light-primary math-eq-open';
        b.dataset.mfFor = field.id;
        b.title = 'Sisipkan rumus (editor visual)';
        b.innerHTML = '<span class="mfx">ƒx</span> Rumus';
        field.insertAdjacentElement('afterend', b);
    }
    function decorateAll(root){ (root || document).querySelectorAll('.math-input:not([data-mf-decorated])').forEach(decorate); }

    function boot(){
        overlay = document.getElementById('rdevMathEditor');
        host = document.getElementById('rdevMfHost');
        if (!overlay || !host) return;
        // Pindahkan overlay ke <body> agar lepas dari focus-trap & transform milik modal.
        if (overlay.parentElement !== document.body) document.body.appendChild(overlay);

        decorateAll(document);
        // Kolom opsi PG bisa ditambah dinamis → pantau & dekorasi otomatis.
        try {
            new MutationObserver(function(muts){
                muts.forEach(function(m){ m.addedNodes && m.addedNodes.forEach(function(n){
                    if (n.nodeType === 1){
                        if (n.matches && n.matches('.math-input')) decorate(n);
                        if (n.querySelectorAll) decorateAll(n);
                    }
                }); });
            }).observe(document.body, {childList:true, subtree:true});
        } catch(e){}

        document.addEventListener('click', function(e){
            var openBtn = e.target.closest && e.target.closest('.math-eq-open');
            if (openBtn){ e.preventDefault(); openFor(openBtn); return; }
            if (e.target.closest && e.target.closest('#rdevMfInsert')){ doInsert(); return; }
            if (e.target.closest && e.target.closest('#rdevMfCancel')){ close(); return; }
            if (e.target === overlay){ close(); }  // klik backdrop
        });
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && overlay.classList.contains('show')) close(); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
</script>
