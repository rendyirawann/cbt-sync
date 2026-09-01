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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logUnguarded()        // model domain memakai $guarded=[] → catat semua atribut
            ->logOnlyDirty()        // hanya atribut yang berubah
            ->dontSubmitEmptyLogs()
            ->useLogName(strtolower(class_basename($this)));
    }
}
