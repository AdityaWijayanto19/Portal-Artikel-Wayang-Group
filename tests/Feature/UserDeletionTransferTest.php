<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Company;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Models\WPSite;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Penghapusan user tidak boleh pernah ikut menghapus artikel.
 *
 * Skenario yang dijaga:
 *  - user tanpa artikel            → langsung terhapus.
 *  - user dengan artikel           → artikel dipindahkan dulu (wajib pilih penerima).
 *  - penerima beda perusahaan      → ditolak.
 *  - penghapusan langsung ke DB    → ditahan FOREIGN KEY RESTRICT.
 */
class UserDeletionTransferTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Company $otherCompany;

    private User $admin;

    private User $author;

    private WPSite $wpSite;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'author'] as $role) {
            Role::findOrCreate($role);
        }

        $this->company = Company::create(['name' => 'PT Contoh', 'slug' => 'pt-contoh']);
        $this->otherCompany = Company::create(['name' => 'PT Lain', 'slug' => 'pt-lain']);

        $this->admin = $this->createUser('admin', $this->company, 'admin');
        $this->author = $this->createUser('author', $this->company, 'penulis');

        $this->wpSite = WPSite::factory()->create(['company_id' => $this->company->id]);
        $this->category = Category::factory()->create(['company_id' => $this->company->id]);
    }

    private function createUser(string $role, Company $company, string $unique): User
    {
        $user = User::create([
            'name' => ucfirst($role).' '.$unique,
            'email' => "{$role}-{$unique}@example.com",
            'username' => "{$role}-{$unique}",
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        $user->assignRole($role);
        $user->companies()->syncWithoutDetaching([$company->id]);

        return $user;
    }

    private function createArticle(User $author, ?string $slug = null): Article
    {
        return Article::create([
            'company_id' => $author->company_id,
            'user_id' => $author->id,
            'wp_site_id' => $this->wpSite->id,
            'category_id' => $this->category->id,
            'title' => 'Artikel Uji',
            'slug' => $slug ?? 'artikel-uji-'.uniqid(),
            'content' => 'Isi konten untuk pengujian pemindahan author.',
            'status' => 'draft',
        ]);
    }

    public function test_deleting_user_without_articles_succeeds(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->author))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $this->author->id]);
    }

    public function test_deleting_user_with_articles_without_recipient_is_rejected(): void
    {
        $article = $this->createArticle($this->author);

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->author))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->author->id]);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'user_id' => $this->author->id,
        ]);
    }

    public function test_deleting_user_transfers_articles_to_same_company_recipient(): void
    {
        $receiver = $this->createUser('author', $this->company, 'penerima');
        $first = $this->createArticle($this->author, 'artikel-pertama');
        $second = $this->createArticle($this->author, 'artikel-kedua');

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->author), ['transfer_user_id' => $receiver->id])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        // User hilang, artikel tetap utuh dan berpindah pemilik.
        $this->assertDatabaseMissing('users', ['id' => $this->author->id]);

        foreach ([$first, $second] as $article) {
            $this->assertDatabaseHas('articles', [
                'id' => $article->id,
                'user_id' => $receiver->id,
                // Perusahaan asal artikel tidak ikut berubah.
                'company_id' => $this->company->id,
            ]);
        }

        $this->assertSame(2, Article::withoutGlobalScope(TenantScope::class)->count());
    }

    public function test_deleting_user_rejects_recipient_from_another_company(): void
    {
        $receiver = $this->createUser('author', $this->otherCompany, 'penerima-lain');
        $article = $this->createArticle($this->author);

        $this->actingAs($this->admin)
            ->delete(route('users.destroy', $this->author), ['transfer_user_id' => $receiver->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->author->id]);
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'user_id' => $this->author->id,
        ]);
        // Artikel tidak berpindah ke penerima yang beda perusahaan.
        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
            'user_id' => $receiver->id,
        ]);
    }

    public function test_users_index_renders_article_transfer_select(): void
    {
        $this->createArticle($this->author);

        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('transfer_user_id', false)
            ->assertSee('Pindahkan');
    }

    public function test_users_index_renders_without_transfer_select_when_user_has_no_article(): void
    {
        $this->actingAs($this->admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('name="transfer_user_id"', false);
    }

    public function test_users_index_blocks_delete_when_no_same_company_recipient_exists(): void
    {
        // Satu-satunya user di perusahaan lain, dan dia punya artikel.
        $lonely = $this->createUser('author', $this->otherCompany, 'sendiri');
        Article::create([
            'company_id' => $this->otherCompany->id,
            'user_id' => $lonely->id,
            'wp_site_id' => WPSite::factory()->create(['company_id' => $this->otherCompany->id])->id,
            'category_id' => Category::factory()->create(['company_id' => $this->otherCompany->id])->id,
            'title' => 'Artikel Tanpa Penerima',
            'slug' => 'artikel-tanpa-penerima',
            'content' => 'Isi konten uji.',
            'status' => 'draft',
        ]);

        // Super admin tanpa perusahaan (sesuai UserService::updateUser).
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super-admin@example.com',
            'username' => 'super-admin',
            'password' => bcrypt('password'),
            'company_id' => null,
        ]);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('name="transfer_user_id"', false)
            ->assertSee('tidak ada pengguna lain dalam perusahaan yang sama');
    }

    public function test_database_blocks_direct_user_delete_while_articles_exist(): void
    {
        $this->createArticle($this->author);

        try {
            $this->author->delete();
        } catch (QueryException $e) {
            // Diharapkan: FOREIGN KEY RESTRICT pada articles.user_id menolak penghapusan.
            $this->assertStringContainsString('FOREIGN KEY constraint failed', $e->getMessage());
            $this->assertDatabaseHas('users', ['id' => $this->author->id]);
            $this->assertSame(1, Article::withoutGlobalScope(TenantScope::class)->count());

            return;
        }

        $this->fail('FOREIGN KEY RESTRICT seharusnya menolak penghapusan user yang masih memiliki artikel.');
    }
}
