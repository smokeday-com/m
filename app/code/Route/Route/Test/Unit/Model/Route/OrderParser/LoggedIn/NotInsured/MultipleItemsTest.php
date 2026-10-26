<?php
/**
 * Created by PhpStorm.
 * User: eduan
 * Date: 08/03/20
 * Time: 00:53
 */

namespace Route\Route\Test\Unit\Model\Route\OrderParser\LoggedIn\NotInsured;

use Magento\Catalog\Model\Product;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\TestCase;
use Route\Route\Service\Model\V2OrdersBody;

class MultipleItemsTest extends TestCase
{
    /** @var \Route\Route\Model\Route\OrderParser $orderParser */
    private $orderParser;

    public function setUp(): void
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $productHelper = $this->createMock(\Magento\Catalog\Helper\Product::class);

        $productHelper->expects($this->any())
            ->method('getImageUrl')
            ->willReturn('http://localhost/image/product.jpg');
        $routeHelper = $this->createMock(\Route\Route\Helper\Data::class);

        $routeHelper->expects($this->any())
            ->method('isRouteProtectionAllowed')
            ->with('flat_rate', 'valid_payment')
            ->willReturn(true);

        $routeHelper->expects($this->any())
            ->method('getShippableItemsSubtotal')
            ->willReturn('60.0');

        $this->orderParser = $objectManager->getObject(
            '\Route\Route\Model\Route\OrderParser',
            [
                'productHelper' => $productHelper,
                'helper' => $routeHelper]
        );
    }

    private function createSimpleOrderMock()
    {
        $order = $this->createMock(\Magento\Sales\Model\Order::class);

        $order->expects($this->any())
            ->method('getIncrementId')
            ->willReturn(100000012);

        $order->expects($this->any())
            ->method('getId')
            ->willReturn(12);

        $order->expects($this->any())
            ->method('getSubtotal')
            ->willReturn(60);

        $order->expects($this->any())
            ->method('getOrderCurrencyCode')
            ->willReturn('USD');

        $order->expects($this->any())
            ->method('getTaxAmount')
            ->willReturn(0);

        $order->expects($this->any())
            ->method('getCustomerEmail')
            ->willReturn('email@test.com');

        $payment = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $payment->expects($this->any())
            ->method('getMethod')
            ->willReturn('valid_payment');

        $order->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);

        return $order;
    }

    private function getItems()
    {
        $item = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item->expects($this->any(9))
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

        return [$item];
    }

    private function getProduct()
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->any())
            ->method('getId')
            ->willReturn(123);
        $product->expects($this->any())
            ->method('getSku')
            ->willReturn('we-123');
        $product->expects($this->any(3))
            ->method('getTypeId')
            ->willReturn(Product\Type::TYPE_SIMPLE);

        return $product;
    }

    private function getProduct2()
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $product->expects($this->any())
            ->method('getId')
            ->willReturn(124);
        $product->expects($this->any())
            ->method('getSku')
            ->willReturn('we-1234');

        return $product;
    }

    private function getMultipleItems()
    {
        $item = $this->getItems();
        $item2 = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $item2->expects($this->any(9))
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

        return array_merge($item, [$item2]);
    }

    private function createCustomerLoggedInOrderWithMultipleItemsMock()
    {
        $order = $this->createSimpleOrderMock();

        $order->expects($this->any(2))
            ->method('getCustomerFirstname')
            ->willReturn('Firstson');

        $order->expects($this->any(2))
            ->method('getCustomerLastname')
            ->willReturn('Lastson');

        //$order->expects($this->any())
        $order->expects($this->any(2))
            ->method('getShippingAddress')
            ->willReturn($this->getShippingAddress());
        $order->expects($this->any(2))
            ->method('getBillingAddress')
            ->willReturn($this->getShippingAddress());

        $order->expects($this->any())
            ->method('getAllItems')
            ->willReturn($this->getMultipleItems());

        $payment = $this->createMock(\Magento\Sales\Model\Order\Payment::class);

        $payment->expects($this->any())
            ->method('getMethod')
            ->willReturn('valid_payment');

        $order->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);

        $order->expects($this->any())
            ->method('getShippingMethod')
            ->willReturn('flat_rate');

        $order->expects($this->any())
            ->method('getCreatedAt')
            ->willReturn((new \DateTime())->format('Y-m-d H:i:s'));

        $order->expects($this->any())
            ->method('getUpdatedAt')
            ->willReturn((new \DateTime())->format('Y-m-d H:i:s'));

        return $order;
    }

    private function getShippingAddress()
    {
        $address = $this->getMockBuilder(\Magento\Sales\Model\Order\Address::class)
            ->disableOriginalConstructor()
            ->getMock();
        $address->expects($this->any())
            ->method('getFirstname')
            ->willReturn('Firstson');
        $address->expects($this->any())
            ->method('getLastname')
            ->willReturn('Lastson');
        $address->expects($this->any(2))
            ->method('getStreetLine')
            ->willReturn('66 Doctor Doe St', 'Ap 111');
        $address->expects($this->any())
            ->method('getCity')
            ->willReturn('San Diego');
        $address->expects($this->any())
            ->method('getRegion')
            ->willReturn('California');
        $address->expects($this->any())
            ->method('getPostcode')
            ->willReturn(92122);
        $address->expects($this->any())
            ->method('getCountryId')
            ->willReturn(1);

        return $address;
    }

    public function testGetRouteObjectWithMultipleItems()
    {
        $expected = [
            'source_order_id' => 100000012,
            'source_order_number' => 12,
            'source_created_on' => date(DATE_ATOM, time()),
            'source_updated_on' => date(DATE_ATOM, time()),
            'subtotal' => 60.0,
            'amount_covered' => 60.0,
            'currency' => 'USD',
            'taxes' => 0.0,
            'insurance_selected' => false,
            'customer_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'email' => 'email@test.com'
            ],
            'shipping_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'street_address1' => '66 Doctor Doe St',
                'street_address2' => 'Ap 111',
                'city' => 'San Diego',
                'province' => 'California',
                'zip' => 92122,
                'country_code' => 1
            ],
            'line_items' => [
                [
                    'source_product_id' => 123,
                    'sku' => 'we-123',
                    'name' => 'Test Product',
                    'price' => 10.0,
                    'quantity' => 3,
                    'upc' => '',
                    'image_url' => 'http://localhost/image/product.jpg',
                    'source_id' => '',
                    'delivery_method' => 'ship_to_home',
                    'is_insured' => false,
                    'weight' => [
                        'value' => 0.0,
                        'unit' => null,
                    ],
                    'origin_location' => [
                        'address1' => null,
                        'address2' => null,
                        'city' => null,
                        'province_code' => null,
                        'zip' => null,
                        'country_code' => null
                    ],
                    'shipping_method' => 'flat_rate',
                    'vendor' => 'magento',
                    'fulfillment_status' => 'unfulfilled'

                ],
                [
                    'source_product_id' => 124,
                    'sku' => 'we-1234',
                    'name' => 'Test Product 2',
                    'price' => 15.0,
                    'quantity' => 2,
                    'upc' => '',
                    'image_url' => 'http://localhost/image/product.jpg',
                    'source_id' => '',
                    'delivery_method' => 'ship_to_home',
                    'is_insured' => false,
                    'weight' => [
                        'value' => 0.0,
                        'unit' => null,
                    ],
                    'origin_location' => [
                        'address1' => null,
                        'address2' => null,
                        'city' => null,
                        'province_code' => null,
                        'zip' => null,
                        'country_code' => null
                    ],
                    'shipping_method' => 'flat_rate',
                    'vendor' => 'magento',
                    'fulfillment_status' => 'unfulfilled'

                ]

            ],
            'shipping_total' => null,
            'discounts_total' => null,
            'order_total' => null,
            'cart_ref' => null,
            'quote_id' => null,
            'billing_details' => [
                'first_name' => 'Firstson',
                'last_name' => 'Lastson',
                'street_address1' => '66 Doctor Doe St',
                'street_address2' => 'Ap 111',
                'city' => 'San Diego',
                'province' => 'California',
                'zip' => 92122,
                'country_code' => 1,
                'company' => null
            ]

        ];

        $order = $this->createCustomerLoggedInOrderWithMultipleItemsMock();

        $this->assertEquals(new V2OrdersBody($expected), $this->orderParser->getRouteObject($order, 0));
    }
}
