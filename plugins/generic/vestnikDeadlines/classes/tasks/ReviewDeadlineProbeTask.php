<?php

namespace APP\plugins\generic\vestnikDeadlines\classes\tasks;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\vestnikDeadlines\VestnikDeadlinesPlugin;
use Illuminate\Support\Facades\Mail;
use PKP\log\SubmissionEmailLogEventType;
use PKP\mail\mailables\ReviewRemindAuto;
use PKP\scheduledTask\ScheduledTask;
use PKP\scheduledTask\ScheduledTaskHelper;

class ReviewDeadlineProbeTask extends ScheduledTask
{
    private VestnikDeadlinesPlugin $plugin;

    public function __construct(VestnikDeadlinesPlugin $plugin, array $args = [])
    {
        $this->plugin = $plugin;
        parent::__construct($args);
    }

    public function getName(): string
    {
        return __('plugins.generic.vestnikDeadlines.taskName');
    }

    protected function executeActions(): bool
    {
        $contextDao = Application::getContextDAO();
        $contexts = $contextDao->getAll(true);

        while ($context = $contexts->next()) {
            $contextId = (int) $context->getId();
            if (!$this->plugin->getEnabled($contextId)) {
                continue;
            }

            $reviewAssignments = Repo::reviewAssignment()->getCollector()
                ->filterByContextIds([$contextId])
                ->filterByIsIncomplete(true)
                ->getMany();

            foreach ($reviewAssignments as $reviewAssignment) {
                // Ищем приглашение без ответа (не подтверждено, не отклонено, не отменено)
                if (
                    $reviewAssignment->getDateConfirmed() !== null
                    || $reviewAssignment->getDeclined()
                    || $reviewAssignment->getCancelled()
                ) {
                    continue;
                }

                $submission = Repo::submission()->get($reviewAssignment->getSubmissionId());
                $reviewer = Repo::user()->get($reviewAssignment->getReviewerId());

                if (!$submission || !$reviewer) {
                    continue;
                }

                $template = Repo::emailTemplate()->getByKey($contextId, ReviewRemindAuto::getEmailTemplateKey());
                $mailable = new ReviewRemindAuto($context, $submission, $reviewAssignment);
                $mailable
                    ->from($context->getData('contactEmail'), $context->getData('contactName'))
                    ->recipients([$reviewer])
                    ->subject($template->getLocalizedData('subject', $context->getPrimaryLocale()))
                    ->body($template->getLocalizedData('body', $context->getPrimaryLocale()));

                Mail::send($mailable);

                // Записываем письмо в историю статьи
                Repo::emailLogEntry()->logMailable(
                    SubmissionEmailLogEventType::REVIEW_REMIND,
                    $mailable,
                    $submission
                );

                $this->addExecutionLogEntry(
                    sprintf(
                        'Probe email sent to %s (reviewAssignment #%d, submission #%d)',
                        $reviewer->getEmail(),
                        $reviewAssignment->getId(),
                        $submission->getId()
                    ),
                    ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
                );

                // Для пробы отправляем ровно одно письмо и завершаем работу
                return true;
            }
        }

        $this->addExecutionLogEntry(
            'No unanswered review assignments found.',
            ScheduledTaskHelper::SCHEDULED_TASK_MESSAGE_TYPE_NOTICE
        );

        return true;
    }
}
