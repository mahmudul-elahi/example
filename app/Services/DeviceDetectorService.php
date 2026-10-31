<?php

declare(strict_types=1);

namespace App\Services;

use Jenssegers\Agent\Agent;

class DeviceDetectorService implements DeviceDetectorServiceInterface
{
    public function detect(?string $userAgent = null): string
    {
        $userAgent ??= request()->userAgent();

        if (! $userAgent) {
            return 'Web Browser';
        }

        $agent = new Agent;
        $agent->setUserAgent($userAgent);

        if ($agent->isRobot()) {
            $robot = $agent->robot();

            return is_string($robot) && $robot !== '' ? $robot : 'Bot';
        }

        $platform = $agent->platform();
        $browser = $agent->browser();

        $platformName = is_string($platform) && $platform !== '' ? $platform : 'Unknown OS';
        $browserName = is_string($browser) && $browser !== '' ? $browser : 'Browser';

        if ($agent->isPhone() || $agent->isTablet()) {
            $device = $agent->device();
            if (is_string($device) && $device !== '' && $device !== 'WebKit') {
                return "{$device} ({$browserName})";
            }

            return "{$browserName} on {$platformName}";
        }

        return "{$browserName} on {$platformName}";
    }
}
