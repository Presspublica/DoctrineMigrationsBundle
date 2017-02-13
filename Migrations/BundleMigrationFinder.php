<?php

namespace Doctrine\Bundle\MigrationsBundle\Migrations;

use Doctrine\DBAL\Migrations\Finder\AbstractFinder;
use Doctrine\DBAL\Migrations\Finder\MigrationFinderInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;

class BundleMigrationFinder extends AbstractFinder
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @inheritdoc
     */
    public function findMigrations($directory, $namespace = null)
    {
        $bundles = $this->kernel->getBundles();
        $migrations = [];

        /**
         * @var string $name
         * @var Bundle $bundle
         */
        foreach ($bundles as $name => $bundle) {
            $migrationsDir = $bundle->getPath() . '/DoctrineMigrations';

            if (file_exists($migrationsDir)) {
                $files = glob(rtrim($migrationsDir, '/') . '/Version*.php');

                foreach ($files as $file) {
                    static::requireOnce($file);
                    $className = basename($file, '.php');
                    $version = (string) substr($className, 7);
                    $versionName = $name . $version;

                    $fullClassName = $bundle->getNamespace() .
                        "\\DoctrineMigrations\\" . $className;

                    $migrations[$versionName] = $fullClassName;
                }
            }
        }

        uksort($migrations, function($key1, $key2) {
            $date1 = intval(preg_replace('/[^\d]/', '', $key1));
            $date2 = intval(preg_replace('/[^\d]/', '', $key2));

            return $date1 - $date2;
        });

        return $migrations;
    }
}