<?php
namespace Route\Route\Test\Unit\Model\Route;

use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Sales\Model\ResourceModel\Order as orderResource;
use Magento\Sales\Model\Spi\OrderResourceInterface;
use PHPUnit\Framework\TestCase;
use Route\Route\Service\Api\OrdersApi;
use Route\Route\Service\Model\V2OrdersBody;

class OrderTest extends TestCase
{
    const ORDER_OBJECT = ['id' => '10001'];

    private function getRouteOrderClassMock($order, $addNewOrder = true)
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $helper = $this->createPartialMock(
            \Route\Route\Helper\Data::class,
            ['canSubmitOrder']
        );

        $helper->expects($this->any())
            ->method('canSubmitOrder')
            ->willReturn(true);


        $logger = $objectManager->getObject('\Route\Route\Logger\Sentry');

        $parser = $this->createPartialMock(
            \Route\Route\Model\Route\OrderParser::class,
            ['setOrder', 'getRouteObject']
        );

        $fallback = $objectManager->getObject('\Route\Route\Model\Route\Operation\FallbackFactory');
        $shipmentClient = $objectManager->getObject('\Route\Route\Model\Route\Shipment');

        $apiClientBuilder = $this->createPartialMock(
            '\Route\Route\Service\ApiClientBuilder',
            ['buildOrderApiClient']
        );

        $ordersApi = $this->createPartialMock(
            '\Route\Route\Service\Api\OrdersApi',
            ['createOrder']
        );

        $orderResponse = $this->createPartialMock(
            '\Route\Route\Service\Model\InlineResponse201',
            ['getId']
        );

        $orderResponse->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        $ordersApi->expects($this->any())
            ->method('createOrder')
            ->willReturn($orderResponse);

        $apiClientBuilder->expects($this->any())
            ->method('buildOrderApiClient')
            ->willReturn($ordersApi);

        $orderResource = $this->createPartialMock(
            OrderResource::class,
            [
                'save',
                'setRouteOrderId'
            ]
        );

        $parser->expects($this->any())
            ->method('setOrder')
            ->willReturn($parser);

        $orderResource->expects($this->any())
            ->method('save')
            ->willReturn(true);

        $mock = $this->getMockForAbstractClass(                         //mock generator made for abstract classes
            \Route\Route\Model\Route\Order::class,     // the fully qualified name of the abstract class to mock
            [
                $helper,
                $logger,
                $parser,
                $fallback,
                $shipmentClient,
                $orderResource,
                $apiClientBuilder
            ],                                                  // parameters for the class constructor
            '',                                 // no new name for the class to mock
            true,                               // do not call the original constructor
            true,                               // call original clone
            true,                               // call autoload
            ['hasRouteOrderId', 'getRouteApiUrl', 'postMethod', 'execute', 'setScopeCode', 'setOperation']
        );                      // mock the methods
        $mock->expects($this->any())
            ->method('hasRouteOrderId')
            ->willReturn(false);
        $mock->expects($this->any())
            ->method('getRouteApiUrl')
            ->willReturn(1);
        $mock->expects($this->any())
            ->method('setScopeCode')
            ->willReturn(1);
        $mock->expects($this->any())
            ->method('setOperation')
            ->willReturn(1);
        $mock->expects($this->any())
            ->method('postMethod')
            ->willReturn($mock);
        $mock->expects($this->any())
            ->method('execute')
            ->willReturn($addNewOrder ? self::ORDER_OBJECT : false);

        return $mock;
    }

    public function testPostOrderOlderThanMerchantCreationDate()
    {
        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [   'getStoreId',
                'setRouteOrderId',
                'save']
        );
        $order->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);
        $order->expects($this->any())
            ->method('setRouteOrderId')
            ->with(\Route\Route\Model\Route\Order::DEFAULT_ROUTE_ORDER_ID)
            ->willReturn(1);
        $order->expects($this->any())
            ->method('save')
            ->willReturn(true);

        $mock = $this->getRouteOrderClassMock($order, false);

        $this->assertEquals(false, $mock->postOrder($order, 1, false));
    }

    public function testPostOrderHappyPath()
    {
        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            [   'getStoreId',
                'setRouteOrderId',
                'save']
        );
        $order->expects($this->any())
            ->method('getStoreId')
            ->willReturn(1);
        $order->expects($this->any())
            ->method('setRouteOrderId')
            ->with('10001')
            ->willReturn(1);
        $order->expects($this->any())
            ->method('save')
            ->willReturn(true);

        $mock = $this->getRouteOrderClassMock($order);
        $this->assertEmpty($mock->postOrder($order, 1, false));
    }
}
