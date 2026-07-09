{{-- Editor rumus WYSIWYG untuk builder guru. Kolom .math-input di-"upgrade" jadi area
     contenteditable: teks biasa tetap teks, rumus tampil sebagai chip ter-render (KaTeX),
     dibangun lewat MathLive (ƒx). Saat submit di-serialize kembali ke $...$ (kompatibel data lama
     & tampilan siswa). Butuh partials.katex sudah dimuat lebih dulu. Self-hosted/offline. --}}
<script defer id="rdevMlScript" src="{{ asset('assets/plugins/mathlive/mathlive.min.js') }}"></script>
<style>
    .rdev-rte{min-height:calc(1.5em + 1.1rem + 2px);height:auto;white-space:pre-wrap;word-break:break-word;
        overflow-wrap:anywhere;cursor:text;line-height:1.7}
    .rdev-rte-multi{min-height:96px}
    .rdev-rte:empty:before{content:attr(data-ph);color:#a1a5b7;pointer-events:none}
    .rdev-rte:focus{border-color:#4F46E5;outline:0;box-shadow:0 0 0 .25rem rgba(79,70,229,.15)}
    .rdev-eq{display:inline-block;padding:1px 5px;margin:0 1px;background:rgba(79,70,229,.10);
        border:1px solid rgba(79,70,229,.25);border-radius:6px;cursor:pointer;user-select:none;vertical-align:middle}
    .rdev-eq:hover{background:rgba(79,70,229,.18)}
    .rdev-eq .katex{font-size:1.02em}
    button.math-eq-open{white-space:nowrap;margin-top:6px}
    button.math-eq-open .mfx{font-style:italic;font-weight:700}
    .rdev-mfe-inline{border:1px solid #4F46E5;border-radius:10px;padding:10px 12px;margin-top:8px;background:var(--bs-body-bg,#fff)}
    .rdev-mfe-inline .mfe-head{font-size:12px;color:#9aa0ac;margin-bottom:6px}
    .rdev-mfe-inline math-field{width:100%;min-height:56px;font-size:22px;border:1px solid var(--bs-border-color,#e4e6ef);
        border-radius:8px;padding:6px 10px;background:var(--bs-body-bg,#fff);display:block}
    .rdev-mfe-inline .mfe-actions{display:flex;justify-content:flex-end;gap:6px;margin-top:8px}
    .ML__keyboard{z-index:3600 !important}
</style>
<script>
(function(){
    var FONTS = "{{ asset('assets/plugins/mathlive/fonts') }}";
    var mlCfg = false, uid = 0, savedRange = null;

    function whenMl(cb){
        if (window.MathfieldElement) return cb();
        var s = document.getElementById('rdevMlScript');
        if (s) s.addEventListener('load', cb, {once:true});
        else { var t = setInterval(function(){ if (window.MathfieldElement){ clearInterval(t); cb(); } }, 50); }
    }
    function cfgMl(){ if (mlCfg) return; try { window.MathfieldElement.fontsDirectory = FONTS; window.MathfieldElement.soundsDirectory = null; } catch(e){} mlCfg = true; }

    function chipHTML(latex){ if (window.katex){ try { return katex.renderToString(latex, {throwOnError:false}); } catch(e){} } return latex; }
    function makeChip(latex){
        var s = document.createElement('span');
        s.className = 'rdev-eq'; s.setAttribute('contenteditable', 'false');
        s.dataset.latex = latex; s.innerHTML = chipHTML(latex);
        s.title = 'Klik untuk ubah rumus';
        return s;
    }

    // $...$ / teks  ->  isi contenteditable
    function loadInto(rte, value){
        rte.innerHTML = '';
        if (!value) return;
        String(value).split(/(\$[^$]*\$)/g).forEach(function(p){
            if (!p) return;
            if (p.length >= 2 && p.charAt(0) === '$' && p.charAt(p.length - 1) === '$'){
                rte.appendChild(makeChip(p.slice(1, -1)));
            } else {
                p.split('\n').forEach(function(ln, i){
                    if (i) rte.appendChild(document.createElement('br'));
                    if (ln) rte.appendChild(document.createTextNode(ln));
                });
            }
        });
    }
    // isi contenteditable -> teks dengan $...$
    function serialize(rte){
        var out = '';
        (function walk(node){
            node.childNodes.forEach(function(n){
                if (n.nodeType === 3) out += n.textContent;
                else if (n.nodeType === 1){
                    if (n.classList && n.classList.contains('rdev-eq')) out += '$' + (n.dataset.latex || '') + '$';
                    else if (n.tagName === 'BR') out += '\n';
                    else walk(n);
                }
            });
        })(rte);
        return out;
    }

    function saveCaret(rte){
        var sel = window.getSelection && window.getSelection();
        if (sel && sel.rangeCount){
            var r = sel.getRangeAt(0);
            if (rte.contains(r.commonAncestorContainer)){ savedRange = r.cloneRange(); return; }
        }
        savedRange = null;
    }

    function insertChip(rte, latex){
        rte.focus();
        var chip = makeChip(latex);
        var r = savedRange;
        if (r && rte.contains(r.commonAncestorContainer)){
            r.deleteContents(); r.insertNode(chip);
            var sp = document.createTextNode(' ');
            if (chip.parentNode) chip.parentNode.insertBefore(sp, chip.nextSibling);
            var nr = document.createRange(); nr.setStartAfter(sp); nr.collapse(true);
            var sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(nr);
        } else {
            rte.appendChild(chip); rte.appendChild(document.createTextNode(' '));
        }
        savedRange = null;
        rte.dispatchEvent(new Event('input', {bubbles:true}));
    }

    function openBuilder(rte, field, chip){
        whenMl(function(){
            cfgMl();
            var panel = rte.__builder;
            if (!panel){
                panel = document.createElement('div'); panel.className = 'rdev-mfe-inline';
                var head = document.createElement('div'); head.className = 'mfe-head';
                head.textContent = 'Tulis rumus (pangkat, akar, pecahan…), lalu klik Sisipkan.';
                panel.appendChild(head);
                var mf = document.createElement('math-field'); panel.appendChild(mf);
                var act = document.createElement('div'); act.className = 'mfe-actions';
                var cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'btn btn-sm btn-light'; cancel.textContent = 'Tutup';
                var ins = document.createElement('button'); ins.type = 'button'; ins.className = 'btn btn-sm btn-primary'; ins.innerHTML = '<i class="ki-outline ki-check fs-6"></i> Sisipkan';
                act.appendChild(cancel); act.appendChild(ins); panel.appendChild(act);
                (field.closest('.input-group') || rte.__fx || rte).insertAdjacentElement('afterend', panel);
                rte.__builder = panel; rte.__mf = mf;
                cancel.addEventListener('click', function(){ panel.style.display = 'none'; rte.__editChip = null; });
                ins.addEventListener('click', function(){ commitBuilder(rte); });
                mf.addEventListener('keydown', function(e){ if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)){ e.preventDefault(); commitBuilder(rte); } });
            }
            rte.__editChip = chip || null;
            rte.__mf.value = chip ? (chip.dataset.latex || '') : '';
            panel.style.display = 'block';
            setTimeout(function(){ try { rte.__mf.focus(); } catch(e){} }, 60);
        });
    }
    function commitBuilder(rte){
        var latex = (rte.__mf && rte.__mf.value ? rte.__mf.value : '').trim();
        if (rte.__builder) rte.__builder.style.display = 'none';
        if (!latex){ rte.__editChip = null; return; }
        if (rte.__editChip){ rte.__editChip.replaceWith(makeChip(latex)); rte.__editChip = null; rte.dispatchEvent(new Event('input', {bubbles:true})); }
        else insertChip(rte, latex);
    }

    function upgrade(field){
        if (field.dataset.rteDone) return; field.dataset.rteDone = '1';
        var multi = field.tagName === 'TEXTAREA';
        var rte = document.createElement('div');
        rte.className = 'form-control rdev-rte' + (multi ? ' rdev-rte-multi' : '');
        rte.setAttribute('contenteditable', 'true');
        rte.setAttribute('data-ph', field.getAttribute('placeholder') || 'Tulis di sini…');
        loadInto(rte, field.value || '');
        field.style.display = 'none';
        field.removeAttribute('required');   // field kini tersembunyi → validasi di server
        field.insertAdjacentElement('beforebegin', rte);
        if (field.dataset.preview){ var pv = document.querySelector(field.dataset.preview); if (pv) pv.style.display = 'none'; }

        function sync(){ var v = serialize(rte); if (!multi) v = v.replace(/\n+/g, ' '); field.value = v.trim(); field.dispatchEvent(new Event('input', {bubbles:true})); }
        rte.addEventListener('input', sync);
        rte.addEventListener('blur', sync);
        if (!multi) rte.addEventListener('keydown', function(e){ if (e.key === 'Enter') e.preventDefault(); });
        rte.addEventListener('click', function(e){ var c = e.target.closest && e.target.closest('.rdev-eq'); if (c){ saveCaret(rte); openBuilder(rte, field, c); } });

        var b = document.createElement('button');
        b.type = 'button'; b.className = 'btn btn-sm btn-light-primary math-eq-open'; b.title = 'Tulis rumus (editor visual)';
        b.innerHTML = '<span class="mfx">ƒx</span> Rumus';
        (field.closest('.input-group') || rte).insertAdjacentElement('afterend', b);
        rte.__fx = b;
        b.addEventListener('mousedown', function(){ saveCaret(rte); });
        b.addEventListener('click', function(e){ e.preventDefault(); openBuilder(rte, field, null); });
    }
    function upgradeAll(root){ (root || document).querySelectorAll('.math-input:not([data-rte-done])').forEach(upgrade); }

    function boot(){
        upgradeAll(document);
        try {
            new MutationObserver(function(muts){
                muts.forEach(function(m){ m.addedNodes && m.addedNodes.forEach(function(n){
                    if (n.nodeType === 1){
                        if (n.matches && n.matches('.math-input')) upgrade(n);
                        if (n.querySelectorAll) upgradeAll(n);
                    }
                }); });
            }).observe(document.body, {childList:true, subtree:true});
        } catch(e){}
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
</script>
