<?php

namespace APP\plugins\generic\vestnikTags;

use APP\core\Application;
use PKP\core\Core;
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
        $handler = & $args[3];

        if ($args[0] === 'vestnikTags') {
            $handler = new VestnikTagsHandler($this);
            return Hook::ABORT;
        }
        if ($args[0] === 'vestnikTagsCatalog') {
            $handler = new CatalogHandler($this);
            return Hook::ABORT;
        }
        return Hook::CONTINUE;
    }

    // Справочник тегов: хранится в настройках плагина, при первом обращении берётся из tags.json
    public function getTags(int $contextId): array
    {
        $tags = json_decode((string) $this->getSetting($contextId, 'tags'), true);
        if (!$tags) {
            $file = Core::getBaseDir() . '/' . $this->getPluginPath() . '/data/tags.json';
            $tags = json_decode(file_get_contents($file), true) ?? [];
            $this->saveTags($contextId, $tags);
        }
        return $tags;
    }

    public function saveTags(int $contextId, array $tags): void
    {
        $this->updateSetting($contextId, 'tags', json_encode(array_values($tags), JSON_UNESCAPED_UNICODE));
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
