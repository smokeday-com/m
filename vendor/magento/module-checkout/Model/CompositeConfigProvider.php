<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Checkout\Model;

/**
 * Composite checkout configuration provider.
 *
 * @see \Magento\Checkout\Model\ConfigProviderInterface
 * @api
 * @since 100.0.2
 */
class CompositeConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigProviderInterface[]
     */
    private $configProviders;

    /**
     * @param ConfigProviderInterface[] $configProviders
     * @codeCoverageIgnore
     */
    public function __construct(
        array $configProviders
    ) {
        $this->configProviders = $configProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config = [];
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Skip checkout-only Signifyd and Vault config providers on /checkout/cart/ in CompositeConfigProvider and skip Magento_Csp header rendering on AJAX/POST requests":
        # https://github.com/smokeday-com/m/issues/25
        $request = \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\App\RequestInterface::class
        );
        $isCartPage = method_exists($request, 'getFullActionName')
            && $request->getFullActionName() === 'checkout_cart_index';
        foreach ($this->configProviders as $configProvider) {
            if (
                $isCartPage
                && (
                    $configProvider instanceof \Signifyd\Connect\Model\ConfigProvider
                    || $configProvider instanceof \Magento\Vault\Model\Ui\VaultConfigProvider
                )
            ) {
                continue;
            }
            $config = array_merge_recursive($config, $configProvider->getConfig());
        }
        return $config;
    }
}
