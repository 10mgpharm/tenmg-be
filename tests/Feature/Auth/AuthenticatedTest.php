<?php

namespace Tests\Feature\Auth;

use App\Models\Business;
use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Passport\Passport;
use Laravel\Passport\PersonalAccessTokenResult;
use Mockery;

beforeEach(function () {
    $this->url = route('signin');
    $this->email = 'admin@example.com';
    $this->password = 'password';

    // Create a mock for User
    $this->user = Mockery::mock(User::class)->makePartial();
    $this->user->email = 'admin@example.com';
    $this->user->name = 'John Doe';
    $this->user->id = 1;

    // Create a mock for PersonalAccessTokenResult
    $this->tokenResultMock = Mockery::mock(PersonalAccessTokenResult::class);
    $this->tokenResultMock->accessToken = 'token';
    $this->tokenResultMock->token = (object) ['expires_at' => now()->addHour()];

    // Mock the createToken method
    $this->user->shouldReceive('createToken')
        ->with('Full Access Token', ['full'])
        ->andReturn($this->tokenResultMock);

    // Mock the token() method on user
    $this->user->shouldReceive('token')->andReturn($this->tokenResultMock);

    // Mock the Business model instance
    $mockedBusiness = Mockery::mock(Business::class);

    $mockedBusiness->shouldReceive('getAttribute')->with('type')->andReturn('VENDOR');
    $mockedBusiness->shouldReceive('offsetExists')->with('type')->andReturn(true);

    $mockedBusiness->shouldReceive('getAttribute')->with('name')->andReturn('Tuyil Pharmaceutical');
    $mockedBusiness->shouldReceive('offsetExists')->with('name')->andReturn(true);

    $mockedBusiness->shouldReceive('getAttribute')->with('status')->andReturn('VERIFIED');
    $mockedBusiness->shouldReceive('offsetExists')->with('status')->andReturn(true);

    $mockedHasOne = Mockery::mock(HasOne::class);
    $mockedHasOne->shouldReceive('getResults')->andReturn($mockedBusiness);
    $this->user->shouldReceive('ownerBusinessType')->andReturn($mockedHasOne);

    // Mock the revoke method on token
    $this->tokenResultMock->shouldReceive('revoke')->andReturn(true);

    // Mock Auth facade methods
    Auth::shouldReceive('attempt')
        ->with(['email' => $this->email, 'password' => $this->password], false)
        ->andReturn(true);
    Auth::shouldReceive('attempt')
        ->with(['email' => $this->email, 'password' => 'wrongpassword'], false)
        ->andReturn(false);
    Auth::shouldReceive('user')->andReturn($this->user);
    Auth::shouldReceive('check')->andReturn(true);
    Auth::shouldReceive('userResolver')->andReturn(fn () => $this->user);
    Auth::shouldReceive('shouldUse')->andReturnSelf(); // Mock shouldUse

    // Mock Auth guard
    $authGuard = Mockery::mock(StatefulGuard::class)->makePartial();
    $authGuard->shouldReceive('attempt')
        ->with(['email' => $this->email, 'password' => $this->password], false)
        ->andReturn(true);
    $authGuard->shouldReceive('login')
        ->with($this->user, false)
        ->andReturn(true);
    $authGuard->shouldReceive('logout')->andReturn(true);
    $authGuard->shouldReceive('check')->andReturn(true);

    $authGuard->shouldReceive('setUser')
        ->with($this->user)
        ->andReturnSelf();
    $authGuard->shouldReceive('hasUser')->andReturn(true);

    Auth::shouldReceive('guard')->andReturn($authGuard);
});

it('can sign in with valid credentials', function () {
    $data = [
        'email' => $this->email,
        'password' => $this->password,
    ];

    $response = $this->postJson($this->url, $data);

    dump($response->json());

    $response->assertStatus(Response::HTTP_OK)
        ->assertJson(
            fn (AssertableJson $json) => $json->where('status', 'success')
                ->where('message', 'Sign in successful.')
                ->has(
                    'accessToken',
                    fn ($accessToken) => $accessToken->where('token', 'token')
                        ->where('tokenType', 'bearer')
                        ->whereType('expiresAt', 'string')
                )
                ->has(
                    'data',
                    fn ($data) => $data->where('id', $this->user->id)
                        ->where('name', $this->user->name)
                        ->where('email', $this->user->email)
                        ->where('emailVerifiedAt', null)
                        ->where('entityType', 'VENDOR')
                        ->where('businessName', 'Tuyil Pharmaceutical')
                        ->where('businessStatus', 'VERIFIED')
                )
        );
});

it('cannot sign in with invalid credentials', function () {
    $data = [
        'email' => $this->email,
        'password' => 'wrongpassword',
    ];

    $response = $this->postJson($this->url, $data);

    $response->assertStatus(Response::HTTP_UNAUTHORIZED)
        ->assertJson(
            fn (AssertableJson $json) => $json->where('status', 'error')
                ->where('message', 'Email or Password is invalid')
        );
});

it('can log out', function () {
    $token = $this->user->createToken('Full Access Token', ['full'])->accessToken;
    Passport::actingAs($this->user, ['full']);

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson(route('signout'))
        ->assertStatus(Response::HTTP_OK)
        ->assertJson(['message' => 'Logged out successfully']);
});

afterEach(function () {
    Mockery::close();
});
