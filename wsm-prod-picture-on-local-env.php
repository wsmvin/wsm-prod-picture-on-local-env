<?php
/**
 * Plugin Name: WSM Prod Picture on Local Env
 * Plugin URI: https://github.com/wsmvin/wsm-prod-picture-on-local-env/
 * Description: Redirects requests for locally non-existent images to another domain
 * Version: 1.0.0
 * Author: WiSiM
 * Author URI: https://github.com/wsmvin/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wsm-prod-picture
 * Domain Path: /languages
 */

// Make sure we don't expose any info if called directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WSM_Prod_Picture_On_Local_Env
 */
class WSM_Prod_Picture_On_Local_Env {
    
    /**
     * Remote domain for redirection
     * @var string
     */
    private $remote_domain = 'https://google.com';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register hooks to intercept requests as early as possible
        add_action('init', array($this, 'check_image_exists'), 0); // Priority 0 for earliest execution
        add_action('template_redirect', array($this, 'check_image_exists'), 0);
        add_action('wp', array($this, 'check_image_exists'), 0);
        
        // Add filter for attachment URLs
        add_filter('wp_get_attachment_url', array($this, 'maybe_redirect_attachment_url'), 10, 2);
        
        // Register admin interface
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Register direct request handling, bypassing WordPress core
        $this->direct_image_handler();
        
        // Load text domain for translations
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }
    
    /**
     * Load plugin textdomain for translations
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'wsm-prod-picture',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
    
    /**
     * Direct handling of image requests
     * This method runs before WordPress initialization
     */
    public function direct_image_handler() {
        // Get current URL
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if the request matches the uploads pattern
        if (preg_match('#^/wp-content/uploads/(.*)$#', $request_uri, $matches)) {
            // Full path to file
            $file_path = $_SERVER['DOCUMENT_ROOT'] . $request_uri;
            
            // Check if file exists
            if (!file_exists($file_path)) {
                // Get redirection domain from settings or use default
                $remote_domain = get_option('wsm_remote_domain', $this->remote_domain);
                $redirect_url = $remote_domain . $request_uri;
                
                if (!headers_sent()) {
                    header('HTTP/1.1 301 Moved Permanently');
                    header('Location: ' . $redirect_url);
                    exit;
                }
            }
        }
    }
    
    /**
     * Checks requested file and redirects if needed
     */
    public function check_image_exists() {
        // Don't process admin requests
        if (is_admin()) {
            return;
        }
        
        // Get current URL
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Check if the request matches the uploads pattern
        if (preg_match('#^/wp-content/uploads/(.*)$#', $request_uri, $matches)) {
            // Full path to file
            $file_path = $_SERVER['DOCUMENT_ROOT'] . $request_uri;
            
            // Check if file exists
            if (!file_exists($file_path)) {
                // Get redirection domain from settings or use default
                $remote_domain = get_option('wsm_remote_domain', $this->remote_domain);
                $redirect_url = $remote_domain . $request_uri;
                
                if (!headers_sent()) {
                    header('HTTP/1.1 301 Moved Permanently');
                    header('Location: ' . $redirect_url);
                    exit;
                }
            }
        }
    }
    
    /**
     * Filter for attachment URLs
     */
    public function maybe_redirect_attachment_url($url, $attachment_id) {
        // Check if file exists locally
        $file_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($url, PHP_URL_PATH);
        
        if (!file_exists($file_path)) {
            // Replace domain in URL
            $remote_domain = get_option('wsm_remote_domain', $this->remote_domain);
            $site_url = get_site_url();
            
            // Return remote domain URL
            return str_replace($site_url, $remote_domain, $url);
        }
        
        return $url;
    }
    
    /**
     * Adds menu item in admin
     */
    public function add_admin_menu() {
        add_options_page(
            __('WSM Prod Picture Settings', 'wsm-prod-picture'),
            __('WSM Prod Picture', 'wsm-prod-picture'),
            'manage_options',
            'wsm-prod-picture',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Registers plugin settings
     */
    public function register_settings() {
        register_setting('wsm_settings', 'wsm_remote_domain');
    }
    
    /**
     * Renders settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wsm_settings');
                do_settings_sections('wsm_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Remote Domain', 'wsm-prod-picture'); ?></th>
                        <td>
                            <input type="text" name="wsm_remote_domain" 
                                   value="<?php echo esc_attr(get_option('wsm_remote_domain', $this->remote_domain)); ?>" 
                                   class="regular-text" />
                            <p class="description"><?php _e('Enter the full URL of the remote domain (e.g., https://www.thetransmitter.org)', 'wsm-prod-picture'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <div class="notice notice-info">
                <p><strong><?php _e('How it works:', 'wsm-prod-picture'); ?></strong> <?php _e('The plugin intercepts requests to files in the <code>/wp-content/uploads/</code> folder and, if the file doesn\'t exist locally, redirects to the specified domain. The plugin also modifies URLs for attachments that don\'t exist locally.', 'wsm-prod-picture'); ?></p>
            </div>
            
            <div class="notice notice-warning">
                <p><strong><?php _e('Note:', 'wsm-prod-picture'); ?></strong> <?php _e('For reliable operation, it\'s recommended to enable the <code>output_buffering</code> option in PHP. This allows intercepting requests before any data is sent to the browser.', 'wsm-prod-picture'); ?></p>
            </div>
        </div>
        <?php
    }
}

// Initialize plugin
$wsm_prod_picture = new WSM_Prod_Picture_On_Local_Env();

// Define global function for direct use in themes
if (!function_exists('wsm_prod_picture_url')) {
    function wsm_prod_picture_url($url) {
        $remote_domain = get_option('wsm_remote_domain', 'https://www.thetransmitter.org');
        $file_path = $_SERVER['DOCUMENT_ROOT'] . parse_url($url, PHP_URL_PATH);
        
        if (!file_exists($file_path)) {
            $site_url = get_site_url();
            return str_replace($site_url, $remote_domain, $url);
        }
        
        return $url;
    }
}
