<?php

namespace Tomato\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class Config extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('config')
            ->setDescription('Configure the branching and project lists')
            ->addOption('filename', 'f', InputOption::VALUE_OPTIONAL, 'Filename to write config', 'tomato.json')
            ->addOption('branches', 'b', InputOption::VALUE_OPTIONAL, 'Configure branches only', false)
            ->addOption('projects', 'p', InputOption::VALUE_OPTIONAL, 'Configure projects only', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $appConfig = $this->container['config'];
        $filename = $input->getOption('filename');
        $processBranches = (bool)$input->getOption('branches');
        $processProjects = (bool)$input->getOption('projects');

        if (!$processBranches && !$processProjects) {
            $processBranches = $processProjects = true;
        }

        $helper = $this->getHelper('question');

        $config = array_intersect_key($appConfig, ['branches' => 0, 'projects' => 1]);

        if ($processBranches) {
            $question = new Question(
                '<info>Enter your comma-separated branching strategy starting from master e.g. master,uat,test;</info> '
                . '[' . implode(',', $config['branches']) . '] '
            );

            if (null !== $branches = $helper->ask($input, $output, $question)) {
                $branches = explode(',', trim($branches));
                array_map('trim', $branches);

                $config['branches'] = $branches;
            }
        }

        if ($processProjects) {
            $projectsConfig = [];
            $groupQuestion = new Question('<info>Enter a project scope e.g. project</info> [exit];');

            while (!empty($group = $helper->ask($input, $output, $groupQuestion))) {
                $group = trim($group);
                $default = $config['projects'][$group] ?? [];

                $question = new Question(
                    '<info>Enter your comma-separated project names e.g. project-1,project-2;</info> '
                    . (empty($default) ? '' : ('[' . implode(',', $default) . ']'))
                );

                $projects = $helper->ask($input, $output, $question);
                $projects = ($projects === null ? [] : explode(',', trim($projects)));
                array_map('trim', $projects);

                $projectsConfig[$group] = empty($projects) ? $default : $projects;
            }

            $config['projects'] = $projectsConfig;
        }

        file_put_contents(
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $filename,
            json_encode($config, JSON_PRETTY_PRINT)
        );

        $output->writeln('<info>Success!</info>');
    }
}
