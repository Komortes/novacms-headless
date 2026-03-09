<?php

namespace Tests\Feature\Console;

use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_create_command_issues_token(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.com',
        ]);

        $this->artisan('api-token:create api@example.com external-client --ability=graphql:write --ability=graphql:read-internal')
            ->expectsOutput('API token created.')
            ->expectsOutputToContain('Token ID:')
            ->expectsOutputToContain('graphql:write, graphql:read-internal')
            ->expectsOutputToContain('nova_')
            ->assertExitCode(0);

        $this->assertDatabaseHas('api_access_tokens', [
            'user_id' => $user->id,
            'name' => 'external-client',
        ]);
    }

    public function test_api_token_list_command_shows_user_tokens(): void
    {
        $user = User::factory()->create([
            'email' => 'list@example.com',
        ]);

        $issued = $user->issueApiToken('listable-token', ['graphql:admin']);

        $this->artisan('api-token:list list@example.com')
            ->expectsTable(
                ['ID', 'Name', 'Abilities', 'Last Used', 'Expires', 'Revoked'],
                [[
                    $issued['access_token']->id,
                    'listable-token',
                    'graphql:admin',
                    '-',
                    '-',
                    '-',
                ]],
            )
            ->assertExitCode(0);
    }

    public function test_api_token_revoke_command_marks_token_revoked(): void
    {
        $user = User::factory()->create();
        $issued = $user->issueApiToken('revocable-token', ['graphql:write']);
        /** @var ApiAccessToken $token */
        $token = $issued['access_token'];

        $this->artisan("api-token:revoke {$token->id}")
            ->expectsOutput("API token [{$token->id}] revoked.")
            ->assertExitCode(0);

        $this->assertNotNull($token->fresh()?->revoked_at);
    }
}
