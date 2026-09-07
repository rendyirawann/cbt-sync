<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class Wave extends Model
{
    use HasUuids, LogsAllActivity;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    /**
     * "07.30-09.40" untuk kolom PUKUL di Daftar Hadir. Kosong bila jamnya
     * belum diisi, supaya lembar cetaknya menyisakan ruang tulis tangan.
     */
    public function getRentangJamAttribute(): string
    {
        if (blank($this->start_time) || blank($this->end_time)) {
            return '';
        }

        $jam = fn ($t) => \Carbon\Carbon::parse($t)->format('H.i');

        return $jam($this->start_time) . '-' . $jam($this->end_time);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    /** Gelombang aktif, urut sesuai sort_order lalu nama. */
    public function scopeTerurut($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
