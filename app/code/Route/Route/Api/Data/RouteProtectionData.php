<?php

namespace Route\Route\Api\Data;

class RouteProtectionData implements RouteProtectionDataInterface
{
    private bool $isProtected = false;
    private mixed $quoteId;

    public function getIsProtected(): bool
    {
        return $this->isProtected;
    }

    public function setIsProtected($isProtected): void
    {
        $this->isProtected = $isProtected;
    }

    public function getQuoteId(): mixed
    {
        return $this->quoteId;
    }

    public function setQuoteId($quoteId): void
    {
        $this->quoteId = $quoteId;
    }
}
