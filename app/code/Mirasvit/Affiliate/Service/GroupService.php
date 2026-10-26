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

namespace Mirasvit\Affiliate\Service;

use Mirasvit\Affiliate\Api\Data\GroupInterface;
use Mirasvit\Affiliate\Model\Group\Rule\GroupRule;
use Mirasvit\Affiliate\Model\ResourceModel\Group\Collection as GroupCollection;
use Mirasvit\Affiliate\Repository\AccountRepository;
use Mirasvit\Affiliate\Repository\GroupRepository;

class GroupService
{
    private $groupRepository;

    private $accountRepository;

    public function __construct(
        GroupRepository   $groupRepository,
        AccountRepository $accountRepository
    ) {
        $this->groupRepository   = $groupRepository;
        $this->accountRepository = $accountRepository;
    }

    public function refresh(int $group_id = null): bool
    {
        $groupsToUpdate = $this->getGroupsToUpdade($group_id);

        $manualGroupIds = $this->groupRepository->getManualUpdateGroupsIds();

        $accounts = $this->accountRepository->getCollection();

        foreach ($accounts as $account) {
            if (in_array($account->getGroupId(), $manualGroupIds)) {
                continue;
            }

            foreach ($groupsToUpdate as $group) {
                if ($group->getRule()->validate($account)) {
                    $account->setGroupId($group->getId());
                    $this->accountRepository->save($account);
                }
            }
        }

        return true;
    }

    /**
     * @return GroupInterface[]
     */
    private function getGroupsToUpdade(int $group_id = null): array
    {
        if ($group_id) {
            $group = $this->groupRepository->get($group_id);

            return $group && $group->getIsAutoUpdate()
                ? [$group]
                : [];
        }

        return $this->groupRepository->getAutoUpdateGroupsCollection()->getItems();
    }
}
