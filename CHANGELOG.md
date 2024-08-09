# Shell Orchestration CLI changelog

All notable changes to so-sli will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

- Improved flag handling
- Improved returning of exit code
- Added support of symlinks for searching commands
- Fixed determining of project root folder in case of using global command
- Improved 'so-cli:pull-commands' command to support both, git repo and zip archive URLs
- Added "so" prefix to internal arguments
- Implemented recursive scanning for command files in config folder
- Implemented scanning for global commands
- Implemented simple (by command `name` value) overriding of "global" commands by "local" commands

## [0.0.3]

- Updated Symfony dependencies
- Fixed cli version display

## [0.0.2]

- Added installation instructions to Readme

## [0.0.1]

- Initial release
