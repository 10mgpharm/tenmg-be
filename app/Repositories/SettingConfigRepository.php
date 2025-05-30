<?php

namespace App\Repositories;

use App\Models\ApiKey;
use App\Settings\CreditSettings;
use App\Settings\LoanSettings;
use Exception;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionProperty;

class SettingConfigRepository
{

    public function getAllSettings()
    {
        $settingsClasses = [
            \App\Settings\CreditSettings::class,
            \App\Settings\LoanSettings::class,
        ];

        return array_reduce($settingsClasses, function (array $carry, string $class) {
            $group = $class::group();
            $settings = app($class);

            $carry[$group] = isset($carry[$group])
                ? array_merge($carry[$group], $this->formatSettings($settings))
                : $this->formatSettings($settings);

            return $carry;
        }, []);
    }

    protected function formatSettings($settings): array
    {
        return collect($settings->toArray())
            ->map(fn ($value, $key) => compact('key', 'value')+ ['group' => $settings::group()])
            ->values()
            ->all();
    }

    public function updateSettingsConfig(Request $request)
    {
        for ($i=0; $i < count($request->settings); $i++) {
            $group = $request->settings[$i]['group'];
            $key = $request->settings[$i]['key'];
            $value = $request->settings[$i]['value'];

            $settings = app($this->getSettingsClass($group));
            $settings->{$key} = $value;
            $settings->save();


        }
        return $this->getAllSettings();

    }

    public function getSettingsClass($group)
    {
        if($group == "general"){
            return CreditSettings::class;
        }elseif($group == "loan"){
            return LoanSettings::class;
        }else{
            throw new \Exception("One or more settings group is Invalid");
        }
    }

}
