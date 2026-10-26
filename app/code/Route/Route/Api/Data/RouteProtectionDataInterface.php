<?php

namespace Route\Route\Api\Data;

interface RouteProtectionDataInterface
{
    /**
     * @return bool
     */
    public function getIsProtected();

    /**
     * @param bool $isProtected
     * @return void
     */
    public function setIsProtected($isProtected);

    /**
     * @return mixed
     */
    public function getQuoteId();

    /**
     * @param mixed $quoteId
     * @return void
     */
    public function setQuoteId($quoteId);
}
