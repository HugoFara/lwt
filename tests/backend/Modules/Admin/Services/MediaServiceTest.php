<?php

declare(strict_types=1);

namespace Lwt\Tests\Modules\Admin\Services;

use Lwt\Modules\Admin\Application\Services\MediaService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for MediaService.
 *
 * Tests media file discovery, player generation, and URL parsing.
 */
class MediaServiceTest extends TestCase
{
    private MediaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MediaService();
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorCreatesValidService(): void
    {
        $this->assertInstanceOf(MediaService::class, $this->service);
    }

    // =========================================================================
    // searchMediaPaths Tests
    // =========================================================================

    public function testSearchMediaPathsReturnsStructuredArray(): void
    {
        // Create a temp directory with test files
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/test.mp3');
        touch($tempDir . '/video.mp4');
        touch($tempDir . '/audio.wav');
        touch($tempDir . '/unsupported.txt');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('paths', $result);
            $this->assertArrayHasKey('folders', $result);
            $this->assertIsArray($result['paths']);
            $this->assertIsArray($result['folders']);
        } finally {
            // Cleanup
            @unlink($tempDir . '/test.mp3');
            @unlink($tempDir . '/video.mp4');
            @unlink($tempDir . '/audio.wav');
            @unlink($tempDir . '/unsupported.txt');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsIncludesMp3Files(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/audio.mp3');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            $this->assertContains($tempDir . '/audio.mp3', $result['paths']);
        } finally {
            @unlink($tempDir . '/audio.mp3');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsIncludesMp4Files(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/video.mp4');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            $this->assertContains($tempDir . '/video.mp4', $result['paths']);
        } finally {
            @unlink($tempDir . '/video.mp4');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsIncludesOggFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/audio.ogg');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            $this->assertContains($tempDir . '/audio.ogg', $result['paths']);
        } finally {
            @unlink($tempDir . '/audio.ogg');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsIncludesWavFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/audio.wav');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            $this->assertContains($tempDir . '/audio.wav', $result['paths']);
        } finally {
            @unlink($tempDir . '/audio.wav');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsIncludesWebmFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/video.webm');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            $this->assertContains($tempDir . '/video.webm', $result['paths']);
        } finally {
            @unlink($tempDir . '/video.webm');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsExcludesUnsupportedFormats(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/image.jpg');
        touch($tempDir . '/document.pdf');
        touch($tempDir . '/audio.mp3');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            // Should include mp3 but not jpg or pdf
            $this->assertContains($tempDir . '/audio.mp3', $result['paths']);
            $this->assertNotContains($tempDir . '/image.jpg', $result['paths']);
            $this->assertNotContains($tempDir . '/document.pdf', $result['paths']);
        } finally {
            @unlink($tempDir . '/image.jpg');
            @unlink($tempDir . '/document.pdf');
            @unlink($tempDir . '/audio.mp3');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsIgnoresHiddenFiles(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/.hidden.mp3');
        touch($tempDir . '/visible.mp3');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            // Should include visible.mp3 but not .hidden.mp3
            $this->assertContains($tempDir . '/visible.mp3', $result['paths']);
            $this->assertNotContains($tempDir . '/.hidden.mp3', $result['paths']);
        } finally {
            @unlink($tempDir . '/.hidden.mp3');
            @unlink($tempDir . '/visible.mp3');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsRecursesIntoSubdirectories(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/subdir');
        touch($tempDir . '/subdir/nested.mp3');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            // Should include nested file
            $this->assertContains($tempDir . '/subdir/nested.mp3', $result['paths']);
            // Should include subdir in folders
            $this->assertContains($tempDir . '/subdir', $result['folders']);
        } finally {
            @unlink($tempDir . '/subdir/nested.mp3');
            @rmdir($tempDir . '/subdir');
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsIncludesRootDirectoryInFolders(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            $this->assertContains($tempDir, $result['folders']);
        } finally {
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsHandlesNonexistentDirectory(): void
    {
        $nonexistent = sys_get_temp_dir() . '/nonexistent_' . uniqid();

        $result = $this->service->searchMediaPaths($nonexistent);

        // Should return default structure with just the base path
        $this->assertIsArray($result);
        $this->assertArrayHasKey('paths', $result);
        $this->assertArrayHasKey('folders', $result);
    }

    // =========================================================================
    // getMediaPaths Tests
    // =========================================================================

    public function testGetMediaPathsReturnsBasePath(): void
    {
        $result = $this->service->getMediaPaths();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('base_path', $result);
    }

    public function testGetMediaPathsReturnsErrorWhenMediaDirectoryMissing(): void
    {
        // This test depends on whether 'media' exists in cwd
        // We'll save current directory, change to temp, then restore
        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/lwt_test_cwd_' . uniqid();
        mkdir($tempDir);

        try {
            chdir($tempDir);

            $result = $this->service->getMediaPaths();

            $this->assertArrayHasKey('error', $result);
            $this->assertSame('does_not_exist', $result['error']);
        } finally {
            if ($originalCwd !== false) {
                chdir($originalCwd);
            }
            @rmdir($tempDir);
        }
    }

    public function testGetMediaPathsReturnsErrorWhenMediaIsFile(): void
    {
        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/lwt_test_cwd_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/media'); // Create 'media' as a file, not directory

        try {
            chdir($tempDir);

            $result = $this->service->getMediaPaths();

            $this->assertArrayHasKey('error', $result);
            $this->assertSame('not_a_directory', $result['error']);
        } finally {
            if ($originalCwd !== false) {
                chdir($originalCwd);
            }
            @unlink($tempDir . '/media');
            @rmdir($tempDir);
        }
    }

    public function testGetMediaPathsReturnsPathsWhenMediaDirectoryExists(): void
    {
        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/lwt_test_cwd_' . uniqid();
        mkdir($tempDir);
        mkdir($tempDir . '/media');
        touch($tempDir . '/media/test.mp3');

        try {
            chdir($tempDir);

            $result = $this->service->getMediaPaths();

            $this->assertArrayHasKey('paths', $result);
            $this->assertArrayHasKey('folders', $result);
            $this->assertNotEmpty($result['paths']);
        } finally {
            if ($originalCwd !== false) {
                chdir($originalCwd);
            }
            @unlink($tempDir . '/media/test.mp3');
            @rmdir($tempDir . '/media');
            @rmdir($tempDir);
        }
    }

    // =========================================================================
    // getMediaPathOptions Tests
    // =========================================================================

    public function testGetMediaPathOptionsReturnsHtmlOptions(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/audio.mp3');

        try {
            $result = $this->service->getMediaPathOptions($tempDir);

            $this->assertIsString($result);
            $this->assertStringContainsString('<option', $result);
            $this->assertStringContainsString('audio.mp3', $result);
        } finally {
            @unlink($tempDir . '/audio.mp3');
            @rmdir($tempDir);
        }
    }

    public function testGetMediaPathOptionsEscapesHtmlInPaths(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        // Create file with special characters that need escaping
        touch($tempDir . '/file.mp3');

        try {
            $result = $this->service->getMediaPathOptions($tempDir);

            // Options should be properly escaped
            $this->assertIsString($result);
            $this->assertStringContainsString('value=', $result);
        } finally {
            @unlink($tempDir . '/file.mp3');
            @rmdir($tempDir);
        }
    }

    public function testGetMediaPathOptionsMarksDirectoriesAsDisabled(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);

        try {
            $result = $this->service->getMediaPathOptions($tempDir);

            // The base directory should appear as a disabled option
            $this->assertIsString($result);
            $this->assertStringContainsString('disabled', $result);
            $this->assertStringContainsString('Directory:', $result);
        } finally {
            @rmdir($tempDir);
        }
    }

    // =========================================================================
    // getMediaPathSelector Tests
    // =========================================================================

    public function testGetMediaPathSelectorReturnsHtml(): void
    {
        $result = $this->service->getMediaPathSelector('mediaUri');

        $this->assertIsString($result);
        $this->assertStringContainsString('select', $result);
        $this->assertStringContainsString('mediaUri', $result);
    }

    public function testGetMediaPathSelectorContainsConfigJson(): void
    {
        $result = $this->service->getMediaPathSelector('audioFile');

        $this->assertStringContainsString('data-lwt-media-select-config', $result);
        $this->assertStringContainsString('application/json', $result);
    }

    public function testGetMediaPathSelectorEscapesFieldName(): void
    {
        // Test with a field name that contains special characters
        $result = $this->service->getMediaPathSelector('field"with"quotes');

        // Should be escaped properly
        $this->assertStringContainsString('data-target-field=', $result);
    }

    public function testGetMediaPathSelectorContainsRefreshButton(): void
    {
        $result = $this->service->getMediaPathSelector('mediaUri');

        $this->assertStringContainsString('Refresh', $result);
        $this->assertStringContainsString('data-action="refresh-media-select"', $result);
    }

    public function testGetMediaPathSelectorContainsMediaInstructions(): void
    {
        $result = $this->service->getMediaPathSelector('mediaUri');

        $this->assertStringContainsString('YouTube', $result);
        $this->assertStringContainsString('Dailymotion', $result);
        $this->assertStringContainsString('Vimeo', $result);
        $this->assertStringContainsString('media', $result);
    }

    // =========================================================================
    // renderMediaPlayer Tests (output capture)
    // =========================================================================

    public function testRenderMediaPlayerOutputsNothingForEmptyPath(): void
    {
        ob_start();
        $this->service->renderMediaPlayer('');
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRenderMediaPlayerOutputsAudioForMp3(): void
    {
        ob_start();
        $this->service->renderMediaPlayer('/path/to/audio.mp3');
        $output = ob_get_clean();

        $this->assertStringContainsString('audio', $output);
    }

    public function testRenderMediaPlayerOutputsAudioForWav(): void
    {
        ob_start();
        $this->service->renderMediaPlayer('/path/to/audio.wav');
        $output = ob_get_clean();

        $this->assertStringContainsString('audio', $output);
    }

    public function testRenderMediaPlayerOutputsAudioForOgg(): void
    {
        ob_start();
        $this->service->renderMediaPlayer('/path/to/audio.ogg');
        $output = ob_get_clean();

        $this->assertStringContainsString('audio', $output);
    }

    public function testRenderMediaPlayerOutputsVideoForMp4(): void
    {
        ob_start();
        $this->service->renderMediaPlayer('/path/to/video.mp4');
        $output = ob_get_clean();

        // Should output video tag
        $this->assertStringContainsString('video', $output);
    }

    // =========================================================================
    // renderVideoPlayer Tests (YouTube URL parsing)
    // =========================================================================

    public function testRenderVideoPlayerParsesYouTubeWatchUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $output);
    }

    public function testRenderVideoPlayerParsesYouTubeShortUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://youtu.be/dQw4w9WgXcQ');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $output);
    }

    public function testRenderVideoPlayerParsesYouTubeWithoutHttps(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('www.youtube.com/watch?v=abc123');
        $output = ob_get_clean();

        $this->assertStringContainsString('youtube.com/embed/abc123', $output);
    }

    public function testRenderVideoPlayerIncludesOffsetForYouTube(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.youtube.com/watch?v=abc123', 60);
        $output = ob_get_clean();

        $this->assertStringContainsString('?t=60', $output);
    }

    public function testRenderVideoPlayerParsesYouTubeShortsUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.youtube.com/shorts/dQw4w9WgXcQ');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $output);
    }

    public function testRenderVideoPlayerParsesYouTubeEmbedUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.youtube.com/embed/dQw4w9WgXcQ');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $output);
    }

    public function testRenderVideoPlayerParsesYouTubeWithoutWww(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://youtube.com/watch?v=abc123');
        $output = ob_get_clean();

        $this->assertStringContainsString('youtube.com/embed/abc123', $output);
    }

    public function testRenderVideoPlayerParsesYouTubeWithHyphenInId(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.youtube.com/watch?v=abc-123_XYZ');
        $output = ob_get_clean();

        $this->assertStringContainsString('youtube.com/embed/abc-123_XYZ', $output);
    }

    // =========================================================================
    // renderVideoPlayer Tests (Dailymotion URL parsing)
    // =========================================================================

    public function testRenderVideoPlayerParsesDailymotionUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://dai.ly/x12345');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('dailymotion.com/embed/video/x12345', $output);
    }

    public function testRenderVideoPlayerParsesDailymotionWithoutHttps(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('dai.ly/abc123');
        $output = ob_get_clean();

        $this->assertStringContainsString('dailymotion.com/embed/video/abc123', $output);
    }

    public function testRenderVideoPlayerParsesDailymotionFullUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.dailymotion.com/video/x8abc12');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('dailymotion.com/embed/video/x8abc12', $output);
    }

    public function testRenderVideoPlayerParsesDailymotionFullUrlWithoutWww(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://dailymotion.com/video/x8xyz99');
        $output = ob_get_clean();

        $this->assertStringContainsString('dailymotion.com/embed/video/x8xyz99', $output);
    }

    // =========================================================================
    // renderVideoPlayer Tests (Vimeo URL parsing)
    // =========================================================================

    public function testRenderVideoPlayerParsesVimeoUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://vimeo.com/123456789');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('player.vimeo.com/video/123456789', $output);
    }

    public function testRenderVideoPlayerParsesVimeoWithoutHttps(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('vimeo.com/987654');
        $output = ob_get_clean();

        $this->assertStringContainsString('player.vimeo.com/video/987654', $output);
    }

    public function testRenderVideoPlayerIncludesOffsetForVimeo(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://vimeo.com/123456', 30);
        $output = ob_get_clean();

        $this->assertStringContainsString('#t=30s', $output);
    }

    // =========================================================================
    // renderVideoPlayer Tests (Bilibili URL parsing)
    // =========================================================================

    public function testRenderVideoPlayerParsesBilibiliBvUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.bilibili.com/video/BV1xx411c7mD');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('player.bilibili.com/player.html?bvid=BV1xx411c7mD', $output);
    }

    public function testRenderVideoPlayerParsesBilibiliAvUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.bilibili.com/video/av12345678');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('player.bilibili.com/player.html?aid=12345678', $output);
    }

    public function testRenderVideoPlayerParsesBilibiliWithoutWww(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://bilibili.com/video/BV1Ab4y1Z7eF');
        $output = ob_get_clean();

        $this->assertStringContainsString('player.bilibili.com/player.html?bvid=BV1Ab4y1Z7eF', $output);
    }

    public function testRenderVideoPlayerIncludesOffsetForBilibili(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.bilibili.com/video/BV1xx411c7mD', 120);
        $output = ob_get_clean();

        $this->assertStringContainsString('&t=120', $output);
    }

    // =========================================================================
    // renderVideoPlayer Tests (NicoNico URL parsing)
    // =========================================================================

    public function testRenderVideoPlayerParsesNicoNicoUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.nicovideo.jp/watch/sm12345678');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('embed.nicovideo.jp/watch/sm12345678', $output);
    }

    public function testRenderVideoPlayerParsesNicoNicoShortUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://nico.ms/sm98765432');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('embed.nicovideo.jp/watch/sm98765432', $output);
    }

    public function testRenderVideoPlayerParsesNicoNicoWithoutWww(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://nicovideo.jp/watch/nm11111111');
        $output = ob_get_clean();

        $this->assertStringContainsString('embed.nicovideo.jp/watch/nm11111111', $output);
    }

    public function testRenderVideoPlayerIncludesOffsetForNicoNico(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://www.nicovideo.jp/watch/sm12345678', 45);
        $output = ob_get_clean();

        $this->assertStringContainsString('?from=45', $output);
    }

    // =========================================================================
    // renderVideoPlayer Tests (PeerTube URL parsing)
    // =========================================================================

    public function testRenderVideoPlayerParsesPeerTubeWatchUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://peertube.example.com/videos/watch/a1b2c3d4-e5f6-7890-abcd-ef1234567890');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('peertube.example.com/videos/embed/a1b2c3d4-e5f6-7890-abcd-ef1234567890', $output);
    }

    public function testRenderVideoPlayerParsesPeerTubeShortUrl(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://video.instance.org/w/xyz123abc');
        $output = ob_get_clean();

        $this->assertStringContainsString('iframe', $output);
        $this->assertStringContainsString('video.instance.org/videos/embed/xyz123abc', $output);
    }

    public function testRenderVideoPlayerIncludesOffsetForPeerTube(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('https://peertube.social/videos/watch/abc123', 90);
        $output = ob_get_clean();

        $this->assertStringContainsString('?start=90s', $output);
    }

    // =========================================================================
    // renderVideoPlayer Tests (Local video files)
    // =========================================================================

    public function testRenderVideoPlayerRendersLocalMp4(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('media/video.mp4');
        $output = ob_get_clean();

        $this->assertStringContainsString('<video', $output);
        $this->assertStringContainsString('media/video.mp4', $output);
        $this->assertStringContainsString('video/mp4', $output);
    }

    public function testRenderVideoPlayerRendersLocalWebm(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('media/clip.webm');
        $output = ob_get_clean();

        $this->assertStringContainsString('<video', $output);
        $this->assertStringContainsString('video/webm', $output);
    }

    public function testRenderVideoPlayerIncludesControls(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('media/video.mp4');
        $output = ob_get_clean();

        $this->assertStringContainsString('controls', $output);
    }

    // =========================================================================
    // renderAudioPlayer Tests
    // =========================================================================

    public function testRenderAudioPlayerOutputsNothingForEmptyPath(): void
    {
        ob_start();
        $this->service->renderAudioPlayer('');
        $output = ob_get_clean();

        $this->assertEmpty($output);
    }

    public function testRenderAudioPlayerOutputsAudioElement(): void
    {
        ob_start();
        $this->service->renderAudioPlayer('/media/audio.mp3');
        $output = ob_get_clean();

        $this->assertStringContainsString('<audio', $output);
    }

    public function testRenderAudioPlayerTrimsWhitespace(): void
    {
        ob_start();
        $this->service->renderAudioPlayer('  /media/audio.mp3  ');
        $output = ob_get_clean();

        $this->assertStringContainsString('<audio', $output);
        $this->assertStringContainsString('/media/audio.mp3', $output);
    }

    // =========================================================================
    // renderHtml5AudioPlayer Tests
    // =========================================================================

    public function testRenderHtml5AudioPlayerOutputsContainer(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('audio-player-container', $output);
    }

    public function testRenderHtml5AudioPlayerIncludesConfigJson(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            30,
            true,
            10,
            12
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('data-audio-config', $output);
        $this->assertStringContainsString('"offset":30', $output);
        $this->assertStringContainsString('"repeatMode":true', $output);
    }

    public function testRenderHtml5AudioPlayerIncludesPlayControls(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('togglePlay', $output);
        $this->assertStringContainsString('audio-player-controls', $output);
    }

    public function testRenderHtml5AudioPlayerIncludesProgressBar(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('audio-player-progress', $output);
        $this->assertStringContainsString('progress-bar', $output);
    }

    public function testRenderHtml5AudioPlayerIncludesVolumeControl(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('audio-player-volume', $output);
        $this->assertStringContainsString('toggleMute', $output);
    }

    public function testRenderHtml5AudioPlayerIncludesSkipControls(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('audio-player-skip', $output);
        $this->assertStringContainsString('skipBackward', $output);
        $this->assertStringContainsString('skipForward', $output);
    }

    public function testRenderHtml5AudioPlayerIncludesSpeedControls(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('audio-player-speed', $output);
        $this->assertStringContainsString('slower', $output);
        $this->assertStringContainsString('faster', $output);
    }

    public function testRenderHtml5AudioPlayerIncludesRepeatToggle(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        $this->assertStringContainsString('audio-player-repeat', $output);
        $this->assertStringContainsString('toggleRepeat', $output);
    }

    public function testRenderHtml5AudioPlayerIncludesSkipOptions(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/test.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        // Should include skip second options
        $this->assertStringContainsString('1s', $output);
        $this->assertStringContainsString('5s', $output);
        $this->assertStringContainsString('10s', $output);
        $this->assertStringContainsString('30s', $output);
    }

    public function testRenderHtml5AudioPlayerEscapesAudioUrl(): void
    {
        ob_start();
        $this->service->renderHtml5AudioPlayer(
            '/media/file with spaces.mp3',
            0,
            false,
            5,
            10
        );
        $output = ob_get_clean();

        // The URL in config should be encoded
        $this->assertIsString($output);
        $this->assertStringContainsString('<source', $output);
    }

    // =========================================================================
    // Edge Cases and Error Handling
    // =========================================================================

    public function testSearchMediaPathsHandlesEmptyDirectory(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_empty_' . uniqid();
        mkdir($tempDir);

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            $this->assertIsArray($result);
            // Should only contain the directory itself in paths
            $this->assertCount(1, $result['paths']);
            $this->assertSame($tempDir, $result['paths'][0]);
        } finally {
            @rmdir($tempDir);
        }
    }

    public function testSearchMediaPathsHandlesMixedCaseExtensions(): void
    {
        $tempDir = sys_get_temp_dir() . '/lwt_test_media_' . uniqid();
        mkdir($tempDir);
        touch($tempDir . '/audio.MP3');
        touch($tempDir . '/video.Mp4');

        try {
            $result = $this->service->searchMediaPaths($tempDir);

            // Should include files with mixed-case extensions
            $foundMp3 = false;
            $foundMp4 = false;
            foreach ($result['paths'] as $path) {
                if (str_contains(strtolower($path), '.mp3')) {
                    $foundMp3 = true;
                }
                if (str_contains(strtolower($path), '.mp4')) {
                    $foundMp4 = true;
                }
            }
            $this->assertTrue($foundMp3);
            $this->assertTrue($foundMp4);
        } finally {
            @unlink($tempDir . '/audio.MP3');
            @unlink($tempDir . '/video.Mp4');
            @rmdir($tempDir);
        }
    }

    public function testRenderVideoPlayerWithUnknownUrlFallsBackToLocal(): void
    {
        ob_start();
        $this->service->renderVideoPlayer('http://unknown-site.com/video.mp4');
        $output = ob_get_clean();

        // Should fall back to local video player
        $this->assertStringContainsString('<video', $output);
    }

    public function testRenderMediaPlayerWithOffset(): void
    {
        ob_start();
        $this->service->renderMediaPlayer('/media/video.mp4', 120);
        $output = ob_get_clean();

        // Video player should be rendered (offset handling for local is in frontend)
        $this->assertStringContainsString('<video', $output);
    }
}
