<?php
/**
 * Email & SMTP Settings Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Hook into phpmailer_init to apply SMTP settings globally
add_action('phpmailer_init', 'mc_setup_smtp');
function mc_setup_smtp($phpmailer) {
    $use_smtp = get_option('mc_smtp_enabled', '0');
    
    if ($use_smtp === '1') {
        $phpmailer->isSMTP();
        $phpmailer->Host       = get_option('mc_smtp_host', '');
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = get_option('mc_smtp_port', '587');
        $phpmailer->Username   = get_option('mc_smtp_user', '');
        $phpmailer->Password   = get_option('mc_smtp_pass', '');
        $phpmailer->SMTPSecure = get_option('mc_smtp_enc', 'tls');
        
        $from_email = get_option('mc_smtp_from_email', get_option('admin_email'));
        $from_name  = get_option('mc_smtp_from_name', get_bloginfo('name'));
        
        $phpmailer->setFrom($from_email, $from_name);
    }
}

// 2. Register Submenu under Moderation
add_action('admin_menu', 'mc_register_email_settings_page');

function mc_register_email_settings_page() {
    add_submenu_page(
        'mc-moderation',           // Parent slug (from admin-dashboard.php)
        'Email & SMTP Settings',   // Page title
        'Email Settings',          // Menu title
        'manage_options',          // Capability
        'mc-email-settings',       // Menu slug
        'mc_render_email_settings' // Callback
    );
}

// 3. Render Dashboard
function mc_render_email_settings() {
    if (!current_user_can('manage_options')) return;

    // Handle form submission
    if (isset($_POST['mc_save_email_settings']) && check_admin_referer('mc_email_settings_nonce')) {
        // SMTP Settings
        update_option('mc_smtp_enabled', isset($_POST['mc_smtp_enabled']) ? '1' : '0');
        update_option('mc_smtp_host', sanitize_text_field($_POST['mc_smtp_host']));
        update_option('mc_smtp_port', sanitize_text_field($_POST['mc_smtp_port']));
        update_option('mc_smtp_user', sanitize_text_field($_POST['mc_smtp_user']));
        update_option('mc_smtp_pass', sanitize_text_field($_POST['mc_smtp_pass']));
        update_option('mc_smtp_enc', sanitize_text_field($_POST['mc_smtp_enc']));
        update_option('mc_smtp_from_email', sanitize_email($_POST['mc_smtp_from_email']));
        update_option('mc_smtp_from_name', sanitize_text_field($_POST['mc_smtp_from_name']));

        // Template Settings (Activation)
        update_option('mc_email_activation_subject', sanitize_text_field($_POST['mc_email_activation_subject']));
        update_option('mc_email_activation_body', wp_kses_post($_POST['mc_email_activation_body']));

        echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
    }

    // Default template fallback if empty
    $default_activation_body = "<h2>Bienvenue, {name} !</h2>\n<p>Nous sommes ravis de vous compter parmi nous. Pour commencer à écrire et interagir avec la communauté, merci d'activer votre compte en cliquant sur le lien ci-dessous :</p>\n<p><a href=\"{activation_link}\">Activer mon compte</a></p>\n<p>À bientôt sur MediumClone !</p>";
    $current_activation_body = get_option('mc_email_activation_body', $default_activation_body);
    if (empty($current_activation_body)) $current_activation_body = $default_activation_body;

    ?>
    <style>
        .mc-email-page { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .mc-email-page .mc-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06); border: 1px solid #e5e7eb; overflow: hidden; }
        .mc-email-page .mc-card-header { padding: 20px 24px; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; gap: 12px; background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%); }
        .mc-email-page .mc-card-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .mc-email-page .mc-card-body { padding: 24px; }
        .mc-email-page .mc-label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin-bottom: 6px; }
        .mc-email-page .mc-input { width: 100%; padding: 9px 13px; font-size: 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; background: #fff; color: #111827; transition: border-color .15s, box-shadow .15s; box-sizing: border-box; }
        .mc-email-page .mc-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        .mc-email-page .mc-select { width: 100%; padding: 9px 13px; font-size: 14px; border: 1.5px solid #e5e7eb; border-radius: 8px; background: #fff; color: #111827; transition: border-color .15s; box-sizing: border-box; }
        .mc-email-page .mc-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.12); }
        .mc-email-page .mc-field-group { margin-bottom: 18px; }
        .mc-email-page .mc-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .mc-email-page .mc-divider { border: none; border-top: 1px solid #f3f4f6; margin: 20px 0; }
        .mc-email-page .mc-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; letter-spacing: .04em; }
        .mc-email-page .mc-badge-green { background: #dcfce7; color: #166534; }
        .mc-email-page .mc-badge-gray { background: #f3f4f6; color: #6b7280; }
        .mc-email-page .mc-badge-purple { background: #ede9fe; color: #5b21b6; }
        .mc-email-page .mc-toggle-wrap { display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f9fafb; border: 1.5px solid #e5e7eb; border-radius: 10px; cursor: pointer; }
        .mc-email-page .mc-toggle { position: relative; width: 42px; height: 24px; flex-shrink: 0; }
        .mc-email-page .mc-toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
        .mc-email-page .mc-slider { position: absolute; inset: 0; background: #d1d5db; border-radius: 24px; cursor: pointer; transition: background .2s; }
        .mc-email-page .mc-slider:before { content: ''; position: absolute; height: 18px; width: 18px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
        .mc-email-page .mc-toggle input:checked + .mc-slider { background: #6366f1; }
        .mc-email-page .mc-toggle input:checked + .mc-slider:before { transform: translateX(18px); }
        .mc-email-page .mc-placeholder-pill { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; background: #fdf4ff; border: 1px dashed #d946ef; border-radius: 6px; font-size: 12px; font-family: monospace; color: #a21caf; font-weight: 600; margin-right: 6px; }
        .mc-email-page .mc-sticky-bar { position: sticky; bottom: 0; z-index: 100; background: rgba(255,255,255,.95); backdrop-filter: blur(8px); border-top: 1px solid #e5e7eb; margin: 0 -20px; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 -4px 16px rgba(0,0,0,.06); }
        .mc-email-page .mc-btn-save { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; font-weight: 700; font-size: 14px; padding: 10px 28px; border: none; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(99,102,241,.35); transition: transform .15s, box-shadow .15s; }
        .mc-email-page .mc-btn-save:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99,102,241,.45); }
        .mc-email-page .mc-section-title { font-size: 15px; font-weight: 700; color: #111827; margin: 0; line-height: 1.3; }
        .mc-email-page .mc-section-desc { font-size: 13px; color: #9ca3af; margin: 2px 0 0; }
        .mc-email-page h1.mc-page-title { font-size: 26px; font-weight: 800; color: #111827; margin: 0 0 6px; letter-spacing: -.02em; display: flex; align-items: center; gap: 12px; }
        .mc-email-page .mc-page-subtitle { font-size: 14px; color: #6b7280; margin: 0 0 28px; }
        .mc-email-page .mc-icon-wrap { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
        @media (max-width: 900px) { .mc-email-page .mc-grid-2 { grid-template-columns: 1fr; } .mc-email-page .mc-main-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="wrap mc-email-page" style="max-width:1200px;">

        <!-- Page Header -->
        <div style="display:flex;align-items:flex-start;gap:16px;margin-bottom:28px;">
            <div class="mc-icon-wrap" style="background:linear-gradient(135deg,#6366f1,#4f46e5);box-shadow:0 8px 20px rgba(99,102,241,.3);">
                <span class="dashicons dashicons-email-alt" style="color:#fff;font-size:22px;width:22px;height:22px;margin-top:4px;"></span>
            </div>
            <div>
                <h1 class="mc-page-title">Email & SMTP Settings</h1>
                <p class="mc-page-subtitle">Configure your outgoing mail server and customize email templates sent to your community.</p>
            </div>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('mc_email_settings_nonce'); ?>
            <input type="hidden" name="mc_save_email_settings" value="1">

            <div class="mc-main-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">

                <!-- ─── SMTP Configuration ─── -->
                <div class="mc-card">
                    <div class="mc-card-header">
                        <div class="mc-card-icon" style="background:linear-gradient(135deg,#e0e7ff,#c7d2fe);">
                            <span class="dashicons dashicons-lock" style="color:#4f46e5;font-size:18px;width:18px;height:18px;margin-top:3px;"></span>
                        </div>
                        <div style="flex:1;">
                            <p class="mc-section-title">SMTP Server</p>
                            <p class="mc-section-desc">Outgoing mail server configuration</p>
                        </div>
                        <?php $smtp_on = get_option('mc_smtp_enabled', '0') === '1'; ?>
                        <span class="mc-badge <?php echo $smtp_on ? 'mc-badge-green' : 'mc-badge-gray'; ?>">
                            <span style="width:6px;height:6px;border-radius:50%;background:<?php echo $smtp_on ? '#16a34a' : '#9ca3af'; ?>;"></span>
                            <?php echo $smtp_on ? 'Active' : 'Disabled'; ?>
                        </span>
                    </div>
                    <div class="mc-card-body">

                        <!-- Toggle -->
                        <div class="mc-field-group">
                            <label class="mc-toggle-wrap">
                                <div class="mc-toggle">
                                    <input type="checkbox" name="mc_smtp_enabled" value="1" <?php checked(get_option('mc_smtp_enabled', '0'), '1'); ?>>
                                    <span class="mc-slider"></span>
                                </div>
                                <div>
                                    <span style="font-size:14px;font-weight:600;color:#111827;">Enable Custom SMTP</span>
                                    <p style="font-size:12px;color:#9ca3af;margin:2px 0 0;">Override WordPress default PHP mail()</p>
                                </div>
                            </label>
                        </div>

                        <!-- Host & Port -->
                        <div class="mc-grid-2 mc-field-group">
                            <div>
                                <label class="mc-label">SMTP Host</label>
                                <input type="text" name="mc_smtp_host" class="mc-input"
                                    value="<?php echo esc_attr(get_option('mc_smtp_host')); ?>"
                                    placeholder="smtp.gmail.com">
                            </div>
                            <div>
                                <label class="mc-label">Port</label>
                                <input type="number" name="mc_smtp_port" class="mc-input"
                                    value="<?php echo esc_attr(get_option('mc_smtp_port', '587')); ?>"
                                    placeholder="587">
                            </div>
                        </div>

                        <!-- Username & Password -->
                        <div class="mc-grid-2 mc-field-group">
                            <div>
                                <label class="mc-label">Username / Email</label>
                                <input type="text" name="mc_smtp_user" class="mc-input"
                                    value="<?php echo esc_attr(get_option('mc_smtp_user')); ?>"
                                    placeholder="you@example.com">
                            </div>
                            <div>
                                <label class="mc-label">Password</label>
                                <div style="position:relative;">
                                    <input type="password" name="mc_smtp_pass" id="mc_smtp_pass_field" class="mc-input" style="padding-right:40px;"
                                        value="<?php echo esc_attr(get_option('mc_smtp_pass')); ?>">
                                    <button type="button" onclick="var f=document.getElementById('mc_smtp_pass_field');f.type=f.type==='password'?'text':'password';"
                                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:0;">
                                        <span class="dashicons dashicons-visibility" style="font-size:16px;width:16px;height:16px;line-height:1.5;"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Encryption -->
                        <div class="mc-field-group">
                            <label class="mc-label">Encryption / Security</label>
                            <div class="mc-grid-2">
                                <?php
                                $current_enc = get_option('mc_smtp_enc', 'tls');
                                $enc_options = [
                                    'tls'  => ['label' => 'TLS', 'desc' => 'Port 587 (Recommended)', 'color' => '#16a34a'],
                                    'ssl'  => ['label' => 'SSL', 'desc' => 'Port 465',                'color' => '#d97706'],
                                    ''     => ['label' => 'None', 'desc' => 'Port 25 (Insecure)',     'color' => '#dc2626'],
                                ];
                                ?>
                                <select name="mc_smtp_enc" class="mc-select">
                                    <?php foreach ($enc_options as $val => $opt): ?>
                                        <option value="<?php echo esc_attr($val); ?>" <?php selected($current_enc, $val); ?>>
                                            <?php echo esc_html($opt['label']); ?> — <?php echo esc_html($opt['desc']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div style="display:flex;align-items:center;gap:8px;padding:9px 13px;background:#f9fafb;border:1.5px solid #e5e7eb;border-radius:8px;">
                                    <span style="width:8px;height:8px;border-radius:50%;background:<?php echo esc_attr($enc_options[$current_enc]['color']); ?>;"></span>
                                    <span style="font-size:13px;color:#374151;font-weight:500;"><?php echo esc_html($enc_options[$current_enc]['label']); ?></span>
                                </div>
                            </div>
                        </div>

                        <hr class="mc-divider">

                        <!-- Sender Identity -->
                        <div style="margin-bottom:12px;">
                            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#9ca3af;margin:0 0 14px;">Sender Identity</p>
                            <div class="mc-grid-2">
                                <div>
                                    <label class="mc-label">From Email</label>
                                    <input type="email" name="mc_smtp_from_email" class="mc-input"
                                        value="<?php echo esc_attr(get_option('mc_smtp_from_email', get_option('admin_email'))); ?>">
                                </div>
                                <div>
                                    <label class="mc-label">From Name</label>
                                    <input type="text" name="mc_smtp_from_name" class="mc-input"
                                        value="<?php echo esc_attr(get_option('mc_smtp_from_name', get_bloginfo('name'))); ?>">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ─── Email Template ─── -->
                <div class="mc-card">
                    <div class="mc-card-header">
                        <div class="mc-card-icon" style="background:linear-gradient(135deg,#fde8ff,#f5d0fe);">
                            <span class="dashicons dashicons-welcome-write-blog" style="color:#a21caf;font-size:18px;width:18px;height:18px;margin-top:3px;"></span>
                        </div>
                        <div style="flex:1;">
                            <p class="mc-section-title">Account Activation Email</p>
                            <p class="mc-section-desc">Sent when a new user registers</p>
                        </div>
                        <span class="mc-badge mc-badge-purple">Template</span>
                    </div>
                    <div class="mc-card-body">

                        <!-- Placeholders Info -->
                        <div style="padding:12px 14px;background:#fdf4ff;border:1px dashed #d946ef;border-radius:10px;margin-bottom:20px;">
                            <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#a21caf;margin:0 0 8px;">Available Placeholders</p>
                            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                <span class="mc-placeholder-pill">{name}</span>
                                <span class="mc-placeholder-pill">{activation_link}</span>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="mc-field-group">
                            <label class="mc-label">Subject Line</label>
                            <div style="position:relative;">
                                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;" class="dashicons dashicons-email"></span>
                                <input type="text" name="mc_email_activation_subject" class="mc-input" style="padding-left:38px;"
                                    value="<?php echo esc_attr(get_option('mc_email_activation_subject', 'Confirmez votre inscription sur MediumClone')); ?>">
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="mc-field-group">
                            <label class="mc-label">Email Body <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af;">(HTML supported)</span></label>
                            <?php
                            wp_editor($current_activation_body, 'mc_email_activation_body', [
                                'media_buttons' => false,
                                'textarea_rows' => 14,
                                'teeny'         => false,
                                'quicktags'     => true,
                            ]);
                            ?>
                        </div>

                    </div>
                </div>

            </div><!-- /.mc-main-grid -->

            <!-- ─── Sticky Save Bar ─── -->
            <div class="mc-sticky-bar">
                <p style="font-size:13px;color:#6b7280;margin:0;">
                    <span class="dashicons dashicons-info" style="font-size:15px;width:15px;height:15px;vertical-align:middle;margin-right:4px;color:#9ca3af;"></span>
                    Changes apply to all outgoing emails immediately.
                </p>
                <button type="submit" class="mc-btn-save">
                    <span class="dashicons dashicons-saved" style="font-size:16px;width:16px;height:16px;margin-top:3px;"></span>
                    Save Settings
                </button>
            </div>

        </form>
    </div>
    <?php
}
