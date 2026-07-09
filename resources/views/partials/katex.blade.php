{{-- KaTeX (self-hosted, jalan tanpa internet) + helper render, toolbar sisip, live preview --}}
<link rel="stylesheet" href="{{ asset('assets/plugins/katex/katex.min.css') }}">
<script defer src="{{ asset('assets/plugins/katex/katex.min.js') }}"></script>
<script defer src="{{ asset('assets/plugins/katex/contrib/auto-render.min.js') }}"></script>
<style>
    .math-toolbar{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px}
    .math-btn{border:1px solid var(--bs-border-color,#e4e6ef);background:var(--bs-body-bg,#fff);border-radius:8px;
        padding:4px 9px;font-size:13px;cursor:pointer;line-height:1.2;color:inherit;transition:.12s}
    .math-btn:hover{background:rgba(79,70,229,.1);border-color:#4F46E5;color:#4F46E5}
    .math-preview{border:1px dashed var(--bs-border-color,#e4e6ef);border-radius:8px;padding:8px 10px;
        min-height:38px;font-size:15px;margin-top:6px;overflow-x:auto}
    .math-preview:empty::before{content:"Pratinjau rumus tampil di sini…";color:#9aa0ac;font-size:12px}
    .math-hint{font-size:11px;color:#9aa0ac;margin-top:4px}
</style>
<script>
(function(){
    var RD = {};

    RD.render = function(el){
        if (!window.renderMathInElement) return;
        try {
            renderMathInElement(el || document.body, {
                delimiters: [
                    {left:'$$', right:'$$', display:true},
                    {left:'\\[', right:'\\]', display:true},
                    {left:'$',  right:'$',  display:false},
                    {left:'\\(', right:'\\)', display:false}
                ],
                throwOnError: false,
                ignoredTags: ['script','noscript','style','textarea','pre','code','input','option']
            });
        } catch(e){}
    };
    window.rdevRenderMath = RD.render;

    function insertAtCaret(el, text){
        el.focus();
        var s = el.selectionStart, e = el.selectionEnd;
        if (s === null || s === undefined){ el.value += text; return; }
        el.value = el.value.slice(0, s) + text + el.value.slice(e);
        var pos = s + text.length;
        try { el.selectionStart = el.selectionEnd = pos; } catch(err){}
    }

    // Lacak input matematika terakhir yang difokus.
    var active = null;
    document.addEventListener('focusin', function(e){
        if (e.target && e.target.matches && e.target.matches('.math-input')) active = e.target;
    });

    // Klik tombol toolbar -> sisipkan LaTeX ke input aktif (atau input dalam toolbar yg sama).
    document.addEventListener('click', function(e){
        var b = e.target.closest && e.target.closest('.math-btn');
        if (!b) return;
        e.preventDefault();
        var snip = b.getAttribute('data-latex') || '';
        var el = active;
        // fallback: math-input terdekat dalam wadah yang sama
        if (!el || !document.contains(el)){
            var scope = b.closest('.rdev-math-scope') || document;
            el = scope.querySelector('.math-input');
        }
        if (!el) return;
        insertAtCaret(el, snip);
        el.dispatchEvent(new Event('input', {bubbles:true}));
    });

    // Live preview untuk .math-input yang punya data-preview="#id".
    document.addEventListener('input', function(e){
        if (!e.target.matches || !e.target.matches('.math-input')) return;
        var sel = e.target.getAttribute('data-preview');
        if (!sel) return;
        var box = document.querySelector(sel);
        if (!box) return;
        box.textContent = e.target.value || '';
        RD.render(box);
    });

    function boot(){
        RD.render(document.body);
        // render preview awal (mode edit yang sudah ada isinya)
        document.querySelectorAll('.math-input[data-preview]').forEach(function(el){
            var box = document.querySelector(el.getAttribute('data-preview'));
            if (box && el.value){ box.textContent = el.value; RD.render(box); }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
    else boot();
})();
</script>
