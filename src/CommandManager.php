<?php

namespace SoCli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class CommandManager {

  protected $configDir;

  public function __construct() {
    $this->configDir = $this->getConfigDir(getcwd());
  }

  public function getCommands(): array {
    $config = $this->loadConfigFile();
    $command_configs = $this->loadCommandConfigFiles();

    // Load env files.
    if (isset($config['env_file'])) {
      $dotenv = new Dotenv();
      foreach ($config['env_file'] as $env_file) {
        $env_file = realpath($this->configDir . '/' . $env_file);
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
      if (isset($command_config['options'])) {
        foreach ($command_config['options'] as $option) {
          $type = InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE;

          $required = $option['value_required'] ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL;
          if ($option['value_type'] == 'array') {
            $type = $required | InputOption::VALUE_IS_ARRAY;
          }
          else {
            if ($option['value_type'] == 'string') {
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

      $command->setCode(function(InputInterface $input, OutputInterface $output) use ($command_config): int {
        // TODO check how many arguments (required, optional).
        $arguments = ['args' => implode(' ', $input->getArgument('arguments'))];
        foreach ($input->getArgument('arguments') as $key => $argument) {
          $arguments['arg_' . $key + 1] = $argument;
        }

        $process = Process::fromShellCommandline($command_config['command']);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) use ($output) {
          $output->setDecorated(true);
          $output->write($buffer);
        }, $_ENV + $input->getOptions() + $arguments + [
          'project_root' => realpath($this->configDir . '/..' ?? '.')
        ]);

        return Command::SUCCESS;
      });

      $commands[] = $command;
    }

    return $commands;
  }

  /**
   * Returns configurations.
   *
   * @return array
   *   The config array.
   */
  protected function loadConfigFile() {
    if (!$this->configDir) {
      return [];
    }

    $config_file = realpath($this->configDir . '/config.yaml');
    if ($config_file) {
      return Yaml::parseFile($config_file);
    }

    return [];
  }

  /**
   * Returns commands from config files.
   *
   * @return array
   *   The commands config array.
   */
  protected function loadCommandConfigFiles(): array {
    if (!$this->configDir) {
      return [];
    }

    $commands = [];
    foreach (glob($this->configDir . '/*.command.yaml') as $file_path) {
      $commands[] = Yaml::parseFile($file_path);
    }

    return $commands;
  }

  /**
   * Returns config files folder.
   *
   * @param string $current_dir
   *   The current folder path.
   *
   * @return string|null
   *   The commands config array.
   */
  protected function getConfigDir(string $current_dir): ?string {
    $config_dir = $current_dir . '/' . Constants::SO_CLI_CONFIG_FOLDER_NAME;
    // TODO: Print iterated folders in verbose mode ($output->isVerbose()).
    if (is_dir($config_dir)) {
      return $config_dir;
    }
    else if ($current_dir != '/') {
      $parent_dir = realpath($current_dir . '/..');
      if (!$parent_dir) {
        return null;
      }

      return $this->getConfigDir($parent_dir);
    }

    return null;
  }

}