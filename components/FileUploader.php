<?php

namespace app\components;

class FileUploader
{
    public bool $isUploaded;
    private string $realFilename = '';

    public function __construct(string $tempFilename, string $extension, string $uploadPath)
    {
        $this->realFilename = \md5(\time()) . '.' . $extension;
        $this->isUploaded = \move_uploaded_file($tempFilename, $uploadPath . '/' . $this->realFilename);
    }

    public function getFilename(): string|null
    {
        return ($this->realFilename !== '') ? $this->realFilename : null;
    }
}

?>