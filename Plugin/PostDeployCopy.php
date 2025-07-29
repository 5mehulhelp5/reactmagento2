<?php

namespace React\React\Plugin;

use Magento\Deploy\Service\DeployStaticContent;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to copy custom static files after deployment
 */
class PostDeployCopy
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var WriteInterface
     */
    private $staticDirectory;
    
    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->staticDirectory = $filesystem->getDirectoryWrite(DirectoryList::STATIC_VIEW);
    }

    /**
     * After plugin for deploy method
     *
     * @param DeployStaticContent $subject
     * @param void $result
     * @param array $options
     * @return void
     */
    public function afterDeploy(DeployStaticContent $subject, $result, array $options)
    {
        try {
            $this->copyCustomStaticFiles();
            $this->logger->info('Custom static files copied successfully after deployment');
        } catch (\Exception $e) {
            $this->logger->error('Failed to copy custom static files: ' . $e->getMessage());
        }
    }

    /**
     * Copy custom static files from module to main static directory
     */
    private function copyCustomStaticFiles()
    {
        $sourcePath = dirname(__DIR__) . '/pub/static';
        $targetPath = BP . '/pub/static';
        if (!is_dir($sourcePath)) {
            $this->logger->warning('Source directory does not exist: ' . $sourcePath);
            return;
        }

        $this->copyDirectory($sourcePath, $targetPath);
    }

    /**
     * Recursively copy directory contents
     *
     * @param string $source
     * @param string $destination
     */
    private function copyDirectory($source, $destination)
    {

        $dir = opendir($source);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . '/' . $file;
            $destPath = $destination . '/' . $file;

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                $this->copyFile($sourcePath, $destPath);
            }
        }
        closedir($dir);
    }

    /**
     * Copy a single file
     *
     * @param string $source
     * @param string $destination
     */
    private function copyFile($source, $destination)
    {
        $destinationDir = dirname($destination);
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }

        if (copy($source, $destination)) {
            $this->logger->debug('Copied file: ' . $source . ' -> ' . $destination);
        } else {
            $this->logger->error('Failed to copy file: ' . $source . ' -> ' . $destination);
        }
    }
}