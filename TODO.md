# To do list

## Beta

- Add flavour support
- Add project config file support (in progress)
- Add support of dependant commands (implemented for preparing env variables)
- Add support of reusable bash functions?
- Improve and streamline variable naming for arguments and options. Possibly allow changing arg names.
- Allow command override on per project basis (partially implemented, project commands always override global ones, in case of multiple overrides, the last appeared command takes precedence).
- Env variable with project root path.
- To check: Make sure to use only env variables parsed by symfony/dotenv.

## Release Candidate

- Add validation.
- Create the [so-cli.dev](https://so-cli.dev/) website

## Stable Release

- Provide default helper commands (for instance `so:generate`).
- Add hiding command support.
- Add version support.
- Allow command definition by other packages.
- Create schema for yaml commands file (ide autocomplete, possibly validation).

## Future plans

- META: Rewrite on native compilable language (presumably Go).
- Add support of so-called "package manager" for exchanging command files.
- Add support of default values for options and arguments, for example `so sh` may have default argument `php` (currently done on the command level).
