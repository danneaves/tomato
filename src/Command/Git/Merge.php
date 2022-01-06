<?php

namespace Tomato\Command\Git;

use Github\Api\Repo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tomato\Command\AbstractGitCommand;

class Merge extends AbstractGitCommand
{
    protected function configure()
    {
        $this->setName('git:merge')
            ->setDescription('Merge from one branch to another using the provided parameters')
            ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'Source branch')
            ->addOption('destination', 'd', InputOption::VALUE_REQUIRED, 'Destination branch')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'Project scope: all|root|sub', 'all')
            ->addOption(
                'projects',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Array of projects, comma separated',
                []
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $source = $input->getOption('source');
        $destination = $input->getOption('destination');
        $scope = $input->getOption('scope');

        /** @var array $projects */
        $projects = [] === $input->getOption('projects')
            ? $this->config['projects'][$scope]
            : $input->getOption('projects');

        foreach ($projects as $project) {
            $output->writeln(
                '<title>Merging in ' . $project . ': ' . $source . ' to ' . $destination . '</title>'
            );

            /** @var Repo $repo */
            $repo = $this->gitHubClient->api('repo');

            try {
                $response = $repo->merge(
                    $this->config['service']['git']['company'],
                    $project,
                    $destination,
                    $source,
                    'Automated merge from ' . $source . ' to ' . $destination
                );
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>' . PHP_EOL);
                continue;
            }

            $message = isset($response['html_url']) ? $response['html_url'] : 'Already up to date';
            $output->writeln('<info>' . $message . '</info>' . PHP_EOL);
        }
    }
}
