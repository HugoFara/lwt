<?php declare(strict_types=1);
/**
 * Import Text Use Case
 *
 * PHP version 8.1
 *
 * @category Lwt
 * @package  Lwt\Modules\Text\Application\UseCases
 * @author   HugoFara <hugo.farajallah@protonmail.com>
 * @license  Unlicense <http://unlicense.org/>
 * @link     https://hugofara.github.io/lwt/docs/php/
 * @since    3.0.0
 */

namespace Lwt\Modules\Text\Application\UseCases;

use Lwt\Modules\Language\Domain\ValueObject\LanguageId;
use Lwt\Shared\Infrastructure\Database\Connection;
use Lwt\Shared\Infrastructure\Database\TextParsing;
use Lwt\Shared\Infrastructure\Database\UserScopedQuery;
use Lwt\Modules\Text\Domain\Text;
use Lwt\Modules\Text\Domain\TextRepositoryInterface;
use Lwt\Modules\Text\Domain\ValueObject\TextId;

/**
 * Use case for importing/creating texts.
 *
 * Handles single text creation and long text import (splitting large
 * texts into multiple smaller texts).
 *
 * @since 3.0.0
 */
class ImportText
{
    private TextRepositoryInterface $textRepository;

    /**
     * Constructor.
     *
     * @param TextRepositoryInterface $textRepository Text repository
     */
    public function __construct(TextRepositoryInterface $textRepository)
    {
        $this->textRepository = $textRepository;
    }

    /**
     * Create a new text.
     *
     * @param int    $languageId Language ID
     * @param string $title      Title
     * @param string $text       Text content
     * @param string $audioUri   Audio URI (optional)
     * @param string $sourceUri  Source URI (optional)
     *
     * @return array{message: string, textId: int|null}
     */
    public function execute(
        int $languageId,
        string $title,
        string $text,
        string $audioUri = '',
        string $sourceUri = ''
    ): array {
        // Remove soft hyphens
        $text = $this->removeSoftHyphens($text);

        // Validate text length
        if (!$this->validateTextLength($text)) {
            return [
                'message' => 'Text too long, must be below 65000 bytes',
                'textId' => null
            ];
        }

        // Create and save text
        $textEntity = Text::create(
            LanguageId::fromInt($languageId),
            $title,
            $text
        );
        $textEntity->setMediaUri($audioUri);
        $textEntity->setSourceUri($sourceUri);

        $textId = $this->textRepository->save($textEntity);

        // Parse text
        $parseResult = TextParsing::parseAndSave($text, $languageId, $textId);

        $bindings = [$textId];
        $sentenceCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM sentences WHERE SeTxID = ?"
            . UserScopedQuery::forTablePrepared('sentences', $bindings, '', 'texts'),
            $bindings,
            'cnt'
        );
        $itemCount = Connection::preparedFetchValue(
            "SELECT COUNT(*) AS cnt FROM textitems2 WHERE Ti2TxID = ?"
            . UserScopedQuery::forTablePrepared('textitems2', $bindings, '', 'texts'),
            $bindings,
            'cnt'
        );

        $message = "Text saved. Sentences: {$sentenceCount}, Items: {$itemCount}";

        return ['message' => $message, 'textId' => $textId];
    }

    /**
     * Prepare long text data from file upload or clipboard.
     *
     * @param string|null $pastedText  Pasted text content
     * @param array|null  $uploadedFile Uploaded file ($_FILES entry)
     *
     * @return string|null Processed text content or null on error
     */
    public function prepareLongTextData(?string $pastedText, ?array $uploadedFile): ?string
    {
        $text = '';

        if ($uploadedFile !== null && !empty($uploadedFile['tmp_name'])) {
            $text = file_get_contents($uploadedFile['tmp_name']);
            if ($text === false) {
                return null;
            }
        } elseif ($pastedText !== null && trim($pastedText) !== '') {
            $text = $pastedText;
        } else {
            return null;
        }

        // Normalize line endings and clean up
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Split long text into multiple texts.
     *
     * @param string $text      Text content
     * @param int    $maxLength Maximum length per text (default 65000 bytes)
     *
     * @return array<int, array{title: string, text: string}> Array of text chunks
     */
    public function splitLongText(string $text, int $maxLength = 60000): array
    {
        $chunks = [];
        $paragraphs = explode("\n\n", $text);
        $currentChunk = '';
        $partNum = 1;

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }

            $newContent = $currentChunk === ''
                ? $para
                : $currentChunk . "\n\n" . $para;

            if (strlen($newContent) > $maxLength) {
                if ($currentChunk !== '') {
                    $chunks[] = [
                        'title' => "Part {$partNum}",
                        'text' => $currentChunk
                    ];
                    $partNum++;
                }
                $currentChunk = $para;
            } else {
                $currentChunk = $newContent;
            }
        }

        // Add remaining content
        if ($currentChunk !== '') {
            $chunks[] = [
                'title' => $partNum === 1 ? '' : "Part {$partNum}",
                'text' => $currentChunk
            ];
        }

        return $chunks;
    }

    /**
     * Save long text import (multiple texts).
     *
     * @param int    $languageId   Language ID
     * @param string $baseTitle    Base title for texts
     * @param array  $chunks       Array of text chunks from splitLongText()
     * @param string $audioUri     Audio URI (optional)
     * @param string $sourceUri    Source URI (optional)
     * @param array  $tagIds       Tag IDs to apply (optional)
     *
     * @return array{message: string, textIds: int[]}
     */
    public function saveLongTextImport(
        int $languageId,
        string $baseTitle,
        array $chunks,
        string $audioUri = '',
        string $sourceUri = '',
        array $tagIds = []
    ): array {
        $textIds = [];
        $totalSentences = 0;
        $totalItems = 0;

        foreach ($chunks as $index => $chunk) {
            $title = $chunk['title'] === '' || $chunk['title'] === 'Part 1'
                ? $baseTitle
                : "{$baseTitle} ({$chunk['title']})";

            $result = $this->execute(
                $languageId,
                $title,
                $chunk['text'],
                $index === 0 ? $audioUri : '', // Only first text gets audio
                $index === 0 ? $sourceUri : '' // Only first text gets source
            );

            if ($result['textId'] !== null) {
                $textIds[] = $result['textId'];

                // Apply tags
                if (!empty($tagIds)) {
                    $this->applyTags($result['textId'], $tagIds);
                }
            }
        }

        $count = count($textIds);
        $message = "Imported {$count} text(s)";

        return ['message' => $message, 'textIds' => $textIds];
    }

    /**
     * Validate text length (max 65000 bytes for MySQL TEXT column).
     *
     * @param string $text Text to validate
     *
     * @return bool True if valid, false if too long
     */
    public function validateTextLength(string $text): bool
    {
        return strlen($text) <= 65000;
    }

    /**
     * Remove soft hyphens from text.
     *
     * @param string $text Text to clean
     *
     * @return string Cleaned text
     */
    private function removeSoftHyphens(string $text): string
    {
        return str_replace("\xC2\xAD", "", $text);
    }

    /**
     * Apply tags to a text.
     *
     * @param int   $textId Text ID
     * @param array $tagIds Array of tag IDs
     */
    private function applyTags(int $textId, array $tagIds): void
    {
        foreach ($tagIds as $tagId) {
            $bindings = [$textId, (int) $tagId];
            Connection::preparedExecute(
                "INSERT IGNORE INTO texttags (TtTxID, TtT2ID) VALUES (?, ?)"
                . UserScopedQuery::forTablePrepared('texttags', $bindings, '', 'texts'),
                $bindings
            );
        }
    }
}
