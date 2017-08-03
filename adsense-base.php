<?php
/**
 * Plugin Name: Adsense Revenue Share
 * Plugin URI: http://cozywp.com/adsense-revenue-share/
 * Author: Alex Mukho
 * Description: This plugin allows you to share your AdSense revenue with blog post author. You can customize revenue percent for author, banners size and position.
 * Author URI: http://cozywp.com/
 * Version: 1.1
 * Text Domain: adsense
 * License: GPL2
 */


define('ARS_VERSION', '1.0');
define('ARS_PATH', dirname(__FILE__));
define('ARS_PATH_INCLUDES', dirname(__FILE__) . '/include');
define('ARS_FOLDER', basename(ARS_PATH));
define('ARS_URL', plugins_url() . '/' . ARS_FOLDER);
define('ARS_URL_INCLUDES', ARS_URL . '/include');

require_once(ARS_PATH . '/adsense-widget.php');

class ARS_Plugin_Base
{

    private $settings;

    function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'ars_add_js'));
        add_action('wp_enqueue_scripts', array($this, 'ars_add_css'));

        add_action('admin_enqueue_scripts', array($this, 'ars_add_admin_js'));
        add_action('admin_enqueue_scripts', array($this, 'ars_add_admin_css'));

        add_action('admin_menu', array($this, 'ars_admin_pages_callback'));

        add_action('show_user_profile', array($this, 'ars_profile_fields'));
        add_action('edit_user_profile', array($this, 'ars_profile_fields'));
        add_action('personal_options_update', array($this, 'ars_profile_fields_update'));
        add_action('edit_user_profile_update', array($this, 'ars_profile_fields_update'));

        add_filter('the_content', array($this, 'ars_display'));

        register_activation_hook(__FILE__, 'ars_on_activate_callback');
        register_deactivation_hook(__FILE__, 'ars_on_deactivate_callback');

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'ars_add_settings_link'));

        add_shortcode('ars', array($this, 'ars_shortcode'));
        add_action('init', array($this, 'ars_shortcode_button'));

        require_once(ARS_PATH_INCLUDES . '/wp-settings-framework.php');
        $this->settings = new WordPressSettingsFramework(ARS_PATH_INCLUDES . '/settings/ars.php');
    }

    /**
     * AdSense display filter
     */
    public function ars_display($content)
    {
        if (!is_single())
            return $content;

        $options = get_option('ars_settings');

        if (!$options)
            return $content;

        $admin_pub = $options['ars_general_publisher_id'];

        if (empty($admin_pub) || !preg_match('/pub-\d{16}/', $admin_pub))
            return $content;

        $author_pub = get_the_author_meta('ars-publisher');
        $author_percent = get_the_author_meta('ars-percent');

        if ($author_percent)
            $percent = $author_percent;

        if (empty($author_pub) || !preg_match('/pub-\d{16}/', $author_pub))
            $author_pub = $admin_pub;

        $percent = $options['ars_general_percent'];

        $display_pub = (mt_rand(1, 100) <= $percent) ? $author_pub : $admin_pub;

        list($width, $height) = explode('x', $options['ars_general_size']);

        $ad_code = '
            <div style="' . $options['ars_general_styles'] . '" class="adsense-banner">
                <script type="text/javascript"><!--
                google_ad_client = "ca-' . $display_pub . '";
                google_ad_width = ' . $width . ';
                google_ad_height = ' . $height . ';
                //-->
                </script>
                <script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
            </div>';

        $position = $options['ars_general_position'];

        switch ($position) {
            case 'header':
                $new_content = $ad_code . '<br>' . $content;
                break;
            case 'footer':
                $new_content = $content . '<br>' . $ad_code;
                break;
            default:
                $new_content = $content;
        }

        return $new_content;
    }

    public function ars_shortcode_button()
    {
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        if (get_user_option('rich_editing') == 'true') {
            add_filter('mce_external_plugins', array($this, 'add_plugin'));
            add_filter('mce_buttons', array($this,'register_button'));
        }
    }

    public function register_button($buttons)
    {
        array_push($buttons, "|", "ars");
        return $buttons;
    }

    public function add_plugin($plugin_array)
    {
        $plugin_array['ars'] = plugins_url('js/ars.js', __FILE__);

        return $plugin_array;
    }

    public function ars_shortcode($atts)
    {
        global $post;

        extract( shortcode_atts(
                array(
                    'size' => '250x250',
                ), $atts )
        );

        $general_options = get_option('ars_settings');

        $admin_pub = $general_options['ars_general_publisher_id'];
        $percent = $general_options['ars_general_percent'];

        if (is_single())
            $post_id = $GLOBALS['post']->ID;

        if ($post->ID) {
            $author_id = get_post($post->ID)->post_author;

            $author_pub = get_the_author_meta('ars-publisher', $author_id);
            $author_percent = get_the_author_meta('ars-percent', $author_id);
        }

        if ($author_percent)
            $percent = $author_percent;

        if (empty($author_pub) || !preg_match('/pub-\d{16}/', $author_pub))
            $author_pub = $admin_pub;

        list($width, $height) = explode('x', $atts['size']);

        $display_pub = (mt_rand(1, 100) <= $percent) ? $author_pub : $admin_pub;

        if (empty($display_pub) || !preg_match('/pub-\d{16}/', $display_pub))
            $content = '';
        else
            $content = '
                <div class="adsense-banner">
                    <script type="text/javascript"><!--
                    google_ad_client = "ca-' . $display_pub . '";
                    google_ad_width = ' . $width . ';
                    google_ad_height = ' . $height . ';
                    //-->
                    </script>
                    <script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
                </div>';

        return $content;
    }


    /**
     * Adding JavaScript scripts
     *
     * Loading existing scripts from wp-includes or adding custom ones
     */
    public function ars_add_js()
    {
    }

    /**
     * Adding JavaScript scripts for the admin pages only
     *
     * Loading existing scripts from wp-includes or adding custom ones
     */
    public function ars_add_admin_js()
    {
    }

    /**
     * Add CSS styles
     */
    public function ars_add_css()
    {
    }

    /**
     * Add admin CSS styles - available only on admin
     */
    public function ars_add_admin_css()
    {
    }

    /**
     * Callback for registering pages
     */
    public function ars_admin_pages_callback()
    {
        add_options_page(__("Adsense Revenue Share", 'base'), __("Adsense Rev Share", 'base'), 'manage_options', 'ars-plugin-base', array($this, 'ars_settings'));
    }

    /**
     * The content of the settings page
     */
    public function ars_settings()
    {
        $this->settings->settings();
    }

    /**
     * Add user extra meta fields
     */
    public function ars_profile_fields($user)
    {
        ?>
        <h3>AdSense Information</h3>
        <table class="form-table">
            <tr>
                <th><label for="twitter">Publisher ID</label></th>
                <td>
                    <input type="text" name="ars-publisher" id="ars-publisher"
                           value="<?php echo esc_attr(get_the_author_meta('ars-publisher', $user->ID)); ?>"
                           class="regular-text"/>
                    <br/>
                    <span class="description">How to find out your Publisher ID - <a href="http://cozywp.com/2014/01/adsense-publisher-id/" target="_blank">Read</a>.</span>
                </td>
            </tr>
            <tr>
                <th><label for="twitter">Revenue share</label></th>
                <td>
                    <?php if (current_user_can('manage_options')): ?>
                        <input type="text" name="ars-percent" id="ars-percent"
                           value="<?php echo esc_attr(get_the_author_meta('ars-percent', $user->ID)); ?>"
                           class="regular-text"/>
                        <br/><span class="description">Only 0-100 value without percent(%) sign</span>
                    <?php else:
                        $percent = get_the_author_meta('ars-percent', $user->ID);

                        $options = get_option('ars_settings');
                        echo ((!empty($percent)) ? $percent : $options['ars_general_percent']) . '%';

                        echo '<br/><span class="description"></span>';
                    endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save user extra meta fields
     */
    public function ars_profile_fields_update($user_id)
    {
        if (!current_user_can('edit_user', $user_id))
            return false;

        update_user_meta($user_id, 'ars-publisher', $_POST['ars-publisher']);

        if (!current_user_can('manage_options', $user_id))
            return false;

        update_user_meta($user_id, 'ars-percent', $_POST['ars-percent']);

        return true;
    }

    /**
     * Add Settings link to plugins page
     */
    public function ars_add_settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page=ars-plugin-base">Settings</a>';
        array_push($links, $settings_link);

        return $links;
    }

}


/**
 * Register activation hook
 */
function ars_on_activate_callback()
{
}

/**
 * Register deactivation hook
 */
function ars_on_deactivate_callback()
{
}

// Initialize plugin
$ars_plugin_base = new ARS_Plugin_Base();