<?php

namespace Tomato\Command\Git;

use Github\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tomato\Command\AbstractCommand;

class Projects extends AbstractCommand
{
    /**
     * @var Client
     */
    protected $git;

    /**
     * @var array
     */
    protected $config;

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('git:list-projects')
            ->setDescription('List all git projects');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Client $git */
        $this->git = $this->container['service:git'];
        $this->config = $this->container['config'];

        $page = 1;

        while ($repos = $this->git->organization()->repositories($this->config['service']['git']['company'], 'all', $page++)) {
            foreach ($repos as $repo) {
                try {
                    $composer = $this->git->repo()->contents()->show($repo['owner']['login'], $repo['name'], 'composer.json', 'master');
                } catch (\Exception $e) {
                    continue;
                }
                $output->writeln($repo['html_url']);
//                $output->writeln(print_r($composer, 1));
            }
        }
    }
}
