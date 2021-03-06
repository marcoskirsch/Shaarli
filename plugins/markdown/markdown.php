<?php

/**
 * Plugin Markdown.
 *
 * Shaare's descriptions are parsed with Markdown.
 */

require_once 'Parsedown.php';

/**
 * Parse linklist descriptions.
 *
 * @param array $data linklist data.
 *
 * @return mixed linklist data parsed in markdown (and converted to HTML).
 */
function hook_markdown_render_linklist($data)
{
    foreach ($data['links'] as &$value) {
        $value['description'] = process_markdown($value['description']);
    }

    return $data;
}

/**
 * Parse daily descriptions.
 *
 * @param array $data daily data.
 *
 * @return mixed daily data parsed in markdown (and converted to HTML).
 */
function hook_markdown_render_daily($data)
{
    // Manipulate columns data
    foreach ($data['cols'] as &$value) {
        foreach ($value as &$value2) {
            $value2['formatedDescription'] = process_markdown($value2['formatedDescription']);
        }
    }

    return $data;
}

/**
 * When link list is displayed, include markdown CSS.
 *
 * @param array $data includes data.
 *
 * @return mixed - includes data with markdown CSS file added.
 */
function hook_markdown_render_includes($data)
{
    if ($data['_PAGE_'] == Router::$PAGE_LINKLIST
        || $data['_PAGE_'] == Router::$PAGE_DAILY
        || $data['_PAGE_'] == Router::$PAGE_EDITLINK
    ) {
        
        $data['css_files'][] = PluginManager::$PLUGINS_PATH . '/markdown/markdown.css';
    }

    return $data;
}

/**
 * Hook render_editlink.
 * Adds an help link to markdown syntax.
 *
 * @param array $data data passed to plugin
 *
 * @return array altered $data.
 */
function hook_markdown_render_editlink($data)
{
    // Load help HTML into a string
    $data['edit_link_plugin'][] = file_get_contents(PluginManager::$PLUGINS_PATH .'/markdown/help.html');
    return $data;
}


/**
 * Remove HTML links auto generated by Shaarli core system.
 * Keeps HREF attributes.
 *
 * @param string $description input description text.
 *
 * @return string $description without HTML links.
 */
function reverse_text2clickable($description)
{
    return preg_replace('!<a +href="([^ ]*)">[^ ]+</a>!m', '$1', $description);
}

/**
 * Remove <br> tag to let markdown handle it.
 *
 * @param string $description input description text.
 *
 * @return string $description without <br> tags.
 */
function reverse_nl2br($description)
{
    return preg_replace('!<br */?>!im', '', $description);
}

/**
 * Remove HTML spaces '&nbsp;' auto generated by Shaarli core system.
 *
 * @param string $description input description text.
 *
 * @return string $description without HTML links.
 */
function reverse_space2nbsp($description)
{
    return preg_replace('/(^| )&nbsp;/m', '$1 ', $description);
}

/**
 * Remove dangerous HTML tags (tags, iframe, etc.).
 * Doesn't affect <code> content (already escaped by Parsedown).
 *
 * @param string $description input description text.
 *
 * @return string given string escaped.
 */
function sanitize_html($description)
{
    $escapeTags = array(
        'script',
        'style',
        'link',
        'iframe',
        'frameset',
        'frame',
    );
    foreach ($escapeTags as $tag) {
        $description = preg_replace_callback(
            '#<\s*'. $tag .'[^>]*>(.*</\s*'. $tag .'[^>]*>)?#is',
            function ($match) { return escape($match[0]); },
            $description);
    }
    $description = preg_replace(
        '#(<[^>]+)on[a-z]*="[^"]*"#is',
        '$1',
        $description);
    return $description;
}

/**
 * Render shaare contents through Markdown parser.
 *   1. Remove HTML generated by Shaarli core.
 *   2. Reverse the escape function.
 *   3. Generate markdown descriptions.
 *   4. Sanitize sensible HTML tags for security.
 *   5. Wrap description in 'markdown' CSS class.
 *
 * @param string $description input description text.
 *
 * @return string HTML processed $description.
 */
function process_markdown($description)
{
    $parsedown = new Parsedown();

    $processedDescription = $description;
    $processedDescription = reverse_text2clickable($processedDescription);
    $processedDescription = reverse_nl2br($processedDescription);
    $processedDescription = reverse_space2nbsp($processedDescription);
    $processedDescription = unescape($processedDescription);
    $processedDescription = $parsedown
        ->setMarkupEscaped(false)
        ->setBreaksEnabled(true)
        ->text($processedDescription);
    $processedDescription = sanitize_html($processedDescription);

    if(!empty($processedDescription)){
        $processedDescription = '<div class="markdown">'. $processedDescription . '</div>';
    }

    return $processedDescription;
}
