<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;

class FileManagerService
{
    protected string $basePath;
    protected string $allowedExtensions = 'txt,php,html,css,js,json,xml,yml,yaml,md,log,htaccess,conf';
    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    public function __construct()
    {
        $this->basePath = storage_path('app/user_files');
        if (!File::exists($this->basePath)) {
            File::makeDirectory($this->basePath, 0755, true);
        }
    }

    /**
     * Get directory contents
     */
    public function getDirectoryContents(string $path = '', string $domain = null): array
    {
        $fullPath = $this->getUserPath($domain) . '/' . ltrim($path, '/');
        
        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
        }

        $items = [];
        $files = File::allFiles($fullPath);
        $directories = File::directories($fullPath);

        // Add directories
        foreach ($directories as $directory) {
            $items[] = [
                'name' => basename($directory),
                'type' => 'directory',
                'size' => 0,
                'modified' => File::lastModified($directory),
                'permissions' => substr(sprintf('%o', fileperms($directory)), -4),
                'path' => str_replace($this->getUserPath($domain), '', $directory),
            ];
        }

        // Add files
        foreach ($files as $file) {
            $items[] = [
                'name' => $file->getFilename(),
                'type' => 'file',
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'permissions' => substr(sprintf('%o', fileperms($file->getPathname())), -4),
                'path' => str_replace($this->getUserPath($domain), '', $file->getPathname()),
                'extension' => $file->getExtension(),
                'is_image' => in_array(strtolower($file->getExtension()), $this->imageExtensions),
                'is_editable' => $this->isEditable($file->getExtension()),
            ];
        }

        return collect($items)->sortBy([
            ['type', 'asc'],
            ['name', 'asc']
        ])->values()->toArray();
    }

    /**
     * Read file content
     */
    public function readFile(string $path, string $domain = null): array
    {
        $fullPath = $this->getUserPath($domain) . '/' . ltrim($path, '/');
        
        if (!File::exists($fullPath) || !File::isFile($fullPath)) {
            throw new \Exception('File not found or is not readable.');
        }

        if (!$this->isEditable(File::extension($fullPath))) {
            throw new \Exception('File type is not editable.');
        }

        return [
            'content' => File::get($fullPath),
            'size' => File::size($fullPath),
            'modified' => File::lastModified($fullPath),
            'extension' => File::extension($fullPath),
            'path' => $path,
        ];
    }

    /**
     * Save file content
     */
    public function saveFile(string $path, string $content, string $domain = null): bool
    {
        $fullPath = $this->getUserPath($domain) . '/' . ltrim($path, '/');
        
        if (!$this->isEditable(File::extension($fullPath))) {
            throw new \Exception('File type is not editable.');
        }

        return File::put($fullPath, $content) !== false;
    }

    /**
     * Upload file
     */
    public function uploadFile(UploadedFile $file, string $path = '', string $domain = null): array
    {
        $targetPath = $this->getUserPath($domain) . '/' . ltrim($path, '/');
        
        if (!File::exists($targetPath)) {
            File::makeDirectory($targetPath, 0755, true);
        }

        $filename = $file->getClientOriginalName();
        $filePath = $targetPath . '/' . $filename;

        // Handle duplicate filenames
        $counter = 1;
        while (File::exists($filePath)) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $newFilename = $name . '_' . $counter . '.' . $ext;
            $filePath = $targetPath . '/' . $newFilename;
            $filename = $newFilename;
            $counter++;
        }

        $file->move($targetPath, $filename);

        return [
            'filename' => $filename,
            'path' => str_replace($this->getUserPath($domain), '', $filePath),
            'size' => File::size($filePath),
        ];
    }

    /**
     * Delete file or directory
     */
    public function delete(string $path, string $domain = null): bool
    {
        $fullPath = $this->getUserPath($domain) . '/' . ltrim($path, '/');
        
        if (!File::exists($fullPath)) {
            return false;
        }

        if (File::isDirectory($fullPath)) {
            return File::deleteDirectory($fullPath);
        } else {
            return File::delete($fullPath);
        }
    }

    /**
     * Create directory
     */
    public function createDirectory(string $path, string $domain = null): bool
    {
        $fullPath = $this->getUserPath($domain) . '/' . ltrim($path, '/');
        
        if (File::exists($fullPath)) {
            throw new \Exception('Directory already exists.');
        }

        return File::makeDirectory($fullPath, 0755, true);
    }

    /**
     * Get breadcrumbs for navigation
     */
    public function getBreadcrumbs(string $path): array
    {
        $breadcrumbs = [['name' => 'Home', 'path' => '']];
        
        if (!empty($path)) {
            $parts = explode('/', trim($path, '/'));
            $currentPath = '';
            
            foreach ($parts as $part) {
                $currentPath .= '/' . $part;
                $breadcrumbs[] = [
                    'name' => $part,
                    'path' => ltrim($currentPath, '/')
                ];
            }
        }

        return $breadcrumbs;
    }

    /**
     * Get user-specific path
     */
    protected function getUserPath(string $domain = null): string
    {
        $userId = auth()->id();
        $userPath = $this->basePath . '/user_' . $userId;
        
        if ($domain) {
            $userPath .= '/domains/' . $domain;
        }
        
        if (!File::exists($userPath)) {
            File::makeDirectory($userPath, 0755, true);
        }
        
        return $userPath;
    }

    /**
     * Check if file is editable
     */
    protected function isEditable(string $extension): bool
    {
        return in_array(strtolower($extension), explode(',', $this->allowedExtensions));
    }

    /**
     * Get file icon based on extension
     */
    public function getFileIcon(string $extension): string
    {
        $icons = [
            'php' => 'fab fa-php',
            'js' => 'fab fa-js-square',
            'css' => 'fab fa-css3-alt',
            'html' => 'fab fa-html5',
            'json' => 'fas fa-code',
            'xml' => 'fas fa-code',
            'txt' => 'fas fa-file-alt',
            'md' => 'fab fa-markdown',
            'yml' => 'fas fa-cog',
            'yaml' => 'fas fa-cog',
            'log' => 'fas fa-file-alt',
            'conf' => 'fas fa-cog',
            'htaccess' => 'fas fa-cog',
            'zip' => 'fas fa-file-archive',
            'tar' => 'fas fa-file-archive',
            'gz' => 'fas fa-file-archive',
            'pdf' => 'fas fa-file-pdf',
            'doc' => 'fas fa-file-word',
            'docx' => 'fas fa-file-word',
            'xls' => 'fas fa-file-excel',
            'xlsx' => 'fas fa-file-excel',
            'ppt' => 'fas fa-file-powerpoint',
            'pptx' => 'fas fa-file-powerpoint',
        ];

        if (in_array(strtolower($extension), $this->imageExtensions)) {
            return 'fas fa-file-image';
        }

        return $icons[strtolower($extension)] ?? 'fas fa-file';
    }
}
