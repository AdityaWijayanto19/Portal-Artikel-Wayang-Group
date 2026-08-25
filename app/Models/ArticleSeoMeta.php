<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleSeoMeta extends Model
{
    use HasFactory;

    protected $table = 'article_seo_meta';

    protected $fillable = [
        'article_id',
        'yoast_title',
        'yoast_metadesc',
        'yoast_focuskw',
        'seo_score',
        'readability_score',
        'reading_time_minutes',
    ];

    protected function casts(): array
    {
        return [
            'seo_score' => 'integer',
            'readability_score' => 'integer',
            'reading_time_minutes' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
