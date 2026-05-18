<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Cog\Contracts\Ban\Bannable as BannableContract;
use Cog\Laravel\Ban\Traits\Bannable;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // 1. Ini penawar errornya

class User extends Authenticatable implements BannableContract
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    // 2. Masukkan HasUuids ke dalam use
    use HasFactory, Notifiable, HasRoles, Bannable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'no_wa',
        'avatar',
        'last_ip',
        'last_login',
        'banned_at',
        'nik',
        'phone',
        'is_active',
        'password',
        'social_id',
        'social_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    /**
     * Get avatar URL with fallback to UI-Avatars.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && file_exists(storage_path('app/public/' . $this->avatar))) {
            return asset('storage/' . $this->avatar);
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=6a11cb&color=fff&size=128&bold=true';
    }

    protected $appends = ['avatar_url'];
}
