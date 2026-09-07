{{-- Satu kartu login peserta.
     Parameter: $s (Student, relasi user & wave dimuat), $sandi (password kartu,
     boleh null), $penyelenggara, $labelTahun, $logo (path/URL gambar). --}}
<table class="kartu">
    <tr>
        <td class="kop">
            <table class="kop-isi">
                <tr>
                    <td class="logo"><img src="{{ $logo }}" alt=""></td>
                    <td class="judul">
                        <div class="j1">KARTU LOGIN</div>
                        <div class="j2">{{ $penyelenggara }}</div>
                        <div class="j3">TAHUN {{ $labelTahun }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="isi">
            <table class="baris">
                <tr>
                    <td class="l">Nama Peserta</td><td class="s">:</td>
                    <td class="v">{{ $s->user->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="l">NISN</td><td class="s">:</td>
                    <td class="v">{{ $s->nisn ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="l">Tempat, Tanggal Lahir</td><td class="s">:</td>
                    <td class="v">
                        {{ $s->birth_place ?: '-' }}{{ $s->birth_date ? ', ' . $s->birth_date->translatedFormat('d F Y') : '' }}
                    </td>
                </tr>
                <tr>
                    <td class="l">Username</td><td class="s">:</td>
                    <td class="v tebal">{{ $s->user->username ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="l">Password</td><td class="s">:</td>
                    <td class="v tebal">{{ $sandi ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="l">Link Ujian</td><td class="s">:</td>
                    <td class="v">
                        @if($s->proctor_id || $s->room)
                            {{ $s->proctor_id ?: '-' }} / {{ $s->room ?: '-' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="l">Gelombang</td><td class="s">:</td>
                    <td class="v">{{ $s->wave->name ?? '-' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
