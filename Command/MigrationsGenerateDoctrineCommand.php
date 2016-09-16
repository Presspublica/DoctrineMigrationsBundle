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

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;

/**
 * Command for generating new blank migration classes
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 */
class MigrationsGenerateDoctrineCommand extends GenerateCommand
{
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:migrations:generate')
            ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database connection to use for this command.')
            ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
            ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.')
            ->addArgument('bundle', null, InputOption::VALUE_REQUIRED, 'Bundle')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output, $namespace = null, $path = null)
    {
        // EM and DB options cannot be set at same time
        if (null !== $input->getOption('em') && null !== $input->getOption('db')) {
            throw new InvalidArgumentException('Cannot set both "em" and "db" for command execution.');
        }

        Helper\DoctrineCommandHelper::setApplicationHelper($this->getApplication(), $input);

        $configuration = $this->getMigrationConfiguration($input, $output);
        $container = $this->getApplication()->getKernel()->getContainer();
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
