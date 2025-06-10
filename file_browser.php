<?php

$root_dir = __DIR__;
$ignored_extensions = ['.git', '.gitignore', '.DS_Store', '.idea', '.vscode', 'node_modules', 'vendor'];
$file_icons = [
    'php' => '📜',
    'js' => '📝',
    'css' => '🎨',
    'html' => '🌐',
    'json' => '📋',
    'md' => '📚',
    'sql' => '🗃️',
    'txt' => '📄',
    'jpg' => '🖼️',
    'jpeg' => '🖼️',
    'png' => '🖼️',
    'gif' => '🖼️',
    'svg' => '🖼️',
    'pdf' => '📑',
    'zip' => '📦',
    'default' => '📁'
];


function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function getFileIcon($filename) {
    global $file_icons;
    $ext = getFileExtension($filename);
    return isset($file_icons[$ext]) ? $file_icons[$ext] : $file_icons['default'];
}


function getDirContents($dir, $relativePath = '') {
    global $ignored_extensions;
    $results = [];

    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || in_array($file, $ignored_extensions)) {
            continue;
        }

        $path = $dir . '/' . $file;
        $relativePath = $relativePath === '' ? $file : $relativePath . '/' . $file;

        if (is_dir($path)) {
            $results[] = [
                'name' => $file,
                'path' => $relativePath,
                'type' => 'dir',
                'children' => getDirContents($path, $relativePath)
            ];
        } else {
            $results[] = [
                'name' => $file,
                'path' => $relativePath,
                'type' => 'file',
                'extension' => getFileExtension($file),
                'size' => filesize($path),
                'modified' => date('Y-m-d H:i:s', filemtime($path))
            ];
        }
    }

    return $results;
}


function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

function renderFileTree($items, $level = 0) {
    $html = '<ul class="file-tree" style="' . ($level === 0 ? '' : 'display: none;') . '">';
    
    foreach ($items as $item) {
        $indent = str_repeat('  ', $level);
        $icon = $item['type'] === 'dir' ? '📁' : getFileIcon($item['name']);
        
        $html .= '<li class="' . $item['type'] . '">';
        $html .= '<span class="item-name" data-path="' . htmlspecialchars($item['path']) . '">';
        $html .= $icon . ' ' . htmlspecialchars($item['name']);
        $html .= '</span>';
        
        if ($item['type'] === 'dir' && !empty($item['children'])) {
            $html .= renderFileTree($item['children'], $level + 1);
        } else if ($item['type'] === 'file') {
            $html .= '<span class="file-info">';
            $html .= formatFileSize($item['size']) . ' | ' . $item['modified'];
            $html .= '</span>';
        }
        
        $html .= '</li>';
    }
    
    $html .= '</ul>';
    return $html;
}


function getFileDetails($filepath) {
    $absolutePath = __DIR__ . '/' . $filepath;
    if (!file_exists($absolutePath)) {
        return ['error' => 'File does not exist'];
    }
    
    $extension = getFileExtension($filepath);
    $size = filesize($absolutePath);
    $modified = date('Y-m-d H:i:s', filemtime($absolutePath));
    
    $content = '';
    $binary = false;
    
    $textExtensions = ['php', 'js', 'css', 'html', 'json', 'md', 'sql', 'txt', 'xml', 'htaccess'];
    
    if (in_array($extension, $textExtensions)) {
        $content = file_get_contents($absolutePath);
        $content = htmlspecialchars($content);
    } else {
        $binary = true;
        $content = 'Binary file, cannot display content.';
    }
    
    return [
        'name' => basename($filepath),
        'path' => $filepath,
        'extension' => $extension,
        'size' => formatFileSize($size),
        'modified' => $modified,
        'content' => $content,
        'binary' => $binary
    ];
}

if (isset($_GET['file'])) {
    header('Content-Type: application/json');
    echo json_encode(getFileDetails($_GET['file']));
    exit;
}

$structure = getDirContents($root_dir);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مستعرض ملفات WEP</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body {
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            direction: rtl;
        }
        .sidebar {
            width: 30%;
            height: 100%;
            overflow: auto;
            padding: 20px;
            background-color: #f5f5f5;
            border-left: 1px solid #ddd;
        }
        .content {
            width: 70%;
            height: 100%;
            overflow: auto;
            padding: 20px;
            background-color: #fff;
        }
        h1 {
            margin-top: 0;
            color: #333;
            font-size: 24px;
        }
        .file-tree {
            list-style-type: none;
            padding-right: 20px;
        }
        .file-tree li {
            margin: 5px 0;
        }
        .item-name {
            cursor: pointer;
            padding: 5px;
            border-radius: 3px;
            display: inline-block;
        }
        .item-name:hover {
            background-color: #e0e0e0;
        }
        .file-info {
            margin-right: 10px;
            font-size: 12px;
            color: #666;
        }
        pre {
            background-color: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
            direction: ltr;
            text-align: left;
        }
        .file-header {
            background-color: #eee;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .toggle-icon {
            margin-left: 5px;
            cursor: pointer;
        }
        .file-path {
            font-family: monospace;
            color: #666;
            margin-bottom: 10px;
        }
        .file-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        .content-wrapper {
            margin-top: 20px;
        }
        .line-numbers {
            font-family: monospace;
            color: #999;
            text-align: right;
            padding-right: 10px;
            user-select: none;
        }
        .search-box {
            margin-bottom: 20px;
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .nav-breadcrumbs {
            margin-bottom: 15px;
            padding: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
        }
        .breadcrumb-item {
            display: inline-block;
            margin-left: 5px;
        }
        .breadcrumb-item:not(:last-child)::after {
            content: '/';
            margin-right: 5px;
            color: #999;
        }
        .breadcrumb-link {
            cursor: pointer;
            color: #0066cc;
        }
        .back-button {
            margin-bottom: 10px;
            padding: 5px 10px;
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .back-button:hover {
            background-color: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h1>مستعرض ملفات WEP</h1>
        <input type="text" class="search-box" id="searchBox" placeholder="البحث عن ملفات...">
        <div class="nav-breadcrumbs" id="breadcrumbs">
            <span class="breadcrumb-item">
                <span class="breadcrumb-link" data-path="">الجذر</span>
            </span>
        </div>
        <button class="back-button" id="backButton" style="display: none;">العودة للخلف</button>
        <div id="fileTreeContainer">
            <?php echo renderFileTree($structure); ?>
        </div>
    </div>
    <div class="content" id="fileContent">
        <div class="welcome-message">
            <h2>مرحبًا بك في مستعرض ملفات WEP</h2>
            <p>اضغط على أي ملف في الشريط الجانبي لعرض محتواه هنا.</p>
            <p>قائمة API المتاحة:</p>
            <ul>
                <li><a href="/WEP/api/login.php" target="_blank">صفحة تسجيل الدخول</a></li>
                <li><a href="/WEP/api/public_api.php?action=users" target="_blank">عرض المستخدمين (Public API)</a></li>
                <li><a href="/WEP/api/test_api.php" target="_blank">اختبار API</a></li>
                <li><a href="/WEP/fix_db.php" target="_blank">إصلاح قاعدة البيانات</a></li>
            </ul>
            <p>بيانات المستخدم التجريبي للاختبار:</p>
            <ul>
                <li>اسم المستخدم: test_user</li>
                <li>كلمة المرور: password123</li>
                <li>البريد الإلكتروني: test@example.com</li>
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileTreeContainer = document.getElementById('fileTreeContainer');
            const fileContent = document.getElementById('fileContent');
            const searchBox = document.getElementById('searchBox');
            const breadcrumbs = document.getElementById('breadcrumbs');
            const backButton = document.getElementById('backButton');
            
            let navigationHistory = [];
            
            fileTreeContainer.addEventListener('click', function(e) {
                const target = e.target;
                if (target.classList.contains('item-name')) {
                    const path = target.getAttribute('data-path');
                    const listItem = target.parentElement;
                    
                    if (listItem.classList.contains('dir')) {
                        const subList = listItem.querySelector('ul');
                        if (subList) {
                            subList.style.display = subList.style.display === 'none' ? 'block' : 'none';
                        }
                        updateBreadcrumbs(path);
                    } else if (listItem.classList.contains('file')) {
                        loadFileContent(path);
                        navigationHistory.push(path);
                        backButton.style.display = navigationHistory.length > 1 ? 'block' : 'none';
                    }
                }
            });
            
            function loadFileContent(path) {
                fetch(`?file=${encodeURIComponent(path)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            fileContent.innerHTML = `<div class="error">${data.error}</div>`;
                            return;
                        }
                        
                        let html = `
                            <div class="file-header">
                                <h2>${data.name}</h2>
                                <div class="file-path">${data.path}</div>
                                <div class="file-meta">
                                    <span>النوع: ${data.extension || 'غير معروف'}</span>
                                    <span>الحجم: ${data.size}</span>
                                    <span>آخر تعديل: ${data.modified}</span>
                                </div>
                            </div>
                        `;
                        
                        if (data.binary) {
                            html += `<div class="content-wrapper">${data.content}</div>`;
                        } else {
                            const lines = data.content.split('\n');
                            let lineNumbers = '';
                            let codeContent = '';
                            
                            for (let i = 0; i < lines.length; i++) {
                                lineNumbers += `${i + 1}<br>`;
                                codeContent += `${lines[i]}<br>`;
                            }
                            
                            html += `
                                <div class="content-wrapper">
                                    <pre><code>${data.content}</code></pre>
                                </div>
                            `;
                        }
                        
                        fileContent.innerHTML = html;
                    })
                    .catch(error => {
                        fileContent.innerHTML = `<div class="error">Error loading file: ${error.message}</div>`;
                    });
            }
            
            function updateBreadcrumbs(path) {
                if (!path) {
                    breadcrumbs.innerHTML = `
                        <span class="breadcrumb-item">
                            <span class="breadcrumb-link" data-path="">الجذر</span>
                        </span>
                    `;
                    return;
                }
                
                const parts = path.split('/');
                let html = `
                    <span class="breadcrumb-item">
                        <span class="breadcrumb-link" data-path="">الجذر</span>
                    </span>
                `;
                
                let currentPath = '';
                for (let i = 0; i < parts.length; i++) {
                    currentPath += (i > 0 ? '/' : '') + parts[i];
                    html += `
                        <span class="breadcrumb-item">
                            <span class="breadcrumb-link" data-path="${currentPath}">${parts[i]}</span>
                        </span>
                    `;
                }
                
                breadcrumbs.innerHTML = html;
            }
            
            searchBox.addEventListener('input', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const allItems = document.querySelectorAll('.file-tree .item-name');
                
                allItems.forEach(item => {
                    const name = item.textContent.toLowerCase();
                    const li = item.parentElement;
                    
                    if (name.includes(searchTerm)) {
                        li.style.display = '';
                    } else {
                        li.style.display = 'none';
                    }
                });
            });
            
            breadcrumbs.addEventListener('click', function(e) {
                if (e.target.classList.contains('breadcrumb-link')) {
                    const path = e.target.getAttribute('data-path');
                    updateBreadcrumbs(path);
                }
            });
            
            backButton.addEventListener('click', function() {
                if (navigationHistory.length > 1) {
                    navigationHistory.pop();
                    const previousPath = navigationHistory[navigationHistory.length - 1];
                    loadFileContent(previousPath);
                    if (navigationHistory.length <= 1) {
                        backButton.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>
