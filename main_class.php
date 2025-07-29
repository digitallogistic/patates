<?php
/**
 * Ana plugin sınıfı
 * 
 * Plugin'in çalışması için gerekli tüm bileşenleri yükler ve koordine eder
 *
 * @package AI_Genius
 * @subpackage AI_Genius/includes
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ana plugin sınıfı
 * 
 * Plugin'in çekirdek işlevselliğini yönetir:
 * - Hook'ları tanımlar
 * - Admin ve public sınıflarını yükler
 * - Dil dosyalarını yükler
 * - Plugin bileşenlerini koordine eder
 */
class AI_Genius {

    /**
     * Plugin yükleyicisi
     * 
     * @since 1.0.0
     * @var AI_Genius_Loader
     */
    protected $loader;

    /**
     * Plugin kimlik bilgisi
     * 
     * @since 1.0.0
     * @var string
     */
    protected $plugin_name;

    /**
     * Plugin versiyonu
     * 
     * @since 1.0.0
     * @var string
     */
    protected $version;

    /**
     * Admin sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_Admin
     */
    protected $admin;

    /**
     * Public sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_Public
     */
    protected $public;

    /**
     * Veritabanı sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_Database
     */
    protected $database;

    /**
     * Lisans yöneticisi
     * 
     * @since 1.0.0
     * @var AI_Genius_License
     */
    protected $license;

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        if (defined('AI_GENIUS_VERSION')) {
            $this->version = AI_GENIUS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        
        $this->plugin_name = 'ai-genius';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_api_hooks();
        
        ai_genius_log('AI_Genius class initialized');
    }

    /**
     * Gerekli sınıfları yükle
     * 
     * @since 1.0.0
     */
    private function load_dependencies() {
        
        /**
         * Hook yöneticisi sınıfı
         */
        require_once AI_GENIUS_PLUGIN_DIR . 'includes/class-loader.php';
        
        /**
         * Çeviri sınıfı
         */
        require_once AI_GENIUS_PLUGIN_DIR . 'includes/class-i18n.php';
        
        /**
         * Veritabanı yöneticisi
         */
        require_once AI_GENIUS_PLUGIN_DIR . 'includes/class-database.php';
        
        /**
         * Lisans yöneticisi
         */
        require_once AI_GENIUS_PLUGIN_DIR . 'includes/class-license.php';
        
        /**
         * Admin panel sınıfları
         */
        require_once AI_GENIUS_PLUGIN_DIR . 'admin/class-admin.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'admin/class-settings.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'admin/class-data-manager.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'admin/class-analytics.php';
        
        /**
         * Public sınıfları
         */
        require_once AI_GENIUS_PLUGIN_DIR . 'public/class-public.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'public/class-chatbot.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'public/class-ai-processor.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'public/class-data-retriever.php';
        
        /**
         * API sınıfları
         */
        require_once AI_GENIUS_PLUGIN_DIR . 'api/class-api-handler.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/class-openai-connector.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/class-envato-api.php';
        
        // Sınıfları başlat
        $this->loader = new AI_Genius_Loader();
        $this->database = new AI_Genius_Database();
        $this->license = new AI_Genius_License();
        
        ai_genius_log('Dependencies loaded successfully');
    }

    /**
     * Dil dosyalarını yükle
     * 
     * @since 1.0.0
     */
    private function set_locale() {
        
        $plugin_i18n = new AI_Genius_i18n();
        
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
        
        ai_genius_log('Locale set successfully');
    }

    /**
     * Admin panel hook'larını tanımla
     * 
     * @since 1.0.0
     */
    private function define_admin_hooks() {
        
        $this->admin = new AI_Genius_Admin($this->get_plugin_name(), $this->get_version());
        
        // Admin panel yükleme hook'ları
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $this->admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $this->admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $this->admin, 'register_settings');
        
        // Admin AJAX hook'ları
        $this->loader->add_action('wp_ajax_ai_genius_save_settings', $this->admin, 'save_settings');
        $this->loader->add_action('wp_ajax_ai_genius_upload_data', $this->admin, 'upload_data');
        $this->loader->add_action('wp_ajax_ai_genius_export_data', $this->admin, 'export_data');
        $this->loader->add_action('wp_ajax_ai_genius_test_connection', $this->admin, 'test_api_connection');
        
        // Lisans yönetimi hook'ları
        $this->loader->add_action('wp_ajax_ai_genius_validate_license', $this->license, 'validate_license');
        $this->loader->add_action('wp_ajax_ai_genius_deactivate_license', $this->license, 'deactivate_license');
        
        // Dashboard widget'ları
        $this->loader->add_action('wp_dashboard_setup', $this->admin, 'add_dashboard_widgets');
        
        // Admin notice'ları
        $this->loader->add_action('admin_notices', $this->admin, 'display_admin_notices');
        
        ai_genius_log('Admin hooks defined successfully');
    }

    /**
     * Public hook'larını tanımla
     * 
     * @since 1.0.0
     */
    private function define_public_hooks() {
        
        $this->public = new AI_Genius_Public($this->get_plugin_name(), $this->get_version());
        
        // Frontend yükleme hook'ları
        $this->loader->add_action('wp_enqueue_scripts', $this->public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $this->public, 'enqueue_scripts');
        
        // Chatbot görüntüleme hook'ları
        $this->loader->add_action('wp_footer', $this->public, 'display_chatbot');
        $this->loader->add_filter('the_content', $this->public, 'maybe_add_chatbot_to_content');
        
        // Shortcode desteği
        $this->loader->add_action('init', $this->public, 'register_shortcodes');
        
        // AJAX hook'ları (giriş yapmış ve yapmamış kullanıcılar için)
        $this->loader->add_action('wp_ajax_ai_genius_chat', $this->public, 'handle_chat_request');
        $this->loader->add_action('wp_ajax_nopriv_ai_genius_chat', $this->public, 'handle_chat_request');
        
        $this->loader->add_action('wp_ajax_ai_genius_rate_response', $this->public, 'rate_response');
        $this->loader->add_action('wp_ajax_nopriv_ai_genius_rate_response', $this->public, 'rate_response');
        
        // Session yönetimi
        $this->loader->add_action('init', $this->public, 'start_session');
        $this->loader->add_action('wp_logout', $this->public, 'end_session');
        
        // Widget desteği
        $this->loader->add_action('widgets_init', $this->public, 'register_widgets');
        
        ai_genius_log('Public hooks defined successfully');
    }

    /**
     * API hook'larını tanımla
     * 
     * @since 1.0.0
     */
    private function define_api_hooks() {
        
        // REST API endpoint'leri
        $this->loader->add_action('rest_api_init', $this, 'register_rest_routes');
        
        // Cron job hook'ları
        $this->loader->add_action('ai_genius_daily_analytics', $this, 'run_daily_analytics');
        $this->loader->add_action('ai_genius_weekly_cleanup', $this, 'run_weekly_cleanup');
        $this->loader->add_action('ai_genius_monthly_backup', $this, 'run_monthly_backup');
        
        // API güvenlik hook'ları
        $this->loader->add_filter('ai_genius_api_authentication', $this, 'authenticate_api_request');
        $this->loader->add_filter('ai_genius_rate_limit', $this, 'check_rate_limit');
        
        ai_genius_log('API hooks defined successfully');
    }

    /**
     * REST API rotalarını kaydet
     * 
     * @since 1.0.0
     */
    public function register_rest_routes() {
        
        require_once AI_GENIUS_PLUGIN_DIR . 'api/endpoints/chat-endpoint.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/endpoints/data-endpoint.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/endpoints/license-endpoint.php';
        
        $chat_endpoint = new AI_Genius_Chat_Endpoint();
        $data_endpoint = new AI_Genius_Data_Endpoint();  
        $license_endpoint = new AI_Genius_License_Endpoint();
        
        $chat_endpoint->register_routes();
        $data_endpoint->register_routes();
        $license_endpoint->register_routes();
        
        ai_genius_log('REST API routes registered successfully');
    }

    /**
     * Günlük analitik işlemlerini çalıştır
     * 
     * @since 1.0.0
     */
    public function run_daily_analytics() {
        
        require_once AI_GENIUS_PLUGIN_DIR . 'admin/class-analytics.php';
        
        $analytics = new AI_Genius_Analytics();
        $analytics->generate_daily_report();
        
        ai_genius_log('Daily analytics completed');
    }

    /**
     * Haftalık temizlik işlemlerini çalıştır
     * 
     * @since 1.0.0
     */
    public function run_weekly_cleanup() {
        
        global $wpdb;
        
        // 30 günden eski chat geçmişini arşivle
        $chat_table = $wpdb->prefix . 'ai_genius_chat_history';
        $archive_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $chat_table WHERE created_at < %s AND is_helpful IS NULL",
                $archive_date
            )
        );
        
        // Eski log dosyalarını temizle
        $this->cleanup_old_logs();
        
        ai_genius_log('Weekly cleanup completed');
    }

    /**
     * Aylık yedekleme işlemlerini çalıştır
     * 
     * @since 1.0.0
     */
    public function run_monthly_backup() {
        
        require_once AI_GENIUS_PLUGIN_DIR . 'includes/class-backup.php';
        
        $backup = new AI_Genius_Backup();
        $backup->create_monthly_backup();
        
        ai_genius_log('Monthly backup completed');
    }

    /**
     * API kimlik doğrulaması
     * 
     * @since 1.0.0
     * @param mixed $result
     * @return mixed
     */
    public function authenticate_api_request($result) {
        
        // Nonce kontrolü
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'ai_genius_nonce')) {
            return new WP_Error('invalid_nonce', 'Geçersiz güvenlik anahtarı', array('status' => 403));
        }
        
        // Rate limiting kontrolü
        if (!$this->check_rate_limit()) {
            return new WP_Error('rate_limit_exceeded', 'İstek limiti aşıldı', array('status' => 429));
        }
        
        return $result;
    }

    /**
     * Rate limiting kontrolü
     * 
     * @since 1.0.0
     * @return bool
     */
    public function check_rate_limit() {
        
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $rate_limit = get_option('ai_genius_rate_limit', 10);
        
        $transient_key = 'ai_genius_rate_' . md5($user_ip);
        $current_requests = get_transient($transient_key);
        
        if ($current_requests === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return true;
        }
        
        if ($current_requests >= $rate_limit) {
            return false;
        }
        
        set_transient($transient_key, $current_requests + 1, MINUTE_IN_SECONDS);
        return true;
    }

    /**
     * Eski log dosyalarını temizle
     * 
     * @since 1.0.0
     */
    private function cleanup_old_logs() {
        
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/ai-genius/logs';
        
        if (!is_dir($logs_dir)) {
            return;
        }
        
        $log_files = glob($logs_dir . '/*.log');
        $cutoff_date = strtotime('-7 days');
        
        foreach ($log_files as $log_file) {
            if (filemtime($log_file) < $cutoff_date) {
                unlink($log_file);
            }
        }
    }

    /**
     * Plugin'i çalıştır
     * 
     * @since 1.0.0
     */
    public function run() {
        $this->loader->run();
        ai_genius_log('Plugin running successfully');
    }

    /**
     * Plugin adını döndür
     * 
     * @since 1.0.0
     * @return string
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Hook yükleyicisini döndür
     * 
     * @since 1.0.0
     * @return AI_Genius_Loader
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Plugin versiyonunu döndür
     * 
     * @since 1.0.0
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
}