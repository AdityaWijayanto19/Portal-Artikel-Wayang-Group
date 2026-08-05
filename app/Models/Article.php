<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Article extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    protected $fillable = [
        'company_id',
        'user_id',
        'wp_site_id',
        'category_id',
        'sub_category_id',
        'title',
        'slug',
        'content',
        'featured_image_path',
        'image_alt_text',
        'seo_score',
        'yoast_title',
        'yoast_metadesc',
        'yoast_focuskw',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'seo_score' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wpSite(): BelongsTo
    {
        return $this->belongsTo(WPSite::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ArticleWPLog::class);
    }

    /**
     * Metadata SEO terisolasi (one-to-one). Menggantikan kolom yoast_* & seo_score
     * pada tabel articles sebagai sumber kebenaran baru.
     */
    public function seoMeta(): HasOne
    {
        return $this->hasOne(ArticleSeoMeta::class);
    }

    /**
     * Relasi many-to-many kategori (menggantikan category_id tunggal).
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_category')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag')->withTimestamps();
    }

    /**
     * State publikasi per situs WordPress target (satu baris per article+site).
     */
    public function sitePublications(): HasMany
    {
        return $this->hasMany(ArticleSitePublication::class);
    }

    /**
     * Situs WordPress target lewat pivot publikasi (menggantikan wp_site_id tunggal).
     */
    public function wpSites(): BelongsToMany
    {
        return $this->belongsToMany(WPSite::class, 'article_site_publications', 'article_id', 'wp_site_id')
            ->withPivot(['wp_post_id', 'wp_media_id', 'status', 'synced_at'])
            ->withTimestamps();
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
