<?php

namespace App\Http\Requests\Article;

use App\Models\Company;
use App\Models\User;
use App\Support\ArticleContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = $this->resolvedCompanyId();

        // Tenant guard: hanya izinkan relasi milik company yang sama agar tidak bocor antar holding.
        $categoryExists = Rule::exists('categories', 'id')
            ->where(fn ($q) => $q->where('company_id', $companyId));
        $wpSiteExists = Rule::exists('wp_sites', 'id')
            ->where(fn ($q) => $q->where('company_id', $companyId));

        return [
            'company_id' => ['required', 'integer', Rule::in($this->allowedCompanyIds())],
            'user_id' => ['required', 'integer', Rule::in($this->allowedAuthorIds($companyId))],

            'title' => ['required', 'string', 'min:10', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'content' => ['required', 'string', 'min:200'],

            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],

            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['integer', $categoryExists],

            // Tag freeform: pengguna mengetik nama tag (bukan ID). Service yang meng-upsert
            // nama → id per-company. Tenant tetap aman karena tag dibuat pada company aktif.
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],

            'wp_site_ids' => ['required', 'array', 'min:1'],
            'wp_site_ids.*' => ['integer', $wpSiteExists],

            'status' => ['required', 'in:draft,published'],

            'yoast_title' => ['nullable', 'string', 'max:255'],
            'yoast_metadesc' => ['nullable', 'string', 'max:500'],
            'yoast_focuskw' => ['nullable', 'string', 'max:255'],

            'action' => ['required', 'in:draft,publish'],
        ];
    }

    /**
     * Sanitasi input & paksa identitas tenant/user mengikuti sesi autentikasi aktif.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $merge = [
            'user_id' => $this->input('user_id') ?: $user->id,
            'company_id' => $this->resolvedCompanyId(),
        ];

        if ($this->filled('slug')) {
            $merge['slug'] = Str::slug((string) $this->input('slug'));
        } elseif ($this->filled('title')) {
            $merge['slug'] = Str::slug((string) $this->input('title'));
        }

        if ($this->filled('title')) {
            $merge['title'] = trim((string) $this->input('title'));
        }

        // Tag freeform bisa datang sebagai string "a,b,c" (fallback non-JS) atau array.
        // Normalisasi ke array unik, trim, buang kosong.
        $merge['tags'] = $this->normalizeTags($this->input('tags'));

        // Tombol Draft/Publish menetapkan `action`; `status` mengikuti (published→publish).
        $status = (string) $this->input('status', 'draft');
        if (! $this->filled('action')) {
            $merge['action'] = $status === 'published' ? 'publish' : 'draft';
        }

        $this->merge($merge);
    }

    /**
     * Normalisasi input tag menjadi array nama tag yang bersih & unik.
     *
     * Tag dikirim sebagai string ber-pemisah enter (dari komponen frontend)
     * atau array; koma tetap ditoleransi sebagai fallback non-JS.
     *
     * @return array<int, string>
     */
    protected function normalizeTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[\r\n,]+/', $tags) ?: [];
        }

        if (! is_array($tags)) {
            return [];
        }

        return collect($tags)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter(fn (string $tag) => $tag !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * ID user yang boleh menjadi author artikel: seluruh user pada company aktif.
     * Menjaga isolasi tenant — author tidak bisa diset ke user perusahaan lain.
     *
     * @return array<int, int>
     */
    protected function allowedAuthorIds(?int $companyId): array
    {
        if ($companyId === null) {
            return [$this->user()?->id];
        }

        return User::query()
            ->where('company_id', $companyId)
            ->pluck('id')
            ->push($this->user()?->id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Company aktif untuk request ini. Super admin memakai FOKUS modul artikel
     * (ArticleContext), independen dari scope global. Non-super-admin terkunci
     * pada perusahaan sendiri.
     */
    protected function resolvedCompanyId(): ?int
    {
        $resolved = ArticleContext::companyId();

        if ($resolved !== null) {
            return $resolved;
        }

        // Fallback: super admin tanpa fokus artikel → pakai company_id dari form.
        $posted = (int) $this->input('company_id');

        return $posted > 0 ? $posted : null;
    }

    /**
     * @return array<int, int>
     */
    protected function allowedCompanyIds(): array
    {
        $user = $this->user();

        return $user?->isSuperAdmin()
            ? Company::query()->pluck('id')->all()
            : ($user?->companyIds() ?? []);
    }

    public function messages(): array
    {
        return [
            // ==================================================
            // COMPANY
            // ==================================================

            'company_id.required' => 'Perusahaan wajib dipilih.',
            'company_id.integer' => 'Perusahaan yang dipilih tidak valid.',
            'company_id.in' => 'Anda tidak memiliki akses ke perusahaan yang dipilih.',

            // ==================================================
            // AUTHOR
            // ==================================================

            'user_id.required' => 'Penulis artikel wajib dipilih.',
            'user_id.integer' => 'Penulis artikel tidak valid.',
            'user_id.in' => 'Penulis yang dipilih tidak memiliki akses ke perusahaan tersebut.',

            // ==================================================
            // TITLE
            // ==================================================

            'title.required' => 'Judul artikel wajib diisi.',
            'title.string' => 'Judul artikel harus berupa teks.',
            'title.min' => 'Judul artikel minimal 10 karakter.',
            'title.max' => 'Judul artikel maksimal 255 karakter.',

            // ==================================================
            // SLUG
            // ==================================================

            'slug.required' => 'Slug artikel wajib diisi.',
            'slug.string' => 'Slug artikel harus berupa teks.',
            'slug.max' => 'Slug artikel maksimal 255 karakter.',
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda strip (-).',

            // ==================================================
            // CONTENT
            // ==================================================

            'content.required' => 'Konten artikel wajib diisi.',
            'content.string' => 'Konten artikel harus berupa teks.',
            'content.min' => 'Konten artikel minimal 200 karakter.',

            // ==================================================
            // FEATURED IMAGE
            // ==================================================

            'featured_image.image' => 'Featured image harus berupa file gambar.',
            'featured_image.mimes' => 'Format featured image harus JPG, JPEG, PNG, atau WEBP.',
            'featured_image.max' => 'Ukuran featured image maksimal 2 MB.',

            // ==================================================
            // IMAGE ALT TEXT
            // ==================================================

            'image_alt_text.string' => 'Alt text gambar harus berupa teks.',
            'image_alt_text.max' => 'Alt text gambar maksimal 255 karakter.',

            // ==================================================
            // CATEGORIES
            // ==================================================

            'categories.required' => 'Minimal satu kategori wajib dipilih.',
            'categories.array' => 'Format kategori tidak valid.',
            'categories.min' => 'Minimal satu kategori wajib dipilih.',

            'categories.*.integer' => 'Kategori yang dipilih tidak valid.',
            'categories.*.exists' => 'Salah satu kategori yang dipilih tidak tersedia atau bukan milik perusahaan tersebut.',

            // ==================================================
            // TAGS
            // ==================================================

            'tags.array' => 'Format tag tidak valid.',
            'tags.*.string' => 'Nama tag harus berupa teks.',
            'tags.*.max' => 'Nama tag maksimal 50 karakter.',

            // ==================================================
            // WORDPRESS SITES
            // ==================================================

            'wp_site_ids.required' => 'Minimal satu WP Site wajib dipilih.',
            'wp_site_ids.array' => 'Format WP Site tidak valid.',
            'wp_site_ids.min' => 'Minimal satu WP Site wajib dipilih.',

            'wp_site_ids.*.integer' => 'WP Site yang dipilih tidak valid.',
            'wp_site_ids.*.exists' => 'Salah satu WP Site yang dipilih tidak tersedia atau bukan milik perusahaan tersebut.',

            // ==================================================
            // STATUS
            // ==================================================

            'status.required' => 'Status artikel wajib dipilih.',
            'status.in' => 'Status artikel tidak valid.',

            // ==================================================
            // YOAST SEO
            // ==================================================

            'yoast_title.string' => 'Judul SEO harus berupa teks.',
            'yoast_title.max' => 'Judul SEO maksimal 255 karakter.',

            'yoast_metadesc.string' => 'Meta description harus berupa teks.',
            'yoast_metadesc.max' => 'Meta description maksimal 500 karakter.',

            'yoast_focuskw.string' => 'Focus keyword harus berupa teks.',
            'yoast_focuskw.max' => 'Focus keyword maksimal 255 karakter.',

            // ==================================================
            // ACTION
            // ==================================================

            'action.required' => 'Aksi artikel wajib ditentukan.',
            'action.in' => 'Aksi artikel tidak valid.',
        ];
    }
}
