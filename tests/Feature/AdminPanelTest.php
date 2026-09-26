<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin']);
        $this->userRole = Role::create(['name' => 'user']);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    }

    public function test_non_admin_cannot_access_admin_routes()
    {
        Sanctum::actingAs(User::factory()->create(['role_id' => $this->userRole->id]));

        foreach (['/api/admin/stats', '/api/admin/users', '/api/admin/domains', '/api/admin/logs'] as $url) {
            $this->getJson($url)->assertForbidden();
        }
        $this->postJson('/api/admin/domains', ['domain' => 'evil.com'])->assertForbidden();
    }

    public function test_guest_cannot_access_admin_routes()
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();
    }

    public function test_admin_can_list_and_create_users()
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/admin/users', [
            'name' => 'Jean', 'email' => 'jean@yamsgroup.com', 'password' => 'Secret123', 'role_id' => $this->userRole->id,
        ])->assertCreated()->assertJsonPath('role.name', 'user')->assertJsonMissingPath('password');

        $this->getJson('/api/admin/users?search=jean')->assertOk()->assertJsonPath('total', 1);
        $this->assertDatabaseHas('activity_logs', ['action' => 'admin_user_created']);
    }

    public function test_admin_cannot_demote_or_delete_self()
    {
        Sanctum::actingAs($this->admin);

        $this->patchJson("/api/admin/users/{$this->admin->id}", ['role_id' => $this->userRole->id])->assertStatus(422);
        $this->deleteJson("/api/admin/users/{$this->admin->id}")->assertStatus(422);
    }

    public function test_role_change_revokes_sessions_and_delete_works()
    {
        $user = User::factory()->create(['role_id' => $this->userRole->id]);
        $user->createToken('t');
        Sanctum::actingAs($this->admin);

        $adminRoleId = $this->admin->role_id;
        $this->patchJson("/api/admin/users/{$user->id}", ['role_id' => $adminRoleId])->assertOk();
        $this->assertSame(0, $user->tokens()->count());

        $this->deleteJson("/api/admin/users/{$user->id}")->assertOk();
        $this->assertModelMissing($user);
    }

    public function test_admin_manages_domains()
    {
        Sanctum::actingAs($this->admin);

        $id = $this->postJson('/api/admin/domains', ['domain' => '  Exemple.COM '])
            ->assertCreated()->assertJsonPath('domain', 'exemple.com')->json('id');
        $this->postJson('/api/admin/domains', ['domain' => 'exemple.com'])->assertStatus(422);
        $this->postJson('/api/admin/domains', ['domain' => 'pas un domaine'])->assertStatus(422);

        $this->deleteJson("/api/admin/domains/{$id}")->assertOk();
        $this->assertSame(0, Domain::count());
    }

    public function test_admin_stats()
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/admin/stats')->assertOk()
            ->assertJsonPath('users', 1)->assertJsonPath('admins', 1)
            ->assertJsonStructure(['documents', 'storage_used', 'top_users', 'recent_logs']);
    }
}
