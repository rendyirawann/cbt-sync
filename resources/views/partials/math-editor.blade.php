{{-- Editor rumus VISUAL (MathLive) — INLINE di dalam form (bukan popup). Self-hosted/offline. --}}
{{-- Tiap kolom .math-input dapat tombol "ƒx Rumus"; klik → panel editor muncul tepat di bawahnya. --}}
<script defer id="rdevMlScript" src="{{ asset('assets/plugins/mathlive/mathlive.min.js') }}"></script>
<style>
    button.math-eq-open{white-space:nowrap}
    button.math-eq-open .mfx{font-style:italic;font-weight:700}
    textarea.math-input + button.math-eq-open{margin-top:6px}
    .rdev-mfe-inline{border:1px solid #4F46E5;border-radius:10px;padding:10px 12px;margin-top:8px;background:var(--bs-body-bg,#fff)}
    .rdev-mfe-inline .mfe-head{font-size:12px;color:#9aa0ac;margin-bottom:6px}
    .rdev-mfe-inline math-field{width:100%;min-height:56px;font-size:22px;border:1px solid var(--bs-border-color,#e4e6ef);
        border-radius:8px;padding:6px 10px;background:var(--bs-body-bg,#fff);display:block}
    .rdev-mfe-inline .mfe-actions{display:flex;justify-content:flex-end;gap:6px;margin-top:8px}
    /* Keyboard simbol MathLive harus di atas modal */
    .ML__keyboard{z-index:3600 !important}
</style>
<script>
(function(){
    var FONTS = "{{ asset('assets/plugins/mathlive/fonts') }}";
    var configured = false, uid = 0;

    function whenReady(cb){
        if (window.MathfieldElement) return cb();
        var s = document.getElementById('rdevMlScript');
        if (s) s.addEventListener('load', cb, {once:true});
        else { var t = setInterval(function(){ if (window.MathfieldElement){ clearInterval(t); cb(); } }, 50); }
    }
    function configure(){
        if (configured) return;
        try { window.MathfieldElement.fontsDirectory = FONTS; window.MathfieldElement.soundsDirectory = null; } catch(e){}
        configured = true;
    }

    function insertInto(field, mf){
        var latex = (mf.value || '').trim();
        if (!latex) return;
        var wrapped = '$' + latex + '$';
        var s = field.__mfeSelS || 0, e = field.__mfeSelE || 0, v = field.value || '';
        field.value = v.slice(0, s) + wrapped + v.slice(e);
        var pos = s + wrapped.length;
        try { field.focus(); field.selectionStart = field.selectionEnd = pos; } catch(err){}
        field.dispatchEvent(new Event('input', {bubbles:true}));   // pemicu pratinjau KaTeX
    }

    function toggleEditor(field, btn){
        var panel = field.__mfePanel;
        if (panel && panel.style.display !== 'none'){ panel.style.display = 'none'; return; }

        whenReady(function(){
            configure();
            if (!panel){
                panel = document.createElement('div');
                panel.className = 'rdev-mfe-inline';
                var head = document.createElement('div');
                head.className = 'mfe-head';
                head.textContent = 'Tulis rumus (pangkat, akar, pecahan…). Klik kolom untuk keyboard simbol, lalu Sisipkan.';
                panel.appendChild(head);

                var mf = document.createElement('math-field');
                panel.appendChild(mf);

                var actions = document.createElement('div'); actions.className = 'mfe-actions';
                var cancel = document.createElement('button');
                cancel.type = 'button'; cancel.className = 'btn btn-sm btn-light'; cancel.textContent = 'Tutup';
                var ins = document.createElement('button');
                ins.type = 'button'; ins.className = 'btn btn-sm btn-primary'; ins.innerHTML = '<i class="ki-outline ki-check fs-6"></i> Sisipkan';
                actions.appendChild(cancel); actions.appendChild(ins);
                panel.appendChild(actions);

                // Sisipkan panel: di bawah baris input-group (opsi) atau tepat setelah tombol ƒx (soal/essay).
                var anchor = field.closest('.input-group') || btn;
                anchor.insertAdjacentElement('afterend', panel);

                field.__mfePanel = panel;
                field.__mfeField = mf;

                cancel.addEventListener('click', function(){ panel.style.display = 'none'; });
                ins.addEventListener('click', function(){ insertInto(field, mf); panel.style.display = 'none'; });
                mf.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)){ e.preventDefault(); insertInto(field, mf); panel.style.display = 'none'; }
                });
            }

            panel.style.display = 'block';
            // Prefill dari teks yang sedang diseleksi pada field.
            var s = field.selectionStart || 0, e = field.selectionEnd || 0;
            field.__mfeSelS = s; field.__mfeSelE = e;
            var sel = (field.value || '').slice(s, e).trim().replace(/^\$+/, '').replace(/\$+$/, '');
            var mfEl = field.__mfeField;
            mfEl.value = sel || '';
            setTimeout(function(){ try { mfEl.focus(); } catch(err){} }, 60);
        });
    }

    function decorate(field){
        if (field.dataset.mfDecorated) return;
        field.dataset.mfDecorated = '1';
        if (!field.id) field.id = 'mfin_' + (++uid);
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'btn btn-sm btn-light-primary math-eq-open';
        b.title = 'Tulis rumus (editor visual)';
        b.innerHTML = '<span class="mfx">ƒx</span> Rumus';
        b.addEventListener('click', function(){ toggleEditor(field, b); });
        field.insertAdjacentElement('afterend', b);
    }
    function decorateAll(root){ (root || document).querySelectorAll('.math-input:not([data-mf-decorated])').forEach(decorate); }

    function boot(){
        decorateAll(document);
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
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
</script>
