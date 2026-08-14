<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WPSite extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $table = 'wp_sites';

    protected $guarded = ['id'];

    protected $fillable = [
        'company_id',
        'site_name',
        'site_url',
        'wp_username',
        'wp_app_password',
        'flag_counter_url',
    ];

    protected function casts(): array
    {
        return [
            'wp_app_password' => 'encrypted',
        ];
    }

    /**
     * Application password tanpa spasi — WordPress Basic Auth mewajibkan format ini.
     * App password WP ditampilkan bergrup 4 karakter ("xxxx xxxx ...") tetapi saat
     * dipakai sebagai HTTP Basic Auth seluruh spasi HARUS dibuang, jika tidak
     * WordPress membalas 401 rest_cannot_create / rest_not_logged_in.
     */
    public function appPassword(): string
    {
        return preg_replace('/\s+/', '', (string) $this->wp_app_password) ?? '';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'wp_site_category', 'wp_site_id', 'category_id')->withTimestamps();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ArticleWPLog::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeHasFlagCounter(Builder $query): Builder
    {
        return $query->whereNotNull('flag_counter_url')
            ->where('flag_counter_url', '!=', '');
    }
}
