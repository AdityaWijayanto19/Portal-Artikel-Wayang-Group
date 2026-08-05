<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi ke Perusahaan
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_user');
    }

    // Helper sederhana untuk keterbacaan
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isAuthor(): bool
    {
        return $this->hasRole('author');
    }

    /**
     * Kumpulan ID holding tempat user tergabung (union kolom company_id & pivot company_user).
     * Dipakai oleh Form Request untuk membatasi pilihan tenant.
     *
     * @return array<int, int>
     */
    public function companyIds(): array
    {
        $ids = $this->companies()->pluck('companies.id')->all();

        if ($this->company_id) {
            $ids[] = $this->company_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Holding utama user (kolom company_id, jatuh ke pivot pertama bila kosong).
     */
    public function primaryCompanyId(): ?int
    {
        return $this->company_id ?? ($this->companyIds()[0] ?? null);
    }
}
