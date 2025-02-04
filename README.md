# Laravel AdminerNeo Builder

A build tool for creating customized AdminerNeo v5 builds for Laravel integration.

## Overview
This repository handles the compilation and build process for AdminerNeo v5. It:
- Clones the official AdminerNeo repository
- Runs the build process
- Processes plugins/themes (optional)
- Outputs a ready-to-use AdminerNeo build
- Tracks version information

## Requirements
- PHP 8.1+
- Git
- PDO extension

## Installation
```bash
# Clone the repository
git clone git@sabbottlabs:sabbottlabs/laravel-adminerneo-builder.git
cd laravel-adminerneo-builder
```

## Usage
```bash
# Run the build process
./scripts/build
```

## Build Output
The build process creates:
- output/adminer.php - Main AdminerNeo file
- output/version.txt - Build information
- output/plugins - Plugin directory
- output/LICENSE.md - License information
- output/README.md - AdminerNeo documentation

## Version Control
The output/ directory is ignored except for:
- output/.gitkeep files to maintain structure
- `output/plugins/` directory for custom plugins

## Integration
This repository is designed to be used as a submodule in the main `laravel-adminerneo` package.

## License
Same as AdminerNeo - Apache License 2.0 or GPL v2
