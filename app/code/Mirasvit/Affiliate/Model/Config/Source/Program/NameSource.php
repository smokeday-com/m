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

namespace Mirasvit\Affiliate\Model\Config\Source\Program;

use Magento\Framework\Data\OptionSourceInterface;
use Mirasvit\Affiliate\Repository\ProgramRepository;

class NameSource implements OptionSourceInterface
{
    private $programRepository;

    public function __construct(
        ProgramRepository $programRepository
    ) {
        $this->programRepository = $programRepository;
    }

    public function toOptionArray(): array
    {
        $data = [];

        $collection = $this->programRepository->getCollection();

        foreach ($collection as $program) {
            $data[] = [
                'value'    => $program->getId(),
                'label'    => $program->getName(),
                'disabled' => true,
            ];
        }

        return $data;
    }
}
