@php $label = $label ?? 'Data'; @endphp
<a href="{{ route($templateRoute) }}" class="btn btn-sm btn-light-success me-2" title="Unduh template Excel">
    <i class="ki-outline ki-file-down fs-5"></i> Template Excel
</a>
<button type="button" class="btn btn-sm btn-success me-2" data-bs-toggle="modal" data-bs-target="#importExcelModal" title="Impor data dari Excel">
    <i class="ki-outline ki-file-up fs-5"></i> Import Excel
</button>

<div class="modal fade drawer-modal" id="importExcelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route($importRoute) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header"><h3 class="modal-title">Import {{ $label }} (Excel)</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body px-8 py-6">
                <div class="alert alert-light-primary fs-8 py-3 mb-5">
                    Unduh <b>Template Excel</b> dulu, isi pada lembar <b>"Data"</b> (lihat lembar "Contoh" &amp; "Petunjuk"), lalu unggah di sini.
                    Baris yang bermasalah akan dilaporkan lengkap dengan alasannya; data yang valid tetap masuk.
                </div>
                <label class="form-label required">Berkas Excel (.xlsx / .xls)</label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                <div class="form-text">Maksimal 8 MB.</div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success"><i class="ki-outline ki-file-up fs-5"></i> Import Sekarang</button></div>
        </form>
    </div></div>
</div>
