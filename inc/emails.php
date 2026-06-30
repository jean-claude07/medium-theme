<?php
/**
 * Email Helper Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Send an HTML email using a template
 */
function mc_send_template_email($to, $subject, $template_name, $args = [])
{
    // Define headers for HTML email
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    // Start output buffering to capture template content
    ob_start();

    // 1. Header
    $header_path = locate_template('templates/emails/email-header.php');
    if ($header_path) {
        include $header_path;
    }

    // 2. Content
    $custom_body = get_option('mc_email_' . $template_name . '_body');
    
    if (!empty($custom_body)) {
        // Replace placeholders from $args
        foreach ($args as $key => $value) {
            $custom_body = str_replace('{' . $key . '}', esc_html($value), $custom_body);
        }
        // Special raw replacements (for links)
        if (isset($args['activation_link'])) {
            $custom_body = str_replace('{raw_activation_link}', esc_url($args['activation_link']), $custom_body);
        }
        
        echo wp_kses_post($custom_body);
    } else {
        // Fallback to file template
        $template_path = locate_template('templates/emails/' . $template_name . '.php');
        if ($template_path) {
            include $template_path;
        } else {
            echo "<p>Contenu non trouvé pour le template: " . esc_html($template_name) . "</p>";
        }
    }

    // 3. Footer
    $footer_path = locate_template('templates/emails/email-footer.php');
    if ($footer_path) {
        include $footer_path;
    }

    $message = ob_get_clean();

    // Send the email
    return wp_mail($to, $subject, $message, $headers);
}
