<?php

namespace Mage4\SignifydFix\Block\Adminhtml\Shopcart\Abandoned;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Framework\Stdlib\Parameters;
use Magento\Framework\Url\DecoderInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;
use Signifyd\Connect\Helper\ConfigHelper;

/**
 * Override Signifyd Grid class to fix dependency injection issue after Magento upgrade
 * This class extends Magento's base Grid and adds Signifyd functionality with proper DI handling
 */
class Grid extends \Magento\Reports\Block\Adminhtml\Shopcart\Abandoned\Grid
{
    /**
     * @var JsonSerializer
     */
    public $jsonSerializer;

    /**
     * @var ConfigHelper
     */
    public $configHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Grid constructor.
     *
     * @param ProductMetadataInterface $productMetadataInterface
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Reports\Model\ResourceModel\Quote\CollectionFactory $quotesFactory
     * @param JsonSerializer $jsonSerializer
     * @param ConfigHelper $configHelper
     * @param DecoderInterface|null $urlDecoder
     * @param Parameters|null $parameters
     * @param LoggerInterface|null $logger
     * @param array $data
     */
    public function __construct(
        ProductMetadataInterface $productMetadataInterface,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Reports\Model\ResourceModel\Quote\CollectionFactory $quotesFactory,
        JsonSerializer $jsonSerializer,
        ConfigHelper $configHelper,
        ?DecoderInterface $urlDecoder = null,
        ?Parameters $parameters = null,
        ?LoggerInterface $logger = null,
        array $data = []
    ) {
        // Initialize logger first
        $this->logger = $logger ?? ObjectManager::getInstance()->get(LoggerInterface::class);
        
        // Resolve null dependencies using ObjectManager (same pattern as parent class)
        $wasUrlDecoderNull = ($urlDecoder === null);
        $wasParametersNull = ($parameters === null);
        
        $urlDecoder = $urlDecoder ?? ObjectManager::getInstance()->get(DecoderInterface::class);
        $parameters = $parameters ?? ObjectManager::getInstance()->get(Parameters::class);

        // Log if dependencies were resolved via ObjectManager (for debugging)
        if ($wasUrlDecoderNull || $wasParametersNull) {
            $this->logger->debug(
                'Signifyd Grid: Resolved null dependencies via ObjectManager',
                [
                    'urlDecoder_was_null' => $wasUrlDecoderNull,
                    'parameters_was_null' => $wasParametersNull,
                    'magento_version' => $productMetadataInterface->getVersion()
                ]
            );
        }

        // Call parent constructor with resolved dependencies
        if (version_compare($productMetadataInterface->getVersion(), '2.4.7') >= 0) {
            parent::__construct($context, $backendHelper, $quotesFactory, $urlDecoder, $parameters, $data);
        } else {
            parent::__construct($context, $backendHelper, $quotesFactory, $data);
        }

        $this->jsonSerializer = $jsonSerializer;
        $this->configHelper = $configHelper;
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $return = parent::_prepareColumns();

        if ($this->getRequest()->getParam('store')) {
            $storeId = (int)$this->getRequest()->getParam('store');
            $policyName = $this->configHelper->getPolicyName(ScopeInterface::SCOPE_STORES, $storeId);
        } else {
            $policyName = $this->configHelper->getPolicyName();
        }

        $isPreAuthInUse = $this->configHelper->getIsPreAuthInUse($policyName);

        if ($isPreAuthInUse) {
            $this->addColumn(
                'signifyd_guarantee',
                [
                    'header' => __('Signifyd Decision'),
                    'index' => 'signifyd_guarantee',
                    'sortable' => false,
                    'header_css_class' => 'col-name',
                    'column_css_class' => 'col-name',
                    'renderer' => \Signifyd\Connect\Block\Adminhtml\Grid\Column\SignifydGuarantee::class
                ]
            );
        }

        return $return;
    }
}

