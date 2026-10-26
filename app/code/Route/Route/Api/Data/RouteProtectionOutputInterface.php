<?php

namespace Route\Route\Api\Data;

/**
 * Route Output data
 *
 * @api
 */
interface RouteProtectionOutputInterface
{
    /**
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * @return string
     */
    public function getRouteFee(): string;

    /**
     * @return string
     */
    public function getCurrency(): string;

    /**
     * @param bool $success
     * @return void
     */
    public function setSuccess(bool $success);

    /**
     * @param string $routeFee
     * @return void
     */
    public function setRouteFee(string $routeFee);

    /**
     * @param string $currency
     * @return void
     */
    public function setCurrency(string $currency);
}
