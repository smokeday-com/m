<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Affiliate\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends Command
{
    protected $appState;

    protected $objectManager;

    public function __construct(
        State                  $appState,
        ObjectManagerInterface $objectManager
    ) {
        $this->appState      = $appState;
        $this->objectManager = $objectManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('mirasvit:affiliate:cron')
            ->setDescription('Run module cron tasks')
            ->setDefinition([]);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->appState->setAreaCode('adminhtml');

        $classes = [
            \Mirasvit\Affiliate\Cron\HandleEventsCron::class,
            \Mirasvit\Affiliate\Cron\CompleteTransactionsCron::class,
            \Mirasvit\Affiliate\Cron\RefreshGroupsCron::class,
            \Mirasvit\Affiliate\Cron\RemoveRelationCron::class,
            \Mirasvit\Affiliate\Cron\RefreshSumCommissionCron::class,
        ];

        foreach ($classes as $class) {
            $output->write("$class...");

            $cron = $this->objectManager->create($class);
            $cron->execute();

            $output->writeln("<info>done</info>");
        }

        return 0;
    }
}
