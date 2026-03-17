<!DOCTYPE html>
<html <?php language_attributes(); ?> class="antialiased">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Georgia:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
    <script>
        // Init dark mode immediately to avoid flash
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body <?php body_class('min-h-screen flex flex-col pt-0'); ?>>
<?php wp_body_open(); ?>

<div class="reading-progress-bar"></div>

<?php get_template_part('templates/components/navbar'); ?>

<main class="flex-grow">
