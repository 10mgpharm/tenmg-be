<?php

namespace Tests\Feature\Credit;

use App\Http\Middleware\ClientPublicApiMiddleware;
use App\Models\TenmgCreditRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenmgCreditTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_initiates_tenmg_credit_and_returns_sdk_url(): void
    {
        config(['services.tenmg_credit.sdk_base_url' => 'https://sdk.tenmg.ai']);

        $payload = [
            'borrower_reference' => 'REF123',
            'amount' => 15000,
            'extra' => ['foo' => 'bar'],
        ];

        $response = $this
            ->withoutMiddleware(ClientPublicApiMiddleware::class)
            ->postJson('/api/v1/client/credit/tenmg/initiate', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.request_id', fn ($value) => is_string($value) && str_starts_with($value, 'TENMGREQ-'))
            ->assertJsonPath('data.sdk_url', fn ($value) => str_starts_with($value, 'https://sdk.tenmg.ai/tenmg-credit?request_id='));

        $requestId = $response->json('data.request_id');

        $this->assertDatabaseHas('tenmg_credit_requests', [
            'request_id' => $requestId,
            'status' => 'pending',
        ]);
    }

    public function test_it_returns_stored_payload_by_request_id(): void
    {
        $requestId = 'TENMGREQ-'.Str::upper(Str::random(12));

        $record = TenmgCreditRequest::create([
            'request_id' => $requestId,
            'payload' => ['borrower_reference' => 'REF999', 'foo' => 'bar'],
            'status' => 'pending',
            'sdk_url' => 'https://sdk.tenmg.ai/tenmg-credit?request_id='.$requestId,
            'initiated_by' => null,
        ]);

        $response = $this
            ->withoutMiddleware(ClientPublicApiMiddleware::class)
            ->getJson('/api/v1/client/credit/tenmg/requests/'.$record->request_id);

        $response->assertStatus(200)
            ->assertJsonPath('data.request_id', $requestId)
            ->assertJsonPath('data.payload.borrower_reference', 'REF999')
            ->assertJsonPath('data.status', 'pending');
    }
}
