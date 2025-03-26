<?php
/**
 * Plugin Name: reCAPTCHA for Comments
 * Description: Добавляет Google reCAPTCHA v3 в форму комментариев WordPress.
 * Version: 1.1
 * Author: Your Name
 */

if (!defined('ABSPATH')) {
    exit; // Запрет прямого доступа
}

// Добавление reCAPTCHA v3 в форму комментариев
function recaptcha_comment_form() {
    $site_key = get_option('recaptcha_site_key');
    if (!$site_key) return;
    echo '<script src="https://www.google.com/recaptcha/api.js?render=' . esc_attr($site_key) . '"></script>';
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            grecaptcha.ready(function() {
                grecaptcha.execute("' . esc_attr($site_key) . '", { action: "comment" }).then(function(token) {
                    var recaptchaResponse = document.createElement("input");
                    recaptchaResponse.setAttribute("type", "hidden");
                    recaptchaResponse.setAttribute("name", "g-recaptcha-response");
                    recaptchaResponse.setAttribute("value", token);
                    document.forms["commentform"].appendChild(recaptchaResponse);
                });
            });
        });
    </script>';
}
add_action('comment_form', 'recaptcha_comment_form');

// Проверка reCAPTCHA при отправке комментария
function verify_recaptcha_comment($commentdata) {
    $secret_key = get_option('recaptcha_secret_key');
    $score_threshold = floatval(get_option('recaptcha_score_threshold', 0.5));
    
    if (!$secret_key || empty($_POST['g-recaptcha-response'])) {
        wp_die('Ошибка: не пройдена проверка reCAPTCHA.');
    }

    $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
        'body' => array(
            'secret' => $secret_key,
            'response' => sanitize_text_field($_POST['g-recaptcha-response']),
            'remoteip' => $_SERVER['REMOTE_ADDR']
        )
    ));
    
    $result = json_decode(wp_remote_retrieve_body($response), true);
    if (!$result['success'] || $result['score'] < $score_threshold) {
        wp_die('Ошибка: подозрительный комментарий, попробуйте ещё раз.');
    }
    
    return $commentdata;
}
add_filter('preprocess_comment', 'verify_recaptcha_comment');

// Страница настроек в админке
function recaptcha_admin_menu() {
    add_options_page('Настройки reCAPTCHA', 'reCAPTCHA', 'manage_options', 'recaptcha-settings', 'recaptcha_settings_page');
}
add_action('admin_menu', 'recaptcha_admin_menu');

function recaptcha_settings_page() {
    ?>
    <div class="wrap">
        <h1>Настройки Google reCAPTCHA</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('recaptcha_settings');
            do_settings_sections('recaptcha-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function recaptcha_register_settings() {
    register_setting('recaptcha_settings', 'recaptcha_site_key');
    register_setting('recaptcha_settings', 'recaptcha_secret_key');
    register_setting('recaptcha_settings', 'recaptcha_score_threshold');
    
    add_settings_section('recaptcha_section', 'Настройки API ключей', null, 'recaptcha-settings');
    add_settings_field('recaptcha_site_key', 'Site Key', 'recaptcha_site_key_field', 'recaptcha-settings', 'recaptcha_section');
    add_settings_field('recaptcha_secret_key', 'Secret Key', 'recaptcha_secret_key_field', 'recaptcha-settings', 'recaptcha_section');
    add_settings_field('recaptcha_score_threshold', 'Score Threshold', 'recaptcha_score_threshold_field', 'recaptcha-settings', 'recaptcha_section');
}
add_action('admin_init', 'recaptcha_register_settings');

function recaptcha_site_key_field() {
    $value = get_option('recaptcha_site_key', '');
    echo '<input type="text" name="recaptcha_site_key" value="' . esc_attr($value) . '" class="regular-text">';
}

function recaptcha_secret_key_field() {
    $value = get_option('recaptcha_secret_key', '');
    echo '<input type="text" name="recaptcha_secret_key" value="' . esc_attr($value) . '" class="regular-text">';
}

function recaptcha_score_threshold_field() {
    $value = get_option('recaptcha_score_threshold', '0.5');
    echo '<input type="number" step="0.1" min="0" max="1" name="recaptcha_score_threshold" value="' . esc_attr($value) . '" class="regular-text">';
    echo '<p class="description">Минимальный пороговый балл (от 0 до 1), при котором комментарий будет считаться допустимым.</p>';
}
