<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleSitePublication extends Model
{
    use HasFactory;

    protected $table = 'article_site_publications';

    protected $fillable = [
        'article_id',
        'wp_site_id',
        'wp_post_id',
        'wp_media_id',
        'published_url',
        'status',
        'response_message',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'wp_post_id' => 'integer',
            'wp_media_id' => 'integer',
            'synced_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function wpSite(): BelongsTo
    {
        return $this->belongsTo(WPSite::class);
    }
}
