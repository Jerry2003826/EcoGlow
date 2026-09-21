<?php
// src/Service/ImageUploadService.php
declare(strict_types=1);

namespace App\Service;

use Psr\Http\Message\UploadedFileInterface;
use Throwable;

class ImageUploadService
{
    protected const MAX_BYTES = 5242880;

    /**
     * @var array<int, string>
     */
    protected const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * @var array<int, string>
     */
    protected const TYPE_EXTENSIONS = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    protected ?string $lastError = null;

    /**
     * Return the most recent store() failure message.
     *
     * @return string|null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Validate and store an uploaded image under webroot/img/{subdirectory}.
     *
     * On success the returned path looks like `landing/<random>.jpg`.
     *
     * @param mixed $upload Uploaded file payload.
     * @param string $subdirectory Destination folder under webroot/img.
     * @return string|null
     */
    public function store(mixed $upload, string $subdirectory): ?string
    {
        $this->lastError = null;

        if (!$upload instanceof UploadedFileInterface) {
            $this->lastError = 'Please select a valid image file.';

            return null;
        }

        if ($upload->getError() !== UPLOAD_ERR_OK) {
            $this->lastError = $this->errorMessageForUploadCode($upload->getError());

            return null;
        }

        if ((int)$upload->getSize() > self::MAX_BYTES) {
            $this->lastError = 'The image is too large. Please upload a file of 5MB or less.';

            return null;
        }

        $originalFilename = $upload->getClientFilename() ?? '';
        $filename = basename(str_replace('\\', '/', $originalFilename));
        $extension = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
        $invalidFilename = $filename === '' || $filename === '.' || $filename === '..';

        if ($invalidFilename || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            $this->lastError = 'Please upload a JPG, PNG, or WEBP image.';

            return null;
        }

        $temporaryPath = $upload->getStream()->getMetadata('uri');
        $imageInfo = is_string($temporaryPath) ? getimagesize($temporaryPath) : false;

        if ($imageInfo === false || !isset(self::TYPE_EXTENSIONS[$imageInfo[2]])) {
            $this->lastError = 'The uploaded file is not a valid JPG, PNG, or WEBP image.';

            return null;
        }

        if (is_string($temporaryPath) && is_file($temporaryPath) && filesize($temporaryPath) > self::MAX_BYTES) {
            $this->lastError = 'The image is too large. Please upload a file of 5MB or less.';

            return null;
        }

        if (!$this->isSafeSubdirectory($subdirectory)) {
            $this->lastError = 'The image folder is not available.';

            return null;
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . self::TYPE_EXTENSIONS[$imageInfo[2]];
        $directory = WWW_ROOT . 'img' . DS . $subdirectory . DS;

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $this->lastError = 'The image folder is not available.';

            return null;
        }

        try {
            $upload->moveTo($directory . $storedName);
        } catch (Throwable $exception) {
            $this->lastError = 'The image could not be saved.';

            return null;
        }

        return $subdirectory . '/' . $storedName;
    }

    /**
     * Delete a previously stored image when the path stays inside the subdirectory.
     *
     * @param string $storedPath Stored relative path such as landing/abc.jpg.
     * @param string $subdirectory Expected folder prefix.
     * @return bool
     */
    public function delete(string $storedPath, string $subdirectory): bool
    {
        if (!$this->isSafeSubdirectory($subdirectory)) {
            return false;
        }

        $prefix = $subdirectory . '/';
        $imageFilename = basename(str_replace('\\', '/', $storedPath));
        $isSafePath = str_starts_with($storedPath, $prefix)
            && $imageFilename !== ''
            && $imageFilename !== '.'
            && $imageFilename !== '..'
            && $imageFilename === substr($storedPath, strlen($prefix));

        if (!$isSafePath) {
            return false;
        }

        $imageFile = WWW_ROOT . 'img' . DS . $subdirectory . DS . $imageFilename;
        if (!is_file($imageFile)) {
            return false;
        }

        return unlink($imageFile);
    }

    /**
     * Allow only a single folder name under webroot/img.
     *
     * @param string $subdirectory Destination folder name.
     * @return bool
     */
    protected function isSafeSubdirectory(string $subdirectory): bool
    {
        return (bool)preg_match('/^[A-Za-z0-9_-]+$/', $subdirectory);
    }

    /**
     * Map a PHP upload error code to a flash-ready message.
     *
     * @param int $errorCode PHP upload error code.
     * @return string
     */
    protected function errorMessageForUploadCode(int $errorCode): string
    {
        if (
            $errorCode === UPLOAD_ERR_INI_SIZE
            || $errorCode === UPLOAD_ERR_FORM_SIZE
        ) {
            return 'The image is too large. Please upload a file of 5MB or less.';
        }

        return 'The image upload failed. Please try again.';
    }
}
