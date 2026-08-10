<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ArticleContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pencatat aktivitas pengguna (audit trail) yang reusable di seluruh modul.
 *
 * Menyelesaikan user/company secara otomatis sehingga pemanggil cukup fokus pada
 * "apa yang terjadi". company_id disimpan eksplisit agar halaman log bisa di-scope
 * per tenant tanpa bergantung pada global scope yang tidak stabil.
 */
class ActivityLogger
{
    public function __construct(private readonly Request $request) {}

    /**
     * Tulis satu baris activity log.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?User $user = null,
        ?int $companyId = null,
    ): ActivityLog {
        $user ??= Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'company_id' => $companyId ?? $this->resolveCompanyId($subject, $user),
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
            'ip_address' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 255),
        ]);
    }

    /**
     * Resolusi company_id dengan prioritas: argumen eksplisit → subject → konteks
     * artikel → primary company user. Untuk super admin yang bertindak global,
     * hasilnya null (aksi lintas perusahaan), yang tetap sah.
     */
    private function resolveCompanyId(?Model $subject, ?User $user): ?int
    {
        if ($subject && $subject->getAttribute('company_id')) {
            return (int) $subject->getAttribute('company_id');
        }

        $contextCompanyId = ArticleContext::companyId();

        if ($contextCompanyId !== null) {
            return $contextCompanyId;
        }

        return $user?->primaryCompanyId();
    }
}
