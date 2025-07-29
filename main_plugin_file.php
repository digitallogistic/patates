<?php
/**
 * Plugin Name:       AI-Genius - Yapay Zeka Destekli Akıllı Asistan
 * Plugin URI:        https://codecanyon.net/item/ai-genius
 * Description:       Site sahiplerinin kendi veri kaynaklarını kullanarak, ziyaretçilerine 7/24 kişiselleştirilmiş, akıllı ve anında destek sunmalarını sağlayan WordPress eklentisi.
 * Version:           1.0.0
 * Author:            AI-Genius Team
 * Author URI:        https://ai-genius.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-genius
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to:      6.4
 * Requires PHP:      7.4
 * Network:           false
 * 
 * @package AI_Genius
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin sabitleri
 */
define('AI_GENIUS_VERSION', '1.0.0');
define('AI_GENIUS_PLUGIN_FILE', __FILE__);
define('AI_GENIUS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_GENIUS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_GENIUS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AI_GENIUS_TEXT_DOMAIN', 'ai-genius');
define('AI_GENIUS_DB_VERSION', '1.0.0');

/**
 * Plugin aktivasyon hook'u
 */
function activate_ai_genius() {
    require_once AI_GENIUS_PLUGIN_DIR . 'includes/class-activator.php';
    AI_Genius_Activator::activate();
}
register_activation_hook(__FILE__, 'activate_ai_genius');

/**
 * Plugin deaktivasyon hook'u
 */
function deactivate_ai_genius() {
    require_once AI_GENIUS_PLUGIN_DIR . 'includes/class-deactivator.php';
    AI_Genius_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_ai_genius');

/**
 * Ana plugin sınıfını yükle
 */
require AI_GENIUS_PLUGIN_DIR . 'includes/class-ai-genius.php';

/**
 * Plugin'i başlat
 */
function run_ai_genius() {
    $plugin = new AI_Genius();
    $plugin->run();
}

/**
 * Plugin'i WordPress'in plugins_loaded hook'unda başlat
 */
add_action('plugins_loaded', 'run_ai_genius');

/**
 * Eklenti güncellemeleri için kontrol
 */
add_action('init', 'ai_genius_check_version');
function ai_genius_check_version() {
    if (AI_GENIUS_VERSION !== get_option('ai_genius_version')) {
        activate_ai_genius();
        update_option('ai_genius_version', AI_GENIUS_VERSION);
    }
}

/**
 * Plugin bağlantıları - Admin sayfasında eklenti listesinde gösterilecek linkler
 */
add_filter('plugin_action_links_' . AI_GENIUS_PLUGIN_BASENAME, 'ai_genius_plugin_action_links');
function ai_genius_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=ai-genius') . '">' . __('Ayarlar', 'ai-genius') . '</a>';
    $support_link = '<a href="https://ai-genius.com/support" target="_blank">' . __('Destek', 'ai-genius') . '</a>';
    $docs_link = '<a href="https://ai-genius.com/docs" target="_blank">' . __('Dokümantasyon', 'ai-genius') . '</a>';
    
    array_unshift($links, $settings_link);
    array_push($links, $support_link, $docs_link);
    
    return $links;
}

/**
 * Plugin meta bağlantıları
 */
add_filter('plugin_row_meta', 'ai_genius_plugin_row_meta', 10, 2);
function ai_genius_plugin_row_meta($links, $file) {
    if (AI_GENIUS_PLUGIN_BASENAME === $file) {
        $new_links = array(
            'changelog' => '<a href="https://ai-genius.com/changelog" target="_blank">' . __('Değişiklik Günlüğü', 'ai-genius') . '</a>',
            'license' => '<a href="' . admin_url('admin.php?page=ai-genius-license') . '">' . __('Lisans', 'ai-genius') . '</a>'
        );
        $links = array_merge($links, $new_links);
    }
    return $links;
}

/**
 * Gerekli PHP sürümü kontrolü
 */
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', 'ai_genius_php_version_notice');
    function ai_genius_php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('AI-Genius eklentisi PHP 7.4 veya daha yeni bir sürüm gerektirir. Şu anda %s sürümünü kullanıyorsunuz.', 'ai-genius'),
            PHP_VERSION
        );
        echo '</p></div>';
    }
    return;
}

/**
 * WordPress sürümü kontrolü
 */
if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    add_action('admin_notices', 'ai_genius_wp_version_notice');
    function ai_genius_wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('AI-Genius eklentisi WordPress 5.0 veya daha yeni bir sürüm gerektirir. Şu anda %s sürümünü kullanıyorsunuz.', 'ai-genius'),
            get_bloginfo('version')
        );
        echo '</p></div>';
    }
    return;
}

/**
 * Plugin yüklenme sırası - Diğer eklentilerden sonra yüklenmesini sağla
 */
add_action('activated_plugin', 'ai_genius_load_last');
function ai_genius_load_last() {
    $path = str_replace(WP_PLUGIN_DIR . '/', '', __FILE__);
    if ($plugins = get_option('active_plugins')) {
        if ($key = array_search($path, $plugins)) {
            array_splice($plugins, $key, 1);
            array_push($plugins, $path);
            update_option('active_plugins', $plugins);
        }
    }
}

/**
 * AJAX işlemleri için nonce güvenlik kontrolü
 */
add_action('wp_ajax_ai_genius_nonce', 'ai_genius_create_nonce');
add_action('wp_ajax_nopriv_ai_genius_nonce', 'ai_genius_create_nonce');
function ai_genius_create_nonce() {
    wp_send_json_success(array(
        'nonce' => wp_create_nonce('ai_genius_nonce')
    ));
}

/**
 * Plugin hata ayıklama modu
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    define('AI_GENIUS_DEBUG', true);
} else {
    define('AI_GENIUS_DEBUG', false);
}

/**
 * Plugin log fonksiyonu
 */
function ai_genius_log($message, $level = 'info') {
    if (AI_GENIUS_DEBUG) {
        error_log('[AI-Genius ' . strtoupper($level) . '] ' . $message);
    }
}

/**
 * Plugin başlatma mesajı
 */
ai_genius_log('AI-Genius Plugin v' . AI_GENIUS_VERSION . ' loaded successfully');