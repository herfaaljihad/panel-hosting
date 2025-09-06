<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class AdvancedFileManagerService
{
    protected string $basePath;
    protected array $allowedExtensions;
    protected array $editableExtensions;

    public function __construct()
    {
        $this->basePath = config('hosting.web_root', '/var/www');
        $this->allowedExtensions = [
            'txt', 'html', 'htm', 'css', 'js', 'php', 'json', 'xml', 'yml', 'yaml',
            'md', 'log', 'conf', 'htaccess', 'sql', 'py', 'sh', 'bat', 'ini'
        ];
        $this->editableExtensions = [
            'txt', 'html', 'htm', 'css', 'js', 'php', 'json', 'xml', 'yml', 'yaml',
            'md', 'log', 'conf', 'htaccess', 'sql', 'py', 'sh', 'bat', 'ini', 'vue', 'tsx', 'jsx'
        ];
    }

    /**
     * Get directory listing with detailed info
     */
    public function getDirectoryListing(string $path, string $domain): array
    {
        $fullPath = $this->getDomainPath($domain, $path);
        
        if (!file_exists($fullPath) || !is_dir($fullPath)) {
            throw new \Exception('Directory not found');
        }

        $items = [];
        $files = scandir($fullPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $fullPath . DIRECTORY_SEPARATOR . $file;
            $fileInfo = $this->getFileInfo($filePath, $file);
            $items[] = $fileInfo;
        }

        // Sort: directories first, then files
        usort($items, function($a, $b) {
            if ($a['type'] === $b['type']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['type'] === 'directory' ? -1 : 1;
        });

        return $items;
    }

    /**
     * Get detailed file information
     */
    private function getFileInfo(string $filePath, string $fileName): array
    {
        $stat = stat($filePath);
        $isDir = is_dir($filePath);
        
        return [
            'name' => $fileName,
            'type' => $isDir ? 'directory' : 'file',
            'size' => $isDir ? 0 : filesize($filePath),
            'size_formatted' => $isDir ? '-' : $this->formatFileSize(filesize($filePath)),
            'permissions' => substr(sprintf('%o', fileperms($filePath)), -4),
            'owner' => function_exists('posix_getpwuid') ? posix_getpwuid($stat['uid'])['name'] ?? $stat['uid'] : $stat['uid'],
            'group' => function_exists('posix_getgrgid') ? posix_getgrgid($stat['gid'])['name'] ?? $stat['gid'] : $stat['gid'],
            'modified' => date('Y-m-d H:i:s', $stat['mtime']),
            'extension' => $isDir ? null : pathinfo($fileName, PATHINFO_EXTENSION),
            'is_editable' => !$isDir && in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), $this->editableExtensions),
            'is_image' => !$isDir && $this->isImageFile($fileName),
            'icon' => $this->getFileIcon($fileName, $isDir)
        ];
    }

    /**
     * Read file content for editing
     */
    public function readFile(string $path, string $domain): array
    {
        $fullPath = $this->getDomainPath($domain, $path);
        
        if (!file_exists($fullPath) || is_dir($fullPath)) {
            throw new \Exception('File not found');
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->editableExtensions)) {
            throw new \Exception('File type not editable');
        }

        $content = file_get_contents($fullPath);
        $fileInfo = $this->getFileInfo($fullPath, basename($fullPath));

        return [
            'content' => $content,
            'info' => $fileInfo,
            'syntax' => $this->getSyntaxMode($extension),
            'encoding' => mb_detect_encoding($content, 'UTF-8, ISO-8859-1, ASCII', true)
        ];
    }

    /**
     * Save file content
     */
    public function saveFile(string $path, string $domain, string $content): bool
    {
        $fullPath = $this->getDomainPath($domain, $path);
        $directory = dirname($fullPath);

        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $result = file_put_contents($fullPath, $content);
        
        if ($result !== false) {
            Log::channel('performance')->info('File saved', [
                'path' => $path,
                'domain' => $domain,
                'size' => strlen($content)
            ]);
            return true;
        }

        return false;
    }

    /**
     * Create new file
     */
    public function createFile(string $path, string $domain, string $fileName, string $content = ''): bool
    {
        $fullPath = $this->getDomainPath($domain, $path . '/' . $fileName);
        
        if (file_exists($fullPath)) {
            throw new \Exception('File already exists');
        }

        return $this->saveFile($path . '/' . $fileName, $domain, $content);
    }

    /**
     * Create new directory
     */
    public function createDirectory(string $path, string $domain, string $dirName): bool
    {
        $fullPath = $this->getDomainPath($domain, $path . '/' . $dirName);
        
        if (file_exists($fullPath)) {
            throw new \Exception('Directory already exists');
        }

        $result = mkdir($fullPath, 0755, true);
        
        if ($result) {
            Log::channel('performance')->info('Directory created', [
                'path' => $path . '/' . $dirName,
                'domain' => $domain
            ]);
        }

        return $result;
    }

    /**
     * Delete file or directory
     */
    public function delete(string $path, string $domain): bool
    {
        $fullPath = $this->getDomainPath($domain, $path);
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File or directory not found');
        }

        if (is_dir($fullPath)) {
            $result = $this->deleteDirectory($fullPath);
        } else {
            $result = unlink($fullPath);
        }

        if ($result) {
            Log::channel('performance')->info('File/directory deleted', [
                'path' => $path,
                'domain' => $domain
            ]);
        }

        return $result;
    }

    /**
     * Copy file or directory
     */
    public function copy(string $sourcePath, string $destPath, string $domain): bool
    {
        $sourceFullPath = $this->getDomainPath($domain, $sourcePath);
        $destFullPath = $this->getDomainPath($domain, $destPath);
        
        if (!file_exists($sourceFullPath)) {
            throw new \Exception('Source not found');
        }

        if (file_exists($destFullPath)) {
            throw new \Exception('Destination already exists');
        }

        if (is_dir($sourceFullPath)) {
            return $this->copyDirectory($sourceFullPath, $destFullPath);
        } else {
            return copy($sourceFullPath, $destFullPath);
        }
    }

    /**
     * Move/rename file or directory
     */
    public function move(string $sourcePath, string $destPath, string $domain): bool
    {
        $sourceFullPath = $this->getDomainPath($domain, $sourcePath);
        $destFullPath = $this->getDomainPath($domain, $destPath);
        
        if (!file_exists($sourceFullPath)) {
            throw new \Exception('Source not found');
        }

        $result = rename($sourceFullPath, $destFullPath);
        
        if ($result) {
            Log::channel('performance')->info('File/directory moved', [
                'from' => $sourcePath,
                'to' => $destPath,
                'domain' => $domain
            ]);
        }

        return $result;
    }

    /**
     * Change file permissions
     */
    public function changePermissions(string $path, string $domain, string $permissions): bool
    {
        $fullPath = $this->getDomainPath($domain, $path);
        
        if (!file_exists($fullPath)) {
            throw new \Exception('File not found');
        }

        $octal = octdec($permissions);
        $result = chmod($fullPath, $octal);
        
        if ($result) {
            Log::channel('security')->info('Permissions changed', [
                'path' => $path,
                'domain' => $domain,
                'permissions' => $permissions
            ]);
        }

        return $result;
    }

    /**
     * Extract archive file
     */
    public function extractArchive(string $archivePath, string $domain, string $extractTo = null): bool
    {
        $fullArchivePath = $this->getDomainPath($domain, $archivePath);
        $extractPath = $extractTo ? $this->getDomainPath($domain, $extractTo) : dirname($fullArchivePath);
        
        if (!file_exists($fullArchivePath)) {
            throw new \Exception('Archive file not found');
        }

        $extension = strtolower(pathinfo($fullArchivePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'zip':
                return $this->extractZip($fullArchivePath, $extractPath);
            case 'tar':
            case 'gz':
            case 'tgz':
                return $this->extractTar($fullArchivePath, $extractPath);
            default:
                throw new \Exception('Unsupported archive format');
        }
    }

    /**
     * Create archive from directory
     */
    public function createArchive(string $sourcePath, string $domain, string $archiveName, string $format = 'zip'): bool
    {
        $sourceFullPath = $this->getDomainPath($domain, $sourcePath);
        $archiveFullPath = $this->getDomainPath($domain, dirname($sourcePath) . '/' . $archiveName);
        
        if (!file_exists($sourceFullPath)) {
            throw new \Exception('Source not found');
        }

        switch ($format) {
            case 'zip':
                return $this->createZip($sourceFullPath, $archiveFullPath);
            case 'tar':
                return $this->createTar($sourceFullPath, $archiveFullPath);
            default:
                throw new \Exception('Unsupported archive format');
        }
    }

    /**
     * Search files
     */
    public function searchFiles(string $path, string $domain, string $query, array $options = []): array
    {
        $fullPath = $this->getDomainPath($domain, $path);
        $results = [];
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            $filename = $file->getFilename();
            $relativePath = str_replace($fullPath, '', $file->getPathname());
            
            // Search by filename
            if (!empty($options['filename']) && stripos($filename, $query) !== false) {
                $results[] = [
                    'path' => $relativePath,
                    'type' => 'filename',
                    'match' => $filename
                ];
            }
            
            // Search by content (only for text files)
            if (!empty($options['content']) && $file->isFile() && $this->isTextFile($filename)) {
                $content = file_get_contents($file->getPathname());
                if (stripos($content, $query) !== false) {
                    $results[] = [
                        'path' => $relativePath,
                        'type' => 'content',
                        'match' => $this->getContextSnippet($content, $query)
                    ];
                }
            }
        }

        return $results;
    }

    // Helper methods
    private function getDomainPath(string $domain, string $path): string
    {
        $domainPath = $this->basePath . '/' . $domain;
        return $domainPath . '/' . ltrim($path, '/');
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    private function isImageFile(string $filename): bool
    {
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $imageExtensions);
    }

    private function isTextFile(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $this->editableExtensions);
    }

    private function getFileIcon(string $filename, bool $isDir): string
    {
        if ($isDir) return 'fas fa-folder';
        
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $iconMap = [
            'php' => 'fab fa-php',
            'html' => 'fab fa-html5',
            'css' => 'fab fa-css3-alt',
            'js' => 'fab fa-js-square',
            'json' => 'fas fa-code',
            'xml' => 'fas fa-code',
            'sql' => 'fas fa-database',
            'pdf' => 'fas fa-file-pdf',
            'doc' => 'fas fa-file-word',
            'docx' => 'fas fa-file-word',
            'xls' => 'fas fa-file-excel',
            'xlsx' => 'fas fa-file-excel',
            'zip' => 'fas fa-file-archive',
            'rar' => 'fas fa-file-archive',
            'tar' => 'fas fa-file-archive',
            'gz' => 'fas fa-file-archive',
            'jpg' => 'fas fa-file-image',
            'jpeg' => 'fas fa-file-image',
            'png' => 'fas fa-file-image',
            'gif' => 'fas fa-file-image',
            'mp4' => 'fas fa-file-video',
            'avi' => 'fas fa-file-video',
            'mp3' => 'fas fa-file-audio',
            'wav' => 'fas fa-file-audio'
        ];

        return $iconMap[$extension] ?? 'fas fa-file';
    }

    private function getSyntaxMode(string $extension): string
    {
        $syntaxMap = [
            'php' => 'php',
            'html' => 'html',
            'htm' => 'html',
            'css' => 'css',
            'js' => 'javascript',
            'json' => 'json',
            'xml' => 'xml',
            'sql' => 'sql',
            'py' => 'python',
            'sh' => 'sh',
            'yml' => 'yaml',
            'yaml' => 'yaml',
            'md' => 'markdown'
        ];

        return $syntaxMap[$extension] ?? 'text';
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!file_exists($dir)) return true;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }

    private function copyDirectory(string $src, string $dst): bool
    {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcPath = $src . DIRECTORY_SEPARATOR . $file;
                $dstPath = $dst . DIRECTORY_SEPARATOR . $file;
                
                if (is_dir($srcPath)) {
                    $this->copyDirectory($srcPath, $dstPath);
                } else {
                    copy($srcPath, $dstPath);
                }
            }
        }
        closedir($dir);
        return true;
    }

    private function extractZip(string $archivePath, string $extractPath): bool
    {
        $zip = new ZipArchive;
        $result = $zip->open($archivePath);
        
        if ($result === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();
            return true;
        }
        return false;
    }

    private function extractTar(string $archivePath, string $extractPath): bool
    {
        $command = "tar -xf {$archivePath} -C {$extractPath}";
        $output = shell_exec($command . ' 2>&1');
        return $output === null || empty(trim($output));
    }

    private function createZip(string $sourcePath, string $archivePath): bool
    {
        $zip = new ZipArchive();
        $result = $zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) return false;

        if (is_file($sourcePath)) {
            $zip->addFile($sourcePath, basename($sourcePath));
        } else {
            $this->addDirectoryToZip($zip, $sourcePath, '');
        }

        $zip->close();
        return true;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $base): void
    {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            $relativePath = $base ? $base . DIRECTORY_SEPARATOR . $file : $file;
            
            if (is_dir($filePath)) {
                $zip->addEmptyDir($relativePath);
                $this->addDirectoryToZip($zip, $filePath, $relativePath);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function createTar(string $sourcePath, string $archivePath): bool
    {
        $command = "tar -czf {$archivePath} -C " . dirname($sourcePath) . " " . basename($sourcePath);
        $output = shell_exec($command . ' 2>&1');
        return file_exists($archivePath);
    }

    private function getContextSnippet(string $content, string $query): string
    {
        $position = stripos($content, $query);
        $start = max(0, $position - 50);
        $length = 100;
        $snippet = substr($content, $start, $length);
        
        if ($start > 0) $snippet = '...' . $snippet;
        if ($start + $length < strlen($content)) $snippet .= '...';
        
        return $snippet;
    }
}
