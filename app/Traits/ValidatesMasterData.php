<?php

namespace App\Traits;

/**
 * Pesan validasi Bahasa Indonesia bersama untuk form master data,
 * agar kesalahan input tampil jelas (bukan 500) dalam bahasa yang mudah dipahami.
 */
trait ValidatesMasterData
{
    protected function idMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min.',
            'numeric' => ':attribute harus berupa angka.',
            'integer' => ':attribute harus berupa angka bulat.',
            'unique' => ':attribute sudah digunakan, silakan pakai yang lain.',
            'exists' => ':attribute yang dipilih tidak valid.',
            'uuid' => ':attribute tidak valid.',
            'in' => 'Pilihan :attribute tidak valid.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'after' => ':attribute harus setelah :date.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat: :values.',
        ];
    }
}
