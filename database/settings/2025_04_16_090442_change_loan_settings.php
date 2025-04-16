<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->rename('general.lenders_interest', 'loan.lenders_interest');
        $this->migrator->rename('general.tenmg_interest', 'loan.tenmg_interest');
    }
};
