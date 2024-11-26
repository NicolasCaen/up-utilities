<?php
/*
Plugin Name: Désactivation des commentaires de wordpress
Description: Désactive toutes les fonctionnalités de commentaires de WordPress
Version: 1.0
Author: GEHIN Nicolas
*/

// Désactive le support des commentaires pour tous les types de posts
function desactiver_support_commentaires() {
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        remove_post_type_support($post_type, 'comments');
        remove_post_type_support($post_type, 'trackbacks');
    }
}
add_action('init', 'desactiver_support_commentaires');

// Ferme les commentaires sur les posts existants
function fermer_commentaires_existants() {
    $posts = get_posts(array(
        'numberposts' => -1,
        'post_type' => 'any'
    ));
    foreach ($posts as $post) {
        wp_update_post(array(
            'ID' => $post->ID,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ));
    }
}
add_action('init', 'fermer_commentaires_existants');

// Masque les éléments de menu liés aux commentaires dans l'admin
function masquer_menu_commentaires() {
    remove_menu_page('edit-comments.php');
}
add_action('admin_menu', 'masquer_menu_commentaires');

// Désactive les widgets de commentaires
function desactiver_widgets_commentaires() {
    unregister_widget('WP_Widget_Recent_Comments');
    unregister_widget('WP_Widget_Comments');
}
add_action('widgets_init', 'desactiver_widgets_commentaires');

// Retire les commentaires de la barre d'admin
function retirer_commentaires_admin_bar() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}
add_action('wp_before_admin_bar_render', 'retirer_commentaires_admin_bar');
