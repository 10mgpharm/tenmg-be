<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.lenders_interest', 15);
        $this->migrator->add('general.tenmg_interest', 5);
    }
};
