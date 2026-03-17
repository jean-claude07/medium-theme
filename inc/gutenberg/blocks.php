<?php
/**
 * Gutenberg Custom Blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('init', 'mc_register_custom_blocks');

function mc_register_custom_blocks() {
    // Check if Gutenberg is active
    if (!function_exists('register_block_type')) {
        return;
    }

    // Register Callout Block
    register_block_type('mediumclone/callout', array(
        'api_version' => 2,
        'title' => 'Callout',
        'icon' => 'warning',
        'category' => 'design',
        'render_callback' => 'mc_render_callout_block',
    ));
    
    // Additional blocks like Highlight, Tweet Embed, Code Snippet can be registered here in a similar way.
}

function mc_render_callout_block($attributes, $content) {
    return sprintf(
        '<div class="p-6 my-8 bg-primary/10 border-l-4 border-primary rounded-r-xl dark:bg-primary/20 text-gray-800 dark:text-gray-200">%s</div>',
        $content ?: 'Callout text here.'
    );
}
