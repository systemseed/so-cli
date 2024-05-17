<?php

namespace SoCli\Command;

use SoCli\Configuration;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Defines the Pull commands command.
 */
#[AsCommand(
  name: 'so-cli:pull-commands',
  description: 'Pulls commands from git repository or zip archive.',
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
    $this->addArgument('url', InputArgument::REQUIRED, 'URL of git repository or zip archive');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $url = $input->getArgument('url');

    if ($output->isVerbose()) {
      $output->writeln('<comment>Processing URL: ' . $url . ' ...</comment>');
    }

    if (preg_match('/\.zip$/', $url)) {
      $this->downloadZipArchive($url, $output);
    } else {
      $this->cloneGitRepository($url, $output);
    }

    return Command::SUCCESS;
  }

  /**
   * Downloads and extracts zip archive.
   *
   * @param string $url
   *   The URL of zip archive.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream.
   *
   * @return void
   *   The nothing.
   */
  private function downloadZipArchive(string $url, OutputInterface $output): void {
    $global_config_dir = $this->configuration->getGlobalConfigDir();

    if ($output->isVerbose()) {
      $output->writeln('<comment>Downloading zip archive...</comment>');
    }

    $archive_name = basename($url, '.zip');
    $destination_dir = $global_config_dir . '/' . $archive_name;
    $zip_file = sys_get_temp_dir() . '/' . $archive_name . '.zip';

    $filesystem = new Filesystem();
    if (!$filesystem->exists($global_config_dir)) {
      if ($output->isVerbose()) {
        $output->writeln('<comment>Creating configuration folder ' . $global_config_dir . ' ...</comment>');
      }
      $filesystem->mkdir($global_config_dir);
    }

    file_put_contents($zip_file, fopen($url, 'r'));

    $zip = new \ZipArchive();
    if ($zip->open($zip_file) === TRUE) {
      $zip->extractTo($destination_dir);
      $zip->close();
      if ($output->isVerbose()) {
        $output->writeln('<comment>Zip archive extracted to: ' . $destination_dir . '</comment>');
      }
    } else {
      $output->writeln('<error>Failed to open zip archive.</error>');
    }

    $filesystem->remove($zip_file);
  }

  /**
   * Clones git repository.
   *
   * @param string $url
   *   The URL of git repository.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output stream.
   *
   * @return void
   *   The nothing.
   */
  private function cloneGitRepository(string $url, OutputInterface $output): void {
    $global_config_dir = $this->configuration->getGlobalConfigDir();

    if ($output->isVerbose()) {
      $output->writeln('<comment>Cloning git repository...</comment>');
    }

    $repo_name = basename($url, '.git');
    $destination_dir = $global_config_dir . '/' . $repo_name;

    $filesystem = new Filesystem();
    if (!$filesystem->exists($global_config_dir)) {
      if ($output->isVerbose()) {
        $output->writeln('<comment>Creating configuration folder ' . $global_config_dir . ' ...</comment>');
      }
      $filesystem->mkdir($global_config_dir);
    }

    $process = new Process(['git', 'clone', $url, $destination_dir]);
    $process->run();

    if (!$process->isSuccessful()) {
      $output->writeln('<error>Failed to clone repository: ' . $process->getErrorOutput() . '</error>');
    } else if ($output->isVerbose()) {
      $output->writeln('<comment>Repository cloned successfully to: ' . $destination_dir . '</comment>');
    }
  }

}
