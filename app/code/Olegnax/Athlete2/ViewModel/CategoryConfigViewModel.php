<?php

declare(strict_types=1);

namespace Olegnax\Athlete2\ViewModel;

use Olegnax\Athlete2\Service\GetCurrentCategoryService;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 *  CategoryConfigViewModel
 */
class CategoryConfigViewModel implements ArgumentInterface
{
    /**
     * @var CategoryInterface|null
     */
    protected $category;

    /**
     * @var GetCurrentCategoryService
     */
    private $currentCategoryService;

    /**
     * @param GetCurrentCategoryService $currentCategoryService
     */
    public function __construct(GetCurrentCategoryService $currentCategoryService)
    {
        $this->currentCategoryService = $currentCategoryService;
    }

    /**
     * @return CategoryInterface|null
     */
    public function getCategory(): ?CategoryInterface
    {
        if (!$this->category) {
            $this->category = $this->currentCategoryService->getCategory();
        }

        return $this->category;
    }

    public function getCatData($option)
    {
        $category = $this->getCategory();
        return array_key_exists($option, $category) ? $category[$option] : '';
    }
}
