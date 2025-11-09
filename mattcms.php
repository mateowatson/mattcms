<?php
const ROOT_DIR = __DIR__;

const TIMEZONE = 'America/Chicago';

date_default_timezone_set(TIMEZONE);

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

        <!-- MattCMS: Content --><?php echo $content; ?><!-- /MattCMS: Content -->
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    file_put_contents($directory . '/index.html', $html);
}

function get_admin_header() {
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

function get_admin_footer() {
    ob_start();
    ?>
    </body>
    </html>
    <?php
    $footer = ob_get_clean();
    return $footer;
}

function get_admin_edit_page_form($path = '') {
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
    <form action="/mattcms.php?controller=page_update_post" method="post">
        <label for="page_update_post_path">Path:</label>
        <input type="text" name="page_update_post_path" id="page_update_post_path" value="<?php echo htmlspecialchars($path); ?>">

        <label for="page_update_post_title">Title:</label>
        <input type="text" name="page_update_post_title" id="page_update_post_title" value="<?php echo htmlspecialchars($title_contents); ?>">

        <label for="page_update_post_content">Content:</label>
        <textarea name="page_update_post_content" id="page_update_post_content"><?php echo $content_contents; ?></textarea>

        <button type="submit" name="page_update_post_submit"><?= !empty($path) ? 'Update Page' : 'Create Page' ?></button>
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

function controller_homepage() {
    echo get_admin_header();
    ob_start();
    ?>
    <h2>Welcome to MattCMS</h2>
    <p>This is a simple content management system.</p>
    <ul>

        <li><a href="/mattcms.php?controller=page_create_get">Add new page</a></li>
        <li><a href="/mattcms.php?controller=page_index_get">Index of pages</a></li>
        <li><a href="/mattcms.php?controller=partials_index_get">Template Partials</a></li>
    </ul>
    <?php
    $homepage = ob_get_clean();
    echo $homepage;
    echo get_admin_footer();
}

function controller_page_index_get() {
    echo get_admin_header();
    $paths = get_page_paths();
    ob_start();
    ?>
    <h2>Available Page Paths:</h2>
    
    <ul>
    <?php foreach ($paths as $path): ?>
        <li>
            <a href="/mattcms.php?controller=page_update_get&path=<?php echo rawurlencode($path); ?>"><?php echo htmlspecialchars($path); ?></a>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php
    $pageIndex = ob_get_clean();
    echo $pageIndex;
    echo get_admin_footer();
}

function controller_blocks_index_get() {
    echo get_admin_header();
    ob_start();
    ?>
    <h2>Blocks</h2>
    <p>List of available blocks/partials will go here.</p>
    <?php
    $partialsIndex = ob_get_clean();
    echo $partialsIndex;
    echo get_admin_footer();
}

function controller_page_create_get() {
    echo get_admin_header();
    ob_start();
    ?>
    <?php echo get_admin_edit_page_form(); ?>
    <?php
    $form = ob_get_clean();
    echo $form;
    echo get_admin_footer();
}

function controller_page_update_get() {
    $path = $_GET['path'] ?? '';
    echo get_admin_header();
    ob_start();
    ?>
    <h2>Update Page: <?php echo htmlspecialchars($path); ?></h2>
    <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
        <p class="notice">Page updated successfully.</p>
    <?php endif; ?>
    <?php echo get_admin_edit_page_form($path); ?>
    <?php
    $form = ob_get_clean();
    echo $form;
    echo get_admin_footer();
}

function controller_page_update_post() {
    if (!isset($_POST['page_update_post_path'], $_POST['page_update_post_title'], $_POST['page_update_post_content'])) {
        throw new InvalidArgumentException('Missing required form fields.');
    }

    $fields = [
        'path' => $_POST['page_update_post_path'],
        'title' => $_POST['page_update_post_title'],
        'content' => $_POST['page_update_post_content'],
    ];
    edit_page($fields);
    header("Location: /mattcms.php?controller=page_update_get&path=".rawurlencode($fields['path'])."&success=1");
    die();
}

function init() {
    $controller = $_REQUEST['controller'] ?? null;
    if ($controller === 'page_update_post') {
        controller_page_update_post();
    } else if($controller === 'page_update_get') {
        controller_page_update_get();
    } else if($controller === 'page_index_get') {
        controller_page_index_get();
    } else if($controller === 'page_create_get') {
        controller_page_create_get();
    } else if ($controller === 'partials_index_get') {
        controller_blocks_index_get();
    } else {
        controller_homepage();
    }
}

init();