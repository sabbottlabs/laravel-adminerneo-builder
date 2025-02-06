# AdminerNeo Builder

Build tool for compiling AdminerNeo v5 from source.

## Overview
Handles compilation of AdminerNeo v5:
- Downloads source code
- Compiles with selected features
- Outputs ready-to-use build

## Requirements
- PHP 8.1+
- Git
- PDO extension

## Usage
```bash
./scripts/build
```

## Output
- output/adminer.php - Compiled AdminerNeo
- output/version.txt - Build info
- output/README.md - Documentation
- output/LICENSE.md - License

Note: Plugin feature is not being supported due to AdminerNeo current dev path.

## License
Apache License 2.0 or GPL v2
