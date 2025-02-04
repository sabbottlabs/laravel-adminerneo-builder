<?php

namespace SabbottLabs\AdminerNeoBuilder;

use RuntimeException;
use Exception;

class Builder
{
    protected array $config;
    protected string $sourceDir;
    protected string $outputDir;
    protected string $branch = 'version-5';
    protected array $errors = [];
    protected array $buildInfo = [];
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->sourceDir = $config['source']['path'] ?? __DIR__ . '/../build/source';
        $this->outputDir = $config['output']['path'] ?? __DIR__ . '/../output';
        $this->buildInfo['started_at'] = date('Y-m-d H:i:s');
    }

    public function build(): bool
    {
        if (!$this->prepareDirectories()) {
            $this->addError('Failed to prepare directories');
            return false;
        }

        try {
            $this->validateEnvironment();
            $this->cloneSource();
            $this->validateSource();
            $this->compile();
            $this->moveCompiledFiles();
            $this->saveVersionInfo();
            $this->cleanup();
            
            return $this->validateOutput();
        } catch (Exception $e) {
            $this->addError($e->getMessage());
            $this->cleanup(true);
            return false;
        }
    }

    protected function validateEnvironment(): void
    {
        if (!extension_loaded('pdo')) {
            throw new RuntimeException('PDO extension required');
        }
        
        exec('git --version', $output, $returnCode);
        if ($returnCode !== 0) {
            throw new RuntimeException('Git not available');
        }
    }

    protected function validateSource(): void
    {
        if (!is_file($this->sourceDir . '/composer.json')) {
            throw new RuntimeException('Invalid AdminerNeo source');
        }
    }

    protected function prepareDirectories(): bool
    {
        foreach ([$this->sourceDir, $this->outputDir] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                return false;
            }
        }
        return true;
    }

    protected function cloneSource(): void
    {
        $output = [];
        if (!is_dir($this->sourceDir . '/.git')) {
            $this->execCommand("git clone {$this->config['source']['repo']} {$this->sourceDir}", $output);
            $this->execCommand("cd {$this->sourceDir} && git checkout {$this->branch}", $output);
        } else {
            $this->execCommand("cd {$this->sourceDir} && git fetch && git checkout {$this->branch} && git pull", $output);
        }
        $this->buildInfo['clone_output'] = $output;
    }

    protected function compile(): void
    {
        $output = [];
        $this->execCommand("cd {$this->sourceDir} && composer install --no-dev", $output);
        $this->buildInfo['composer_output'] = $output;
        
        $output = [];
        // $this->execCommand("cd {$this->sourceDir} && php bin/compile.php mysql,pgsql,sqlite,mssql,mongo,oracle default+", $output);
        $this->execCommand("cd {$this->sourceDir} && php bin/compile.php default+", $output);
        $this->buildInfo['compile_output'] = $output;
        
        if (!file_exists($this->sourceDir . '/export/adminer.php')) {
            throw new RuntimeException('Compilation failed: No output file');
        }
    }

    protected function execCommand(string $command, array &$output = []): void 
    {
        exec($command, $output, $returnCode);
        if ($returnCode !== 0) {
            throw new RuntimeException(
                "Command failed: {$command}\n" . implode("\n", $output)
            );
        }
    }

    protected function getVersion(): string
    {
        $output = [];
        $this->execCommand("cd {$this->sourceDir} && git rev-parse HEAD", $output);
        return trim($output[0] ?? '');
    }

    protected function cleanup(bool $force = false): void 
    {
        try {
            if ($force) {
                $this->execCommand("rm -rf {$this->sourceDir}");
            } else {
                $this->execCommand("cd {$this->sourceDir} && git clean -fd");
            }
        } catch (Exception $e) {
            $this->addError('Cleanup failed: ' . $e->getMessage());
        }
    }

    protected function validateOutput(): bool 
    {
        $required = [
            $this->outputDir . '/adminer.php' => 'file',
            $this->outputDir . '/version.txt' => 'file'
        ];
        
        foreach ($required as $path => $type) {
            if (($type === 'file' && !is_file($path)) || 
                ($type === 'dir' && !is_dir($path))) {
                $this->addError("Missing {$type}: {$path}");
                return false;
            }
        }
        
        return true;
    }

    protected function moveCompiledFiles(): void
    {
        if (!file_exists($this->sourceDir . '/export/adminer.php')) {
            throw new RuntimeException('No compiled file found');
        }

        $output = [];

        // Copy main adminer file
        $this->execCommand("cp {$this->sourceDir}/export/adminer.php {$this->outputDir}/adminer.php", $output);

        // Copy plugins if they exist
        if (is_dir($this->sourceDir . '/plugins')) {
            $this->execCommand("cp -r {$this->sourceDir}/plugins/* {$this->outputDir}/plugins/", $output);
        }

        // Include License file
        $this->execCommand("cp {$this->sourceDir}/LICENSE.md {$this->outputDir}/LICENSE.md", $output);

        // Include README file
        $this->execCommand("cp {$this->sourceDir}/README.md {$this->outputDir}/README.md", $output);

        $this->buildInfo['move_output'] = $output;
    }

    protected function saveVersionInfo(): void
    {
        $this->buildInfo['completed_at'] = date('Y-m-d H:i:s');
        $this->buildInfo['version'] = $this->getVersion();
        $this->buildInfo['branch'] = $this->branch;
        
        file_put_contents(
            $this->outputDir . '/version.txt',
            json_encode($this->buildInfo, JSON_PRETTY_PRINT)
        );
    }

    protected function addError(string $message): void
    {
        $this->errors[] = $message;
        error_log("[AdminerNeo Builder] {$message}");
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getBuildInfo(): array
    {
        return $this->buildInfo;
    }

    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    public function getSourceDir(): string
    {
        return $this->sourceDir;
    }

    public function getBranch(): string
    {
        return $this->branch;
    }
}