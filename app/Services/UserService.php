<?php

namespace App\Services;

use App\Exceptions\UserHasArticlesException;
use App\Models\Article;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data, int $targetCompanyId): User
    {
        return DB::transaction(function () use ($data, $targetCompanyId) {
            // 1. Create Base User
            $user = User::create([
                'name' => $data['name'],
                'username' => trim($data['username']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $targetCompanyId, // Primary Company
            ]);

            // 2. Attach Pivot company_user
            $user->companies()->syncWithoutDetaching([$targetCompanyId]);

            // 3. Assign Spatie Role
            $user->assignRole($data['role']);

            return $user;
        });
    }

    public function updateUser(User $user, array $data, ?int $targetCompanyId = null, ?string $role = null): User
    {
        return DB::transaction(function () use ($user, $data, $targetCompanyId, $role) {
            $updateData = [
                'name' => $data['name'],
                'username' => trim($data['username']),
                'email' => $data['email'],
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            // Super Admin = holding "Wayang Group" (bukan tenant) → tanpa company & pivot.
            if ($role === 'super_admin') {
                $updateData['company_id'] = null;
                $user->companies()->sync([]);
            } elseif ($targetCompanyId) {
                $updateData['company_id'] = $targetCompanyId;
                $user->companies()->sync([$targetCompanyId]);
            }

            $user->update($updateData);

            // Sync Role
            if ($role) {
                $user->syncRoles([$role]);
            }

            return $user;
        }); 
    }

    /**
     * Hapus user tanpa pernah menghapus artikelnya.
     *
     * Aturannya:
     *  - user tanpa artikel  → langsung dihapus.
     *  - user dengan artikel → seluruh artikel WAJIB dipindahkan ke user lain yang
     *    satu perusahaan lebih dulu, baru user dihapus.
     *
     * FK articles.user_id memakai RESTRICT, sehingga bila ada artikel yang lolos
     * dari pemindahan, database akan menolak penghapusan (data tetap aman).
     *
     * @return array{articles_transferred: int, recipient: User|null}
     *
     * @throws UserHasArticlesException bila syarat pemindahan artikel belum terpenuhi.
     */
    public function deleteUser(User $user, ?int $transferToUserId = null): array
    {
        return DB::transaction(function () use ($user, $transferToUserId) {
            // TenantScope sengaja dilepas: jumlah dan pemindahan harus menjangkau
            // seluruh artikel user terkait, apa pun filter tenant aktif pemanggil.
            $articlesQuery = Article::withoutGlobalScope(TenantScope::class)
                ->where('user_id', $user->id);

            $articleCount = (int) (clone $articlesQuery)->count();
            $recipient = null;

            if ($articleCount > 0) {
                $recipient = $this->resolveArticleRecipient($user, $transferToUserId, $articleCount);

                // toBase(): hanya user_id yang berubah, updated_at artikel tidak
                // ikut diubah karena kontennya tidak tersentuh.
                $articlesQuery->toBase()->update(['user_id' => $recipient->id]);
            }

            $user->delete();

            return [
                'articles_transferred' => $articleCount,
                'recipient' => $recipient,
            ];
        });
    }

    /**
     * Validasi user penerima pemindahan artikel (wajib satu perusahaan dengan
     * user yang akan dihapus; role tidak dibatasi).
     *
     * @throws UserHasArticlesException
     */
    private function resolveArticleRecipient(User $user, ?int $transferToUserId, int $articleCount): User
    {
        if ($transferToUserId === null) {
            throw new UserHasArticlesException(
                "Pengguna \"{$user->name}\" masih memiliki {$articleCount} artikel. ".
                'Pilih pengguna penerima terlebih dahulu agar artikelnya tidak ikut terhapus.'
            );
        }

        $recipient = User::with('companies')->find($transferToUserId);

        if ($recipient === null || $recipient->id === $user->id) {
            throw new UserHasArticlesException('Pengguna penerima artikel tidak valid. Silakan pilih penerima lain.');
        }

        if (array_intersect($recipient->companyIds(), $user->companyIds()) === []) {
            throw new UserHasArticlesException(
                "Pengguna penerima \"{$recipient->name}\" harus berada dalam perusahaan yang sama dengan \"{$user->name}\"."
            );
        }

        return $recipient;
    }
}
