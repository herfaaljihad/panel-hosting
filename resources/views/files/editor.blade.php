@extends('layouts.panel')

@section('title', 'File Editor - Panel Hosting')

@section('content')
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-edit me-2"></i>File Editor
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-success" id="saveFile">
                <i class="fas fa-save me-1"></i>Save
            </button>
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left me-1"></i>Back
            </button>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-file-code me-2"></i>{{ $fileData['path'] ?? 'New File' }}
                </h6>
            </div>
            <div class="col-md-6 text-end">
                <span class="badge bg-info">{{ strtoupper($fileData['extension'] ?? 'txt') }}</span>
                <span class="badge bg-secondary">{{ format_bytes($fileData['size'] ?? 0) }}</span>
                <span class="badge bg-light text-dark">{{ date('M d, Y H:i', $fileData['modified'] ?? time()) }}</span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <form id="editorForm" method="POST" action="{{ route('files.save') }}">
            @csrf
            <input type="hidden" name="path" value="{{ $path }}">
            <input type="hidden" name="domain" value="{{ $domain }}">
            
            <div id="editor" style="height: 600px; width: 100%;">{{ $fileData['content'] ?? '' }}</div>
            
            <!-- Hidden textarea for form submission -->
            <textarea name="content" id="content" style="display: none;"></textarea>
        </form>
    </div>
    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Use Ctrl+S to save quickly
                </small>
            </div>
            <div class="col-md-6 text-end">
                <span id="editorStatus" class="badge bg-success">Ready</span>
                <span id="cursorPosition" class="text-muted ms-2">Line 1, Column 1</span>
            </div>
        </div>
    </div>
</div>

<!-- File Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editor Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fontSize" class="form-label">Font Size</label>
                            <select class="form-select" id="fontSize">
                                <option value="12">12px</option>
                                <option value="14" selected>14px</option>
                                <option value="16">16px</option>
                                <option value="18">18px</option>
                                <option value="20">20px</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="theme" class="form-label">Theme</label>
                            <select class="form-select" id="theme">
                                <option value="monokai">Monokai</option>
                                <option value="github">GitHub</option>
                                <option value="terminal">Terminal</option>
                                <option value="tomorrow">Tomorrow</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="wordWrap" checked>
                            <label class="form-check-label" for="wordWrap">Word Wrap</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="showInvisibles">
                            <label class="form-check-label" for="showInvisibles">Show Invisibles</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="applySettings">Apply Settings</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.min.css">
<style>
    #editor {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }
    
    .ace_editor {
        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace !important;
    }
    
    .card-footer {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/ace.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/mode-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/mode-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/mode-css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/mode-html.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/mode-json.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/mode-xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/mode-yaml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/theme-monokai.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/theme-github.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/theme-terminal.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.14/theme-tomorrow.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize ACE Editor
    var editor = ace.edit("editor");
    var extension = "{{ $fileData['extension'] ?? 'txt' }}";
    
    // Set editor mode based on file extension
    var modeMap = {
        'php': 'php',
        'js': 'javascript',
        'css': 'css',
        'html': 'html',
        'htm': 'html',
        'json': 'json',
        'xml': 'xml',
        'yml': 'yaml',
        'yaml': 'yaml'
    };
    
    var mode = modeMap[extension] || 'text';
    editor.setTheme("ace/theme/monokai");
    editor.session.setMode("ace/mode/" + mode);
    
    // Editor settings
    editor.setOptions({
        fontSize: 14,
        showPrintMargin: false,
        wrap: true,
        enableBasicAutocompletion: true,
        enableLiveAutocompletion: true
    });
    
    // Update cursor position
    editor.selection.on('changeCursor', function() {
        var cursor = editor.getCursorPosition();
        $('#cursorPosition').text('Line ' + (cursor.row + 1) + ', Column ' + (cursor.column + 1));
    });
    
    // Save file function
    function saveFile() {
        $('#editorStatus').removeClass('bg-success bg-warning bg-danger').addClass('bg-warning').text('Saving...');
        
        // Get content from editor
        var content = editor.getValue();
        $('#content').val(content);
        
        // Submit form via AJAX
        $.ajax({
            url: $('#editorForm').attr('action'),
            method: 'POST',
            data: $('#editorForm').serialize(),
            success: function(response) {
                $('#editorStatus').removeClass('bg-warning bg-danger').addClass('bg-success').text('Saved');
                
                // Show success message
                var alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                    'File saved successfully!' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>');
                $('.card').before(alert);
                
                setTimeout(function() {
                    $('#editorStatus').text('Ready');
                }, 2000);
            },
            error: function(xhr) {
                $('#editorStatus').removeClass('bg-warning bg-success').addClass('bg-danger').text('Error');
                
                var message = 'Failed to save file.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                var alert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>');
                $('.card').before(alert);
                
                setTimeout(function() {
                    $('#editorStatus').text('Ready');
                }, 2000);
            }
        });
    }
    
    // Save button click
    $('#saveFile').click(function() {
        saveFile();
    });
    
    // Keyboard shortcuts
    $(document).keydown(function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            saveFile();
        }
    });
    
    // Apply settings
    $('#applySettings').click(function() {
        var fontSize = $('#fontSize').val();
        var theme = $('#theme').val();
        var wordWrap = $('#wordWrap').is(':checked');
        var showInvisibles = $('#showInvisibles').is(':checked');
        
        editor.setOptions({
            fontSize: parseInt(fontSize),
            wrap: wordWrap,
            showInvisibles: showInvisibles
        });
        
        editor.setTheme("ace/theme/" + theme);
        
        $('#settingsModal').modal('hide');
    });
});
</script>
@endpush
