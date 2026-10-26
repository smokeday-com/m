<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-affiliate
 * @version   1.5.2
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Affiliate\Model;

use DateTime;
use Magento\Framework\DataObject;

class Chart extends DataObject
{
    public const FROM          = 'from';
    public const TO            = 'to';
    public const PERIOD_DAY    = 'day';
    public const PERIOD_WEEK   = 'week';
    public const PERIOD_MONTH  = 'month';
    public const TYPE_LINE     = 'line';
    public const TYPE_BAR      = 'bar';
    public const CURRENCY_RATE = 'currency_rate';

    private const PERIOD_DAY_LIMIT  = 31;
    private const PERIOD_WEEK_LIMIT = 365;

    private $datasets = [];

    public function addDatasetData(string $label, string $type, string $color, array $data)
    {
        $dataset = [
            'label'           => $label,
            'type'            => $type,
            'backgroundColor' => $color,
            'borderColor'     => $color,
            'borderWidth'     => 2,
            'data'            => [],
        ];
        if ($type == self::TYPE_LINE) {
            $dataset['fill']    = false;
            $dataset['tension'] = 0.4;
        }

        foreach ($data as $item) {
            $dataset['data'][$item['x']] = $item['y'];
        }

        $key = strtolower($label);

        $this->datasets[$key] = $dataset;
    }

    public function getPeriod(): string
    {
        $from = $this->getFrom();
        $to   = $this->getTo();
        $days = (int)$to->diff($from)->format("%a");

        if ($days <= self::PERIOD_DAY_LIMIT) {
            return self::PERIOD_DAY;
        }
        if ($days <= self::PERIOD_WEEK_LIMIT) {
            return self::PERIOD_WEEK;
        }

        return self::PERIOD_MONTH;
    }

    public function getChartConfig(): array
    {
        $labels = $this->getLabels();

        $configDatasets = [];
        foreach ($this->datasets as $dataset) {
            $datasetData = $dataset['data'];

            $dataset['data'] = [];
            foreach ($labels as $x) {
                $dataset['data'][] = (array_key_exists($x, $datasetData)) ? $datasetData[$x] : 0;
            }

            $configDatasets[] = $dataset;
        }

        return [
            'type'    => 'bar',
            'data'    => [
                'labels'   => $labels,
                'datasets' => $configDatasets,
            ],
            'options' => [
                'scales' => [
                    'x' => [
                        'ticks' => [
                            'minRotation' => 0,
                            'maxRotation' => 0,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getFrom(): DateTime
    {
        return $this->getData(self::FROM);
    }

    public function getTo(): DateTime
    {
        return $this->getData(self::TO);
    }

    public function getCurrencyRate(): float
    {
        return (float)$this->getData(self::CURRENCY_RATE);
    }

    private function getLabels(): array
    {
        $labels = [];
        foreach ($this->datasets as $dataset) {
            $datasetLabels = array_keys($dataset['data']);
            $labels        = array_merge($labels, $datasetLabels);
        }
        $labels = array_unique($labels);
        usort($labels, function ($a, $b) {
            return strtotime($a) <=> strtotime($b);
        });

        return $labels;
    }
}
