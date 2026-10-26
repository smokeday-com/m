<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Helper
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Helper;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Backend\Model\Session\Quote as adminQuote;
use Magento\Checkout\Model\Session;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\State;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Pricing\Helper\Data as Price;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\Information;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Route\Route\Logger\Sentry;
use Route\Route\Model\Route\Merchant;
use Route\Route\Model\Route\Quote;
use Route\Route\Model\Route\User;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\Manager as moduleManager;

/**
 * Helper to setup new User/ Merchant Account
 *
 * Php version 7.0^
 *
 * @category  Helper
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */
class Setup extends Data
{
    const REGISTRATION_STEP = 'registration_step';
    const FAILED_REGISTRATION = 'failed_registration';
    const MAGENTO = 'magento2';
    const REGISTRATION_FIXED = '0';
    const REGISTRATION_STEP_USER_LOGIN_SUCCESS = '1-200';

    const FAILED_REGISTRATION_STEP_USER = '1';
    const FAILED_REGISTRATION_STEP_USER_DUPLICATED = '1-409';
    const FAILED_REGISTRATION_STEP_USER_LOGIN_FAILED = '1-401';
    const FAILED_REGISTRATION_STEP_USER_ACTIVATION = '2';
    const FAILED_REGISTRATION_STEP_MERCHANT = '3';
    const FAILED_REGISTRATION_STEP_MERCHANT_DUPLICATED = '3-409';
    const REDIRECTED = '2';
    const MODULE_INSTALLED = '1';
    const ACTIVATION_LINK = 'activation_link';

    const SETUP_CHECK_WIDGET_INSTALLATION_DATE = 'setup_check_widget_installation_date';
    const SETUP_CHECK_WIDGET_API_CALL = 'setup_check_widget_api_call';
    const SETUP_CHECK_WIDGET_PHP = 'setup_check_widget_php';
    const SETUP_CHECK_WIDGET_JS = 'setup_check_widget_js';

    private $userClient;
    private $merchantClient;
    private $adminSession;
    private $storeInfo;
    private $cacheManager;
    private $orderCollectionFactory;
    private $fullModuleList;

    /**
     * Setup constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     * @param Quote $quoteClient
     * @param StoreManagerInterface $storeManager
     * @param Price $price
     * @param User $userClient
     * @param Merchant $merchantClient
     * @param AdminSession $adminSession
     * @param Information $storeInfo
     * @param WriterInterface $configWriter
     * @param Manager $cacheManager
     * @param CollectionFactory $collectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Sentry $logger Sentry logger
     * @param CartRepositoryInterface $quoteRepository
     * @param State $state
     * @param adminQuote $adminQuoteSession
     * @param FullModuleList $fullModuleList
     * @param moduleManager $moduleManager
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        Quote $quoteClient,
        StoreManagerInterface $storeManager,
        Price $price,
        User $userClient,
        Merchant $merchantClient,
        AdminSession $adminSession,
        Information $storeInfo,
        WriterInterface $configWriter,
        Manager $cacheManager,
        CollectionFactory $collectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        Sentry $logger,
        CartRepositoryInterface $quoteRepository,
        State $state,
        adminQuote $adminQuoteSession,
        FullModuleList $fullModuleList,
        moduleManager $moduleManager,
        RedirectInterface $redirect
    ) {
        $this->userClient = $userClient;
        $this->merchantClient = $merchantClient;
        $this->adminSession = $adminSession;
        $this->storeInfo = $storeInfo;
        $this->cacheManager = $cacheManager;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->fullModuleList = $fullModuleList;
        parent::__construct(
            $context,
            $checkoutSession,
            $productMetadata,
            $moduleList,
            $quoteClient,
            $storeManager,
            $price,
            $merchantClient,
            $collectionFactory,
            $configWriter,
            $logger,
            $quoteRepository,
            $state,
            $adminQuoteSession,
            $moduleManager,
            $redirect
        );
    }

    /**
     * Register new user account at Route
     * It should only executed once
     *
     * @return bool
     */
    public function registerAccountToFreshNewInstallation()
    {
        $responseUserCreation = $this->userClient->createUser($this->getUserData());

        if ($this->userClient->hasFailed()) {
            $this->setRegistrationFailedAs(
                $this->userClient->hasConflicted() ?
                    self::FAILED_REGISTRATION_STEP_USER_DUPLICATED :
                    self::FAILED_REGISTRATION_STEP_USER
            );
            $this->cleanCache();
            return false;
        }

        if ($this->registerUser($responseUserCreation)) {
            $this->setAsInstalled();

            $this->setRegistrationFailedAs(self::REGISTRATION_FIXED);

            $this->cleanCache();

            return true;
        }
    }

    /**
     * Register new user account at Route by login action
     * It should only executed once
     *
     * @param $username
     * @param $password
     *
     * @return bool
     */
    public function registerAccountLogin($username, $password)
    {
        $responseUserCreation = $this->userClient->login($username, $password);

        if ($this->userClient->hasFailed()) {
            $this->setRegistrationFailedAs(
                self::FAILED_REGISTRATION_STEP_USER_LOGIN_FAILED
            );
            $this->cleanCache();
            return false;
        }

        if ($this->registerUser($responseUserCreation, true)) {
            $this->setAsInstalled();

            $this->setRegistrationFailedAs(self::REGISTRATION_STEP_USER_LOGIN_SUCCESS);

            $this->cleanCache();

            return true;
        }
    }

    /**
     * Register User data
     *
     * @param $responseUser
     * @param $isActive
     *
     * @return bool
     */
    public function registerUser($responseUser, $isActive = false)
    {
        try {
            $this->setSecretToken($responseUser['token']);
        } catch (\Exception $exception) {
            $this->_logger->debug('Exception while trying to store the user token: ' . $exception->getMessage(), $exception->getTrace());
            $this->setRegistrationFailedAs(self::FAILED_REGISTRATION_STEP_USER);
            $this->cleanCache();
            return false;
        }

        $this->setRegistrationFailedAs(self::REGISTRATION_FIXED);

        $this->cleanCache();

        if (!$isActive) {
            $this->activateAccount();
        }

        return $this->registerMerchant($responseUser['token']);
    }

    /**
     * Activate User Account
     *
     * @return bool
     */
    public function activateAccount()
    {
        try {
            $activationResponse = $this->userClient->activateAccount($this->getCurrentEmailAccount());
            if (!$activationResponse) {
                $this->setRegistrationFailedAs(self::FAILED_REGISTRATION_STEP_USER_ACTIVATION);
                $this->cleanCache();
                return false;
            }
            $this->setActivationLink($activationResponse['set_password_url']);
        } catch (\Exception $e) {
            $this->setRegistrationFailedAs(self::FAILED_REGISTRATION_STEP_USER_ACTIVATION);
            $this->cleanCache();
            return false;
        }

        $this->setRegistrationFailedAs(self::REGISTRATION_FIXED);

        return true;
    }

    /**
     * Register merchant account to all available stores
     *
     * @param $userToken
     *
     * @return bool
     */
    public function registerMerchant($userToken = '')
    {
        $this->_logger->debug("Creating Merchant account by Store");

        if (empty($userToken)) {
            $userToken = $this->getMerchantSecretToken();
        }

        $defaultStoreWasSet = false;
        $domains = [];

        foreach ((array)$this->storeManager->getStores() as $id => $store) {
            $currentMerchant = $this->getMerchantData($store);

            if (in_array($currentMerchant['store_domain'], $domains)) {
                continue;
            }

            $domains[] = $currentMerchant['store_domain'];

            $merchantCreationResponse = $this->merchantClient
                ->createMerchant(
                    $currentMerchant,
                    $userToken
                );

            if ($this->merchantClient->hasFailed()) {
                $this->setRegistrationFailedAs(
                    $this->merchantClient->hasConflicted() ?
                        self::FAILED_REGISTRATION_STEP_MERCHANT_DUPLICATED :
                        self::FAILED_REGISTRATION_STEP_MERCHANT
                );
                $this->cleanCache();
                return false;
            }

            //TODO Currently when we try to create merchant that already exists it's returning 200 http code success
            //TODO So we need try to update it with current platform
            if ($this->merchantClient->isSuccess()) {
                if (!$this->merchantCanBeUpdated($merchantCreationResponse)) {
                    $this->setRegistrationFailedAs(self::FAILED_REGISTRATION_STEP_MERCHANT);
                    $this->cleanCache();
                    return false;
                }

                //TODO This is happening when we try to update merchant that user doesn't own
                //TODO So we need try to recreate it with additional slash at the store's domain end
                $currentMerchant['store_domain'] = $currentMerchant['store_domain'] . '/';
                $merchantCreationResponse = $this->merchantClient
                    ->createMerchant(
                        $currentMerchant,
                        $userToken
                    );

                if ($this->merchantClient->hasFailed()) {
                    $this->setRegistrationFailedAs(
                        $this->merchantClient->hasConflicted() ?
                            self::FAILED_REGISTRATION_STEP_MERCHANT_DUPLICATED :
                            self::FAILED_REGISTRATION_STEP_MERCHANT
                    );
                    $this->cleanCache();
                    return false;
                }
            }

            if (!$defaultStoreWasSet) {
                $this->_logger->debug("Creating Merchant account to default store");

                $this->setPublicToken(
                    $merchantCreationResponse['public_api_key']
                );

                $this->setSecretToken(
                    $merchantCreationResponse['prod_api_secret']
                );
                $defaultStoreWasSet = true;
            }

            $this->_logger->debug("Creating Merchant account to specific stores");

            $this->setPublicToken(
                $merchantCreationResponse['public_api_key'],
                ScopeInterface::SCOPE_STORES,
                $store->getId()
            );

            $this->setSecretToken(
                $merchantCreationResponse['prod_api_secret'],
                ScopeInterface::SCOPE_STORES,
                $store->getId()
            );
        }

        $this->removeMerchantInvalidFlag();

        return true;
    }

    /**
     * Check if it's fresh installation
     *
     * @return bool
     */
    public function isFreshNewInstallation()
    {
        return $this->adminSession->isLoggedIn() &&
            is_null($this->registrationStep()) &&
            is_null($this->getFailedRegistration()) &&
            !$this->hasValidMerchantTokens();
    }

    /**
     * Set registration failed
     *
     * @param $status
     *
     * @return void
     */
    public function setRegistrationFailedAs($status)
    {
        $this->_logger->debug($status > 0 ? "Setup failed at step " . $status :
            "Setup was successful");
        $this->saveConfig(self::FAILED_REGISTRATION, $status);
    }

    /**
     * If it's installation has failed at user step creation step
     *
     * @return bool
     */
    public function hasUserCreationFailed()
    {
        return
            $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_USER;
    }

    /**
     * If it's installation has failed at Merchant creation step
     *
     * @return bool
     */
    public function hasMerchantCreationFailed()
    {
        return $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_MERCHANT;
    }

    /**
     * If it's installation has failed at user step creation step
     *
     * @return bool
     */
    public function hasUserCreationConflicted()
    {
        return $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_USER_DUPLICATED || $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_USER_LOGIN_FAILED;
    }

    /**
     * Check if user login has succeed
     *
     * @return bool
     */
    public function hasUserLoginSucceed()
    {
        return $this->getFailedRegistration() == self::REGISTRATION_STEP_USER_LOGIN_SUCCESS;
    }

    /**
     * If it's installation has failed at Merchant creation step
     *
     * @return bool
     */
    public function hasMerchantCreationConflicted()
    {
        return $this->getFailedRegistration() == self::FAILED_REGISTRATION_STEP_MERCHANT_DUPLICATED;
    }

    /**
     * Set secret token generated after user creation
     *
     * @param $token
     * @param $scope
     * @param int $scopeId
     */
    private function setSecretToken($token, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->_logger->debug('New secret token has set: ' . $token);
        $this->saveConfig(parent::MERCHANT_SECRET_TOKEN, $token, $scope, $scopeId);
    }

    /**
     * Set public token generated after user creation
     *
     * @param $token
     * @param $scope
     * @param int $scopeId
     */
    private function setPublicToken($token, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0)
    {
        $this->saveConfig(parent::MERCHANT_PUBLIC_TOKEN, $token, $scope, $scopeId);
    }

    /**
     * Prepare user data
     *
     * @return array
     */
    private function getUserData()
    {
        $userData = [];
        $userData['name'] = $this->adminSession->getUser()->getFirstName();
        $userData['password'] = $this->generateTempPassword();
        $userData['platform_id'] = self::MAGENTO;
        $userData['phone'] = $this->getPhoneNumber();
        $userData['primary_email'] = $this->getCurrentEmailAccount();
        return $userData;
    }

    /**
     * Prepare merchant data
     *
     * @param $store
     *
     * @return array
     */
    private function getMerchantData($store)
    {
        $merchantData = [];
        $merchantData['platform_id'] = self::MAGENTO;
        $merchantData['store_domain'] = parse_url($store->getBaseUrl(), PHP_URL_HOST);
        $merchantData['store_name'] = $store->getName();
        $merchantData['deal_size_order_count'] = $this->getDealSize();
        $merchantData['country'] = $this->getCountryByStore($store);
        $merchantData['currency'] = $store->getCurrentCurrency()->getCode();
        $merchantData['source'] = self::MAGENTO;
        $merchantData['status'] = 'Active';
        return $merchantData;
    }

    /**
     * Check if we can send compatiblity report
     *
     * @return bool
     */
    public function canSendCompatibilityReport()
    {
        $apiCalled = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_API_CALL);
        $creationDate = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_INSTALLATION_DATE);

        return (time() > $creationDate && !$apiCalled);
    }

    public function prepareCompatibilityData($store)
    {
        $widgetPhpFlag = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_PHP);
        $widgetJsFlag = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_JS);
        $creationDate = $this->getSetupCheckWidget(self::SETUP_CHECK_WIDGET_INSTALLATION_DATE);
        $successInstallation = $widgetPhpFlag && $widgetJsFlag;

        $data = [];
        $data['php_flag'] = $widgetPhpFlag ? $widgetPhpFlag : 0;
        $data['js_flag'] = $widgetJsFlag ? $widgetJsFlag : 0;
        $data['success_installation'] = $successInstallation;
        $data['install_date'] = $creationDate;
        $data['store_domain'] = parse_url($store->getBaseUrl(), PHP_URL_HOST);
        $data['platform_id'] = self::MAGENTO;
        $data['subject'] = $successInstallation ? 'Success Installation' : 'Installation Issues';
        $data['modules'] = $this->fullModuleList->getAll();
        return $data;
    }

    /**
     * Check if merchant can be updated
     * @param $merchantCreationResponse
     * @return bool
     */
    public function merchantCanBeUpdated($merchantCreationResponse)
    {
        return (isset($merchantCreationResponse['platform_id']) && strtolower($merchantCreationResponse['platform_id']) == 'email') ||
            (isset($merchantCreationResponse['status']) && strtolower($merchantCreationResponse['status']) != 'active');
    }

    /**
     * Get General Phone from settings
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        try {
            return $this->storeInfo->getStoreInformationObject($this->storeManager->getStore())->getPhone();
        } catch (\Exception $e) {
            return "";
        }
    }

    /**
     * Generates temporary password to account creation
     *
     * @return string
     */
    public function generateTempPassword()
    {
        return "pass" . substr(hash('sha256', rand()), 0, 10);
    }

    /**
     * @param $store
     * @return mixed
     */
    private function getCountryByStore($store)
    {
        $countryCode = $this->scopeConfig->getValue('general/country/default', ScopeInterface::SCOPE_STORE, $store->getId());
        return is_null($countryCode) ? '' : $countryCode;
    }

    /**
     * Set as installed successful.
     *
     * @return void
     */
    public function setAsInstalled()
    {
        $this->registerStep(self::MODULE_INSTALLED);
        $this->setSetupCheckWidget(self::SETUP_CHECK_WIDGET_INSTALLATION_DATE, time());
    }

    /**
     * Set as uninstalled.
     *
     * @return void
     */
    public function setAsUninstalled()
    {
        $this->merchantClient->updateMerchantStatus($this->merchantClient::MERCHANT_UNINSTALLED_STATUS, false);
    }

    public function cleanCache()
    {
        $this->cacheManager->flush($this->cacheManager->getAvailableTypes());
        $this->cacheManager->clean($this->cacheManager->getAvailableTypes());
    }

    /**
     * Get registration failed
     *
     * @return string
     */
    public function getFailedRegistration()
    {
        return $this->getConfigValue(self::FAILED_REGISTRATION, true);
    }

    /**
     * @return mixed
     */
    public function registrationStep()
    {
        return $this->getConfigValue(self::REGISTRATION_STEP, true);
    }

    /**
     * Set config as redirected.
     *
     * @return bool
     */
    public function setRedirected()
    {
        $this->registerStep(self::REDIRECTED);
        return true;
    }

    /**
     * @return mixed
     */
    public function wasRedirected()
    {
        return $this->registrationStep() == self::REDIRECTED;
    }

    /**
     * check if it's installed
     *
     * @return bool
     */
    public function isInstalled()
    {
        return $this->registrationStep() == self::MODULE_INSTALLED;
    }

    /**
     * @param $step
     * @return mixed
     */
    public function registerStep($step)
    {
        return $this->saveConfig(self::REGISTRATION_STEP, $step);
    }

    /**
     * Activation Link
     *
     * @param string $activationLink
     * @param string $scope
     * @param string $storeId
     *
     * @return void
     */
    public function setActivationLink($activationLink, $scope = 'default', $storeId = '0')
    {
        $this->_logger->debug('Activation link: ' . $activationLink);
        $this->saveConfig(
            self::ACTIVATION_LINK,
            $activationLink,
            $scope,
            $storeId
        );
    }

    /**
     * Get activation link
     *
     * @return mixed
     */
    public function getActivationLink()
    {
        return $this->getConfigValue(self::ACTIVATION_LINK);
    }

    /**
     * @return mixed|string
     */
    public function getCurrentEmailAccount()
    {
        return $this->adminSession->getUser()->getEmail();
    }

    public function getDealSize()
    {
        $currentDate = new \DateTime();
        $monthAgo = $currentDate->sub(new \DateInterval("P1M"));

        $orderCollection = $this->orderCollectionFactory
            ->create()
            ->addFieldToSelect('*')
            ->addFieldToFilter(
                'created_at',
                [
                    'gteq' => $monthAgo->format('YmdHis')
                ]
            );

        return strval($orderCollection->count());
    }

    /**
     * Get setup check widget config
     *
     * @param $config
     * @return mixed
     */
    public function getSetupCheckWidget($config)
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
        # https://github.com/smokeday-com/m/issues/23
        return $this->getConfigValue($config, false);
    }

    /**
     * Set setup check widget
     *
     * @param $config
     * @param bool $value
     */
    public function setSetupCheckWidget($config, $value = false)
    {
        # 2026-10-26 Dmitrii Fediuk https://github.com/dmitrii-fediuk
        # "Fix broken Route_Route QuoteApi caching and FulfilledPromise bug, increase Merchant cache TTL to 86400s, use Magento config cache in Setup::getSetupCheckWidget, and eliminate duplicate checkWidget calls and full-screen loader blocking":
        # https://github.com/smokeday-com/m/issues/23
        if (!$this->getConfigValue($config, false)) {
            $value = $value ? $value : 1;
            $this->saveConfig($config, $value);
        }
    }
}
