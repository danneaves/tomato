<?php

namespace Tomato\Command\Git;

use Github\Api\GitData;
use Github\Client;
use Tomato\Command\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Branch extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('git:branch')
            ->setDescription('Branch from one branch to another using the provided parameters')
            ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'Source branch')
            ->addOption('name', 'a', InputOption::VALUE_REQUIRED, 'Name for the branch')
            ->addOption('scope', null, InputOption::VALUE_OPTIONAL, 'Project scope: all|root|sub', 'all')
            ->addOption('delete', null, InputOption::VALUE_OPTIONAL, 'Delete the branch named in `name`', false)
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
        $name = $input->getOption('name');
        $scope = $input->getOption('scope');
        $isDelete = false !== $input->getOption('delete');

        /** @var array $projects */
        $projects = [] === $input->getOption('projects')
            ? $this->config['projects'][$scope]
            : $input->getOption('projects');

        foreach ($projects as $project) {
            $message = $isDelete
                ? 'Deleting ' . $name . ' branch from ' . $project
                : 'Creating branch from ' . $project . ': ' . $source . ' as ' . $name;
            $output->writeln('<title>' . $message . '</title>');

            /** @var GitData\References $refs */
            $refs = $this->gitHubClient->api('git')->references();

            try {
                if ($isDelete) {
                    $refs->remove($this->config['service']['git']['company'], $project, 'heads/'.$name);
                    $output->writeln('<info>Deleted ' . $name . ' branch from ' . $project . '</info>' . PHP_EOL);
                    continue;
                }
                $branch = $refs->show($this->config['service']['git']['company'], $project, 'heads/'.$source);
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>' . PHP_EOL);
                continue;
            }

            if (!isset($branch['object']['sha'])) {
                $output->writeln('<error>Could not get branch details</error>' . PHP_EOL);
                continue;
            }

            $output->writeln(
                '<info>Branch ' . $source . ' head: ' . $branch['object']['sha'] . '</info>'
            );

            try {
                $newBranch = $refs->create($this->config['service']['git']['company'], $project, [
                    'ref' => 'refs/heads/' . $name,
                    'sha' => $branch['object']['sha'],
                ]);
            } catch (\Exception $e) {
                $output->writeln('<error>' . $e->getMessage() . '</error>' . PHP_EOL);
                continue;
            }

            $output->writeln('<info>' . $newBranch['url'] . '</info>' . PHP_EOL);
        }
    }
}
