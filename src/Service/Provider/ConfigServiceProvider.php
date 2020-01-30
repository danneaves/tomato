<?php

namespace Tomato\Service\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ConfigServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $pimple['config'] = function () {
            $file = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'tomato.json';
            $config = [
                'branches' => [],
                'projects' => [],
            ];

            if (file_exists($file)) {
                $configFile = json_decode(file_get_contents($file), true);
                $config['branches'] = $configFile['branches'] ?? [];
                $config['projects'] = $configFile['projects'] ?? [];
            }

            $config = array_merge($config, [
                'service' => [
                    'git' => [
                        'company' => getenv('TOMATO_GIT_ORG') ?: '',
                        'username' => getenv('TOMATO_GIT_USER') ?: '',
                        'password' => getenv('TOMATO_GIT_PASS') ?: '',
                    ],
                    'console-output' => [
                        'title' => [
                            'fore' => 'green',
                            'options' => ['bold'],
                        ],
                    ],
                ],
            ]);

            $all = [];
            if (!empty($config['projects'])) {
                $all = array_merge(...array_values($config['projects']));
                sort($all);
            }

            $config['projects']['all'] = array_unique($all);
            return $config;
        };
    }
}
