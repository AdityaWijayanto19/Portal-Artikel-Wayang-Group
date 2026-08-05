<?php

namespace App\Http\Requests\Article;

/**
 * Aturan update artikel identik dengan pembuatan: featured_image tetap opsional
 * (nullable) sehingga penulis boleh menyimpan tanpa mengganti gambar lama.
 * Mewarisi seluruh rule & sanitasi tenant-safe dari StoreArticleRequest (DRY).
 */
class UpdateArticleRequest extends StoreArticleRequest
{
}
