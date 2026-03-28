<?php

declare(strict_types=1);

namespace VentasPos\Domain\Uploads;

use finfo;
use RuntimeException;

final class UploadService
{
    private const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(
        private readonly string $diskPath,
        private readonly string $publicDiskPath,
        private readonly string $publicRelativePath,
        private readonly int $maxFileSize
    ) {
    }

    /**
     * @param array<string, mixed> $uploadedFile
     */
    public function storeUploadedImage(array $uploadedFile): string
    {
        if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir la imagen.');
        }

        $tmpName = (string) ($uploadedFile['tmp_name'] ?? '');
        $originalName = (string) ($uploadedFile['name'] ?? 'archivo');
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: (string) ($uploadedFile['type'] ?? '');
        $size = (int) ($uploadedFile['size'] ?? 0);

        return $this->storeFile($tmpName, $originalName, $mimeType, $size, true);
    }

    public function storeFile(string $sourcePath, string $originalName, string $mimeType, int $size, bool $isUploadedFile = false): string
    {
        if ($sourcePath === '' || !is_file($sourcePath)) {
            throw new RuntimeException('El archivo temporal no existe.');
        }

        if ($size <= 0 || $size > $this->maxFileSize) {
            throw new RuntimeException('La extension o el tamano del archivo no es valido.');
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = $this->inferExtension($mimeType);
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true) || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException('Solo se permiten imagenes gif, jpg, png o webp.');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

        $this->ensureDirectory($this->diskPath);
        $this->ensureDirectory($this->publicDiskPath);

        $diskDestination = rtrim($this->diskPath, '/') . '/' . $fileName;
        $publicDestination = rtrim($this->publicDiskPath, '/') . '/' . $fileName;

        $moved = $isUploadedFile
            ? move_uploaded_file($sourcePath, $diskDestination)
            : copy($sourcePath, $diskDestination);

        if (!$moved) {
            throw new RuntimeException('Ocurrio un error al guardar la imagen.');
        }

        if (!copy($diskDestination, $publicDestination)) {
            @unlink($diskDestination);
            throw new RuntimeException('Ocurrio un error al publicar la imagen.');
        }

        return trim($this->publicRelativePath, '/') . '/' . $fileName;
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el directorio de subida.');
        }
    }

    private function inferExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => '',
        };
    }
}
