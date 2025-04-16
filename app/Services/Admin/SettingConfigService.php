<?php

namespace App\Services\Admin;

use App\Repositories\SettingConfigRepository;
use Illuminate\Http\Request;

class SettingConfigService
{

    function __construct( private SettingConfigRepository $settingConfigRepository){}

    public function getAllSettings()
    {
        return $this->settingConfigRepository->getAllSettings();
    }

    public function updateSettingsConfig(Request $request)
    {
        return $this->settingConfigRepository->updateSettingsConfig($request);
    }

}
