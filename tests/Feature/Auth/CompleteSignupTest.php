<?php

namespace Tests\Feature\Auth;

use App\Enums\BusinessType;
use App\Enums\StatusEnum;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Testing\Fluent\AssertableJson;

beforeEach(function () {
    $this->completeSignupEndpoint = route('auth.signup.complete');
});

describe('Complete Signup Tests', function () {
    test('authenticated user can complete signup with Google provider', function () {
        $user = User::factory()->create([
            'email' => 'googleuser@example.com',
            'status' => StatusEnum::ACTIVE->value,
            'email_verified_at' => now(),
        ]);

        $role = Role::where('name', 'supplier')->first();
        $user->assignRole($role);

        $business = Business::factory()->create([
            'owner_id' => $user->id,
            'type' => BusinessType::SUPPLIER->value,
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'provider' => 'google',
            'businessName' => 'Complete Business Ltd',
            'businessType' => 'supplier',
            'businessEmail' => 'business@example.com',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'John Doe',
            'contactPersonPosition' => 'Manager',
            'termsAndConditions' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->completeSignupEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->where('status', 'success')
                    ->where('message', 'Signup process completed successfully.')
                    ->has('data')
            );

        // Assert business was created (Google provider creates a new business)
        // The existing business might be different, so find the newly created business
        $newBusiness = Business::where('owner_id', $user->id)
            ->where('name', 'Complete Business Ltd')
            ->first();

        $this->assertNotNull($newBusiness);
        $this->assertEquals('Complete Business Ltd', $newBusiness->name);
        $this->assertEquals('business@example.com', $newBusiness->contact_email);
        $this->assertEquals('+2349012345678', $newBusiness->contact_phone);
        $this->assertEquals('John Doe', $newBusiness->contact_person);
    });

    test('authenticated user can complete signup with credentials provider', function () {
        $user = User::factory()->create([
            'email' => 'credentialuser@example.com',
            'status' => StatusEnum::ACTIVE->value,
            'email_verified_at' => now(),
        ]);

        $role = Role::where('name', 'customer_pharmacy')->first();
        $user->assignRole($role);

        $business = Business::factory()->create([
            'owner_id' => $user->id,
            'type' => BusinessType::CUSTOMER_PHARMACY->value,
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'provider' => 'credentials',
            'businessName' => $business->name, // Name must be provided but won't be updated
            'businessEmail' => 'pharmacy@example.com',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'Jane Doe',
            'contactPersonPosition' => 'Owner',
            'termsAndConditions' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->completeSignupEndpoint, $data);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson(
                fn (AssertableJson $json) => $json
                    ->has('status')
                    ->where('message', 'Signup process completed successfully.')
                    ->has('data')
            );

        // Assert business was updated (credentials provider only updates contact fields, not name)
        $business->refresh();
        // Note: completeCredentialSignUp doesn't update the business name, only contact fields
        $this->assertEquals('pharmacy@example.com', $business->contact_email);
        $this->assertEquals('+2349012345678', $business->contact_phone);
        $this->assertEquals('Jane Doe', $business->contact_person);
        $this->assertEquals('Owner', $business->contact_person_position);
    });

    test('complete signup requires authentication', function () {
        $data = [
            'provider' => 'google',
            'businessName' => 'Test Business',
            'businessEmail' => 'business@example.com',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'John Doe',
            'contactPersonPosition' => 'Manager',
            'termsAndConditions' => true,
        ];

        $response = $this->postJson($this->completeSignupEndpoint, $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    });

    test('complete signup requires provider field', function () {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'businessName' => 'Test Business',
            'businessEmail' => 'business@example.com',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'John Doe',
            'contactPersonPosition' => 'Manager',
            'termsAndConditions' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->completeSignupEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['provider']);
    });

    test('complete signup requires business name', function () {
        $user = User::factory()->create();
        // Create a business for the user (credentials provider requires existing business)
        $business = Business::factory()->create([
            'owner_id' => $user->id,
        ]);

        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'provider' => 'credentials',
            'businessEmail' => 'business@example.com',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'John Doe',
            'contactPersonPosition' => 'Manager',
            'termsAndConditions' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->completeSignupEndpoint, $data);

        // Note: The validation uses 'name' field (mapped from businessName), but for credentials
        // provider, business name might not be required. Let's check the actual validation.
        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['name']); // Rule field name after camelCase conversion
    });

    test('complete signup requires business email', function () {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'provider' => 'credentials',
            'businessName' => 'Test Business',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'John Doe',
            'contactPersonPosition' => 'Manager',
            'termsAndConditions' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->completeSignupEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['contactEmail']); // Rule field name after camelCase conversion
    });

    test('complete signup with Google provider requires business type', function () {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'provider' => 'google',
            'businessName' => 'Test Business',
            'businessEmail' => 'business@example.com',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'John Doe',
            'contactPersonPosition' => 'Manager',
            'termsAndConditions' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->completeSignupEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['type']); // Rule field name after camelCase conversion
    });

    test('complete signup requires terms and conditions acceptance', function () {
        $user = User::factory()->create();
        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'provider' => 'credentials',
            'businessName' => 'Test Business',
            'businessEmail' => 'business@example.com',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'John Doe',
            'contactPersonPosition' => 'Manager',
            'termsAndConditions' => false,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->completeSignupEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['termsAndConditions']);
    });

    test('complete signup prevents duplicate business names', function () {
        $existingBusiness = Business::factory()->create([
            'name' => 'Existing Business',
        ]);

        $user = User::factory()->create();
        $tokenResult = $user->createToken('Full Access Token', ['full']);
        $token = $tokenResult->accessToken;

        $data = [
            'provider' => 'credentials',
            'businessName' => 'Existing Business', // Duplicate name
            'businessEmail' => 'business@example.com',
            'contactPhone' => '+2349012345678',
            'contactPersonName' => 'John Doe',
            'contactPersonPosition' => 'Manager',
            'termsAndConditions' => true,
        ];

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->postJson($this->completeSignupEndpoint, $data);

        $response->assertStatus(Response::HTTP_BAD_REQUEST)
            ->assertJsonValidationErrors(['name']); // Rule field name after camelCase conversion
    });
});
