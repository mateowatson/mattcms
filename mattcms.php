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

    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
    </head>
    <body>

        <h1><!-- MattCMS: Title --><?php echo htmlspecialchars($title); ?><!-- /MattCMS: Title --></h1>

        <!-- MattCMS: Content -->
        <?php echo $content; ?>
        <!-- /MattCMS: Content -->
    </body>
    </html>
    <?php
    $html = ob_get_clean();

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
    <h1><a href="/mattcms.php">MattCMS</a></h1>
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

function get_mattcms_edit_page_form($path = '') {
    $relativePath = ltrim(trim($path), '/\\');
    $directory = ROOT_DIR . '/' . $relativePath;
    $htmlFile = $directory . '/index.html';
    $file_contents = '';
    $title_contents = '';
    $content_contents = '';

    $directory = ROOT_DIR . '/' . $relativePath;
    if (is_dir($directory)) {
        if (file_exists($htmlFile)) {
            // Handle the case where an HTML file already exists
            $file_contents = file_get_contents($htmlFile);
            // Extract existing title and content if needed
            $title_contents = preg_match('/<!-- MattCMS: Title -->(.*?)<!-- \/MattCMS: Title -->/s', $file_contents, $headerMatches) ? $headerMatches[1] : '';
            $content_contents = preg_match('/<!-- MattCMS: Content -->(.*?)<!-- \/MattCMS: Content -->/s', $file_contents, $contentMatches) ? $contentMatches[1] : '';
        }
    }

    ob_start();
    ?>
    <form action="/mattcms.php?edit_page" method="post">
        <label for="edit_page_path">Path:</label>
        <input type="text" name="edit_page_path" id="edit_page_path" value="<?php echo htmlspecialchars($path); ?>">

        <label for="edit_page_title">Title:</label>
        <input type="text" name="edit_page_title" id="edit_page_title" value="<?php echo htmlspecialchars($title_contents); ?>">

        <label for="edit_page_content">Content:</label>
        <textarea name="edit_page_content" id="edit_page_content"><?php echo $content_contents; ?></textarea>

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

function get_mattcms_homepage() {
    ob_start();
    ?>
    <h2>Welcome to MattCMS</h2>
    <p>This is a simple content management system.</p>
    <ul>

        <li><a href="/mattcms.php?page_create">Add new page</a></li>
        <li><a href="/mattcms.php?page_index">Index of pages</a></li>
        <li><a href="/mattcms.php?partials_index">Template Partials</a></li>
    </ul>
    <?php
    $homepage = ob_get_clean();
    return $homepage;
}

function get_mattcms_page_index() {
    $paths = get_page_paths();
    ob_start();
    ?>
    <h2>Available Page Paths:</h2>
    
    <ul>
    <?php foreach ($paths as $path): ?>
        <li>
            <a href="/mattcms.php?page_update=<?php echo htmlspecialchars($path); ?>"><?php echo htmlspecialchars($path); ?></a>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php
    $pageIndex = ob_get_clean();
    return $pageIndex;
}

function get_mattcms_partials_index() {
    ob_start();
    ?>
    <h2>Template Partials Index</h2>
    <p>List of available template partials will go here.</p>
    <?php
    $partialsIndex = ob_get_clean();
    return $partialsIndex;
}

function get_mattcms_page_create() {
    ob_start();
    ?>
    <?php echo get_mattcms_edit_page_form(); ?>
    <?php
    $form = ob_get_clean();
    return $form;
}

function get_mattcms_page_update($path) {
    ob_start();
    ?>
    <h2>Update Page: <?php echo htmlspecialchars($path); ?></h2>
    <?php echo get_mattcms_edit_page_form($path); ?>
    <?php
    $form = ob_get_clean();
    return $form;
}

function init() {
    echo get_mattcms_header();
    if (isset($_GET['edit_page'])) {
        echo get_mattcms_edit_page_form();
    } else if(isset($_GET['page_index'])) {
        echo get_mattcms_page_index();
    } else if(isset($_GET['page_create'])) {
        echo get_mattcms_page_create();
    } else if(isset($_GET['page_update'])) {
        echo get_mattcms_page_update($_GET['page_update']);
    } else if (isset($_GET['partials_index'])) {
        echo get_mattcms_partials_index();
    } else {
        echo get_mattcms_homepage();
    }
    echo get_mattcms_footer();
}

init();