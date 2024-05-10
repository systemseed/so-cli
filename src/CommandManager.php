<?php

namespace SoCli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

class CommandManager {

  protected Configuration $configuration;

  public function __construct() {
    $this->configuration = new Configuration();
  }

  public function getCommands(): array {
    $config_dir = $this->configuration->getConfigDir();
    $config = $this->configuration->loadConfigFile();
    $command_configs = $this->configuration->loadCommandConfigFiles();

    // Load env files.
    if (isset($config['env_file'])) {
      $dotenv = new Dotenv();
      foreach ($config['env_file'] as $env_file) {
        $env_file = realpath($config_dir . '/' . $env_file);
        if ($env_file) {
          $dotenv->load($env_file);
        }
      }
    }

    $commands = [];
    foreach ($command_configs as $command_config) {
      $command = new Command();
      $command->setName($command_config['name']);

      if (isset($command_config['aliases'])) {
        $command->setAliases($command_config['aliases']);
      }

      if (!empty($command_config['description'])) {
        $command->setDescription($command_config['description']);
      }

      // TODO: Handle the configuration values.
      $command->addArgument(
        'arguments',
        InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
        'List of optional arguments.'
      );

      $command->ignoreValidationErrors();
      if (isset($command_config['options'])) {
        foreach ($command_config['options'] as $option) {
          $type = InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE;

          $required = $option['required'] ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
          if ($option['type'] == 'array') {
            $type = $required | InputOption::VALUE_IS_ARRAY;
          }
          else {
            if ($option['type'] == 'string') {
              $type = $required;
            }
          }

          $command->addOption(
            $option['name'],
            $option['shortcut'],
            $type,
            $option['description'],
            $option['default'] ?? null,
            $option['suggested_values'],
          );
        }
      }

      $command->setCode(function(InputInterface $input, OutputInterface $output) use ($config_dir, $command_config): int {
        // TODO check how many arguments (required, optional).
        $arguments = [
          'args' => implode(' ', $input->getArgument('arguments')),
          'command' => substr($input->__toString(), strlen($input->getFirstArgument()) + 1)
        ];

        foreach ($input->getArgument('arguments') as $key => $argument) {
          $arguments['arg_' . $key + 1] = $argument;
        }

        $process = Process::fromShellCommandline($command_config['command']);
        $process->setTty(TRUE);
        $process->setTimeout(NULL);
        $process->run(function ($type, $buffer) {
          echo $buffer;
        }, $_ENV + $input->getOptions() + $arguments + [
          'project_root' => realpath($config_dir . '/..' ?? '.')
        ]);

        return Command::SUCCESS;
      });

      $commands[] = $command;
    }

    return $commands;
  }

}