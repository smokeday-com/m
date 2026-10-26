<?php

namespace Route\Route\Model\Route\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
use Magento\Framework\Data\OptionSourceInterface;

class Insured extends AbstractSource implements SourceInterface, OptionSourceInterface
{
    const INSURED = 1;

    const NOT_INSURED = 0;

    /**
     *
     *
     * @return int[]
     */
    public function getVisibleStatusIds()
    {
        return [self::INSURED];
    }

    /**
     *
     * @return int[]
     */
    public function getSaleableStatusIds()
    {
        return [self::INSURED];
    }

    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public static function getOptionArray()
    {
        return [self::INSURED => __('Yes'), self::NOT_INSURED => __('No')];
    }

    /**
     * Retrieve option array with empty value
     *
     * @return string[]
     */
    public function getAllOptions()
    {
        $result = [];

        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }

        return $result;
    }

    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::getOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }
}
