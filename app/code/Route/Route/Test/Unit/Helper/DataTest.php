<?php
/**
 * Created by PhpStorm.
 * User: eduan
 * Date: 18/01/21
 * Time: 15:27
 */

namespace Route\Route\Test\Unit\Helper;

use Magento\Checkout\Model\Session;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\State;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Pricing\Helper\Data as Price;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Route\Route\Logger\Sentry;
use Route\Route\Helper\Data;
use Route\Route\Model\Route\Merchant;
use Route\Route\Model\Route\Quote;
use Route\Route\Service\Model\CustomerPaymentResponsible;
use Route\Route\Service\Model\QuoteInlineResponse2001;

class DataTest extends TestCase
{
    const EXCLUDED_SHIPPING_METHOD = 'excluded_shipping_method';
    const EXCLUDED_PAYMENT_METHOD = 'excluded_payment_method';
    const ALLOWED_SHIPPING_METHOD = 'allowed_shipping_method';
    const ALLOWED_PAYMENT_METHOD = 'allowed_payment_method';

    public function testRouteProtectionShouldNotBeAllowedWhenModuleIsDisable()
    {
        $scope = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $scope->expects($this->any())
            ->method('getValue')
            ->willReturn(false);

        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($scope);

        $routeHelper = $this->mockRouteHelper($context, $this->createMock(Session::class));

        $this->assertFalse($routeHelper->isRouteProtectionAllowed());
    }

    public function testQuoteRouteProtectionShouldNotBeAllowedWhenModuleIsEnableAndExcludedShippingMethod()
    {
        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->mockRegularScope());

        $address = $this->createMock(\Magento\Quote\Model\Quote\Address::class);

        $address->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn(self::EXCLUDED_SHIPPING_METHOD);

        $payment = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);
        $payment->expects($this->any())
            ->method('getMethod')
            ->willReturn(self::ALLOWED_PAYMENT_METHOD);

        $cart = $this->createMock(\Magento\Quote\Model\Quote::class);
        $cart->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($address);

        $cart->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getQuote')
            ->willReturn($cart);

        $routeHelper = $this->mockRouteHelper($context, $session);

        $this->assertFalse($routeHelper->isRouteProtectionAllowed());
    }

    public function testRouteProtectionShouldNotBeAllowedWhenExcludedShippingMethodIsPassedByParam()
    {
        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->mockRegularScope());

        $address = $this->createMock(\Magento\Quote\Model\Quote\Address::class);

        $address->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn(self::EXCLUDED_SHIPPING_METHOD);

        $payment = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);
        $payment->expects($this->any())
            ->method('getMethod')
            ->willReturn(self::ALLOWED_PAYMENT_METHOD);

        $cart = $this->createMock(\Magento\Quote\Model\Quote::class);
        $cart->expects($this->any())
            ->method('getShippingAddress')
            ->willReturn($address);

        $cart->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getQuote')
            ->willReturn($cart);

        $routeHelper = $this->mockRouteHelper($context, $session);

        $this->assertFalse($routeHelper->isRouteProtectionAllowed(self::EXCLUDED_SHIPPING_METHOD, self::ALLOWED_PAYMENT_METHOD));
    }

    public function testRouteProtectionShouldBeAllowedWhenShippingMethodExcludedIsPassedByParam()
    {
        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($this->mockRegularScope());

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote::class));

        $routeHelper = $this->mockRouteHelper($context, $session);

        $this->assertTrue($routeHelper->isRouteProtectionAllowed(self::ALLOWED_SHIPPING_METHOD, self::ALLOWED_PAYMENT_METHOD));
    }

    public function testRouteProtectionShouldNotBeAllowedWhenShippingMethodExcludedIsPassedByParam()
    {
        $scope = $this->mockRegularScope();

        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($scope);

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote::class));

        $routeHelper = $this->mockRouteHelper($context, $session);

        $this->assertFalse($routeHelper->isRouteProtectionAllowed(self::EXCLUDED_SHIPPING_METHOD, self::ALLOWED_PAYMENT_METHOD));
    }

    public function testRouteProtectionShouldNotBeAllowedWhenExcludedPaymentMethodIsPassedByParam()
    {
        $scope = $this->mockRegularScope();

        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($scope);

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote::class));

        $routeHelper = $this->mockRouteHelper($context, $session);

        $this->assertFalse($routeHelper->isRouteProtectionAllowed(self::ALLOWED_SHIPPING_METHOD, self::EXCLUDED_PAYMENT_METHOD));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function mockRegularScope()
    {
        $scope = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $scope->expects($this->any())
            ->method('getValue')
            ->withConsecutive(
                ['route/route/enabled', ScopeInterface::SCOPE_STORES, null],
                ['route/route/excluded_shipping_methods', ScopeInterface::SCOPE_STORES, null],
                ['route/route/excluded_shipping_methods', ScopeInterface::SCOPE_STORES, null],
                ['route/route/excluded_payment_methods', ScopeInterface::SCOPE_STORES, null],
                ['route/route/excluded_payment_methods', ScopeInterface::SCOPE_STORES, null]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                self::EXCLUDED_SHIPPING_METHOD,
                self::EXCLUDED_SHIPPING_METHOD,
                self::EXCLUDED_PAYMENT_METHOD,
                self::EXCLUDED_PAYMENT_METHOD
            );


        return $scope;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject $context
     * @param \PHPUnit\Framework\MockObject\MockObject $session
     * @param null $productMetadata
     * @param null $moduleList
     * @param null $quoteClient
     * @param null $storeManager
     * @param null $price
     * @param null $merchantClient
     * @param null $collectionFactory
     * @param null $configWriter
     * @param null $logger
     * @param null $quoteRepository
     * @param null $state
     * @param null $adminQuoteSession
     * @param null $moduleManager
     * @return Data
     */
    private function mockRouteHelper(
        $context = null,
        $session = null,
        $productMetadata = null,
        $moduleList = null,
        $quoteClient = null,
        $storeManager = null,
        $price = null,
        $merchantClient = null,
        $collectionFactory = null,
        $configWriter = null,
        $logger = null,
        $quoteRepository = null,
        $state = null,
        $adminQuoteSession = null,
        $moduleManager = null
    ): Data {
        return new Data(
            is_null($context) ? $this->createMock(Context::class) : $context,
            is_null($session) ? $this->createMock(Session::class) : $session,
            is_null($productMetadata) ? $this->createMock(ProductMetadataInterface::class) : $productMetadata,
            is_null($moduleList) ? $this->createMock(ModuleListInterface::class) : $moduleList,
            is_null($quoteClient) ? $this->createMock(Quote::class) : $quoteClient,
            is_null($storeManager) ? $this->createMock(StoreManagerInterface::class) : $storeManager,
            is_null($price) ? $this->createMock(Price::class) : $price,
            is_null($merchantClient) ? $this->createMock(Merchant::class) : $merchantClient,
            is_null($collectionFactory) ? $this->createMock(CollectionFactory::class) : $collectionFactory,
            is_null($configWriter) ? $this->createMock(WriterInterface::class) : $configWriter,
            is_null($logger) ? $this->createMock(Sentry::class) : $logger,
            is_null($quoteRepository) ? $this->createMock(CartRepositoryInterface::class) : $quoteRepository,
            is_null($state) ? $this->createMock(State::class) : $state,
            is_null($adminQuoteSession) ? $this->createMock(\Magento\Backend\Model\Session\Quote::class) : $adminQuoteSession,
            is_null($moduleManager) ? $this->createMock(\Magento\Framework\Module\Manager::class) : $moduleManager
        );
    }

    private function getProduct()
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $product->expects($this->any())
            ->method('getSku')
            ->willReturn('we-123');
        $product->expects($this->any())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);

        return $product;
    }

    private function getProduct2()
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->any())
            ->method('getId')
            ->willReturn(124);
        $product->expects($this->any())
            ->method('getSku')
            ->willReturn('we-1234');
        $product->expects($this->any())
            ->method('getTypeId')
            ->willReturn(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);

        return $product;
    }

    private function getVirtualProduct()
    {
        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->any())
            ->method('getId')
            ->willReturn(125);
        $product->expects($this->any())
            ->method('getSku')
            ->willReturn('we-125');
        $product->expects($this->any())
            ->method('getIsVirtual')
            ->willReturn(true);

        return $product;
    }

    private function getItem()
    {
        $item = $this->getMockBuilder(Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->getProduct());
        $item->expects($this->any())
            ->method('getPrice')
            ->willReturn(10.00);
        $item->expects($this->any())
            ->method('getQtyOrdered')
            ->willReturn(3);
        $item->expects($this->any())
            ->method('getName')
            ->willReturn('Test Product');

        return $item;
    }

    public function getItem2()
    {
        $item2 = $this->getMockBuilder(Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item2->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->getProduct2());
        $item2->expects($this->any())
            ->method('getPrice')
            ->willReturn(15.0);
        $item2->expects($this->any())
            ->method('getQtyOrdered')
            ->willReturn(2);
        $item2->expects($this->any())
            ->method('getName')
            ->willReturn('Test Product 2');
        return $item2;
    }

    private function getVirtualItem()
    {
        $item = $this->getMockBuilder(Order\Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->getVirtualProduct());
        $item->expects($this->any())
            ->method('getPrice')
            ->willReturn(10.00);
        $item->expects($this->any())
            ->method('getQtyOrdered')
            ->willReturn(3);
        $item->expects($this->any())
            ->method('getName')
            ->willReturn('Test Product');

        return $item;
    }

    private function getMultipleItems()
    {
        return [$this->getItem(), $this->getItem2()];
    }

    private function getMixedItems()
    {
        return [$this->getItem(), $this->getVirtualItem()];
    }

    public function testGetOrderShippableItemsSubtotal()
    {
        $context = $this->createMock(Context::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote::class));

        $routeHelper = $this->mockRouteHelper($context, $session);

        $order = $this->createMock(Order::class);
        $order->expects($this->any())
            ->method('getSubtotal')
            ->willReturn(60.0);

        $order->expects($this->any())
            ->method('getAllItems')
            ->willReturn($this->getMultipleItems());

        $this->assertEquals(60.0, $routeHelper->getShippableItemsSubtotal($order));
    }

    public function testGetOrderShippableItemsSubtotalWithVirtualProduct()
    {
        $context = $this->createMock(Context::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote::class));

        $routeHelper = $this->mockRouteHelper($context, $session);

        $order = $this->createMock(Order::class);
        $order->expects($this->any())
            ->method('getSubtotal')
            ->willReturn(60.0);

        $order->expects($this->any())
            ->method('getAllItems')
            ->willReturn($this->getMixedItems());

        $this->assertEquals(30.0, $routeHelper->getShippableItemsSubtotal($order));
    }

    public function testGetQuoteShippableItemsSubtotalWithVirtualProduct()
    {
        $context = $this->createMock(Context::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->any())
            ->method('getQuote')
            ->willReturn($this->createMock(\Magento\Quote\Model\Quote::class));

        $routeHelper = $this->mockRouteHelper($context, $session);

        $quote = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getSubtotal', 'getAllItems']
        );

        $quote->expects($this->any())
            ->method('getSubtotal')
            ->willReturn(60.0);

        $quote->expects($this->any())
            ->method('getAllItems')
            ->willReturn($this->getMixedItems());

        $this->assertEquals(30.0, $routeHelper->getShippableItemsSubtotal($quote));
    }

    public function testIsInsuredOptOutWithCheckedSession()
    {
        $scope = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $scope->expects($this->any())
            ->method('getValue')
            ->withConsecutive(['route/route/enabled', ScopeInterface::SCOPE_STORES, null])
            ->willReturn(true);

        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($scope);

        $session = $this->createPartialMock(Session::class, ['getInsured']);
        $session->expects($this->any())
            ->method('getInsured')
            ->willReturn(null);

        $merchant = $this->createMock(Merchant::class);

        $merchant->expects($this->any())
            ->method('isOptOut')
            ->willReturn(true);

        $routeHelper = $this->mockRouteHelper(
            $context,
            $session,
            null,
            null,
            null,
            null,
            null,
            $merchant
        );

        $quoteResponse = $this->getQuoteResponse();

        $this->assertTrue($routeHelper->isInsured($quoteResponse));
    }

    public function testIsInsuredOptOutWithoutCheckedSession()
    {
        $scope = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $scope->expects($this->any())
            ->method('getValue')
            ->withConsecutive(['route/route/enabled', ScopeInterface::SCOPE_STORES, null])
            ->willReturn(true);

        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($scope);

        $session = $this->createPartialMock(Session::class, ['getInsured']);
        $session->expects($this->any())
            ->method('getInsured')
            ->willReturn(true);

        $merchant = $this->createMock(Merchant::class);

        $merchant->expects($this->any())
            ->method('isOptOut')
            ->willReturn(true);

        $routeHelper = $this->mockRouteHelper(
            $context,
            $session,
            null,
            null,
            null,
            null,
            null,
            $merchant
        );

        $quoteResponse = $this->getQuoteResponse();

        $this->assertTrue($routeHelper->isInsured($quoteResponse));
    }

    public function testIsInsuredOptInWithCheckedSession()
    {
        $scope = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $scope->expects($this->any())
            ->method('getValue')
            ->withConsecutive(['route/route/enabled', ScopeInterface::SCOPE_STORES, null])
            ->willReturn(true);

        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($scope);

        $session = $this->createPartialMock(Session::class, ['getInsured']);
        $session->expects($this->any())
            ->method('getInsured')
            ->willReturn(true);

        $merchant = $this->createMock(Merchant::class);

        $merchant->expects($this->any())
            ->method('isOptIn')
            ->willReturn(true);

        $routeHelper = $this->mockRouteHelper(
            $context,
            $session,
            null,
            null,
            null,
            null,
            null,
            $merchant
        );

        $quoteResponse = $this->getQuoteResponse('unchecked');

        $this->assertTrue($routeHelper->isInsured($quoteResponse));
    }

    public function testIsInsuredOptInWithoutSession()
    {
        $scope = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);

        $scope->expects($this->any())
            ->method('getValue')
            ->withConsecutive(['route/route/enabled', ScopeInterface::SCOPE_STORES, null])
            ->willReturn(true);

        $context = $this->createMock(Context::class);
        $context->expects($this->any())
            ->method('getScopeConfig')
            ->willReturn($scope);

        $session = $this->createPartialMock(Session::class, ['getInsured']);
        $session->expects($this->any())
            ->method('getInsured')
            ->willReturn(null);

        $merchant = $this->createMock(Merchant::class);

        $merchant->expects($this->any())
            ->method('isOptIn')
            ->willReturn(true);

        $routeHelper = $this->mockRouteHelper(
            $context,
            $session,
            null,
            null,
            null,
            null,
            null,
            $merchant
        );

        $quoteResponse = $this->getQuoteResponse('unchecked');

        $this->assertFalse($routeHelper->isInsured($quoteResponse));
    }

    public function getQuoteResponse($checked = 'checked')
    {
        $paymentResponsible = $this->createMock(CustomerPaymentResponsible::class);
        $paymentResponsible->expects($this->any())
            ->method('getType')
            ->willReturn('paid_by_customer');

        ;
        $paymentResponsible->expects($this->any())
            ->method('getToggleState')
            ->willReturn($checked);

        $quoteResponse = $this->createMock(QuoteInlineResponse2001::class);
        $quoteResponse->expects($this->any())
            ->method('getPaymentResponsible')
            ->willReturn($paymentResponsible);
        return $quoteResponse;
    }
}
