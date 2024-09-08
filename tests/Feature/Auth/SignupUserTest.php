<?php

namespace Tests\Feature\Auth;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class SignupUserTest extends TestCase
{
    private $url = 'api/v1/auth/signup';

    private $created = null;

    private $data = null;

    protected function setUp(): void
    {
        parent::setUp();

        $password = fake()->password(8);

        $this->data = [
            'name' => fake()->words(3, true),
            'email' => fake()->email(),
            'password' => $password,
            'passwordConfirmation' => $password,
            'termsAndConditions' => 1,
            'businessType' => 'supplier',
        ];
    }

    /**
     * Test user signup with valid data.
     */
    public function test_user_signup_with_valid_data(): void
    {
        $response = $this->postJson($this->url, $this->data);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson(fn (AssertableJson $json) => $json->whereType('temporalAccessToken', 'string')
                ->where('tokenType', 'Bearer')
                ->whereType('expiresAt', 'string')
                ->where('message', 'Sign up successful. Please verify your email using the OTP sent.')
                ->has('data', fn ($json) => $json->where('user.email', $this->data['email'])
                    ->where('user.name', $this->data['name'])
                    ->whereType('user.createdAt', 'string')
                    ->whereType('user.updatedAt', 'string')
                    ->whereType('user.id', 'integer')
                )
            );

        // Assert that the user was created and the OTP was generated
        $this->assertDatabaseHas('users', ['email' => $this->data['email']]);
        $user = User::where('email', $this->data['email'])->first();
        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'type' => 'SIGNUP_EMAIL_VERIFICATION',
        ]);
    }

    /**
     * Test user signup with validation errors.
     */
    public function test_user_signup_with_validation_error(): void
    {
        $data = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'secret123',
            'passwordConfirmation' => 'mismatch',
        ];

        $this->postJson($this->url, $data)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJson(fn (AssertableJson $json) => $json->whereType('message', 'string')
                ->has('errors')
                ->whereType('errors.email', 'array')
                ->whereType('errors.passwordConfirmation', 'array')
                ->whereType('errors.name', 'array')
                ->whereType('errors.businessType', 'array')
                ->whereType('errors.termsAndConditions', 'array')
            );
    }

    /**
     * Test user signup with duplicate email.
     */
    public function test_user_signup_with_duplicate_email(): void
    {
        // Create a user first to simulate a duplicate email scenario
        $this->created = User::create($this->data);

        // Attempt to sign up again with the same email
        $duplicateData = [
            'name' => 'Jane Doe',
            'email' => $this->created->email,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'termsAndConditions' => 1,
            'businessType' => 'supplier',
        ];

        $this->postJson($this->url, $duplicateData)
            ->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) => $json->whereType('message', 'string')
                ->has('errors')
                ->where('errors.email.0', 'The email has already been taken.')
            );
    }

    protected function tearDown(): void
    {
        if ($this->created) {
            $this->created->delete();
        }

        parent::tearDown();
    }
}
