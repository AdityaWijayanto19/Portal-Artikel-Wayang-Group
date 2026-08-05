<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleWPLog extends Model
{
    use HasFactory;

    protected $table = 'article_wp_logs';

    protected $fillable = [
        'article_id',
        'wp_site_id',
        'wp_post_id',
        'status',
        'response_message',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
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
