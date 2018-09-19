<?php

namespace Tomato\Command;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Deploy extends AbstractCommand
{
    protected static $commands = [
        'sudo git fetch -p',
        'sudo git stash',
        'sudo git checkout %2$s',
        'sudo git pull',
        'sudo git stash pop',
        'sudo composer update',
    ];

    protected function configure()
    {
        $user = get_current_user();
        $keyPath = '/home/' . $user . '/.ssh/id_rsa';

        $this->setName('deploy')
            ->setDescription('Deploy to a given server over ssh - extremely experimental!!')
            ->addArgument('server', InputArgument::REQUIRED, 'The server hostname')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Branch to deploy')
            ->addOption('key', 'k', InputOption::VALUE_OPTIONAL, 'Key file location', $keyPath)
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Username', $user)
            ->addOption('pass', null, InputOption::VALUE_OPTIONAL, 'Password')
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
        $config = $this->container['config'];

        $server = $input->getArgument('server');
        $user = $input->getOption('user');
        $key = $input->getOption('key');
        $branch = $input->getOption('branch');
        $pass = $input->getOption('pass');

        /** @var array $projects */
        $projects = [] === $input->getOption('projects')
            ? $config['projects']['root']
            : $input->getOption('projects');

        if (null === $pass) {
            $pass = new RSA();
            $pass->loadKey(file_get_contents($key));
        }

        foreach ($projects as $project) {
            $pid = pcntl_fork();

            if (!$pid) {
                $output->writeln('<info>Forking project: ' . $project . '</info>' . PHP_EOL);

                $ssh = new SSH2($server);

                $output->writeln(
                    '<info>(' . $project . ') Attempting login as ' . $user . '@' . $server . '</info>' . PHP_EOL
                );
                if (!$ssh->login($user, $pass)) {
                    $output->writeln('<error>Login failed!: ' .
                        ($ssh->isConnected() ? 'Bad username or password' : 'Unable to establish connection') .
                        '</error>' . PHP_EOL);
                    exit(255);
                }

                foreach (self::$commands as $command) {
                    $exec = sprintf('cd /var/www/%1$s; ' . $command, $project, $branch);
                    $output->writeln('<info>(' . $project . ') Executing: ' . $exec . '</info>' . PHP_EOL);

                    $ssh->exec($exec, function ($out) use ($output, $project) {
                        if (preg_replace('/[^A-Za-z0-9\-]/', '', $out)) {
                            $output->writeln('<info>(' . $project . ') ' . $out . '</info>');
                        }
                    });

                    if ($error = $ssh->getLastError()) {
                        $output->writeln('<error>(' . $project . ') ' . $error . '</error>' . PHP_EOL);
                        break;
                    }
                }

                exit(0);
            }
        }

        foreach ($projects as $project) {
            pcntl_wait($status);
        }

        $output->writeln('<info>Complete!</info>' . PHP_EOL);
    }
}
