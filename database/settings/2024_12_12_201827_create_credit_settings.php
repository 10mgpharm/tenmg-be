<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.interest_config', 15);
        $this->migrator->add('general.logo', "logo");
    }
};
