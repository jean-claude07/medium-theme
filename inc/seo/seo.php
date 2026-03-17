<?php
/**
 * SEO Optimization Module
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', 'mc_add_comprehensive_seo_meta_tags', 1);

function mc_add_comprehensive_seo_meta_tags()
{
    global $post;

    $site_name = get_bloginfo('name');
    $site_desc = get_bloginfo('description');

    $title = wp_get_document_title();
    $desc = $site_desc;
    $url = home_url(add_query_arg([], $GLOBALS['wp']->request));
    $image = '';
    $type = 'website';
    $robots = 'index, follow, max-image-preview:large';

    if (is_front_page() || is_home()) {

        $title = $site_name;
        $desc = $site_desc;

    } elseif (is_single() || is_page()) {

        $title = get_the_title();
        $url = get_permalink();
        $type = 'article';

        if (has_excerpt()) {
            $desc = get_the_excerpt();
        } else {
            $desc = wp_trim_words(strip_tags($post->post_content), 30);
        }

        if (has_post_thumbnail()) {
            $image = get_the_post_thumbnail_url($post->ID, 'large');
        }

    } elseif (is_author()) {

        $author = get_queried_object();

        $title = $author->display_name . ' - ' . $site_name;
        $desc = get_the_author_meta('description', $author->ID);
        $type = 'profile';

    } elseif (is_search()) {

        $title = sprintf(__('Search results for "%s"', 'medium-clone'), get_search_query());
        $robots = 'noindex, follow';

    } elseif (is_category() || is_tag()) {

        $term = get_queried_object();

        $title = single_term_title('', false) . ' - ' . $site_name;
        $desc = term_description();

    } elseif (is_404()) {

        $title = __('Page Not Found', 'medium-clone');
        $robots = 'noindex, nofollow';

    }

    // fallback image
    if (!$image) {
        $logo_id = get_theme_mod('custom_logo');

        if ($logo_id) {
            $image = wp_get_attachment_image_url($logo_id, 'full');
        }
    }

    echo "\n<!-- SEO Meta -->\n";

    if ($desc) {
        echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($desc)) . '" />' . "\n";
    }

    echo '<meta name="robots" content="' . esc_attr($robots) . '" />' . "\n";

    echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";

    // Open Graph
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";

    if ($desc) {
        echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags($desc)) . '" />' . "\n";
    }

    echo '<meta property="og:type" content="' . esc_attr($type) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";

    if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '" />' . "\n";
    }

    // Twitter
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";

    if ($desc) {
        echo '<meta name="twitter:description" content="' . esc_attr(wp_strip_all_tags($desc)) . '" />' . "\n";
    }

    if ($image) {
        echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
    }

    echo "<!-- /SEO Meta -->\n";
}

add_action('wp_head', 'mc_add_structured_data', 10);

function mc_add_structured_data()
{

    global $post;

    $site_name = get_bloginfo('name');
    $site_url = home_url('/');

    $logo_id = get_theme_mod('custom_logo');
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';

    $payload = null;

    if (is_front_page() || is_home()) {

        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $site_name,
            'url' => $site_url,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $site_url . '?s={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ]
        ];

    } elseif (is_single()) {

        $author_id = $post->post_author;
        $author = [
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $author_id),
            'url' => get_author_posts_url($author_id)
        ];

        $content = strip_tags($post->post_content);
        $wordcount = str_word_count($content);

        $reading_time = ceil($wordcount / 200);

        $image = get_the_post_thumbnail_url($post->ID, 'full');

        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'image' => $image ? [$image] : [],
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'wordCount' => $wordcount,
            'timeRequired' => 'PT' . $reading_time . 'M',
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author(),
                'url' => get_author_posts_url($author_id)
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $site_name,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logo_url
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => get_permalink()
            ]
        ];

    } elseif (is_author()) {

        $author = get_queried_object();

        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $author->display_name,
                'description' => get_the_author_meta('description', $author->ID),
                'image' => get_avatar_url($author->ID),
                'url' => get_author_posts_url($author->ID)
            ]
        ];

    } elseif (is_category() || is_tag()) {

        $term = get_queried_object();

        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => $site_url
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => single_term_title('', false),
                    'item' => get_term_link($term)
                ]
            ]
        ];
    }

    if ($payload) {

        echo "\n<script type=\"application/ld+json\">\n";
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n</script>\n";
    }
}