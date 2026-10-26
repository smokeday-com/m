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

namespace Route\Route\Logger;

use Route\Route\Model\Route\Log\Sentry as SentryApi;

class Sentry
{
    private $sentry;

    /**
     * Sentry constructor.
     * @param \Route\Route\Model\Route\Log\Sentry $sentry
     */
    public function __construct(
        SentryApi $sentry
    ) {
        $this->sentry = $sentry;
    }

    /**
     * Send emergency
     *
     * @param string $message
     * @param array $traces
     * @return void|null
     */
    public function emergency($message, array $traces = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('emergency', $message, $traces);
    }

    /**
     * Send alert
     *
     * @param string $message
     * @param array $traces
     * @return void|null
     */
    public function alert($message, array $traces = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('alert', $message, $traces);
    }

    /**
     * Send critical
     *
     * @param string $message
     * @param array $traces
     * @return void|null
     */
    public function critical($message, array $traces = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('critical', $message, $traces);
    }

    /**
     * Send error
     *
     * @param string $message
     * @param array $traces
     * @param array $extraData
     * @return void|null
     */
    public function error($message, array $traces = [], array $extraData = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('error', $message, $traces, $extraData);
    }

    /**
     * Send warning
     *
     * @param string $message
     * @param array $traces
     * @return void|null
     */
    public function warning($message, array $traces = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('warning', $message, $traces);
    }

    /**
     * Send notice
     *
     * @param string $message
     * @param array $traces
     * @return void|null
     */
    public function notice($message, array $traces = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('notice', $message, $traces);
    }

    /**
     * Send info
     *
     * @param string $message
     * @param array $traces
     * @return void|null
     */
    public function info($message, array $traces = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('info', $message, $traces);
    }

    /**
     * Send debug
     *
     * @param string $message
     * @param array $traces
     * @return void|null
     */
    public function debug($message, array $traces = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('debug', $message, $traces);
    }

    /**
     * Send log
     *
     * @param mixed $level
     * @param string $message
     * @param array $traces
     * @return void|null
     */
    public function log($level, $message, array $traces = [])
    {
        if (empty($traces)) {
            $traces = debug_backtrace();
        }
        $this->sentry->send('log', $message, $traces);
    }
}
