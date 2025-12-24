<?php declare(strict_types=1);
/**
 * Vocabulary Controller
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

use Lwt\Core\Http\InputValidator;
use Lwt\Modules\Vocabulary\Application\UseCases\CreateTermFromHover;
use Lwt\View\Helper\PageLayoutHelper;

require_once __DIR__ . '/../../../backend/View/Helper/PageLayoutHelper.php';

/**
 * Controller for vocabulary/term management operations.
 *
 * @since 3.0.0
 */
class VocabularyController
{
    /**
     * View base path.
     */
    private string $viewPath;

    /**
     * Use cases.
     */
    private CreateTermFromHover $createTermFromHover;

    /**
     * Constructor.
     *
     * @param CreateTermFromHover|null $createTermFromHover Create term from hover use case
     */
    public function __construct(
        ?CreateTermFromHover $createTermFromHover = null
    ) {
        $this->viewPath = __DIR__ . '/../Views/';
        $this->createTermFromHover = $createTermFromHover ?? new CreateTermFromHover();
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
     * Create a term from hover action in reading view.
     *
     * This is called when user clicks a status from the hover menu
     * while reading a text.
     *
     * @param int    $textId     Text ID
     * @param string $wordText   Word text
     * @param int    $status     Word status (1-5)
     * @param string $sourceLang Source language code
     * @param string $targetLang Target language code
     *
     * @return array Term creation result
     */
    public function createFromHover(
        int $textId,
        string $wordText,
        int $status,
        string $sourceLang = '',
        string $targetLang = ''
    ): array {
        // Set no-cache headers for new words
        if ($this->createTermFromHover->shouldSetNoCacheHeaders($status)) {
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        return $this->createTermFromHover->execute(
            $textId,
            $wordText,
            $status,
            $sourceLang,
            $targetLang
        );
    }

    /**
     * Render a view.
     *
     * @param string $view View name (without .php)
     * @param array  $data View data
     *
     * @return void
     */
    public function render(string $view, array $data = []): void
    {
        $viewFile = $this->viewPath . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract($data);
        require $viewFile;
    }

    /**
     * Handle the hover create action from reading view.
     *
     * This is the route handler that parses request params and
     * renders the result view.
     *
     * @param array<string, string> $params Route parameters
     *
     * @return void
     */
    public function hoverCreate(array $params): void
    {
        $text = InputValidator::getString('text');
        $textId = InputValidator::getInt('tid', 0);
        $status = InputValidator::getInt('status', 1);
        $targetLang = InputValidator::getString('tl');
        $sourceLang = InputValidator::getString('sl');

        // Create the term
        $result = $this->createFromHover(
            $textId,
            $text,
            $status,
            $sourceLang,
            $targetLang
        );

        // Render page
        PageLayoutHelper::renderPageStart("New Term: " . $result['word'], false);

        // Prepare view variables
        $word = $result['word'];
        $wordRaw = $result['wordRaw'];
        $wid = $result['wid'];
        $hex = $result['hex'];
        $translation = $result['translation'];

        $this->render('hover_save_result', [
            'word' => $word,
            'wordRaw' => $wordRaw,
            'wid' => $wid,
            'hex' => $hex,
            'translation' => $translation,
            'textId' => $textId,
            'status' => $status,
        ]);

        PageLayoutHelper::renderPageEnd();
    }
}
