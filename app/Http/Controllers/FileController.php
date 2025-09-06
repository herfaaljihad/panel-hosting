<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\AdvancedFileManagerService;
use App\Http\Requests\StoreFileRequest;

class FileController extends Controller
{
    protected AdvancedFileManagerService $fileManager;

    public function __construct(AdvancedFileManagerService $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $domain = $request->get('domain', $user->domains->first()?->name);
        $path = $request->get('path', '/');
        
        if (!$domain || !$user->domains->where('name', $domain)->first()) {
            return redirect()->route('files.index')->with('error', 'Domain not found');
        }

        try {
            $items = $this->fileManager->getDirectoryListing($path, $domain);
            $breadcrumbs = $this->getBreadcrumbs($path);
            
            return view('files.index', compact('items', 'domain', 'path', 'breadcrumbs'));
        } catch (\Exception $e) {
            Log::error('File listing error: ' . $e->getMessage());
            return redirect()->route('files.index')->with('error', 'Unable to access directory');
        }
    }

    public function editor(Request $request)
    {
        $user = Auth::user();
        $domain = $request->get('domain');
        $path = $request->get('path');
        
        if (!$domain || !$user->domains->where('name', $domain)->first()) {
            return redirect()->route('files.index')->with('error', 'Domain not found');
        }

        try {
            $fileData = $this->fileManager->readFile($path, $domain);
            return view('files.editor', compact('fileData', 'domain', 'path'));
        } catch (\Exception $e) {
            Log::error('File editor error: ' . $e->getMessage());
            return redirect()->route('files.index')->with('error', $e->getMessage());
        }
    }

    public function saveFile(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'path' => 'required|string',
            'content' => 'required|string'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->fileManager->saveFile(
                $request->get('path'),
                $domain,
                $request->get('content')
            );

            if ($result) {
                return response()->json(['success' => true, 'message' => 'File saved successfully']);
            } else {
                return response()->json(['error' => 'Failed to save file'], 500);
            }
        } catch (\Exception $e) {
            Log::error('File save error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createFile(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'path' => 'required|string',
            'filename' => 'required|string|max:255',
            'content' => 'nullable|string'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->fileManager->createFile(
                $request->get('path'),
                $domain,
                $request->get('filename'),
                $request->get('content', '')
            );

            if ($result) {
                return response()->json(['success' => true, 'message' => 'File created successfully']);
            } else {
                return response()->json(['error' => 'Failed to create file'], 500);
            }
        } catch (\Exception $e) {
            Log::error('File creation error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createDirectory(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'path' => 'required|string',
            'dirname' => 'required|string|max:255'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->fileManager->createDirectory(
                $request->get('path'),
                $domain,
                $request->get('dirname')
            );

            if ($result) {
                return response()->json(['success' => true, 'message' => 'Directory created successfully']);
            } else {
                return response()->json(['error' => 'Failed to create directory'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Directory creation error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $userId = Auth::id();
        $userPath = "user_files/{$userId}";
        
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        
        // Check if file already exists
        if (Storage::exists("{$userPath}/{$filename}")) {
            return redirect()->route('files.index')->with('error', 'File dengan nama tersebut sudah ada!');
        }

        $file->storeAs($userPath, $filename);

        return redirect()->route('files.index')->with('success', 'File berhasil diupload!');
    }

    public function download(Request $request)
    {
        $user = Auth::user();
        $domain = $request->get('domain');
        $path = $request->get('path');
        
        if (!$domain || !$user->domains->where('name', $domain)->first()) {
            abort(403, 'Unauthorized');
        }

        $fullPath = storage_path('app/domains/' . $domain . '/' . ltrim($path, '/'));
        
        if (!file_exists($fullPath) || is_dir($fullPath)) {
            abort(404, 'File not found');
        }

        return response()->download($fullPath);
    }

    public function delete(Request $request)
    {
        $userId = Auth::id();
        $filename = $request->input('filename');
        $filePath = "user_files/{$userId}/{$filename}";

        if (!Storage::exists($filePath)) {
            return redirect()->route('files.index')->with('error', 'File tidak ditemukan!');
        }

        Storage::delete($filePath);

        return redirect()->route('files.index')->with('success', 'File berhasil dihapus!');
    }

    public function copy(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'source' => 'required|string',
            'destination' => 'required|string'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->fileManager->copy(
                $request->get('source'),
                $request->get('destination'),
                $domain
            );

            if ($result) {
                return response()->json(['success' => true, 'message' => 'Item copied successfully']);
            } else {
                return response()->json(['error' => 'Failed to copy item'], 500);
            }
        } catch (\Exception $e) {
            Log::error('File copy error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function move(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'source' => 'required|string',
            'destination' => 'required|string'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->fileManager->move(
                $request->get('source'),
                $request->get('destination'),
                $domain
            );

            if ($result) {
                return response()->json(['success' => true, 'message' => 'Item moved successfully']);
            } else {
                return response()->json(['error' => 'Failed to move item'], 500);
            }
        } catch (\Exception $e) {
            Log::error('File move error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function changePermissions(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'path' => 'required|string',
            'permissions' => 'required|string|regex:/^[0-7]{4}$/'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->fileManager->changePermissions(
                $request->get('path'),
                $domain,
                $request->get('permissions')
            );

            if ($result) {
                return response()->json(['success' => true, 'message' => 'Permissions changed successfully']);
            } else {
                return response()->json(['error' => 'Failed to change permissions'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Permissions change error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function extractArchive(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'path' => 'required|string',
            'extract_to' => 'nullable|string'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->fileManager->extractArchive(
                $request->get('path'),
                $domain,
                $request->get('extract_to')
            );

            if ($result) {
                return response()->json(['success' => true, 'message' => 'Archive extracted successfully']);
            } else {
                return response()->json(['error' => 'Failed to extract archive'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Archive extraction error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function createArchive(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'path' => 'required|string',
            'archive_name' => 'required|string',
            'format' => 'required|in:zip,tar'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $result = $this->fileManager->createArchive(
                $request->get('path'),
                $domain,
                $request->get('archive_name'),
                $request->get('format')
            );

            if ($result) {
                return response()->json(['success' => true, 'message' => 'Archive created successfully']);
            } else {
                return response()->json(['error' => 'Failed to create archive'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Archive creation error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function search(Request $request)
    {
        $request->validate([
            'domain' => 'required|string',
            'path' => 'required|string',
            'query' => 'required|string|min:3',
            'search_filename' => 'boolean',
            'search_content' => 'boolean'
        ]);

        $user = Auth::user();
        $domain = $request->get('domain');
        
        if (!$user->domains->where('name', $domain)->first()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $options = [
                'filename' => $request->boolean('search_filename'),
                'content' => $request->boolean('search_content')
            ];

            $results = $this->fileManager->searchFiles(
                $request->get('path'),
                $domain,
                $request->get('query'),
                $options
            );

            return response()->json(['success' => true, 'results' => $results]);
        } catch (\Exception $e) {
            Log::error('File search error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getBreadcrumbs(string $path): array
    {
        $breadcrumbs = [['name' => 'Home', 'path' => '/']];
        
        if ($path !== '/') {
            $parts = explode('/', trim($path, '/'));
            $currentPath = '';
            
            foreach ($parts as $part) {
                $currentPath .= '/' . $part;
                $breadcrumbs[] = ['name' => $part, 'path' => $currentPath];
            }
        }
        
        return $breadcrumbs;
    }
}
