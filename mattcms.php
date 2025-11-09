<?php
const ROOT_DIR = __DIR__;

const TIMEZONE = 'America/Chicago';

date_default_timezone_set(TIMEZONE);

if (isset($_POST['edit_page'])) {
    $fields = [
        'path' => $_POST['edit_page_path'],
        'title' => $_POST['edit_page_title'],
        'content' => $_POST['edit_page_content'],
    ];
    edit_page($fields);
}

function edit_page($fields) {
    $relativePath = ltrim(trim($fields['path']), '/\\');
    if ($relativePath === '') {
        throw new InvalidArgumentException('Path cannot be empty.');
    }

    $title = $fields['title'];
    $content = $fields['content'];

    $directory = ROOT_DIR . '/' . $relativePath;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException("Unable to create directory: $directory");
    }

    $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n    <meta charset=\"UTF-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>$title</title>\n</head>\n<body>\n    <h1>$title</h1>\n    $content\n</body>\n</html>";

    file_put_contents($directory . '/index.html', $html);
}

function get_mattcms_header() {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MattCMS</title>
        <!-- Minified version -->
        <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
    </head>
    <body>
    <h1>MattCMS</h1>
    <?php
    $header = ob_get_clean();
    return $header;
}

function get_mattcms_footer() {
    ob_start();
    ?>
    </body>
    </html>
    <?php
    $footer = ob_get_clean();
    return $footer;
}

function get_mattcms_edit_page_form() {
    ob_start();
    ?>
    <form action="/mattcms.php?edit_page" method="post">
        <label for="edit_page_path">Path:</label>
        <input type="text" name="edit_page_path" id="edit_page_path">

        <label for="edit_page_title">Title:</label>
        <input type="text" name="edit_page_title" id="edit_page_title">

        <label for="edit_page_content">Content:</label>
        <textarea name="edit_page_content" id="edit_page_content"></textarea>

        <button type="submit" name="edit_page">Edit Page</button>
    </form>
    <?php
    $form = ob_get_clean();
    return $form;
}

function get_page_paths() {
    $paths = [];
    $badlist = ['.', '..', '.git', '.github', '.vscode', 'mattcms.php', '.ddev'];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(ROOT_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        foreach($badlist as $baditem) {
            if (stristr($file->getPathname(), $baditem)) {
                continue 2;
            }
        }
        if ($file->isDir()) {
            $relativePath = str_replace(ROOT_DIR . '/', '', $file->getPathname());
            $paths[] = $relativePath;
        }
    }

    return $paths;
}

echo get_mattcms_header();
if (isset($_GET['edit_page'])) {
    echo get_mattcms_edit_page_form();
} else {
    $paths = get_page_paths();
    echo "<h2>Available Page Paths:</h2><ul>";
    foreach ($paths as $path) {
        echo "<li>$path</li>";
    }
    echo "</ul>";
}
echo get_mattcms_footer();