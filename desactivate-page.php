<?php
/*
Plugin Name: Désactivation des Pages
Description: Désactive les pages WordPress
Version: 1.0
Author: GEHIN Nicolas
*/

// Désactive l'interface des pages dans l'admin
function desactiver_pages() {
    // Retire le menu "Pages" du tableau de bord
    remove_menu_page('edit.php?post_type=page');
}
add_action('admin_menu', 'desactiver_pages');

// Retire "Nouvelle page" de la barre d'admin
function desactiver_pages_admin_bar() {
    global $wp_admin_bar;
    if ($wp_admin_bar) {
        $wp_admin_bar->remove_node('new-page');
    }
}
add_action('admin_bar_menu', 'desactiver_pages_admin_bar', 999);

// Redirige les utilisateurs qui essaient d'accéder aux pages de gestion
function bloquer_acces_pages() {
    global $pagenow;
    $array_pages = array('post-new.php', 'post.php', 'edit.php');
    
    if (in_array($pagenow, $array_pages) && isset($_GET['post_type']) && $_GET['post_type'] === 'page') {
        wp_redirect(admin_url());
        exit;
    }
}
add_action('admin_init', 'bloquer_acces_pages');

// Désactive l'affichage des pages sur le front
function desactiver_affichage_pages() {
    if (is_page()) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
    }
}
add_action('template_redirect', 'desactiver_affichage_pages'); 