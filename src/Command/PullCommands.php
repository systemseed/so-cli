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

#[AsCommand(
  name: 'so-cli:pull-commands',
  description: 'Pulls commands from git repository.',
)]
class PullCommands extends Command {

  protected Configuration $configuration;

  public function __construct() {
    parent::__construct();
    $this->configuration = new Configuration();
  }

  protected function configure(): void {
    $this->addArgument('url', InputArgument::REQUIRED, 'Git repository URL');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int {
    $global_config_dir = $this->configuration->getGlobalConfigDir();
    // Unfortunately realpath doesn't handle '~' in path.
    if (str_starts_with($global_config_dir, '~')) {
      $global_config_dir = $_SERVER['HOME'] . substr($global_config_dir, 1);
    }
    if (!is_dir($global_config_dir)) {
      $output->writeln('<comment>Creating configuration folder...</comment>');
      mkdir($global_config_dir, 0777, TRUE);
    }

    $output->writeln('<comment>Pulling commands...</comment>');
    $url = $input->getArgument('url');
    // TODO: Download zip and unpack it instead of cloning to exclude git dependency.
    $command = [
      'git',
      'clone',
      $url,
    ];
    $process = Process::fromShellCommandline(implode(' ', $command), $global_config_dir);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new ProcessFailedException($process);
    }

    $output->writeln('<comment>Done.</comment>');
    return Command::SUCCESS;
  }
}
