<?php
/**
 * Plugin Name: WP Tag Linker
 * Description: Automatically links words in post content to existing tags (max 5 per post) with customizable styles.
 * Version: 1.3
 * Author: sinchro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Function to link tags in content
function wp_tag_linker($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $tags = get_the_tags();
        // Get allowed tags from settings, split by comma, and trim spaces
        $allowed_tags = array_map('trim', explode(',', get_option('wp_tag_linker_allowed_tags', '')));
        
        if ($tags) {
            $linked_count = 0; // Track the number of linked tags
            foreach ($tags as $tag) {
                if ($linked_count >= 5) {
                    break; // Stop after 5 links
                }
                
                // Skip tags that are not in the allowed list (if specified)
                if (!empty($allowed_tags) && !in_array($tag->name, $allowed_tags)) {
                    continue;
                }
                
                $tag_name = preg_quote($tag->name, '/'); // Escape tag name for regex
                $tag_link = esc_url(get_tag_link($tag->term_id)); // Get tag URL
                
                // Regex pattern to find the tag in the content, ensuring it is not inside an existing link
                $pattern = '/\b(' . $tag_name . ')\b(?![^<]*>|[^<>]*<\/a>)/iu';
                $replacement = '<a href="' . $tag_link . '" class="wp-tag-link">$1</a>';
                
                // Replace only the first occurrence of the tag in the content
                $new_content = preg_replace($pattern, $replacement, $content, 1);
                if ($new_content !== null && $new_content !== $content) {
                    $linked_count++;
                    $content = $new_content;
                }
            }
        }
    }
    return $content;
}
add_filter('the_content', 'wp_tag_linker');

// Add settings menu in WordPress admin panel
function wp_tag_linker_menu() {
    add_options_page('WP Tag Linker Settings', 'WP Tag Linker', 'manage_options', 'wp-tag-linker', 'wp_tag_linker_settings_page');
}
add_action('admin_menu', 'wp_tag_linker_menu');

// Register plugin settings in WordPress database
function wp_tag_linker_register_settings() {
    register_setting('wp_tag_linker_settings_group', 'wp_tag_linker_color');
    register_setting('wp_tag_linker_settings_group', 'wp_tag_linker_underline');
    register_setting('wp_tag_linker_settings_group', 'wp_tag_linker_allowed_tags');
}
add_action('admin_init', 'wp_tag_linker_register_settings');

// Render the settings page in the admin panel
function wp_tag_linker_settings_page() {
    $all_tags = get_tags(array('fields' => 'names')); // Get all tag names from the site
    $all_tags_list = implode(', ', $all_tags); // Convert to a comma-separated list with spaces
    ?>
    <div class="wrap">
        <h1>WP Tag Linker Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wp_tag_linker_settings_group'); ?>
            <?php do_settings_sections('wp_tag_linker_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Tag Link Color</th>
                    <td><input type="text" name="wp_tag_linker_color" value="<?php echo esc_attr(get_option('wp_tag_linker_color', '#0073aa')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Underline Links</th>
                    <td>
                        <input type="checkbox" name="wp_tag_linker_underline" value="1" <?php checked(1, get_option('wp_tag_linker_underline', 1)); ?> /> Enable underline
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Allowed Tags (comma-separated)</th>
                    <td>
                        <textarea name="wp_tag_linker_allowed_tags" rows="4" cols="70" placeholder="e.g. tag1, tag2, tag3"><?php echo esc_attr(get_option('wp_tag_linker_allowed_tags', '')); ?></textarea>
                        <p>Available tags: <?php echo esc_html($all_tags_list); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Apply custom styles from user settings
function wp_tag_linker_styles() {
    $color = esc_attr(get_option('wp_tag_linker_color', '#0073aa'));
    $underline = get_option('wp_tag_linker_underline', 1) ? 'underline' : 'none';
    echo "<style>.wp-tag-link { color: {$color}; text-decoration: {$underline}; }</style>";
}
add_action('wp_head', 'wp_tag_linker_styles');
?>
