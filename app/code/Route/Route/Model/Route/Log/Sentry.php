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

namespace Route\Route\Model\Route\Log;

use Route\Route\Model\Route\Client;

class Sentry extends Client
{
    const SENTRY_KEY = '23c296ffd2dd44f58917de51e2f42eaa';
    const SENTRY_PROJECT_ID = '2048206';
    const SENTRY_LOG_CONTEXT_LINES = 10;
    const SENTRY_TIMEOUT = 5;


    protected $_eventType = '';
    protected $_eventMessage = '';
    protected $_eventTrace = [];
    protected $_requiredLogLevel = ['error','exception'];

    private $_extraData = [];

    /**
     * Send log event to Sentry
     *
     * @param $eventType
     * @param $eventMessage
     * @param $eventTrace
     * @param $extraData
     * @return bool|string
     */
    public function send($eventType, $eventMessage, $eventTrace, $extraData = [])
    {
        if (!$this->canBeSent($eventType)) {
            return false;
        }

        $this->_eventType = $eventType;
        $this->_eventMessage = $eventMessage;
        $this->_eventTrace = $eventTrace;
        $this->_extraData = $extraData;
        $this->_setSentryAuth();
        try {
            $log = $this->postMethod(
                $this->_getSentryApiUrl(),
                $this->_prepareEvent()
            )->execute([parent::CREATED_HTTP_CODE, parent::SUCCESS_HTTP_CODE]);
        } catch (\Exception $exception) {
            return false;
        }
        return $log;
    }

    /**
     * Initialize rest client to Route API defining basic headers
     *
     * @return void
     */
    public function initClient()
    {
        parent::initClient();
        curl_setopt($this->client, CURLOPT_TIMEOUT, self::SENTRY_TIMEOUT);
    }

    /**
     * Send logs according to log level
     *
     * @param $eventType
     * @return bool
     */
    private function canBeSent($eventType)
    {
        return in_array($eventType, $this->_requiredLogLevel) || $this->helper->isDebugEnable();
    }

    /**
     * Set Sentry Auth if not exist yet
     */
    private function _setSentryAuth()
    {
        $headers = $this->getHeaders();
        if (!isset($headers['X-Sentry-Auth'])) {
            parent::addHeader('X-Sentry-Auth', 'X-Sentry-Auth: ' . $this->_getSentryAuth());
        }
    }

    /**
     * Prepare event in array
     *
     * @return array
     */
    private function _prepareEvent()
    {
        $eventArray = [];
        $eventArray['message'] = (string)$this->_eventMessage;
        $eventArray['platform'] = 'php';
        $eventArray['type'] = (string)$this->_eventMessage;
        $eventArray['tags'] = [
            ['module', 'Route Module'],
            ['module.version', (string) $this->helper->getVersion()],
            ['url', $this->helper->getStoreUrl()],
            ['php.version', phpversion()],
            ['report.level', strtolower($this->_eventType)],
            ['http.server', isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : ''],
            ['magento.version', $this->helper->getMageVersion()]
        ];

        $eventArray['exception'] = [
            'values' => [
                [
                    'type' => ucfirst($eventArray['type']),
                    'module' => 'Route Magento 2 Integration',
                    'value' => $eventArray['message'],
                    'stacktrace' => [
                        'frames' => $this->_prepareStackTrace()
                    ]
                ]
            ]
        ];
        $eventArray['extra']['url'] = $this->helper->getStoreUrl();

        if (!empty($this->_extraData)) {
            $eventArray['extra']['extraData'] = $this->_extraData;
        }

        return $eventArray;
    }

    /**
     * Receive error or exception traces, return formatted array for Sentry
     *
     * @return array
     */
    private function _prepareStackTrace()
    {
        $tracesArray = [];
        foreach (array_reverse($this->_eventTrace) as $trace) {
            if ($this->_isValidTrace($trace)) {
                $traceArray = [];
                $traceArray['filename'] = $trace['file'];
                $traceArray['abs_path'] = $trace['file'];
                $traceArray['lineno'] = (int)$trace['line'];
                $traceArray['function'] = $trace['function'];
                $traceArray['context_line'] = $trace['function'];
                $traceArray['pre_context'][0] = "";
                $traceArray['post_context'][0] = "";

                $sourceCodeExcerpt = $this->getSourceCodeExcerpt(
                    $traceArray['filename'],
                    $traceArray['lineno'],
                    self::SENTRY_LOG_CONTEXT_LINES
                );

                if (isset($sourceCodeExcerpt['context_line'])) {
                    $traceArray['context_line'] = $sourceCodeExcerpt['context_line'];
                }

                if (isset($sourceCodeExcerpt['pre_context'])) {
                    $traceArray['pre_context'] = $sourceCodeExcerpt['pre_context'];
                }
                if (isset($sourceCodeExcerpt['post_context'])) {
                    $traceArray['post_context'] = $sourceCodeExcerpt['post_context'];
                }
                $traceArray['in_app'] = false;
                $tracesArray[] = $traceArray;
            }
        }
        return $tracesArray;
    }

    /**
     * Check if is a valid trace
     * @param $trace
     * @return bool
     */
    private function _isValidTrace($trace)
    {
        if (isset($trace['file']) && isset($trace['line']) && isset($trace['function'])) {
            if (!empty($trace['file']) && !empty($trace['line']) && !empty($trace['function'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets an excerpt of the source code around a given line.
     *
     * @param $path            string The file path
     * @param $lineNumber      int The line to centre about
     * @param $maxLinesToFetch int The maximum number of lines to fetch
     */
    protected function getSourceCodeExcerpt($path, $lineNumber, $maxLinesToFetch)
    {
        if (!is_readable($path) || !is_file($path)) {
            return [];
        }

        $frame = [
            'pre_context' => [],
            'context_line' => '',
            'post_context' => [],
        ];

        $target = max(0, ($lineNumber - ($maxLinesToFetch + 1)));
        $currentLineNumber = $target + 1;

        try {
            $file = new \SplFileObject($path);
            $file->seek($target);

            while (!$file->eof()) {
                /** @var string $line */
                $line = $file->current();
                $line = rtrim($line ?? '', "\r\n");

                if ($currentLineNumber == $lineNumber) {
                    $frame['context_line'] = $line;
                } elseif ($currentLineNumber < $lineNumber) {
                    $frame['pre_context'][] = $line;
                } elseif ($currentLineNumber > $lineNumber) {
                    $frame['post_context'][] = $line;
                }

                ++$currentLineNumber;

                if ($currentLineNumber > $lineNumber + $maxLinesToFetch) {
                    break;
                }

                $file->next();
            }
        } catch (\Exception $exception) {
            // Do nothing, if any error occurs while trying to get the excerpts
            // it's not a drama
        }

        return $frame;
    }

    /**
     * Get Sentry Auth
     *
     * @return string
     */
    private function _getSentryAuth()
    {
        $sentryAuth = 'Sentry sentry_version=7, sentry_key=';
        $sentryAuth.= self::SENTRY_KEY;
        $sentryAuth.= ', sentry_client=raven-bash/0.1';
        return $sentryAuth;
    }

    /**
     * Get Sentry API endpoint
     *
     * @return string
     */
    private function _getSentryApiUrl()
    {
        return 'https://sentry.io/api/' . self::SENTRY_PROJECT_ID . '/store/';
    }
}
