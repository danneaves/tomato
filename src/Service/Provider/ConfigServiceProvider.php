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
            $branches = getenv('TOMATO_BRANCHES') ? explode(',', getenv('TOMATO_BRANCHES')) : [];
            $projects = [];
            if (getenv('TOMATO_PROJECTS')) {
                $projectGroups = explode('#', getenv('TOMATO_PROJECTS'));

                foreach ($projectGroups as $group) {
                    list($groupTitle, $groupProjects) = explode('|', $group);

                    $projects[$groupTitle] = explode(',', $groupProjects);
                }
            }

            $config = [
                'branches' => $branches,
                'projects' => $projects,
                'service' => [
                    'git' => [
                        'company' => getenv('TOMATO_GIT_ORG') ?: 'Eagle-Eye-Solutions',
                        'username' => getenv('TOMATO_GIT_USER') ?: 'daniel.neaves@eagleeye.com',
                        'password' => getenv('TOMATO_GIT_PASS') ?: 'GolfZebra123',
                    ],
                    'console-output' => [
                        'title' => [
                            'fore' => 'green',
                            'options' => ['bold'],
                        ],
                    ],
                ],
            ];

            $all = [];
            foreach ($config['projects'] as $project) {
                $all = array_merge($all, $project);
            }

            sort($all);

            $config['projects']['all'] = $all;
            return $config;
        };
    }
}
