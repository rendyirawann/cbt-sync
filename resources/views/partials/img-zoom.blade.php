{{--
    Lightbox gambar dengan kontrol zoom (perbesar / perkecil / normal).
    Dipakai bersama halaman ujian siswa & halaman guru agar perilakunya sama.

    Parameter:
      $sel         : selector CSS gambar yang bisa diperbesar
                     (default: apa pun di dalam .zoomable atau img.zoomable)
      $closeOnBlur : true → tutup otomatis saat jendela kehilangan fokus
                     (dipakai halaman ujian agar overlay kunci tetap terlihat)
      $z           : z-index overlay. Di halaman ujian WAJIB lebih kecil dari gerbang
                     layar penuh (3000) & kunci sesi (3500) supaya penguncian menang.

    Cara pakai:
      @include('partials.img-zoom', ['sel' => '.exam-q img, .rdev-thumb img', 'closeOnBlur' => true])

    Gambar boleh menyertakan data-full="URL besar" bila yang tampil hanya thumbnail.
--}}
@php
    $sel = $sel ?? '.zoomable img, img.zoomable';
    $closeOnBlur = $closeOnBlur ?? false;
    $z = $z ?? 3900;
@endphp

<style>
    #imgZoom{position:fixed;inset:0;z-index:{{ (int) $z }};background:rgba(6,10,22,.94);display:none;align-items:center;justify-content:center;padding:14px}
    #imgZoom.open{display:flex}
    #imgZoomStage{max-width:96vw;max-height:84vh;overflow:hidden;border-radius:12px;display:grid;place-items:center;touch-action:none}
    #imgZoom img{max-width:96vw;max-height:84vh;border-radius:12px;background:#fff;box-shadow:0 24px 70px rgba(0,0,0,.55);cursor:grab;transform-origin:center center;transition:transform .12s ease-out;will-change:transform;user-select:none;-webkit-user-drag:none}
    #imgZoom img.dragging{cursor:grabbing;transition:none}
    #imgZoomClose{position:absolute;top:12px;right:12px;width:52px;height:52px;border-radius:50%;border:none;background:#fff;color:#111827;font-size:30px;line-height:1;cursor:pointer;display:grid;place-items:center;box-shadow:0 8px 24px rgba(0,0,0,.45)}
    #imgZoomBar{position:absolute;bottom:56px;left:50%;transform:translateX(-50%);display:flex;align-items:center;gap:8px;background:rgba(17,24,39,.92);border:1px solid rgba(255,255,255,.18);padding:8px 10px;border-radius:999px;box-shadow:0 10px 30px rgba(0,0,0,.5);z-index:2}
    #imgZoomBar button{min-width:44px;height:44px;padding:0 12px;border:none;border-radius:999px;background:#fff;color:#111827;font-size:22px;font-weight:700;line-height:1;cursor:pointer}
    #imgZoomBar button#zoomReset{font-size:14px}
    #imgZoomBar button:active{transform:scale(.96)}
    #imgZoomBar button:disabled{opacity:.45;cursor:not-allowed}
    #zoomLevel{min-width:58px;text-align:center;color:#fff;font-weight:700;font-size:13px}
    #imgZoomHint{position:absolute;bottom:16px;left:0;right:0;text-align:center;color:rgba(255,255,255,.9);font-size:13px;font-weight:600;padding:0 16px}
    @media (max-width:575.98px){ #imgZoom img,#imgZoomStage{max-height:78vh} #imgZoomClose{width:46px;height:46px;font-size:26px} #imgZoomBar{bottom:48px} #imgZoomHint{display:none} }
</style>

<div id="imgZoom" role="dialog" aria-modal="true" aria-label="Gambar diperbesar">
    <button type="button" id="imgZoomClose" aria-label="Tutup gambar">&times;</button>
    <div id="imgZoomStage"><img id="imgZoomImg" src="" alt="Gambar diperbesar"></div>
    <div id="imgZoomBar" role="group" aria-label="Kontrol perbesaran">
        <button type="button" id="zoomOut" title="Perkecil" aria-label="Perkecil">&minus;</button>
        <span id="zoomLevel">100%</span>
        <button type="button" id="zoomIn" title="Perbesar" aria-label="Perbesar">&plus;</button>
        <button type="button" id="zoomReset" title="Kembali normal" aria-label="Kembali normal">Normal</button>
    </div>
    <div id="imgZoomHint">Ketuk area gelap atau &times; untuk menutup &bull; roda mouse / cubit untuk zoom &bull; geser untuk memindah</div>
</div>

<script>
(function(){
    var box = document.getElementById('imgZoom');
    var big = document.getElementById('imgZoomImg');
    if (!box || !big || box.dataset.ready === '1') return;
    box.dataset.ready = '1';

    var SEL = {!! json_encode($sel) !!};
    var MIN = 1, MAKS = 5, LANGKAH = 0.5;
    var skala = 1, geserX = 0, geserY = 0, dragged = false;
    var lvl = document.getElementById('zoomLevel');
    var btnIn = document.getElementById('zoomIn');
    var btnOut = document.getElementById('zoomOut');

    function terapkan(){
        if (skala <= MIN){ skala = MIN; geserX = 0; geserY = 0; }
        big.style.transform = 'translate(' + geserX + 'px,' + geserY + 'px) scale(' + skala + ')';
        if (lvl) lvl.textContent = Math.round(skala * 100) + '%';
        if (btnIn) btnIn.disabled = skala >= MAKS;
        if (btnOut) btnOut.disabled = skala <= MIN;
    }
    function setSkala(s){ skala = Math.min(MAKS, Math.max(MIN, s)); terapkan(); }
    function resetZoom(){ skala = 1; geserX = 0; geserY = 0; terapkan(); }
    function openZoom(src){ big.src = src; resetZoom(); box.classList.add('open'); }
    function closeZoom(){ box.classList.remove('open'); big.removeAttribute('src'); resetZoom(); }

    // Fase capture + preventDefault: gambar opsi PG berada di dalam <label>, dan foto
    // jawaban di halaman guru dibungkus <a>; keduanya tidak boleh ikut ter-aktivasi.
    document.addEventListener('click', function(e){
        var t = e.target;
        if (!t || t.tagName !== 'IMG' || !t.matches(SEL)) return;
        e.preventDefault(); e.stopPropagation();
        openZoom(t.dataset.full || t.currentSrc || t.src);
    }, true);

    if (btnIn) btnIn.addEventListener('click', function(e){ e.stopPropagation(); setSkala(skala + LANGKAH); });
    if (btnOut) btnOut.addEventListener('click', function(e){ e.stopPropagation(); setSkala(skala - LANGKAH); });
    var btnReset = document.getElementById('zoomReset');
    if (btnReset) btnReset.addEventListener('click', function(e){ e.stopPropagation(); resetZoom(); });
    var bar = document.getElementById('imgZoomBar');
    if (bar) bar.addEventListener('click', function(e){ e.stopPropagation(); });

    big.addEventListener('click', function(e){
        e.stopPropagation();
        if (dragged){ dragged = false; return; }
        setSkala(skala >= MAKS ? MIN : skala + 1);
    });

    box.addEventListener('wheel', function(e){
        if (!box.classList.contains('open')) return;
        e.preventDefault();
        setSkala(skala + (e.deltaY < 0 ? LANGKAH : -LANGKAH));
    }, { passive: false });

    // Geser saat diperbesar (mouse & satu jari)
    var seret = false, mulaiX = 0, mulaiY = 0, awalX = 0, awalY = 0;
    function titik(e){ return (e.touches && e.touches[0]) ? e.touches[0] : e; }
    function mulaiSeret(e){
        if (skala <= MIN) return;
        if (e.touches && e.touches.length > 1) return;
        seret = true; dragged = false;
        var p = titik(e); mulaiX = p.clientX; mulaiY = p.clientY; awalX = geserX; awalY = geserY;
        big.classList.add('dragging');
    }
    function jalanSeret(e){
        if (!seret) return;
        var p = titik(e), dx = p.clientX - mulaiX, dy = p.clientY - mulaiY;
        if (Math.abs(dx) > 3 || Math.abs(dy) > 3) dragged = true;
        geserX = awalX + dx; geserY = awalY + dy;
        big.style.transform = 'translate(' + geserX + 'px,' + geserY + 'px) scale(' + skala + ')';
        if (e.cancelable) e.preventDefault();
    }
    function selesaiSeret(){ seret = false; big.classList.remove('dragging'); }
    big.addEventListener('mousedown', mulaiSeret);
    window.addEventListener('mousemove', jalanSeret);
    window.addEventListener('mouseup', selesaiSeret);
    big.addEventListener('touchstart', mulaiSeret, { passive: true });
    big.addEventListener('touchmove', jalanSeret, { passive: false });
    big.addEventListener('touchend', selesaiSeret);

    // Cubit dua jari
    var jarakAwal = 0, skalaAwal = 1;
    big.addEventListener('touchstart', function(e){
        if (e.touches.length !== 2) return;
        var a = e.touches[0], b = e.touches[1];
        jarakAwal = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
        skalaAwal = skala;
    }, { passive: true });
    big.addEventListener('touchmove', function(e){
        if (e.touches.length !== 2 || !jarakAwal) return;
        var a = e.touches[0], b = e.touches[1];
        setSkala(skalaAwal * (Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY) / jarakAwal));
        if (e.cancelable) e.preventDefault();
    }, { passive: false });
    big.addEventListener('touchend', function(e){ if (e.touches.length < 2) jarakAwal = 0; });

    document.addEventListener('keydown', function(e){
        if (!box.classList.contains('open')) return;
        if (e.key === '+' || e.key === '=') setSkala(skala + LANGKAH);
        else if (e.key === '-' || e.key === '_') setSkala(skala - LANGKAH);
        else if (e.key === '0') resetZoom();
        else if (e.key === 'Escape') closeZoom();
    });

    box.addEventListener('click', closeZoom);
    document.getElementById('imgZoomClose').addEventListener('click', function(e){ e.stopPropagation(); closeZoom(); });
@if($closeOnBlur)
    // Halaman ujian: bila jendela kehilangan fokus, tutup agar overlay kunci jelas terlihat.
    window.addEventListener('blur', function(){ if (box.classList.contains('open')) closeZoom(); });
@endif
})();
</script>
