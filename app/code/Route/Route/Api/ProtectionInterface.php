<?php

namespace Route\Route\Api;

use Route\Route\Api\Data\RouteProtectionDataInterface;
use Route\Route\Api\Data\RouteProtectionOutputInterface;

interface ProtectionInterface
{
    /**
     * @param $data
     * @return Route\Route\Api\Data\RouteProtectionOutputInterface
     */
    public function protect(RouteProtectionDataInterface $protectionData);
}
