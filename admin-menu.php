<?php
/*
Plugin Name: Upcoder Admin Menu
Description: Plugin permettant d'ajouter un bouton pour afficher/masquer les menus administratifs selon les préférences de l'utilisateur
Version: 1.0
Author: GEHIN Nicolas
*/

// Ajoute le bouton dans l'admin bar
function ajouter_toggle_admin_bar() {
    global $wp_admin_bar;
    
    $user_id = get_current_user_id();
    $is_simplified = get_user_meta($user_id, 'menu_simplified', true);
    $is_simplified = $is_simplified === '' ? false : $is_simplified;
    
    $button_text = $is_simplified ? 'Voir tout' : 'Simplifier';
    
    $wp_admin_bar->add_node(array(
        'id'    => 'toggle-admin-menu',
        'title' => $button_text,
        'href'  => '#',
    ));
}
add_action('admin_bar_menu', 'ajouter_toggle_admin_bar', 999);

// Gère l'affichage des menus
function gerer_affichage_menus() {
    $user_id = get_current_user_id();
    $is_simplified = get_user_meta($user_id, 'menu_simplified', true);
    
    if ($is_simplified) {
        $menus_to_hide = get_option('toggle_menu_options', array(
            'options-general.php',
            'tools.php',
            'users.php',
            'plugins.php',
            'themes.php',
            'upload.php'
        ));
        
        foreach ($menus_to_hide as $menu) {
            remove_menu_page($menu);
        }
    }
}
add_action('admin_menu', 'gerer_affichage_menus', 999);

// Enregistre et charge le script JavaScript
function enregistrer_toggle_script() {
    wp_enqueue_script('jquery');
    
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            $("#wp-admin-bar-toggle-admin-menu").on("click", function(e) {
                e.preventDefault();
                
                $.post(ajaxurl, {
                    action: "toggle_admin_menu",
                    nonce: "' . wp_create_nonce('toggle_admin_menu_nonce') . '"
                }, function(response) {
                    if(response.success) {
                        window.location.reload();
                    }
                });
            });
        });
    ');
}
add_action('admin_enqueue_scripts', 'enregistrer_toggle_script');

// Gère l'action AJAX
function handle_toggle_ajax() {
    check_ajax_referer('toggle_admin_menu_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $current_state = get_user_meta($user_id, 'menu_simplified', true);
    $new_state = $current_state ? '' : '1';
    
    update_user_meta($user_id, 'menu_simplified', $new_state);
    
    wp_send_json_success();
}
add_action('wp_ajax_toggle_admin_menu', 'handle_toggle_ajax');

// Ajoute du CSS pour le bouton
function ajouter_toggle_styles() {
    ?>
    <style>
    #wp-admin-bar-toggle-admin-menu {
        background: #2271b1 !important;
    }
    #wp-admin-bar-toggle-admin-menu .ab-item {
        color: white !important;
        cursor: pointer !important;
    }
    #wp-admin-bar-toggle-admin-menu:hover {
        background: #135e96 !important;
    }
    </style>
    <?php
}
add_action('admin_head', 'ajouter_toggle_styles');

// Ajoute la page d'options
function ajouter_menu_options() {
    add_options_page(
        'Configuration Menu Toggle',
        'Menu Toggle',
        'manage_options',
        'toggle-menu-settings',
        'afficher_page_options'
    );
}
add_action('admin_menu', 'ajouter_menu_options');

// Fonction pour récupérer tous les menus admin
function get_all_admin_menus() {
    global $menu;
    
    // Assurez-vous que la variable $menu est disponible
    if (!$menu) {
        require_once(ABSPATH . 'wp-admin/includes/admin.php');
    }
    
    $all_menus = array();
    
    foreach ($menu as $menu_item) {
        if (!empty($menu_item[0]) && !empty($menu_item[2])) {
            // Nettoie le nom du menu des balises HTML
            $menu_name = strip_tags($menu_item[0]);
            $menu_slug = $menu_item[2];
            
            $all_menus[$menu_slug] = $menu_name;
        }
    }
    
    return $all_menus;
}

// Modifier la fonction afficher_page_options pour utiliser les menus dynamiques
function afficher_page_options() {
    // Vérifie les droits d'accès
    if (!current_user_can('manage_options')) {
        return;
    }

    // Sauvegarde les options
    if (isset($_POST['submit'])) {
        check_admin_referer('toggle_menu_options');
        $menus_to_hide = isset($_POST['menus_to_hide']) ? $_POST['menus_to_hide'] : array();
        update_option('toggle_menu_options', $menus_to_hide);
        echo '<div class="notice notice-success"><p>Paramètres sauvegardés.</p></div>';
    }

    // Récupère les options sauvegardées
    $saved_menus = get_option('toggle_menu_options', array(
        'options-general.php',
        'tools.php',
        'users.php',
        'plugins.php',
        'themes.php',
        'upload.php'
    ));

    // Remplacer la liste statique par les menus dynamiques
    $available_menus = get_all_admin_menus();
    ?>
    <div class="wrap">
        <h1>Configuration Menu Toggle</h1>
        <form method="post" action="">
            <?php wp_nonce_field('toggle_menu_options'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Menus à masquer en vue simplifiée</th>
                    <td>
                        <?php foreach ($available_menus as $menu_slug => $menu_name) : ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" 
                                       name="menus_to_hide[]" 
                                       value="<?php echo esc_attr($menu_slug); ?>"
                                       <?php checked(in_array($menu_slug, $saved_menus)); ?>>
                                <?php echo esc_html($menu_name); ?>
                            </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
            <?php submit_button('Enregistrer les modifications'); ?>
        </form>
    </div>
    <?php
}

// Ajoute un lien vers les paramètres dans la liste des plugins
function ajouter_lien_configuration($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=toggle-menu-settings') . '">Paramètres</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ajouter_lien_configuration');