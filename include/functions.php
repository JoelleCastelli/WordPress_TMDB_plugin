<?php

$slug = 'esgi-tmdb';

// Add menu in admin list
add_action('admin_menu', 'esgi_tmdb_addAdminLink');
function esgi_tmdb_addAdminLink() {
    global $slug;
    add_menu_page(
        'Configuration du plugin ESGI TMDB',  // Page title
        'ESGI TMDB',                         // Menu title
        'manage_options',                    // Capability
        $slug,                        // Slug
        'esgi_tmdb_config_page',             // Display callback
        'dashicons-pets'                     // Icon
    );
}

// Config page layout
function esgi_tmdb_config_page() {
    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()) ?></h1>
        <div class="wrap">
            Pour obtenir votre clé API, consultez la <a href="https://developers.themoviedb.org/3/getting-started/introduction" target="_blank">documentation TMDB</a>.
            <form action="options.php" method="POST">
                <?php
                settings_fields('esgi_tmdb_settings');
                do_settings_sections('esgi_tmdb_settings_page');
                submit_button();
                ?>
            </form>
        </div>
        <?php
            $tmdb = new EsgiTmdb();
            if($tmdb->getTmdbKey()) { ?>
                <div class="wrap">
                    <h3>Générer un shortcode :</h3>
                    <div id="itemType">
                        <b>Type :</b>
                        <input class="checkbox itemType" type="checkbox" id="movie" name="movie" />
                        <label for="movie">Films</label>
                        <input class="checkbox itemType" type="checkbox" id="tv" name="tv" />
                        <label for="tv">Séries</label>
                    </div>
                    <br>
                    <div id="movieGenres">
                        <b>Genres de films : </b>
                        <?php foreach ($tmdb->getMovieGenres() as $id => $name) { ?>
                            <input class="checkbox movieGenres" type="checkbox" id="<?= $id ?>" />
                            <label for="<?= $id ?>"><?= $name ?></label>
                        <?php } ?>
                    </div>
                    <br>
                    <div id="tvGenres">
                        <b>Genres de séries : </b>
                        <?php foreach ($tmdb->getTvGenres() as $id => $name) { ?>
                            <input class="checkbox tvGenres" type="checkbox" id="<?= $id ?>" />
                            <label for="<?= $id ?>"><?= $name ?></label>
                        <?php } ?>
                    </div>
                    <div id="shortcode"></div>
                </div>
                <?php
            }
        ?>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', 'esgi_generate_shortcode');
function esgi_generate_shortcode($hook) {
    global $slug;
    if ($hook == "toplevel_page_".$slug) {
        wp_enqueue_script('generate_shortcode', plugin_dir_url(__FILE__) . 'script.js');
    }
}

// Register a setting
add_action('admin_init', 'esgi_tmdb_settings');
function esgi_tmdb_settings(){
    register_setting(
        'esgi_tmdb_settings',  // Group name
        'esgi_tmdb_settings',  // Setting name
        'esgi_tmdb_sanitize'                   // Callback function
    );

    add_settings_section(
        'esgi_tmdb_config_section',  // ID
        '',                         // Title
        '',                      // Callback
        'esgi_tmdb_settings_page'  // Page
    );

    add_settings_field(
        'public-tmdb-api-key',         // ID
        'Clé publique API TMDB',      // Title
        'esgi_tmdb_display_field',           // Callback
        'esgi_tmdb_settings_page',   // Page
        'esgi_tmdb_config_section'  // Section ID
    );
}

// Display the field
function esgi_tmdb_display_field()
{
    $setting = get_option('esgi_tmdb_settings');
    $value = !empty($setting['tmdb-key']) ? $setting['tmdb-key'] : '';
    echo '<input class="regular-text" type="text" name="esgi_tmdb_settings[tmdb-key]" value="' . esc_attr($value) . '">';
}

function esgi_tmdb_sanitize($settings)
{
    $settings['tmdb-key'] = !empty($settings['tmdb-key']) ? sanitize_text_field($settings['tmdb-key']) : '';
    return $settings;
}

// Activate widget
add_action('widgets_init', 'esgi_tmdb_widgets');
function esgi_tmdb_widgets(){
    register_widget('EsgiTmdbWidget');
}

// Shortcodes
add_shortcode('esgi-tmdb', 'esgi_tmdb_shortcode');
function esgi_tmdb_shortcode($attributes)
{
    $genres = [];
    foreach ($attributes as $type => $genresId) {
        if ($genresId === "none") {
            $types[$type] = false;
        } else {
            $types[$type] = true;
            if ($genresId !== "all")
                $genres[$type] = $genresId;
        }
    }
    if (empty($genres)) $genres = null;

    $tmdb = new EsgiTmdb();
    $tmdbRandomWork = $tmdb->esgi_get_random_tmdb_item(['movie' => $types['movie'], 'tv' => $types['tv']], $genres);
    if ($tmdbRandomWork)
        return "<p>" . $tmdb->esgi_get_tmdb_preview($tmdbRandomWork) . "</p>";

    return false;
}