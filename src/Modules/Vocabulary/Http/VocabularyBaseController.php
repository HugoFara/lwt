<?php declare(strict_types=1);
/**
 * Vocabulary Base Controller
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Vocabulary\Http
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Vocabulary\Http;

use Lwt\Modules\Vocabulary\Application\Services\WordService;
use Lwt\Modules\Vocabulary\Application\Services\ExpressionService;
use Lwt\Modules\Vocabulary\Application\Services\WordUploadService;
use Lwt\Modules\Text\Application\Services\SentenceService;

require_once __DIR__ . '/../../../backend/Core/Bootstrap/db_bootstrap.php';
require_once __DIR__ . '/../../../Shared/UI/Helpers/PageLayoutHelper.php';

/**
 * Base controller for vocabulary-related controllers.
 *
 * Provides shared view rendering and lazy-loaded services.
 *
 * @since 3.0.0
 */
abstract class VocabularyBaseController
{
    /**
     * View base path.
     */
    protected string $viewPath;

    /**
     * Lazy-loaded services.
     */
    protected ?WordService $wordService = null;
    protected ?SentenceService $sentenceService = null;
    protected ?ExpressionService $expressionService = null;
    protected ?WordUploadService $uploadService = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->viewPath = __DIR__ . '/../Views/';
    }

    /**
     * Get WordService (lazy loaded).
     *
     * @return WordService
     */
    protected function getWordService(): WordService
    {
        if ($this->wordService === null) {
            $this->wordService = new WordService();
        }
        return $this->wordService;
    }

    /**
     * Get SentenceService (lazy loaded).
     *
     * @return SentenceService
     */
    protected function getSentenceService(): SentenceService
    {
        if ($this->sentenceService === null) {
            $this->sentenceService = new SentenceService();
        }
        return $this->sentenceService;
    }

    /**
     * Get ExpressionService (lazy loaded).
     *
     * @return ExpressionService
     */
    protected function getExpressionService(): ExpressionService
    {
        if ($this->expressionService === null) {
            $this->expressionService = new ExpressionService();
        }
        return $this->expressionService;
    }

    /**
     * Get WordUploadService (lazy loaded).
     *
     * @return WordUploadService
     */
    protected function getUploadService(): WordUploadService
    {
        if ($this->uploadService === null) {
            $this->uploadService = new WordUploadService();
        }
        return $this->uploadService;
    }

    /**
     * Set custom view path.
     *
     * @param string $path View path
     *
     * @return void
     */
    public function setViewPath(string $path): void
    {
        $this->viewPath = rtrim($path, '/') . '/';
    }

    /**
     * Render a view.
     *
     * @param string $view View name (without .php)
     * @param array  $data View data
     *
     * @return void
     */
    protected function render(string $view, array $data = []): void
    {
        $viewFile = $this->viewPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract($data);
        require $viewFile;
    }
}
