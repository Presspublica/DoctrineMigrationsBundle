<?php

/*
 * This file is part of the Doctrine MigrationsBundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Bundle\MigrationsBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Command\Proxy\DoctrineCommandHelper;
use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand;
use Doctrine\DBAL\Sharding\PoolingShardConnection;
use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class MigrationsDiffDoctrineCommand extends DiffCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:migrations:diff')
            ->addOption('em', null, InputOption::VALUE_OPTIONAL, 'The entity manager to use for this command.')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.')
            ->addArgument('bundle', null, InputOption::VALUE_REQUIRED, 'Bundle')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output, $namespace = null, $path = null)
    {
        DoctrineCommandHelper::setApplicationEntityManager($this->getApplication(), $input->getOption('em'));

        if ($input->getOption('shard')) {
            $connection = $this->getApplication()->getHelperSet()->get('db')->getConnection();
            if (!$connection instanceof PoolingShardConnection) {
                throw new LogicException(sprintf("Connection of EntityManager '%s' must implements shards configuration.", $input->getOption('em')));
            }

            $connection->connect($input->getOption('shard'));
        }

        /** @var ContainerInterface $container */
        $container = $this->getApplication()->getKernel()->getContainer();

        $configuration = $this->getMigrationConfiguration($input, $output);
        DoctrineCommand::configureMigrations($container, $configuration);

        if ($bundle = $input->getArgument('bundle')) {
            $bundles = $container->get('kernel')->getBundles();

            /** @var Bundle $bundleObject */
            $bundleObject = $bundles[$bundle];

            $namespace = $bundleObject->getNamespace();

            $path = $bundleObject->getPath() . DIRECTORY_SEPARATOR . 'DoctrineMigrations';

        } else {
            $namespace = null;
            $path = null;
        }

        return parent::execute($input, $output, $namespace, $path);
    }
}
