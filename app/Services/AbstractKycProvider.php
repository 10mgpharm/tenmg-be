<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ServiceProvider;
use Illuminate\Support\Facades\Log;

abstract class AbstractKycProvider
{
    /**
     * Get the database provider ID by looking up the service provider using the slug
     */
    public function getDatabaseProviderId(): ?string
    {
        try {
            // Get the config slug that's used to look up the provider in the database
            $configSlug = config('services.'.$this->getProviderSlug().'.database_slug');

            // If no specific database slug is configured, use the provider slug
            $lookupSlug = $configSlug ?? $this->getProviderSlug();

            $provider = ServiceProvider::where('slug', $lookupSlug)->first();

            return $provider ? $provider->id : null;
        } catch (\Exception $e) {
            Log::error("Failed to get {$this->getProviderName()} database ID: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Get the provider display name
     * Default implementation uses capitalized slug, but can be overridden
     */
    public function getProviderName(): string
    {
        return ucfirst($this->getProviderSlug());
    }

    /**
     * Get the provider slug/identifier.
     * This method must be implemented by concrete classes.
     */
    abstract public function getProviderSlug(): string;

    /**
     * Check if provider is Fincra
     */
    public function isFincra(): bool
    {
        return $this->getProviderSlug() === 'fincra';
    }

    /**
     * Check if provider is SafeHaven
     */
    public function isSafeHaven(): bool
    {
        return $this->getProviderSlug() === 'safehaven';
    }
}
