<?php

namespace SoCli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class CommandManager {

  public function getCommands(): array {
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
          $option['default'],
          $option['suggested_values'],
        );
      }

      $command->setCode(function(InputInterface $input, OutputInterface $output) use ($command_config): int {
        // TODO check how many arguments (required, optional).
        $arguments = ['args' => implode(' ', $input->getArgument('arguments'))];
        foreach ($input->getArgument('arguments') as $key => $argument) {
          $arguments['arg_' . $key + 1] = $argument;
        }

        $process = Process::fromShellCommandline($command_config['command']);
        $process->run(NULL, $input->getOptions() + $arguments);
        $output->write($process->getOutput());

        return Command::SUCCESS;
      });

      $commands[] = $command;
    }

    return $commands;
  }

  /**
   * Returns commands from config files.
   *
   * @return array
   *   The commands config array.
   */
  protected function loadCommandConfigFiles(): array {
    $config_dir = $this->getConfigDir(getcwd());
    if (!$config_dir) {
      // TODO: Notify user that no config file found and so on.
      return [];
    }
    $commands = [];
    foreach (glob($config_dir . '/*.command.yaml') as $file_path) {
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