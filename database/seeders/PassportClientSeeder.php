<?php

namespace Database\Seeders;

use App\Models\PassportClient;
use App\Models\PassportPersonalAccessClient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PassportClientSeeder extends Seeder
{
    /**
     * Seed the Passport clients.
     *
     * @return void
     */
    public function run()
    {
        // Check if the client already exists
        $client = PassportClient::where('name', '10MG Health Personal Access Client')->first();

        if (! $client) {
            // Generate a client secret
            $clientSecret = Str::random(40);

            // Create the client
            $client = PassportClient::create([
                'name' => '10MG Health Personal Access Client',
                'user_id' => null,
                'secret' => $clientSecret,
                'redirect' => config('app.url'),
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Check if the personal access client record already exists
        $personalAccessClient = PassportPersonalAccessClient::where('client_id', $client->id)->first();

        if (! $personalAccessClient) {
            // Create the personal access client record
            PassportPersonalAccessClient::create([
                'client_id' => $client->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
