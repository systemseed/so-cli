<?php

namespace SoCli\Command;

use SoCli\Configuration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Defines the Pull commands command.
 */
#[AsCommand(
  name: 'so-cli:pull-commands',
  description: 'Pulls commands from git repository.',
)]
class PullCommands extends Command {

  protected Configuration $configuration;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->configuration = new Configuration();
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->addArgument('url', InputArgument::REQUIRED, 'Git repository URL');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $global_config_dir = $this->configuration->getGlobalConfigDir();
    // Unfortunately realpath doesn't handle '~' in path.
    if (str_starts_with($global_config_dir, '~')) {
      $global_config_dir = $_SERVER['HOME'] . substr($global_config_dir, 1);
    }
    if (!is_dir($global_config_dir)) {
      // @todo Print only in verbose mode.
      $output->writeln('<comment>Creating configuration folder "' . $global_config_dir . '" ...</comment>');
      mkdir($global_config_dir, 0777, TRUE);
    }

    // @todo Print only in verbose mode.
    $output->writeln('<comment>Pulling commands to "' . $global_config_dir . '" ...</comment>');
    $url = $input->getArgument('url');
    // @todo Download zip and unpack it instead of cloning to exclude git dependency.
    // @todo Replace md5($url) with real folder name and allows to pass destination folder.
    // @todo Do not ever remove anything without user confirmation.
    //   Just stop execution and notify the user instead. We don't have to solve such problems.
    $process = Process::fromShellCommandline('rm -rf ' . md5($url), $global_config_dir);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $command = [
      'git',
      'clone',
      $url,
      md5($url),
    ];
    $process = Process::fromShellCommandline(implode(' ', $command), $global_config_dir);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    // @todo Print only in verbose mode.
    $output->writeln('<comment>Done.</comment>');
    return Command::SUCCESS;
  }

}
