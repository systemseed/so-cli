<?php

namespace SoCli;

use Symfony\Component\Yaml\Yaml;

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
    // TODO: Print iterated folders in verbose mode ($output->isVerbose()).
    if (is_dir($config_dir)) {
      return $config_dir;
    }
    else if ($current_dir != '/') {
      $parent_dir = realpath($current_dir . '/..');
      if (!$parent_dir) {
        return null;
      }

      return $this->locateConfigDir($parent_dir);
    }

    return null;
  }

  /**
   * Returns config files folder.
   *
   * @return string|null
   *   The config files folder.
   */
  public function getConfigDir(): ?string {
    return $this->configDir;
  }

  /**
   * Returns global config folder.
   *
   * @return string|null
   *   The global config folder.
   */
  public function getGlobalConfigDir(): ?string {
    $config = $this->loadConfigFile();

    return $config['global_config_folder'] ?? Constants::SO_CLI_GLOBAL_CONFIG_FOLDER;
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
  public function loadCommandConfigFiles(): array {
    if (!$this->configDir) {
      return [];
    }

    $commands = [];
    foreach (glob($this->configDir . '/*.command.yaml') as $file_path) {
      $commands[] = Yaml::parseFile($file_path);
    }

    return $commands;
  }

}