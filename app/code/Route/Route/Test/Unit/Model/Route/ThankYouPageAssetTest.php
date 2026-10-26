<?php
/**
 * Created by PhpStorm.
 * User: eduan
 * Date: 08/03/20
 * Time: 00:53
 */

namespace Route\Route\Test\Unit\Model\Route;

use Magento\Framework\App\ProductMetadata;
use PHPUnit\Framework\TestCase;

class ThankYouPageAssetTest extends TestCase
{
    private function _getRouteModelWidgetMock($returnValue)
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $httpClientFactory = $objectManager->getObject('\Magento\Framework\HTTP\ZendClientFactory');
        $helper = $objectManager->getObject('\Route\Route\Helper\Data');
        $logger = $objectManager->getObject('\Route\Route\Logger\Sentry');
        $parser = $objectManager->getObject('\Route\Route\Model\Route\OrderParser');
        $fallback = $objectManager->getObject('\Route\Route\Model\Route\Operation\FallbackFactory');
        $shipmentClient = $objectManager->getObject('\Route\Route\Model\Route\Shipment');
        $helperSetup = $objectManager->getObject('\Route\Route\Helper\Setup');
        $cache = $objectManager->getObject('\Magento\Framework\App\Cache');
        $productMetadata = $objectManager->getObject(ProductMetadata::class);

        $mock = $this->getMockForAbstractClass(                         //mock generator made for abstract classes
            \Route\Route\Model\Route\Widget::class,     // the fully qualified name of the abstract class to mock
            [   $httpClientFactory,
                $helper,
                $logger,
                $parser,
                $fallback,
                $shipmentClient,
                $helperSetup,
                $cache,
                $productMetadata
                ],                                                  // parameters for the class constructor
            '',                                 // no new name for the class to mock
            true,                               // do not call the original constructor
            true,                               // call original clone
            true,                               // call autoload
            ['execute']
        );                      // mock the execute method
        $mock->expects($this->any())->method('execute')
            ->will($this->returnValue($returnValue));
        return $mock;
    }

    public function testHasThankYouPageAsset()
    {
        $returnValue = [
            "store_domain" => "domain.test",
            "platform_id"  => "magento1",
            "asset_settings" => [
                "asset_live" => true,
                "asset_content" => [
                    "raw_html" => "testResult",
                    "css_url" => "https://route-cdn.s3.amazonaws.com/route-widget-shopify/images/thank-you-asset/static-asset.css"
                ]
            ]
        ];
        $mock = $this->_getRouteModelWidgetMock($returnValue);
        $response = $mock->getThankYouPageWidget('domain.test');

        //check content of return
        $this->assertEquals($response['asset_settings']['asset_live'], true);
        $this->assertEquals($response['asset_settings']['asset_content']['css_url'], 'https://route-cdn.s3.amazonaws.com/route-widget-shopify/images/thank-you-asset/static-asset.css');
    }

    public function testCannotReadPropertyThankYouPageAsset()
    {
        $returnValue= <<<html
{"err":"Cannot read property 'asset_live' of null","message":"Cannot read property 'asset_live' of null"}
html;
        $mock = $this->_getRouteModelWidgetMock($returnValue);
        $response = $mock->getThankYouPageWidget('domain.test');

        //check content of return
        $this->assertEquals($response, false);
    }

    public function testDomainNotExistThankYouPageAsset()
    {
        $returnValue= <<<html
{"err":"NOT_FOUND","message":"Store domain not found"}
html;
        $mock = $this->_getRouteModelWidgetMock($returnValue);
        $response = $mock->getThankYouPageWidget('domain.test');

        //check content of return
        $this->assertEquals($response, false);
    }

    public function testDisableThankYouPageAsset()
    {
        $returnValue= <<<html
{"store_domain":"asdasd","platform_id":"api","asset_settings":{"asset_live":false,"asset_content":{}}}
html;
        $mock = $this->_getRouteModelWidgetMock($returnValue);
        $response = $mock->getThankYouPageWidget('domain.test');

        //check content of return
        $this->assertEquals($response, false);
    }
}
