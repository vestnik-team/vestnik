<?php

namespace APP\plugins\generic\vestnikExample;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\PKPPageRouter;
use PKP\plugins\GenericPlugin;

class VestnikExamplePlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if (!Application::isInstalled() || Application::isUpgrading() || !$this->getEnabled($mainContextId)) {
            return true;
        }

        $request = Application::get()->getRequest();
        if (!$request->getRouter() instanceof PKPPageRouter) {
            return true;
        }

        $page = $request->getRequestedPage();
        if ($page === 'workflow' || ($page === 'dashboard' && $request->getRequestedOp() === 'editorial')) {
            $template = TemplateManager::getManager($request);
            $template->setConstants([
                'VESTNIK_EXAMPLE' => [
                    'message' => __('plugins.generic.vestnikExample.notice.message'),
                    'stop' => __('plugins.generic.vestnikExample.notice.stop'),
                ],
            ]);

            $pluginUrl = $request->getBaseUrl() . '/' . $this->getPluginPath();
            $template->addStyleSheet('vestnikExampleNotice', $pluginUrl . '/css/notice.css', ['contexts' => ['backend']]);
            $template->addJavaScript(
                'vestnikExampleNotice',
                $pluginUrl . '/js/notice.js',
                ['contexts' => ['backend'], 'priority' => TemplateManager::STYLE_SEQUENCE_LAST],
            );
        }

        return true;
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.vestnikExample.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.vestnikExample.description');
    }
}
