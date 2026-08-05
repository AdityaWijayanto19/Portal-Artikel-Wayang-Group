<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'is_active',
    ];

    // Satu perusahaan punya banyak user
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function wpSites(): HasMany
    {
        return $this->hasMany(WpSite::class);
    }
}
