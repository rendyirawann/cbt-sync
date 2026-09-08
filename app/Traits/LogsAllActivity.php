<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Pencatatan aktivitas otomatis (buat/ubah/hapus) untuk model domain,
 * agar "keseluruhan proses" ikut terekam di Log Activity. Causer diambil
 * otomatis dari user login oleh spatie/activitylog.
 */
trait LogsAllActivity
{
    use LogsActivity;

    /**
     * Atribut yang TIDAK boleh masuk log, walau ikut berubah.
     *
     * logUnguarded() mencatat seluruh atribut model, dan model domain di sini
     * memakai $guarded = []. Tanpa daftar ini, mengubah sandi seorang user akan
     * menuliskan hash sandinya ke tabel log — dan log itu dibaca oleh Admin.
     * exam_password bahkan tersimpan terenkripsi dan bisa dibaca ulang; hasil
     * dekripsinya adalah sandi kartu ujian siswa yang masih dipakai untuk login.
     */
    protected array $rahasiaLog = [
        'password',
        'remember_token',
        'exam_password',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()        // model domain memakai $guarded=[] → catat semua atribut
            ->logExcept($this->rahasiaLog)
            ->logOnlyDirty()        // hanya atribut yang berubah
            ->dontSubmitEmptyLogs()
            ->useLogName(strtolower(class_basename($this)));
    }
}
