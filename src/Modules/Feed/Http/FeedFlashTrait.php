<?php

declare(strict_types=1);

namespace Lwt\Modules\Feed\Http;

use Lwt\Shared\Infrastructure\Http\FlashMessageService;

/**
 * Shared flash message rendering for Feed controllers.
 *
 * @since 3.0.0
 */
trait FeedFlashTrait
{
    /**
     * Render flash messages from the flash message service.
     *
     * @param FlashMessageService $flashService Flash message service
     *
     * @return void
     */
    protected function renderFlashMessages(FlashMessageService $flashService): void
    {
        $flashMessages = $flashService->getAndClear();
        foreach ($flashMessages as $flashMsg) {
            $isError = FlashMessageService::isError($flashMsg['type']);
            $notifClass = $isError ? 'is-danger' : 'is-success';
            $autoHide = $isError ? '' : ' data-auto-hide="true"';
            echo '<div class="notification ' . $notifClass . '"' . $autoHide . '>' .
                '<button class="delete" aria-label="close"></button>' .
                htmlspecialchars($flashMsg['message']) .
                '</div>';
        }
    }
}
