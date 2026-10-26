<?php
/**
 * A Route Magento Extension that adds secure shipping
 * insurance to your orders
 *
 * Php version 7.0^
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html
 */

namespace Route\Route\Model\Route;

/**
 * User Route API client
 *
 * Php version 7.0^
 *
 * @category  Model
 * @package   Route_Route
 * @author    Route Development Team <dev@routeapp.io>
 * @copyright 2019 Route App Inc. Copyright (c) https://www.routeapp.io/
 * @license   https://www.routeapp.io/merchant-terms-of-use  Proprietary License
 * @link      https://magento.routeapp.io/magento2/index.html

 */
class User extends Client
{
    const API_ENDPOINT = 'users';
    const API_ACCOUNT_ACTIVATE_ENDPOINT = 'activate_account';
    const API_ACCOUNT_LOGIN_ENDPOINT = 'login';

    /**
     * Create new user Account
     *
     * @param array $user User data
     *
     * @return mixed
     */
    public function createUser($user)
    {
        try {
            $response =  $this
                ->setUnauthorizedRequest()
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ENDPOINT),
                    $user
                )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            return $response;
        } catch (\Exception $exception) {
            $this->logger->error(
                ($this->hasConflicted() ? "User already exists." : "User account creation has failed") . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]
            );
            return false;
        }
    }

    /**
     * Activate user Account
     *
     * @param string $email User email
     *
     * @return mixed
     */
    public function activateAccount($email)
    {
        try {

            $response =  $this
                ->setUnauthorizedRequest()
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ACCOUNT_ACTIVATE_ENDPOINT),
                    ['email' => $email]
                )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            return $response;

        } catch (\Exception $exception) {
            $this->logger->error(
                "Activation account has failed: " . $exception->getMessage(),
                $exception->getTrace(),
                [
                    'params' => $this->getData(),
                    'method' => $this->getCurrentMethod(),
                    'endpoint' => $this->getUrl(),
                ]
            );
            return false;
        }
    }

    /**
     * Login at Route account
     *
     * @param string $username username
     * @param string $password password
     *
     * @return mixed
     */
    public function login($username, $password)
    {
        try {
            $this->logger->debug("Trying to login User Account with params: " . print_r($username, 1));

            $response =  $this
                ->setUnauthorizedRequest()
                ->postMethod(
                    $this->getRouteApiUrl(self::API_ACCOUNT_LOGIN_ENDPOINT),
                    [
                        "username" => $username,
                        "password" => $password
                    ]
                )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);

            if ($this->hasFailed()) {
                $this->logger->debug("Login has failed" . print_r($this->getInfo(), 1));
            } else {
                $this->logger->debug("Successfully user login.");
            }

            return $response;
        } catch (\Exception $e) {
            return false;
        }
    }
}
