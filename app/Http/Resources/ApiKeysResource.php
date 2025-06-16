<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiKeysResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'key' => $this->key,
            'secret' => $this->secret,
            'webhookUrl' => $this->webhook_url,
            'callbackUrl' => $this->callback_url,
            'testSecret' => $this->test_secret,
            'testKey' => $this->test_key,
            'testWebhookUrl' => $this->test_webhook_url,
            'testCallbackUrl' => $this->test_callback_url,
            'testEncryptionKey' => $this->test_encryption_key,
            'encryptionKey' => $this->encryption_key,
            'business' => $this->business,
            'isTest' => $this->is_test == 1 ? true:false,
            'isActive' => $this->is_active == 1 ? true:false,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
