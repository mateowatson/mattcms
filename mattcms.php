<?php
const ROOT_DIR = __DIR__;

const TIMEZONE = 'America/Chicago';

date_default_timezone_set(TIMEZONE);

function edit_page($fields) {
    $relativePath = ltrim(trim($fields['path']), '/\\');
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
        <!-- mattcms:content --><?php echo $content; ?><!-- /mattcms:content -->
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

        <!-- Styles to separate black fields.  -->
        <style>
            .blocks-field {
                border: 1px solid #ccc;
                padding: 10px;
                margin-bottom: 10px;
            }
        </style>

        <!-- Add JavaScript that will add the ability to add more blocks to the block form, as in a repeater field.  -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const numFields = document.querySelectorAll('.blocks-field').length;
                const addButton = document.querySelector('button[data-blocks-add-field]');
                const fieldsContainer = document.querySelector('div.blocks-fields');

                addButton.addEventListener('click', function() {
                    const newField = document.createElement('div');
                    newField.classList.add('blocks-field');
                    let fieldNum = numFields + 1;
                    newField.innerHTML = `
                        <label for="label_field${fieldNum}">Field Label:</label>
                        <input type="text" name="label_field${fieldNum}" id="label_field${fieldNum}">
                        <label for="field_type${fieldNum}">Field Type:</label>
                        <select name="field_type${fieldNum}" id="field_type${fieldNum}" onchange="toggleSelectOptions(this)">
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="select">Select</option>
                        </select>
                        <div style="display: none;" class="field-select-options-wrapper">
                            <label for="field_selectoptions${fieldNum}">Field Options (for select type, one per line):</label>
                            <textarea name="field_selectoptions${fieldNum}" id="field_selectoptions${fieldNum}"></textarea>
                        </div>
                    `;
                    fieldsContainer.appendChild(newField);
                });

                toggleSelectOptions = function(selectElement) {
                    const wrapper = selectElement.nextElementSibling;
                    if (selectElement.value === 'select') {
                        wrapper.style.display = 'block';
                    } else {
                        wrapper.style.display = 'none';
                    }
                };

                // Remove success notice after 3 seconds from the HTML and the URL using the History API. 
                setTimeout(function() {
                    const successNotice = document.querySelectorAll('[data-mattcms-success-notice]');

                    if (successNotice.length > 0) {
                        successNotice.forEach(notice => notice.remove());
                        // Replace success parameter from URL with all other parameters intact. 
                        const url = new URL(window.location);
                        url.searchParams.delete('success');
                        window.history.replaceState({}, document.title, url.toString());
                    }
                }, 3000);

                // Automatically fill in field slug if blank after the user enters the field label. Do it on when the user leaves the field (onblur event).
                document.addEventListener('blur', function(event) {
                    if (event.target && event.target.name && event.target.name.startsWith('label_field')) {
                        const fieldNum = event.target.name.replace('label_field', '');
                        const slugField = document.querySelector(`input[name="slug_field${fieldNum}"]`);
                        if (slugField && slugField.value.trim() === '') {
                            const labelValue = event.target.value.trim();
                            const slugValue = labelValue.toLowerCase().replace(/[^a-z0-9-_]/g, '-');
                            slugField.value = slugValue;
                        }
                    }
                }, true);
            });
        </script>
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
    if ($path && is_dir($directory)) {
        if (file_exists($htmlFile)) {
            // Handle the case where an HTML file already exists
            $file_contents = file_get_contents($htmlFile);
            // Extract existing title and content if needed
            $title_contents = preg_match('/<head>.*<title>(.*?)<\/title>.*<\/head>/s', $file_contents, $headerMatches) ? $headerMatches[1] : '';
            $content_contents = preg_match('/<!-- mattcms:content -->(.*?)<!-- \/mattcms:content -->/s', $file_contents, $contentMatches) ? $contentMatches[1] : '';
        }
    }

    ob_start();
    ?>
    <form action="/mattcms.php?controller=page_update_post" method="post">
        <label for="page_update_post_path">Path:</label>
        <input type="text" name="page_update_post_path" id="page_update_post_path" value="<?php echo htmlspecialchars($path); ?>">

        <label for="page_update_post_title">Title:</label>
        <input type="text" name="page_update_post_title" id="page_update_post_title" value="<?php echo $title_contents; ?>">

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
        $relativePath = str_replace(ROOT_DIR . '/', '', $file->getPathname());
        if($relativePath === 'index.html') {
            array_unshift($paths, '/');
            continue;
        }
        foreach($badlist as $baditem) {
            if (stristr($file->getPathname(), $baditem)) {
                continue 2;
            }
        }
        if ($file->isDir()) {
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
        <li><a href="/mattcms.php?controller=blocks_index_get">Blocks</a></li>
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
            <a href="/mattcms.php?controller=page_update_get&path=<?php echo rawurlencode($path); ?>"><?php echo htmlspecialchars($path); ?></a> &mdash; <a style="font-size: 14px;" target="_blank" href="/<?php echo $path !== '/' ? rawurlencode($path) : ''; ?>">(Visit page)</a>
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
    <p><a href="/mattcms.php?controller=blocks_create_get">Create block</a></p>

    <!-- Show a list of all the blocks from the mattcms-blocks directory.  -->
    <?php
    $blocksDir = ROOT_DIR . '/mattcms-blocks';
    if (is_dir($blocksDir)) {
        $blockFiles = glob($blocksDir . '/*.json');
        foreach ($blockFiles as $blockFile) {
            $blockData = json_decode(file_get_contents($blockFile), true);
            ?>
                <!-- Update this to include only the file name and not the whole path.  -->
                <li><a href="/mattcms.php?controller=blocks_update_get&block_file=<?= htmlspecialchars(str_replace($blocksDir . '/', '', $blockFile)); ?>"><?php echo htmlspecialchars($blockData['name']); ?></a></li>
            <?php
        }
    }
    ?>
    <?php
    $partialsIndex = ob_get_clean();
    echo $partialsIndex;
    echo get_admin_footer();
}

function get_block_create_or_update_form($blockData = null) {
    ob_start();
    ?>
    <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
        <p class="notice" data-mattcms-success-notice>Block saved successfully.</p>
    <?php endif; ?>
    <form action="/mattcms.php?controller=blocks_create_or_update_post" method="post">
        <input type="hidden" name="block_file" value="<?php echo $blockData && isset($blockData['slug']) ? htmlspecialchars($blockData['slug']) : ''; ?>">
        <label for="block_name">Block Name:</label>
        <input type="text" name="block_name" id="block_name" required value="<?php echo $blockData ? htmlspecialchars($blockData['name']) : ''; ?>">

        <?php if ($blockData): ?>
            <div class="blocks-fields">
                <?php foreach ($blockData['fields'] as $id => $field): ?>
                    <div class="blocks-field">
                        <label for="label_field<?php echo $id; ?>">Field Label:</label>
                        <input type="text" name="label_field<?php echo $id; ?>" id="label_field<?php echo $id; ?>" value="<?php echo htmlspecialchars($field['label']); ?>">

                        <label for="slug_field<?php echo $id; ?>">Field Slug:</label>
                        <input type="text" name="slug_field<?php echo $id; ?>" id="slug_field<?php echo $id; ?>" value="<?php echo htmlspecialchars(isset($field['slug']) ? $field['slug'] : ''); ?>">

                        <label for="field_type<?php echo $id; ?>">Field Type:</label>
                        <select name="field_type<?php echo $id; ?>" id="field_type<?php echo $id; ?>" onchange="toggleSelectOptions(this)">
                            <option value="text" <?= $field['type'] === 'text' ? 'selected' : ''; ?>>Text</option>
                            <option value="textarea" <?= $field['type'] === 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                            <option value="select" <?= $field['type'] === 'select' ? 'selected' : ''; ?>>Select</option>
                        </select>

                        <div style="<?= $field['type'] === 'select' ? '' : 'display: none;' ?>" class="field-select-options-wrapper">
                            <label for="field_selectoptions<?php echo $id; ?>">Field Options (for select type, one per line):</label>
                            <textarea name="field_selectoptions<?php echo $id; ?>" id="field_selectoptions<?php echo $id; ?>"><?php echo implode("\n", $field['options']); ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!$blockData): ?>
            <div class="blocks-fields">
                <div class="blocks-field">
                    <label for="label_field1">Field Label:</label>
                    <input type="text" name="label_field1" id="label_field1">

                    <label for="slug_field1">Field Slug:</label>
                    <input type="text" name="slug_field1" id="slug_field1">

                    <label for="field_type1">Field Type:</label>
                    <select name="field_type1" id="field_type1" onchange="toggleSelectOptions(this)">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select</option>
                    </select>

                    <div style="display: none;" class="field-select-options-wrapper">
                        <label for="field_selectoptions1">Field Options (for select type, one per line):</label>
                        <textarea name="field_selectoptions1" id="field_selectoptions1"></textarea>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="block-html-template">
            <label for="block_html_template">HTML Template:</label>
            <textarea name="block_html_template" id="block_html_template"><?php echo $blockData ? htmlspecialchars($blockData['html_template']) : ''; ?></textarea>
        </div>

        <button type="button" data-blocks-add-field>Add Field</button>

        <button type="submit" name="blocks_create_or_update_post_submit"><?= $blockData ? 'Update Block' : 'Create Block' ?></button>
    </form>
    <?php
    $form = ob_get_clean();
    return $form;
}

function controller_blocks_create_get() {
    echo get_admin_header();
    ob_start();
    ?>
    <h2>Create Block</h2>
    <?= get_block_create_or_update_form(); ?>
    <?php
    $form = ob_get_clean();
    echo $form;
    echo get_admin_footer();
}

function controller_blocks_update_get() {
    echo get_admin_header();
    ob_start();
    ?>
    <h2>Update Block</h2>
    <p>Block update form will go here.</p>
    <?php
    // Get the existing block data to pass to the function.
    $blockFile = ROOT_DIR . '/mattcms-blocks/' . $_GET['block_file'] ?? '';
    $blockData = json_decode(file_get_contents($blockFile), true);
    ?>
    <?= get_block_create_or_update_form($blockData); ?>
    <?php
    $form = ob_get_clean();
    echo $form;
    echo get_admin_footer();
}

function string_to_slug($string) {
    $slug = preg_replace('/[^a-z0-9-_]/i', '-', strtolower($string));
    return $slug;
}

function controller_blocks_create_or_update_post() {
    // Handle block creation form submission.
    // Save the data for the block, with all the data for its fields, in a file in a directory called mattcms-blocks.
    $blockName = $_POST['block_name'] ?? 'unnamed-block';
    $blockSlug = string_to_slug($blockName);
    $fields = [];
    foreach ($_POST as $key => $value) {
        if (str_starts_with($key, 'label_field')) {
            $fieldNum = str_replace('label_field', '', $key);
            $fieldSlugKey = 'slug_field' . $fieldNum;
            $fieldSlug = isset($_POST[$fieldSlugKey]) && $_POST[$fieldSlugKey] !== '' ? $_POST[$fieldSlugKey] : string_to_slug($value);
            $fieldTypeKey = 'field_type' . $fieldNum;
            $fieldType = $_POST[$fieldTypeKey] ?? 'text';
            $fieldOptions = [];
            if ($fieldType === 'select') {
                $fieldSelectOptionsKey = 'field_selectoptions' . $fieldNum;
                $optionsRaw = $_POST[$fieldSelectOptionsKey] ?? '';
                $fieldOptions = array_map('trim', explode("\n", $optionsRaw));
            }
            $fields[] = [
                'label' => $value,
                'slug' => $fieldSlug,
                'type' => $fieldType,
                'options' => $fieldOptions,
            ];
        }
    }
    $blockHtmlTemplate = $_POST['block_html_template'] ?? '';
    $blockData = [
        'name' => $blockName,
        'slug' => $blockSlug,
        'fields' => $fields,
        'html_template' => $blockHtmlTemplate,
    ];
    $blocksDir = ROOT_DIR . '/mattcms-blocks';
    if (!is_dir($blocksDir) && !mkdir($blocksDir, 0755, true) && !is_dir($blocksDir)) {
        throw new RuntimeException("Unable to create directory: $blocksDir");
    }
    if (isset($_POST['block_file']) && !empty($_POST['block_file'])) {
        // Delete the old block file if the slug has changed.
        $oldBlockFile = $blocksDir . '/' . $_POST['block_file'] . '.json';
        if ($oldBlockFile !== $blocksDir . '/' . $blockSlug . '.json' && file_exists($oldBlockFile)) {
            unlink($oldBlockFile);
        }
    }
    
    // Create new block file.
    $blockFile = $blocksDir . '/' . $blockSlug . '.json';

    file_put_contents($blockFile, json_encode($blockData, JSON_PRETTY_PRINT));
    header("Location: /mattcms.php?controller=blocks_update_get&block_file=".rawurlencode($blockSlug . '.json')."&success=1");
    die();
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
        <p class="notice" data-mattcms-success-notice>Page updated successfully.</p>
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
    } else if ($controller === 'blocks_index_get') {
        controller_blocks_index_get();
    } else if($controller === 'blocks_create_get') {
        controller_blocks_create_get();
    } else if($controller === 'blocks_update_get') {
        controller_blocks_update_get();
    } else if($controller === 'blocks_create_or_update_post') {
        controller_blocks_create_or_update_post();
    } else {
        controller_homepage();
    }
}

init();