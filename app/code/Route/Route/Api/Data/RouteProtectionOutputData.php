<?php

namespace Route\Route\Api\Data;

class RouteProtectionOutputData implements RouteProtectionOutputInterface, \JsonSerializable
{
    private bool $success = false;
    private string $routeFee = '';
    private string $currency = '';

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function getRouteFee(): string
    {
        return $this->routeFee;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setSuccess(bool $success)
    {
        $this->success = $success;
        return $this;
    }

    public function setRouteFee(string $routeFee)
    {
        $this->routeFee = $routeFee;
        return $this;
    }

    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'route_fee' => $this->routeFee,
            'currency' => $this->currency
        ];
    }
}
