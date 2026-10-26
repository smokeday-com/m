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

namespace Mirasvit\Affiliate\Ui\Account\Form\Modifier;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DynamicRows;
use Magento\Ui\Component\Form\Element\DataType\Text;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Modal;
use Mirasvit\Affiliate\Api\Data\AccountCustomerInterface;
use Mirasvit\Affiliate\Model\ConfigProvider;
use Mirasvit\Affiliate\Repository\AccountCustomerRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerModifier
{
    private const DATA_SCOPE           = '';
    private const DATA_SCOPE_CUSTOMERS = 'account_customer';
    private const GROUP                = 'referred';

    private $configProvider;

    private $accountCustomerRepository;

    private $customerRepository;

    private $pricingHelper;

    private $urlBuilder;

    /**
     * @var string
     */
    private $scopeName;

    /**
     * @var string
     */
    private $scopePrefix;

    public function __construct(
        ConfigProvider              $configProvider,
        AccountCustomerRepository   $accountCustomerRepository,
        CustomerRepositoryInterface $customerRepository,
        PricingHelper               $pricingHelper,
        UrlInterface                $urlBuilder
    ) {
        $this->configProvider            = $configProvider;
        $this->accountCustomerRepository = $accountCustomerRepository;
        $this->customerRepository        = $customerRepository;
        $this->pricingHelper             = $pricingHelper;
        $this->urlBuilder                = $urlBuilder;
        $this->scopeName                 = 'affiliate_account_form.affiliate_account_form';
        $this->scopePrefix               = 'affiliate_';
    }

    /**
     * @since 101.0.0
     */
    public function modifyMeta(array $meta): array
    {
        if (!$this->configProvider->isShowReferredCustomers()) {
            return $meta;
        }

        $meta = array_replace_recursive(
            $meta,
            [
                static::GROUP => [
                    'children'  => [
                        $this->scopePrefix . static::DATA_SCOPE_CUSTOMERS => $this->getCustomersFieldset(),
                    ],
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label'         => __('Referred Customers'),
                                'collapsible'   => true,
                                'componentType' => Fieldset::NAME,
                                'dataScope'     => static::DATA_SCOPE,
                                'sortOrder'     => 100,
                            ],
                        ],

                    ],
                ],
            ]
        );

        return $meta;
    }

    public function modifyData(array $data, int $id): array
    {
        if (!$this->configProvider->isShowReferredCustomers()) {
            return $data;
        }

        $collection = $this->accountCustomerRepository->getCollection();
        $collection->addFieldToFilter(AccountCustomerInterface::ACCOUNT_ID, $id);

        $data[$id][self::DATA_SCOPE_CUSTOMERS] = [];

        foreach ($collection as $item) {
            try {
                $customer = $this->customerRepository->getById($item->getCustomerId());
            } catch (NoSuchEntityException $e) {
                $this->accountCustomerRepository->delete($item);
                continue;
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }

            $data[$id][self::DATA_SCOPE_CUSTOMERS][] = [
                'id'             => $customer->getId(),
                'name'           => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'email'          => $customer->getEmail(),
                'sum_commission' => $this->pricingHelper->currency($item->getSumCommission(), true, false),
            ];
        }

        return $data;
    }

    private function getCustomersFieldset(): array
    {
        return [
            'children'  => [
                'button_set' => $this->getButtonSet(
                    __('Actions'),
                    __('Add Customers'),
                    $this->scopePrefix . static::DATA_SCOPE_CUSTOMERS
                ),
                'modal'      => $this->getGenericModal(
                    __('Add Customers'),
                    $this->scopePrefix . static::DATA_SCOPE_CUSTOMERS
                ),

                static::DATA_SCOPE_CUSTOMERS => $this->getGrid($this->scopePrefix . static::DATA_SCOPE_CUSTOMERS),
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses' => 'admin__fieldset-section',
                        'label'             => __('Customers'),
                        'collapsible'       => false,
                        'componentType'     => Fieldset::NAME,
                        'dataScope'         => '',
                        'sortOrder'         => 10,
                    ],
                ],
            ],
        ];
    }

    private function getButtonSet(Phrase $content, Phrase $buttonTitle, string $scope): array
    {
        $modalTarget = $this->scopeName . '.' . static::GROUP . '.' . $scope . '.modal';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement'   => 'container',
                        'componentType' => 'container',
                        'label'         => false,
                        'content'       => $content,
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
                                'actions'       => [
                                    [
                                        'targetName' => $modalTarget,
                                        'actionName' => 'toggleModal',
                                    ],
                                    [
                                        'targetName' => $modalTarget . '.affiliate_account_customer_listing',
                                        'actionName' => 'render',
                                    ],
                                ],
                                'title'         => $buttonTitle,
                                'provider'      => null,
                            ],
                        ],
                    ],

                ],
            ],
        ];
    }

    private function getGenericModal(Phrase $title, string $scope): array
    {
        $listingTarget = 'affiliate_account_customer_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Modal::NAME,
                        'dataScope'     => '',
                        'options'       => [
                            'title'   => $title,
                            'buttons' => [
                                [
                                    'text'    => __('Cancel'),
                                    'actions' => [
                                        'closeModal',
                                    ],
                                ],
                                [
                                    'text'    => __('Add Selected Customers'),
                                    'class'   => 'action-primary',
                                    'actions' => [
                                        [
                                            'targetName' => 'index = ' . $listingTarget,
                                            'actionName' => 'save',
                                        ],
                                        [
                                            'targetName' => 'index = account_customer',
                                            'actionName' => 'reload',
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
                                'autoRender'         => false,
                                'componentType'      => 'insertListing',
                                'dataScope'          => 'account_customer',
                                'externalProvider'   => $listingTarget . '.' . $listingTarget . '_data_source',
                                'selectionsProvider' => $listingTarget . '.' . $listingTarget . '.customer_columns.ids',
                                'ns'                 => $listingTarget,
                                'render_url'         => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink'       => true,
                                'dataLinks'          => [
                                    'imports' => false,
                                    'exports' => true,
                                ],
                                'behaviourType'      => 'simple',
                                'externalFilterMode' => false,
                                'imports'            => [
                                    'account_id' => '${ $.provider }:data.account_id',
                                ],
                                'exports'            => [
                                    'account_id' => '${ $.externalProvider }:params.account_id',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getGrid(string $scope): array
    {
        $dataProvider = 'affiliate_account_customer_listing';

        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'additionalClasses'        => 'admin__field-wide',
                        'componentType'            => DynamicRows::NAME,
                        'label'                    => null,
                        'columnsHeader'            => true,
                        'columnsHeaderAfterRender' => false,
                        'renderDefaultRecord'      => false,
                        'template'                 => 'ui/dynamic-rows/templates/grid',
                        'component'                => 'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
                        'addButton'                => false,
                        'recordTemplate'           => 'record',
                        'index'                    => 'account_customer',
                        'deleteButtonLabel'        => __('Remove'),
                        'dataProvider'             => $dataProvider,
                        'map'                      => [
                            'id'    => 'entity_id',
                            'name'  => 'name',
                            'email' => 'email',
                        ],
                        'links'                    => [
                            'insertData'    => '${ $.provider }:${ $.dataProvider }',
                            '__disableTmpl' => ['insertData' => false],
                        ],
                        'dndConfig'                => [
                            'enabled' => false,
                        ],
                        'sortOrder'                => 2,
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
                                'dataScope'     => '',
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
            'id'             => $this->getTextColumn('id', false, __('Customer ID'), '0'),
            'name'           => $this->getTextColumn('name', false, __('Name'), '20'),
            'email'          => $this->getTextColumn('email', false, __('Email'), '30'),
            'sum_commission' => $this->getTextColumn('sum_commission', false, __('Sum Commission'), '40'),
            'actionDelete'   => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'additionalClasses' => 'data-grid-actions-cell',
                            'componentType'     => 'actionDelete',
                            'dataType'          => Text::NAME,
                            'label'             => __('Actions'),
                            'sortOrder'         => 70,
                            'fit'               => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getTextColumn(string $dataScope, bool $fit, Phrase $label, string $sortOrder): array
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement'   => Input::NAME,
                        'elementTmpl'   => 'ui/dynamic-rows/cells/text',
                        'component'     => 'Magento_Ui/js/form/element/text',
                        'dataType'      => Text::NAME,
                        'dataScope'     => $dataScope,
                        'fit'           => $fit,
                        'label'         => $label,
                        'sortOrder'     => $sortOrder,
                    ],
                ],
            ],
        ];
    }
}
