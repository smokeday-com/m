<?php

namespace Macrew\StockLogger\Plugin;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SqlStockTracker
{
    private $logger;

    public function __construct()
    {
        $this->logger = new Logger('stock_sql_trace');
        $this->logger->pushHandler(
            new StreamHandler(BP . '/var/log/stock_sql_trace.log', Logger::WARNING)
        );
    }

    /**
     * Around plugin for write() method
     */
    public function aroundQuery(
        \Magento\Framework\DB\Adapter\Pdo\Mysql $subject,
        callable $proceed,
        $sql,
        $bind = []
    ) {
        // Only log queries that affect cataloginventory_stock_item
        /*if (stripos($sql, 'cataloginventory_stock_item') !== false &&
            preg_match('/insert|update|delete/i', $sql)) {

            $this->logger->warning('Stock SQL executed:', [
                'sql' => $sql,
                'bind' => $bind,
                'stack' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 50) // capture top 10 callers
            ]);
        }*/

        return $proceed($sql, $bind);
    }
}