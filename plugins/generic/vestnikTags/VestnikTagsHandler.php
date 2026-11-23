<?php

namespace APP\plugins\generic\vestnikTags;

use APP\facades\Repo;
use APP\handler\Handler;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

// Проба задачи #27: читаем ключевые слова статьи и научные интересы рецензентов.
class VestnikTagsHandler extends Handler
{
    public VestnikTagsPlugin $plugin;

    public function __construct(VestnikTagsPlugin $plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;
        $this->addRoleAssignment(
            [Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_SITE_ADMIN],
            ['index']
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function index($args, $request)
    {
        header('Content-Type: text/html; charset=utf-8');

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');
        $submission = Repo::submission()->get($submissionId);
        if (!$submission || (int) $submission->getData('contextId') !== (int) $context->getId()) {
            echo 'Статья не найдена';
            return;
        }
        $publication = $submission->getCurrentPublication();

        echo '<h2>Ключевые слова статьи ' . $submissionId . '</h2>';
        $keywords = $publication->getData('keywords') ?: [];
        foreach ($keywords as $locale => $list) {
            $names = array_column($list, 'name');
            echo '<p>' . htmlspecialchars((string) $locale) . ': ' . htmlspecialchars(implode(', ', $names)) . '</p>';
        }

        echo '<h2>Рецензенты и их интересы</h2>';
        $reviewers = Repo::user()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByRoleIds([Role::ROLE_ID_REVIEWER])
            ->getMany();

        $count = 0;
        foreach ($reviewers as $user) {
            $interests = Repo::userInterest()->getInterestsForUser($user);
            echo '<p>' . htmlspecialchars($user->getFullName()) . ': '
                . htmlspecialchars(implode(', ', $interests)) . '</p>';
            $count++;
        }
        if ($count === 0) {
            echo '<p>Рецензентов нет</p>';
        }
    }
}