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

namespace Mirasvit\Affiliate\Ui\Program\Form;

use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirasvit\Affiliate\Api\Data\ProgramInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Mirasvit\Affiliate\Repository\ProgramRepository;
use Mirasvit\Affiliate\Service\TierService;

class DataProvider extends AbstractDataProvider
{
    private $programRepository;

    private $context;

    private $uiComponentFactory;

    private $arrayManager;

    private $tierService;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ProgramRepository  $transactionRepository,
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        TierService        $tierService,
        ArrayManager       $arrayManager,
        string             $name,
        string             $primaryFieldName,
        string             $requestFieldName,
        array              $meta = [],
        array              $data = []
    ) {
        $this->programRepository  = $transactionRepository;
        $this->collection         = $this->programRepository->getCollection();
        $this->arrayManager       = $arrayManager;
        $this->context            = $context;
        $this->uiComponentFactory = $uiComponentFactory;
        $this->tierService        = $tierService;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getMeta(): array
    {
        $meta = parent::getMeta();

        $types = [
            ProgramInterface::TYPE_PAY_PER_SALE,
            ProgramInterface::TYPE_PAY_PER_CLICK,
            ProgramInterface::TYPE_PAY_PER_IMPRESSION,
            ProgramInterface::TYPE_PAY_PER_LEAD,
        ];

        foreach ($types as $type) {
            $component = $this->uiComponentFactory->create('affiliate_program_form_type_' . $type);

            $meta[$type] = $this->prepareComponent($component);

            if ($programId = $this->context->getRequestParam(ProgramInterface::ID)) {
                $visibility = $this->programRepository->get((int)$programId)->getType() === $type;
                $component  = $this->toggleVisibility($meta[$type], $type, $visibility);
                $meta       = $this->arrayManager->set($type, $meta, $component);
            }
        }

        return $meta;
    }

    private function toggleVisibility(array $config, string $type, bool $visibility): array
    {
        // Change visibility for fieldset itself
        if (isset($config['arguments']['data']['config'])) {
            $config['arguments']['data']['config']['visible'] = $visibility;
        }

        //$visibility = true;
        // Change visibility for children elements
        if (isset($config['children'])) {
            foreach ($config['children'] as $fieldName => $child) {
                if (isset($child['arguments']['data']['config'])) {
                    $child['arguments']['data']['config']['visible'] = $visibility;
                    $config['children'][$fieldName]                  = $child;
                }
            }
        }

        return $config;
    }

    private function prepareComponent(UiComponentInterface $component): array
    {

        $data = [];
        foreach ($component->getChildComponents() as $child) {
            $data['children'][$child->getName()] = $this->prepareComponent($child);
        }
        $component->prepare();
        $data['arguments']['data'] = $component->getData();

        return $data;
    }

    public function getData(): array
    {
        $result = [];

        foreach ($this->programRepository->getCollection() as $item) {
            $activeFrom = $item->getActiveFrom();
            $activeTo   = $item->getActiveTo();

            $result[$item->getId()] = [
                ProgramInterface::ID          => $item->getId(),
                ProgramInterface::NAME        => $item->getName(),
                ProgramInterface::DESCRIPTION => $item->getDescription(),
                ProgramInterface::TYPE        => $item->getType(),
                ProgramInterface::IS_ACTIVE   => $item->getIsActive() ? '1' : '0',
                ProgramInterface::ACTIVE_FROM => ($activeFrom === '0000-00-00 00:00:00') ? null : $activeFrom,
                ProgramInterface::ACTIVE_TO   => ($activeTo === '0000-00-00 00:00:00') ? null : $activeTo,
                ProgramInterface::IS_VISIBLE  => $item->getIsVisible() ? '1' : '0',
                ProgramInterface::PRIORITY    => $item->getPriority(),
                ProgramInterface::TIERS       => $this->tierService->toArray($item->getTiers()),
                ProgramInterface::STORE_IDS   => $item->getStoreIds(),

                ProgramInterface::COUPON_IS_ACTIVE   => $item->getCouponIsActive() ? '1' : '0',
                ProgramInterface::CART_RULE_ID       => $item->getCartRuleId(),
                ProgramInterface::IS_STOP_PROCESSING => $item->getIsStopProcessing() ? '1' : '0',
            ];
        }

        return $result;
    }
}
