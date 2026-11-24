<?php

namespace APP\plugins\generic\vestnikDeadlines;

use APP\core\Application;
use APP\plugins\generic\vestnikDeadlines\classes\tasks\ReviewDeadlineProbeTask;
use PKP\plugins\GenericPlugin;
use PKP\plugins\interfaces\HasTaskScheduler;
use PKP\scheduledTask\PKPScheduler;

class VestnikDeadlinesPlugin extends GenericPlugin implements HasTaskScheduler
{
    public function register($category, $path, $mainContextId = null)
    {
        if (!parent::register($category, $path, $mainContextId)) {
            return false;
        }

        if (Application::isInstalled() && !Application::isUpgrading() && $this->getEnabled($mainContextId)) {
            // Здесь позже подключим хуки и страницу для секретаря.
        }

        return true;
    }

    public function registerSchedules(PKPScheduler $scheduler): void
    {
        $scheduler
            ->addSchedule(new ReviewDeadlineProbeTask($this))
            ->name(ReviewDeadlineProbeTask::class)
            ->hourly()
            ->withoutOverlapping();
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.vestnikDeadlines.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.vestnikDeadlines.description');
    }
}
