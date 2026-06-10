<?php
// src/Helpers/FileUpload.php

class FileUpload
{
    private string $uploadDir;
    private array $allowedTypes;
    private int $maxSize;

    public function __construct(string $uploadDir, array $allowedTypes = [], int $maxSize = 50 * 1024 * 1024)
    {
        $this->uploadDir    = rtrim($uploadDir, '/');
        $this->allowedTypes = $allowedTypes;
        $this->maxSize      = $maxSize;

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function upload(array $file): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException($this->getUploadError($file['error']));
        }

        if ($file['size'] > $this->maxSize) {
            throw new RuntimeException('文件大小超限');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($this->allowedTypes) && !in_array($ext, $this->allowedTypes)) {
            throw new RuntimeException('不支持的文件类型: ' . $ext);
        }

        $newName = $this->generateName($ext);
        $dest    = $this->uploadDir . '/' . $newName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('文件保存失败');
        }

        return $dest;
    }

    private function generateName(string $ext): string
    {
        return date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    }

    private function getUploadError(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return '文件大小超过服务器限制';
            case UPLOAD_ERR_FORM_SIZE:
                return '文件大小超过表单限制';
            case UPLOAD_ERR_PARTIAL:
                return '文件只有部分被上传';
            case UPLOAD_ERR_NO_FILE:
                return '没有文件被上传';
            default:
                return '上传失败';
        }
    }
}
