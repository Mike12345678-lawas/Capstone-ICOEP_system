<?php

namespace App\Services;

use Illuminate\Foundation\Vite as BaseVite;

class DynamicHostVite extends BaseVite
{
    /**
     * Get the path to a given asset when running in HMR mode.
     * Dynamically swaps 0.0.0.0 or localhost with the current request host
     * so assets load seamlessly on both the host machine and mobile/LAN devices.
     */
    protected function hotAsset($asset)
    {
        $url = parent::hotAsset($asset);

        if (app()->bound('request')) {
            $host = request()->getHost();
            if ($host && !in_array($host, ['0.0.0.0', '::', ''])) {
                $url = preg_replace(
                    '#^(https?://)(?:0\.0\.0\.0|localhost|127\.0\.0\.1)(:\d+)?#',
                    '${1}' . $host . '${2}',
                    $url
                );
            }
        }

        return $url;
    }
}
