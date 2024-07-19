<?php

namespace SoCli;

use Symfony\Component\Yaml\Yaml;

/**
 * Defines the Configuration class.
 */
class Configuration {

  protected ?string $configDir;

  public function __construct() {
    $this->configDir = $this->locateConfigDir(getcwd());
  }

  /**
   * Locates config files folder.
   *
   * @param string $current_dir
   *   The current folder path.
   *
   * @return string|null
   *   The config files folder.
   */
  protected function locateConfigDir(string $current_dir): ?string {
    $config_dir = $current_dir . '/' . Constants::SO_CLI_CONFIG_FOLDER_NAME;
    // @todo Print iterated folders in verbose mode ($output->isVerbose()).
    if (is_dir($config_dir)) {
      return $config_dir;
    }
    elseif ($current_dir != '/') {
      $parent_dir = realpath($current_dir . '/..');
      if (!$parent_dir) {
        return NULL;
      }

      return $this->locateConfigDir($parent_dir);
    }

    return NULL;
  }

  /**
   * Returns project config folder.
   *
   * @return string|null
   *   The project config folder.
   */
  public function getConfigDir(): ?string {
    return $this->configDir;
  }

  /**
   * Returns global config folder.
   *
   * @return string
   *   The global config folder.
   */
  public function getGlobalConfigDir(): string {
    $config = $this->loadConfigFile();
    $global_config_dir = $config['global_config_folder'] ?? Constants::SO_CLI_GLOBAL_CONFIG_FOLDER;
    // Unfortunately realpath doesn't handle '~' in path.
    if (str_starts_with($global_config_dir, '~')) {
      $global_config_dir = $_SERVER['HOME'] . substr($global_config_dir, 1);
    }

    return $global_config_dir;
  }

  /**
   * Returns configurations.
   *
   * @return array
   *   The config array.
   */
  public function loadConfigFile(): array {
    if (!$this->configDir) {
      return [];
    }

    $config_file = realpath($this->configDir . '/config.yaml');
    if ($config_file) {
      return Yaml::parseFile($config_file) ?? [];
    }

    return [];
  }

  /**
   * Recursively scans and returns list of command config files.
   *
   * @param string $dir
   *   The path to folder for scanning for commands.
   *
   * @return \Generator
   *   The list of command config file paths.
   */
  private function getCommandConfigFiles(string $dir): \Generator {
    if (!is_dir($dir)) {
      throw new \RuntimeException("{$dir} is not a directory");
    }

    $it = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::FOLLOW_SYMLINKS);
    $it = new \RecursiveIteratorIterator($it);
    $it = new \RegexIterator($it, '/\.command\.yaml$/', \RegexIterator::MATCH);

    yield from $it;
  }

  /**
   * Returns commands from config files.
   *
   * @param string $commands_dir
   *   The path to commands folder.
   *
   * @return array
   *   The commands config array.
   */
  public function loadCommandConfigFiles(string $commands_dir): array {
    if (!$commands_dir) {
      return [];
    }

    $commands = [];
    foreach ($this->getCommandConfigFiles($commands_dir) as $file) {
      $commands[] = Yaml::parseFile($file->getPathname());
    }

    return $commands;
  }

}
