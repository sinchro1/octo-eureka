<?php
/**
 * Plugin Name: Bricks No HTML Comments
 * Description: Отключает HTML и удаляет ссылки из комментариев в WordPress для Bricks Builder.
 * Version: 1.0
 * Author: sinchro
 */

if (!defined('ABSPATH')) {
    exit; // Запрет прямого доступа
}

function bricks_sanitize_comment($comment_content) {
    // Удаляем HTML-теги
    $comment_content = wp_strip_all_tags($comment_content);
    
    // Удаляем ссылки
    $comment_content = preg_replace('/https?:\/\/[^\s]+/', '', $comment_content);
    
    return $comment_content;
}
add_filter('pre_comment_content', 'bricks_sanitize_comment');
