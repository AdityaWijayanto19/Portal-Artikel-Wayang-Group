<?php

namespace App\Http\Requests\Article;

use App\Models\Company;
use App\Models\User;
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
     * @return array<int, string>
     */
    protected function normalizeTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = explode(',', $tags);
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
     * Company aktif untuk request ini (super admin memakai company terpilih di sesi).
     */
    protected function resolvedCompanyId(): ?int
    {
        $user = $this->user();

        if (! $user) {
            return null;
        }

        if ($user->isSuperAdmin()) {
            $active = $this->session()->get('active_company_id');

            return is_numeric($active) ? (int) $active : (int) $this->input('company_id');
        }

        return $user->primaryCompanyId();
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
}
