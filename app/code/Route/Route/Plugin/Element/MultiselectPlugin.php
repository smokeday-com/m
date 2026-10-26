<?php

namespace Route\Route\Plugin\Element;

use Magento\Framework\Data\Form\Element\Multiselect;

class MultiselectPlugin
{
    public function after__call(MultiSelect $subject, $result, $args)
    {
        if ($args === "getValues") {
            foreach ($result as $key => $value) {
                if (!isset($value['label'])) {
                    $value['label'] = '';
                }
                $newResult[$key] = $value;
            }
            return $newResult;
        }
        return $result;
    }
}
