<?php

namespace SoCli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class CommandManager
{

    public function getCommands()
    {
        $command_configs = $this->loadCommandConfigFiles();

        $commands = [];
        foreach ($command_configs as $command_config) {
            $command = new Command();
            $command->setName($command_config['name']);
            $command->setAliases($command_config['aliases']);

            if (!empty($command_config['description'])) {
              $command->setDescription($command_config['description']);
            }

            // TODO: Handle the configuration values.
            $command->addArgument(
                'arguments',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'List of optional arguments.'
            );
            foreach ($command_config['options'] as $option) {
                $type = InputOption::VALUE_NONE|InputOption::VALUE_NEGATABLE;

                $required = $option['required'] ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
                if ($option['type'] == 'array') {
                    $type = $required|InputOption::VALUE_IS_ARRAY;
                }
                else if($option['type'] == 'string') {
                    $type = $required;
                }

                $command->addOption(
                    $option['name'],
                    $option['shortcut'],
                    $type,
                    $option['description'],
                    $option['default'],
                    $option['suggested_values'],
                );
            }

            $command->setCode(function (InputInterface $input, OutputInterface $output) use ($command_config): int {
                // TODO check how many arguments (required, optional).
                $arguments = ['args' => implode(' ', $input->getArgument('arguments'))];
                foreach ($input->getArgument('arguments') as $key => $argument) {
                    $arguments['arg_' . $key + 1] = $argument;
                }
                $process = Process::fromShellCommandline($command_config['command']);
                $process->run(null, $input->getOptions() + $arguments);
                $output->write($process->getOutput());

                return Command::SUCCESS;
            });

            $commands[] = $command;
        }

        return $commands;
    }

    /**
     * @throws \Exception
     */
    protected function loadCommandConfigFiles()
    {

        $file_path = './' . Constants::SO_CLI_COMMAND_CONFIG_FOLDER . '/hello_world.command.yaml';

        $command = Yaml::parseFile($file_path);

        return [$command];
    }
}