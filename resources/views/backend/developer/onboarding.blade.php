@extends('backend.layout.app')
@section('title', 'Onboarding Sekolah')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl">
        <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Onboarding Sekolah</h1>
        <span class="text-muted fs-7">Buat sekolah baru + akun admin sekolahnya (Superadmin per-sekolah). Tiap sekolah = satu lisensi.</span>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="row g-6">
            {{-- Form onboarding --}}
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h3 class="card-title fw-bold">Sekolah Baru</h3></div>
                    <form action="{{ route('onboarding.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="fw-bold text-gray-700 text-uppercase fs-8 mb-3">Data Sekolah</div>
                            <div class="mb-4"><label class="form-label required">Nama Sekolah</label><input type="text" name="school_name" class="form-control" value="{{ old('school_name') }}" required></div>
                            <div class="mb-4"><label class="form-label">Alamat</label><input type="text" name="school_address" class="form-control" value="{{ old('school_address') }}"></div>
                            <div class="row">
                                <div class="col-6 mb-4"><label class="form-label">Telepon</label><input type="text" name="school_phone" class="form-control" value="{{ old('school_phone') }}"></div>
                                <div class="col-6 mb-4"><label class="form-label">Email Sekolah</label><input type="email" name="school_email" class="form-control" value="{{ old('school_email') }}"></div>
                            </div>

                            <div class="separator my-5"></div>
                            <div class="fw-bold text-gray-700 text-uppercase fs-8 mb-3">Akun Admin Sekolah (Superadmin)</div>
                            <div class="mb-4"><label class="form-label required">Nama Admin</label><input type="text" name="admin_name" class="form-control" value="{{ old('admin_name') }}" required></div>
                            <div class="mb-4"><label class="form-label required">Email Admin (untuk login)</label><input type="email" name="admin_email" class="form-control" value="{{ old('admin_email') }}" required></div>
                            <div class="mb-2"><label class="form-label required">Password Admin</label><input type="text" name="admin_password" class="form-control" value="{{ old('admin_password') }}" minlength="6" required><div class="form-text">Min. 6 karakter. Berikan ke pihak sekolah untuk login pertama.</div></div>
                        </div>
                        <div class="card-footer"><button type="submit" class="btn btn-primary w-100"><i class="ki-outline ki-plus fs-4"></i> Buat Sekolah & Admin</button></div>
                    </form>
                </div>
            </div>

            {{-- Daftar sekolah --}}
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><h3 class="card-title fw-bold">Sekolah Terdaftar ({{ $schools->count() }})</h3></div>
                    <div class="card-body pt-2">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <thead><tr class="text-gray-400 fw-bold fs-7 text-uppercase"><th>Sekolah</th><th>Email</th><th class="text-center">Jml Akun</th></tr></thead>
                            <tbody>
                                @forelse($schools as $s)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $s->name }}<div class="text-muted fs-8">{{ $s->address }}</div></td>
                                    <td class="text-gray-700">{{ $s->email ?? '-' }}</td>
                                    <td class="text-center"><span class="badge badge-light-primary">{{ $s->users_count }}</span></td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center py-8 text-muted">Belum ada sekolah.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
