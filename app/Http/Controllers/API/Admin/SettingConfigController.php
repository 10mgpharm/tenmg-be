<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingConfigService;
use Illuminate\Http\Request;

class SettingConfigController extends Controller
{
    function __construct(private SettingConfigService $settingConfigService){}

    public function getAllSettings()
    {
        return $this->settingConfigService->getAllSettings();

    }

    public function updateSettingsConfig(Request $request)
    {
        return $this->settingConfigService->updateSettingsConfig($request);
    }

}
