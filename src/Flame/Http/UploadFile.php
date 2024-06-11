<?php

declare(strict_types=1);

namespace Flame\Http;

class UploadFile extends File
{
    protected ?string $uploadName = null;

    protected ?string $uploadMimeType = null;

    protected ?int $uploadErrorCode = null;

    /**
     * UploadFile constructor.
     */
    public function __construct(string $fileName, string $uploadName, string $uploadMimeType, int $uploadErrorCode)
    {
        $this->uploadName = $uploadName;
        $this->uploadMimeType = $uploadMimeType;
        $this->uploadErrorCode = $uploadErrorCode;
        parent::__construct($fileName);
    }

    /**
     * GetUploadName
     */
    public function getUploadName(): ?string
    {
        return $this->uploadName;
    }

    /**
     * GetUploadMimeType
     */
    public function getUploadMimeType(): ?string
    {
        return $this->uploadMimeType;
    }

    /**
     * GetUploadExtension
     */
    public function getUploadExtension(): string
    {
        return pathinfo($this->uploadName, PATHINFO_EXTENSION);
    }

    /**
     * GetUploadErrorCode
     */
    public function getUploadErrorCode(): ?int
    {
        return $this->uploadErrorCode;
    }

    /**
     * IsValid
     */
    public function isValid(): bool
    {
        return $this->uploadErrorCode === UPLOAD_ERR_OK;
    }
}
