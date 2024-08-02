<?php

namespace SoCli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

/**
 * Defines the Command manager class.
 */
class CommandManager {

  protected Configuration $configuration;

  /**
   * Constructs a Command manager object.
   */
  public function __construct() {
    $this->configuration = new Configuration();
  }

  /**
   * Returns global commands.
   *
   * @return array
   *   The array of commands objects.
   */
  public function getGlobalCommands(): array {
    $global_config_dir = $this->configuration->getGlobalConfigDir();

    return $this->getCommandsFromFolder($global_config_dir);
  }

  /**
   * Returns local commands.
   *
   * @return array
   *   The array of commands objects.
   */
  public function getLocalCommands(): array {
    $config_dir = $this->configuration->getConfigDir();
    if (!$config_dir) {
      return [];
    }
    return $this->getCommandsFromFolder($config_dir);
  }

  /**
   * Returns combined global and local commands.
   *
   * @return array
   *   The array of commands objects.
   */
  public function getCommands(): array {
    $global_commands = $this->getGlobalCommands();
    $local_commands = $this->getLocalCommands();
    // @todo Handle same alias for different commands.
    $commands = array_merge($global_commands, $local_commands);
    return array_values($commands);
  }

  /**
   * Returns commands for a given folder.
   *
   * @param string $config_dir
   *   The path to folder for scanning for commands.
   *
   * @return array
   *   The array of commands objects.
   */
  public function getCommandsFromFolder(string $config_dir): array {
    $commands = [];
    $config = $this->configuration->loadConfigFile();
    // Load env files.
    if (isset($config['env_file'])) {
      $dotenv = new Dotenv();
      foreach ($config['env_file'] as $env_file) {
        $env_file = realpath($this->configuration->getConfigDir() . '/' . $env_file);
        if ($env_file) {
          $dotenv->load($env_file);
        }
      }
    }

    $command_configs = $this->configuration->loadCommandConfigFiles($config_dir);
    foreach ($command_configs as $command_config) {
      $command = new Command();
      $command->setName($command_config['name']);

      if (isset($command_config['aliases'])) {
        $command->setAliases($command_config['aliases']);
      }

      if (!empty($command_config['description'])) {
        $command->setDescription($command_config['description']);
      }

      // @todo Handle the configuration values.
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
          if (!empty($option['type'])) {
            if ($option['type'] == 'array') {
              $type = $required | InputOption::VALUE_IS_ARRAY;
            }
            else if ($option['type'] == 'string') {
              $type = $required;
            }
          }

          $command->addOption(
            $option['name'],
            $option['shortcut'],
            $type,
            $option['description'],
            $option['default'] ?? NULL,
            $option['suggested_values'] ?? [],
          );
        }
      }

      $command->setCode(function (InputInterface $input, OutputInterface $output) use ($command_config): int {
        // @todo check how many arguments (required, optional).
        $arguments = [
          'so_args' => implode(' ', $input->getArgument('arguments')),
          'so_command' => substr($input->__toString(), strlen($input->getFirstArgument()) + 1),
        ];

        foreach ($input->getArgument('arguments') as $key => $argument) {
          $arguments['so_arg_' . $key + 1] = $argument;
        }

        $local_config_dir = $this->configuration->getConfigDir();

        $process = Process::fromShellCommandline($command_config['command']);
        $process->setTty(TRUE);
        $process->setTimeout(NULL);
        $process->run(function ($type, $buffer) {
          echo $buffer;
        }, $_ENV + $input->getOptions() + $arguments + [
          'so_project_root' => realpath($local_config_dir . '/..' ?? '.'),
        ]);

        // Return actual exit code for managing command chaining.
        return $process->getExitCode();
      });

      $commands[$command->getName()] = $command;
    }

    return $commands;
  }

}
