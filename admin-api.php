<?php
// admin-api.php - Backend API for page management
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Security: Check if user is authenticated
session_start();
if (!isset($_SESSION['admin_logged_in']) && $_GET['action'] !== 'login') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'list-pages':
        listPages();
        break;
    case 'load-page':
        loadPage();
        break;
    case 'save-page':
        savePage();
        break;
    case 'create-page':
        createPage();
        break;
    case 'delete-page':
        deletePage();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function handleLogin() {
    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    
    // CHANGE THESE CREDENTIALS!
    $validUsername = 'admin';
    $validPassword = 'admin123';
    
    // In production, use password_hash() and database
    if ($username === $validUsername && $password === $validPassword) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        echo json_encode(['success' => true, 'username' => $username]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true]);
}

function listPages() {
    $pages = [];
    $files = glob('*.html');
    
    foreach($files as $file) {
        // Skip admin pages
        if (strpos($file, 'admin-') === 0) continue;
        
        $content = file_get_contents($file);
        preg_match('/<title>(.*?)<\/title>/', $content, $titleMatch);
        
        $pages[] = [
            'filename' => $file,
            'name' => $titleMatch[1] ?? $file,
            'size' => filesize($file),
            'modified' => date('Y-m-d H:i:s', filemtime($file)),
            'status' => 'published'
        ];
    }
    
    echo json_encode(['pages' => $pages]);
}

function loadPage() {
    $filename = $_GET['page'] ?? '';
    
    if (empty($filename) || !file_exists($filename)) {
        http_response_code(404);
        echo json_encode(['error' => 'Page not found']);
        return;
    }
    
    // Security: Only allow HTML files
    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'html') {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid file type']);
        return;
    }
    
    $content = file_get_contents($filename);
    
    // Extract meta information
    preg_match('/<title>(.*?)<\/title>/', $content, $title);
    preg_match('/<meta name="description" content="(.*?)"/', $content, $description);
    preg_match('/<meta name="keywords" content="(.*?)"/', $content, $keywords);
    
    // Extract body content
    preg_match('/<body[^>]*>(.*?)<\/body>/s', $content, $body);
    
    echo json_encode([
        'filename' => $filename,
        'fullContent' => $content,
        'bodyContent' => $body[1] ?? '',
        'metaTitle' => $title[1] ?? '',
        'metaDescription' => $description[1] ?? '',
        'metaKeywords' => $keywords[1] ?? '',
        'lastModified' => date('Y-m-d H:i:s', filemtime($filename))
    ]);
}

function savePage() {
    $data = json_decode(file_get_contents('php://input'), true);
    $filename = $data['filename'] ?? '';
    $content = $data['content'] ?? '';
    $bodyContent = $data['bodyContent'] ?? '';
    $saveType = $data['saveType'] ?? 'full'; // 'full' or 'body'
    
    if (empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Filename required']);
        return;
    }
    
    // Security: Only allow HTML files
    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'html') {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid file type']);
        return;
    }
    
    // Create backup
    if (file_exists($filename)) {
        $backupDir = 'backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $backupFile = $backupDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_' . date('Y-m-d_H-i-s') . '.html';
        copy($filename, $backupFile);
    }
    
    if ($saveType === 'body' && file_exists($filename)) {
        // Replace only body content
        $originalContent = file_get_contents($filename);
        $updatedContent = preg_replace(
            '/(<body[^>]*>)(.*?)(<\/body>)/s',
            '$1' . $bodyContent . '$3',
            $originalContent
        );
        
        // Update meta tags if provided
        if (!empty($data['metaTitle'])) {
            $updatedContent = preg_replace(
                '/<title>(.*?)<\/title>/',
                '<title>' . htmlspecialchars($data['metaTitle']) . '</title>',
                $updatedContent
            );
        }
        
        if (!empty($data['metaDescription'])) {
            if (preg_match('/<meta name="description"/', $updatedContent)) {
                $updatedContent = preg_replace(
                    '/<meta name="description" content="(.*?)">/',
                    '<meta name="description" content="' . htmlspecialchars($data['metaDescription']) . '">',
                    $updatedContent
                );
            } else {
                $updatedContent = preg_replace(
                    '/(<head[^>]*>)/',
                    '$1' . "\n    " . '<meta name="description" content="' . htmlspecialchars($data['metaDescription']) . '">',
                    $updatedContent
                );
            }
        }
        
        $content = $updatedContent;
    }
    
    // Save the file
    $result = file_put_contents($filename, $content);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Page saved successfully',
            'filename' => $filename,
            'bytes' => $result
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save file']);
    }
}

function createPage() {
    $data = json_decode(file_get_contents('php://input'), true);
    $filename = $data['filename'] ?? '';
    $template = $data['template'] ?? 'default';
    
    if (empty($filename)) {
        http_response_code(400);
        echo json_encode(['error' => 'Filename required']);
        return;
    }
    
    // Ensure .html extension
    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'html') {
        $filename .= '.html';
    }
    
    if (file_exists($filename)) {
        http_response_code(409);
        echo json_encode(['error' => 'File already exists']);
        return;
    }
    
    // Create page from template
    $templateContent = getTemplate($template);
    
    $result = file_put_contents($filename, $templateContent);
    
    if ($result !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Page created successfully',
            'filename' => $filename
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create file']);
    }
}

function deletePage() {
    $filename = $_GET['page'] ?? '';
    
    if (empty($filename) || !file_exists($filename)) {
        http_response_code(404);
        echo json_encode(['error' => 'Page not found']);
        return;
    }
    
    // Prevent deletion of admin pages
    if (strpos($filename, 'admin-') === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Cannot delete admin pages']);
        return;
    }
    
    // Move to trash instead of deleting
    $trashDir = 'trash';
    if (!is_dir($trashDir)) {
        mkdir($trashDir, 0755, true);
    }
    
    $trashFile = $trashDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_' . date('Y-m-d_H-i-s') . '.html';
    
    if (rename($filename, $trashFile)) {
        echo json_encode([
            'success' => true,
            'message' => 'Page moved to trash'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete file']);
    }
}

function getTemplate($template) {
    $baseTemplate = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Page - CY visa help</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        h1 { font-family: 'Playfair Display', serif; color: #0a2342; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Your New Page</h1>
        <p>Start editing this page using the admin editor.</p>
    </div>
</body>
</html>
HTML;
    
    return $baseTemplate;
}
?>
