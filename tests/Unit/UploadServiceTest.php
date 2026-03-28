<?php

declare(strict_types=1);

namespace VentasPos\Tests\Unit;

use PHPUnit\Framework\TestCase;
use VentasPos\Domain\Uploads\UploadService;

final class UploadServiceTest extends TestCase
{
    public function testStoresImageIntoDiskAndPublicTargets(): void
    {
        $base = sys_get_temp_dir() . '/ventaspos-upload-test-' . bin2hex(random_bytes(4));
        $disk = $base . '/disk';
        $public = $base . '/public';
        mkdir($base, 0777, true);

        $fixture = $base . '/fixture.png';
        file_put_contents($fixture, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO8B7GkAAAAASUVORK5CYII='));

        $service = new UploadService($disk, $public, 'fotoproducto', 1024 * 1024);
        $relative = $service->storeFile($fixture, 'fixture.png', 'image/png', filesize($fixture), false);

        self::assertStringStartsWith('fotoproducto/', $relative);
        self::assertFileExists($disk . '/' . basename($relative));
        self::assertFileExists($public . '/' . basename($relative));
    }
}
