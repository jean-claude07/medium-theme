<?php
/**
 * Database Setup Module
 * Creates custom tables on theme activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function medium_clone_create_custom_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    // 1. Reactions Table
    $table_reactions = $wpdb->prefix . 'mc_reactions';
    $sql_reactions = "CREATE TABLE $table_reactions (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        post_id bigint(20) DEFAULT 0,
        comment_id bigint(20) DEFAULT 0,
        reaction_type varchar(50) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY u_user_post_reaction (user_id, post_id, comment_id, reaction_type)
    ) $charset_collate;";
    dbDelta( $sql_reactions );

    // 2. Follows Table
    $table_follows = $wpdb->prefix . 'mc_follows';
    $sql_follows = "CREATE TABLE $table_follows (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        follower_id bigint(20) NOT NULL,
        following_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY u_follower_following (follower_id, following_id)
    ) $charset_collate;";
    dbDelta( $sql_follows );

    // 3. Bookmarks Table
    $table_bookmarks = $wpdb->prefix . 'mc_bookmarks';
    $sql_bookmarks = "CREATE TABLE $table_bookmarks (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        post_id bigint(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY u_user_post (user_id, post_id)
    ) $charset_collate;";
    dbDelta( $sql_bookmarks );

    // 4. Points Log Table
    $table_points = $wpdb->prefix . 'mc_points_log';
    $sql_points = "CREATE TABLE $table_points (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        action varchar(100) NOT NULL,
        points int(11) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_points );

    // 5. Badges Table
    $table_badges = $wpdb->prefix . 'mc_badges';
    $sql_badges = "CREATE TABLE $table_badges (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        badge_key varchar(100) NOT NULL,
        awarded_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY u_user_badge (user_id, badge_key)
    ) $charset_collate;";
    dbDelta( $sql_badges );

    // 6. Notifications Table
    $table_notifications = $wpdb->prefix . 'mc_notifications';
    $sql_notifications = "CREATE TABLE $table_notifications (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        actor_id bigint(20) NOT NULL,
        type varchar(100) NOT NULL,
        reference_id bigint(20) DEFAULT 0,
        is_read tinyint(1) DEFAULT 0 NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_notifications );
}

add_action('after_switch_theme', 'medium_clone_create_custom_tables');
