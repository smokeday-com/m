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

namespace Mirasvit\Affiliate\Ui\Group\Form\Modifier;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Mirasvit\Affiliate\Api\Data\AccountInterface;
use Mirasvit\Affiliate\Repository\AccountRepository;

class AccountModifier
{
    private const DATA_SCOPE_ACCOUNTS = 'assigned_affiliates';

    private $accountRepository;

    private $urlBuilder;

    private $scopeName;

    public function __construct(
        AccountRepository $accountRepository,
        UrlInterface      $urlBuilder
    ) {
        $this->accountRepository = $accountRepository;
        $this->urlBuilder        = $urlBuilder;
        $this->scopeName         = 'affiliate_group_form.affiliate_group_form';
    }

    public function modifyMeta(array $meta): array
    {
        $meta = array_replace_recursive($meta, [
            static::DATA_SCOPE_ACCOUNTS => $this->getAccountsFieldset(),
        ]);

        return $meta;
    }

    public function modifyData(array $data, int $id): array
    {
        $collection = $this->accountRepository->getByGroupId($id);

        $data[$id]['links'][self::DATA_SCOPE_ACCOUNTS] = [];

        foreach ($collection as $item) {
            $data[$id]['links'][self::DATA_SCOPE_ACCOUNTS][] = [
                'id'    => $item->getId(),
                'name'  => $item->getName(),
                'email' => $item->getEmail(),
            ];
        }

        return $data;
    }

    private function getAccountsFieldset(): array
    {
        return [
            'children'  => [
                'button_set'                => $this->getButtonSet(),
                'modal'                     => $this->getGenericModal(),
                static::DATA_SCOPE_ACCOUNTS => $this->getGrid(),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__fieldset-section',
                        'label'             => __('Assigned Affiliates'),
                        'collapsible'       => true,
                        'opened'            => false,
                        'componentType'     => Fieldset::NAME,
                        'sortOrder'         => 10,
                    ],
                ],
            ],
        ];
    }

    private function getButtonSet(): array
    {
        $scope       = static::DATA_SCOPE_ACCOUNTS;
        $modalTarget = $this->scopeName . '.' . $scope . '.modal';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement'   => 'container',
                        'componentType' => 'container',
                        'label'         => false,
                        'content'       => '',
                        'template'      => 'ui/form/components/complex',
                    ],
                ],
            ],
            'children'  => [
                'button_' . $scope => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement'   => 'container',
                                'componentType' => 'container',
                                'component'     => 'Magento_Ui/js/form/components/button',
                                'title'         => (string)__('Add Affiliate'),
                                'actions'       => [
                                    [
                                        'targetName' => $modalTarget,
                                        'actionName' => 'toggleModal',
                                    ],
                                    [
                                        'targetName' => $modalTarget . '.affiliate_account_listing',
                                        'actionName' => 'render',
                                    ],
                                ],
                                'provider'      => false,
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }

    private function getGenericModal(): array
    {
        $listingTarget = 'affiliate_account_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'options'       => [
                            'title'   => (string)__('Add Affiliate'),
                            'buttons' => [
                                [
                                    'text'    => __('Cancel'),
                                    'actions' => [
                                        'closeModal',
                                    ],
                                ],
                                [
                                    'text'    => __('Add Selected'),
                                    'class'   => 'action-primary',
                                    'actions' => [
                                        [
                                            'targetName' => 'index=' . $listingTarget,
                                            'actionName' => 'save',
                                        ],
                                        'closeModal',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'children'  => [
                $listingTarget => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'dataScope'          => 'affiliate_account_listing',
                                'componentType'      => 'insertListing',
                                'autoRender'         => false,
                                'externalProvider'   => $listingTarget . '.' . $listingTarget . '_data_source',
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget . '.columns.ids',
                                'ns'                 => $listingTarget,
                                'render_url'         => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink'       => true,
                                'dataLinks'          => [
                                    'imports' => false,
                                    'exports' => true,
                                ],
                                'behaviourType'      => 'simple',
                                'externalFilterMode' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getGrid(): array
    {
        $dataProvider = 'data.affiliate_account_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'component'                => 'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
                        'template'                 => 'ui/dynamic-rows/templates/grid',
                        'componentType'            => DynamicRows::NAME,
                        'addButton'                => false,
                        'additionalClasses'        => 'admin__field-wide',
                        'columnsHeader'            => true,
                        'columnsHeaderAfterRender' => false,
                        'renderDefaultRecord'      => false,
                        'recordTemplate'           => 'record',
                        'deleteButtonLabel'        => __('Remove'),
                        'dataScope'                => 'links',
                        'dataProvider'             => $dataProvider,
                        'map'                      => [
                            'id'    => AccountInterface::ID,
                            'name'  => 'name',
                            'email' => 'email',
                        ],
                        'links'                    => [
                            'insertData'    => '${ $.provider }:${ $.dataProvider }',
                            '__disableTmpl' => ['insertData' => false],
                        ],
                        'sortOrder'                => 3,
                        'dndConfig'                => ['enabled' => false],
                    ],
                ],
            ],
            'children'  => [
                'record' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'isTemplate'    => true,
                                'is_collection' => true,
                                'component'     => 'Magento_Ui/js/dynamic-rows/record',
                            ],
                        ],
                    ],
                    'children'  => $this->fillMeta(),
                ],
            ],
        ];
    }

    private function fillMeta(): array
    {
        return [
            AccountInterface::ID => $this->getTextColumn('id', false, (string)__('ID'), 10),
            'name'               => $this->getTextColumn('name', false, (string)__('Name'), 20),
            'email'              => $this->getTextColumn('email', false, (string)__('Email'), 30),
            'actionDelete'       => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType'     => 'actionDelete',
                            'dataType'          => Text::NAME,
                            'label'             => __('Actions'),
                            'fit'               => true,
                            'sortOrder'         => 70,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getTextColumn(string $dataScope, bool $fit, string $label, int $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'dataScope'     => $dataScope,
                        'componentType' => Field::NAME,
                        'formElement'   => Input::NAME,
                        'dataType'      => Text::NAME,
                        'elementTmpl'   => 'ui/dynamic-rows/cells/text',
                        'component'     => 'Magento_Ui/js/form/element/text',
                        'label'         => $label,
                        'fit'           => $fit,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }
}
