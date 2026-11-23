<?php

namespace APP\plugins\generic\vestnikTags;

use APP\core\Application;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class VestnikTagsPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if (Application::isInstalled() && !Application::isUpgrading() && $this->getEnabled($mainContextId)) {
            Hook::add('LoadHandler', $this->setPageHandler(...));
        }

        return true;
    }

    public function setPageHandler(string $hookName, array $args): bool
    {
        if ($args[0] === 'vestnikTags') {
            $handler = & $args[3];
            $handler = new VestnikTagsHandler($this);
            return Hook::ABORT;
        }
        return Hook::CONTINUE;
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.vestnikTags.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.vestnikTags.description');
    }
}