<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Domain;
use App\Models\Package;

class DomainControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;
    protected Package $package;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->package = Package::factory()->create();
        $this->user = User::factory()->create(['package_id' => $this->package->id]);
        $this->admin = User::factory()->create(['role' => 'admin', 'is_admin' => true]);
    }

    public function test_user_can_view_domains_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('domains.index'));
        $response->assertStatus(200);
        $response->assertViewIs('domains.index');
    }

    public function test_user_can_create_domain(): void
    {
        $domainData = [
            'name' => 'mytest-domain.com',
            'document_root' => '/public_html'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('domains.store'), $domainData);

        $response->assertRedirect(route('domains.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('domains', [
            'name' => 'mytest-domain.com',
            'user_id' => $this->user->id
        ]);
    }

    public function test_user_cannot_create_domain_if_limit_exceeded(): void
    {
        // Create domains up to the limit
        Domain::factory($this->package->max_domains)->create(['user_id' => $this->user->id]);

        $domainData = [
            'name' => 'another-test-domain.com',
            'document_root' => '/public_html'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('domains.store'), $domainData);

        $response->assertRedirect();
        $response->assertSessionHasErrors('name');
    }

    public function test_user_can_only_view_own_domains(): void
    {
        $otherUser = User::factory()->create(['package_id' => $this->package->id]);
        $userDomain = Domain::factory()->create(['user_id' => $this->user->id]);
        $otherDomain = Domain::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->get(route('domains.show', $userDomain));
        $response->assertStatus(200);

        $response = $this->actingAs($this->user)->get(route('domains.show', $otherDomain));
        $response->assertStatus(403);
    }

    public function test_user_can_update_own_domain(): void
    {
        $domain = Domain::factory()->create(['user_id' => $this->user->id]);
        
        $updateData = [
            'name' => 'updated-test-domain.com',
            'document_root' => '/public_html/updated'
        ];

        $response = $this->actingAs($this->user)
            ->patch(route('domains.update', $domain), $updateData);

        $response->assertRedirect(route('domains.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('domains', [
            'id' => $domain->id,
            'name' => 'updated-test-domain.com'
        ]);
    }

    public function test_user_can_delete_own_domain(): void
    {
        $domain = Domain::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('domains.destroy', $domain));

        $response->assertRedirect(route('domains.index'));
        $response->assertSessionHas('success');
        
        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    public function test_admin_can_view_all_domains(): void
    {
        $userDomain = Domain::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->actingAs($this->admin)->get(route('domains.show', $userDomain));
        $response->assertStatus(200);
    }

    public function test_domain_name_must_be_unique(): void
    {
        Domain::factory()->create(['name' => 'unique-test-domain.com']);

        $domainData = [
            'name' => 'unique-test-domain.com',
            'document_root' => '/public_html'
        ];

        $response = $this->actingAs($this->user)
            ->post(route('domains.store'), $domainData);

        $response->assertSessionHasErrors('name');
    }

    public function test_domain_name_validation(): void
    {
        $invalidDomains = [
            '',
            'invalid_domain',
            'toolongdomainnamethatshouldnotbeacceptedasitsexceedsthelimitofcharacters.com',
            '.valid-domain.com',
            'valid-domain.',
        ];

        foreach ($invalidDomains as $invalidDomain) {
            $response = $this->actingAs($this->user)
                ->post(route('domains.store'), [
                    'name' => $invalidDomain,
                    'document_root' => '/public_html'
                ]);

            $response->assertSessionHasErrors('name');
        }
    }
}
