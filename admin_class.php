<?php
/**
 * Admin panel ana sınıfı
 * 
 * WordPress admin panelindeki tüm işlevleri yönetir
 *
 * @package AI_Genius
 * @subpackage AI_Genius/admin
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin sınıfı
 * 
 * Plugin'in admin panel işlevlerini yönetir:
 * - Menü ve sayfalar
 * - Stil ve script yükleme
 * - AJAX işlemleri
 * - Dashboard widget'ları
 */
class AI_Genius_Admin {

    /**
     * Plugin adı
     * 
     * @since 1.0.0
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin versiyonu
     * 
     * @since 1.0.0
     * @var string
     */
    private $version;

    /**
     * Veritabanı sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_Database
     */
    private $database;

    /**
     * Lisans sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_License
     */
    private $license;

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     * @param string $plugin_name Plugin adı
     * @param string $version Plugin versiyonu
     */
    public function __construct($plugin_name, $version) {
        
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->database = new AI_Genius_Database();
        $this->license = new AI_Genius_License();
        
        // Admin hook'ları
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_notices', array($this->license, 'show_license_notices'));
        
        ai_genius_log('Admin class initialized');
    }

    /**
     * Admin stilleri yükle
     * 
     * @since 1.0.0
     */
    public function enqueue_styles() {
        
        // Sadece AI-Genius sayfalarında stil yükle
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'ai-genius') === false) {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            AI_GENIUS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // WordPress media uploader
        wp_enqueue_media();
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
        
        // Chart.js CSS (eğer varsa)
        wp_enqueue_style(
            $this->plugin_name . '-charts',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css',
            array(),
            '3.9.1'
        );
        
        ai_genius_log('Admin styles enqueued');
    }

    /**
     * Admin script'leri yükle
     * 
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        
        // Sadece AI-Genius sayfalarında script yükle
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'ai-genius') === false) {
            return;
        }
        
        // Ana admin script
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            AI_GENIUS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            $this->version,
            false
        );
        
        // Chart.js
        wp_enqueue_script(
            $this->plugin_name . '-charts',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array('jquery'),
            '3.9.1',
            false
        );
        
        // Veri yükleme script'i
        wp_enqueue_script(
            $this->plugin_name . '-data-upload',
            AI_GENIUS_PLUGIN_URL . 'admin/js/data-upload.js',
            array('jquery'),
            $this->version,
            false
        );
        
        // JavaScript çevirileri
        wp_set_script_translations(
            $this->plugin_name . '-admin',
            'ai-genius',
            AI_GENIUS_PLUGIN_DIR . 'languages'
        );
        
        // AJAX verilerini localize et
        wp_localize_script(
            $this->plugin_name . '-admin',
            'aiGeniusAdmin',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_genius_nonce'),
                'plugin_url' => AI_GENIUS_PLUGIN_URL,
                'strings' => array(
                    'saving' => __('Kaydediliyor...', 'ai-genius'),
                    'saved' => __('Kaydedildi', 'ai-genius'),
                    'error' => __('Hata oluştu', 'ai-genius'),
                    'confirm_delete' => __('Bu öğeyi silmek istediğinizden emin misiniz?', 'ai-genius'),
                    'uploading' => __('Yükleniyor...', 'ai-genius'),
                    'upload_complete' => __('Yükleme tamamlandı', 'ai-genius'),
                    'processing' => __('İşleniyor...', 'ai-genius')
                )
            )
        );
        
        ai_genius_log('Admin scripts enqueued');
    }

    /**
     * Admin menüsünü ekle
     * 
     * @since 1.0.0
     */
    public function add_admin_menu() {
        
        // Ana menü
        add_menu_page(
            __('AI-Genius', 'ai-genius'), // Sayfa başlığı
            __('AI-Genius', 'ai-genius'), // Menü başlığı
            'manage_ai_genius', // Yetki
            'ai-genius', // Menü slug
            array($this, 'display_admin_page'), // Callback
            'dashicons-robot', // İkon
            30 // Pozisyon
        );
        
        // Dashboard alt menüsü
        add_submenu_page(
            'ai-genius',
            __('Dashboard', 'ai-genius'),
            __('Dashboard', 'ai-genius'),
            'manage_ai_genius',
            'ai-genius',
            array($this, 'display_admin_page')
        );
        
        // Bot Ayarları
        add_submenu_page(
            'ai-genius',
            __('Bot Ayarları', 'ai-genius'),
            __('Bot Ayarları', 'ai-genius'),
            'configure_ai_genius_bot',
            'ai-genius-bot-settings',
            array($this, 'display_bot_settings_page')
        );
        
        // Veri Yönetimi
        add_submenu_page(
            'ai-genius',
            __('Veri Yönetimi', 'ai-genius'),
            __('Veri Yönetimi', 'ai-genius'),
            'manage_ai_genius_data',
            'ai-genius-data',
            array($this, 'display_data_management_page')
        );
        
        // Analitik
        add_submenu_page(
            'ai-genius',
            __('Analitik', 'ai-genius'),
            __('Analitik', 'ai-genius'),
            'view_ai_genius_analytics',
            'ai-genius-analytics',
            array($this, 'display_analytics_page')
        );
        
        // Genel Ayarlar
        add_submenu_page(
            'ai-genius',
            __('Ayarlar', 'ai-genius'),
            __('Ayarlar', 'ai-genius'),
            'manage_ai_genius',
            'ai-genius-settings',
            array($this, 'display_settings_page')
        );
        
        // Lisans
        add_submenu_page(
            'ai-genius',
            __('Lisans', 'ai-genius'),
            __('Lisans', 'ai-genius'),
            'manage_ai_genius',
            'ai-genius-license',
            array($this, 'display_license_page')
        );
        
        // Yardım
        add_submenu_page(
            'ai-genius',
            __('Yardım', 'ai-genius'),
            __('Yardım', 'ai-genius'),
            'manage_ai_genius',
            'ai-genius-help',
            array($this, 'display_help_page')
        );
        
        ai_genius_log('Admin menu added');
    }

    /**
     * Admin ayarlarını kaydet
     * 
     * @since 1.0.0
     */
    public function register_settings() {
        
        // Genel ayarlar grubu
        register_setting(
            'ai_genius_settings',
            'ai_genius_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            )
        );
        
        register_setting(
            'ai_genius_settings',
            'ai_genius_api_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'openai'
            )
        );
        
        register_setting(
            'ai_genius_settings',
            'ai_genius_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-3.5-turbo'
            )
        );
        
        register_setting(
            'ai_genius_settings',
            'ai_genius_max_tokens',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'intval',
                'default' => 500
            )
        );
        
        register_setting(
            'ai_genius_settings',
            'ai_genius_temperature',
            array(
                'type' => 'number',
                'sanitize_callback' => 'floatval',
                'default' => 0.7
            )
        );
        
        // Bot ayarları grubu
        register_setting(
            'ai_genius_bot_settings',
            'ai_genius_bot_settings',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_bot_settings'),
                'default' => array()
            )
        );
        
        ai_genius_log('Settings registered');
    }

    /**
     * Bot ayarlarını sanitize et
     * 
     * @since 1.0.0
     * @param array $input Gelen veriler
     * @return array
     */
    public function sanitize_bot_settings($input) {
        
        $sanitized = array();
        
        if (isset($input['name'])) {
            $sanitized['name'] = sanitize_text_field($input['name']);
        }
        
        if (isset($input['personality'])) {
            $sanitized['personality'] = sanitize_textarea_field($input['personality']);
        }
        
        if (isset($input['welcome_message'])) {
            $sanitized['welcome_message'] = sanitize_textarea_field($input['welcome_message']);
        }
        
        if (isset($input['fallback_message'])) {
            $sanitized['fallback_message'] = sanitize_textarea_field($input['fallback_message']);
        }
        
        if (isset($input['theme'])) {
            $sanitized['theme'] = sanitize_text_field($input['theme']);
        }
        
        if (isset($input['position'])) {
            $sanitized['position'] = sanitize_text_field($input['position']);
        }
        
        if (isset($input['avatar'])) {
            $sanitized['avatar'] = esc_url_raw($input['avatar']);
        }
        
        return $sanitized;
    }

    /**
     * Admin init işlemleri
     * 
     * @since 1.0.0
     */
    public function admin_init() {
        
        // Plugin veritabanı güncellemelerini kontrol et
        $this->maybe_update_database();
        
        // Kullanıcı yetkilerini kontrol et
        $this->check_user_capabilities();
        
        ai_genius_log('Admin initialized');
    }

    /**
     * Veritabanı güncellemelerini kontrol et
     * 
     * @since 1.0.0
     */
    private function maybe_update_database() {
        
        $current_db_version = get_option('ai_genius_db_version', '0.0.0');
        
        if (version_compare($current_db_version, AI_GENIUS_DB_VERSION, '<')) {
            
            // Veritabanı güncellemesi gerekli
            require_once AI_GENIUS_PLUGIN_DIR . 'includes/class-activator.php';
            AI_Genius_Activator::activate();
            
            update_option('ai_genius_db_version', AI_GENIUS_DB_VERSION);
            
            ai_genius_log('Database updated from ' . $current_db_version . ' to ' . AI_GENIUS_DB_VERSION);
        }
    }

    /**
     * Kullanıcı yetkilerini kontrol et
     * 
     * @since 1.0.0
     */
    private function check_user_capabilities() {
        
        // Admin rolünde AI-Genius yetkileri yoksa ekle
        $admin_role = get_role('administrator');
        if ($admin_role && !$admin_role->has_cap('manage_ai_genius')) {
            $admin_role->add_cap('manage_ai_genius');
            $admin_role->add_cap('view_ai_genius_analytics');
            $admin_role->add_cap('manage_ai_genius_data');
            $admin_role->add_cap('configure_ai_genius_bot');
        }
    }

    /**
     * Ana dashboard sayfasını göster
     * 
     * @since 1.0.0
     */
    public function display_admin_page() {
        include_once AI_GENIUS_PLUGIN_DIR . 'admin/partials/admin-display.php';
    }

    /**
     * Bot ayarları sayfasını göster
     * 
     * @since 1.0.0
     */
    public function display_bot_settings_page() {
        include_once AI_GENIUS_PLUGIN_DIR . 'admin/partials/bot-settings-display.php';
    }

    /**
     * Veri yönetimi sayfasını göster
     * 
     * @since 1.0.0
     */
    public function display_data_management_page() {
        include_once AI_GENIUS_PLUGIN_DIR . 'admin/partials/data-display.php';
    }

    /**
     * Analitik sayfasını göster
     * 
     * @since 1.0.0
     */
    public function display_analytics_page() {
        include_once AI_GENIUS_PLUGIN_DIR . 'admin/partials/analytics-display.php';
    }

    /**
     * Ayarlar sayfasını göster
     * 
     * @since 1.0.0
     */
    public function display_settings_page() {
        include_once AI_GENIUS_PLUGIN_DIR . 'admin/partials/settings-display.php';
    }

    /**
     * Lisans sayfasını göster
     * 
     * @since 1.0.0
     */
    public function display_license_page() {
        include_once AI_GENIUS_PLUGIN_DIR . 'admin/partials/license-display.php';
    }

    /**
     * Yardım sayfasını göster
     * 
     * @since 1.0.0
     */
    public function display_help_page() {
        include_once AI_GENIUS_PLUGIN_DIR . 'admin/partials/help-display.php';
    }

    /**
     * Dashboard widget'ları ekle
     * 
     * @since 1.0.0
     */
    public function add_dashboard_widgets() {
        
        // Sadece yetki sahibi kullanıcılar için
        if (!current_user_can('view_ai_genius_analytics')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'ai_genius_dashboard_widget',
            __('AI-Genius İstatistikleri', 'ai-genius'),
            array($this, 'display_dashboard_widget')
        );
        
        ai_genius_log('Dashboard widgets added');
    }

    /**
     * Dashboard widget içeriğini göster
     * 
     * @since 1.0.0
     */
    public function display_dashboard_widget() {
        
        // Günlük istatistikleri al
        $today = current_time('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $today_chats = $this->database->get_analytics('daily_chats', $today, $today);
        $yesterday_chats = $this->database->get_analytics('daily_chats', $yesterday, $yesterday);
        
        $today_count = !empty($today_chats) ? $today_chats[0]['metric_value'] : 0;
        $yesterday_count = !empty($yesterday_chats) ? $yesterday_chats[0]['metric_value'] : 0;
        
        $change_percent = $yesterday_count > 0 ? (($today_count - $yesterday_count) / $yesterday_count) * 100 : 0;
        
        ?>
        <div class="ai-genius-dashboard-widget">
            <div class="widget-stats">
                <div class="stat-item">
                    <h4><?php _e('Bugünkü Sohbetler', 'ai-genius'); ?></h4>
                    <span class="stat-number"><?php echo number_format($today_count); ?></span>
                    <?php if ($change_percent != 0): ?>
                        <span class="stat-change <?php echo $change_percent > 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $change_percent > 0 ? '+' : ''; ?><?php echo number_format($change_percent, 1); ?>%
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="stat-item">
                    <h4><?php _e('Bot Durumu', 'ai-genius'); ?></h4>
                    <?php
                    $bot_settings = $this->database->get_bot_settings();
                    $is_active = !empty($bot_settings) && $bot_settings['is_active'];
                    ?>
                    <span class="status-indicator <?php echo $is_active ? 'active' : 'inactive'; ?>">
                        <?php echo $is_active ? __('Aktif', 'ai-genius') : __('Pasif', 'ai-genius'); ?>
                    </span>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=ai-genius'); ?>" class="button button-primary">
                    <?php _e('Detaylı İstatistikler', 'ai-genius'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=ai-genius-bot-settings'); ?>" class="button">
                    <?php _e('Bot Ayarları', 'ai-genius'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Admin notice'ları göster
     * 
     * @since 1.0.0
     */
    public function display_admin_notices() {
        
        // Sadece AI-Genius sayfalarında göster
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'ai-genius') === false) {
            return;
        }
        
        // API anahtarı eksikse uyarı
        $api_key = get_option('ai_genius_api_key', '');
        if (empty($api_key)) {
            echo '<div class="notice notice-warning is-dismissible">
                <p><strong>AI-Genius:</strong> ' . 
                sprintf(
                    __('API anahtarınızı ayarlamadınız. <a href="%s">Ayarlara git</a>', 'ai-genius'),
                    admin_url('admin.php?page=ai-genius-settings')
                ) . '</p>
            </div>';
        }
        
        // Veritabanı tabloları eksikse uyarı
        $table_status = $this->database->check_tables_exist();
        $missing_tables = array_filter($table_status, function($exists) { return !$exists; });
        
        if (!empty($missing_tables)) {
            echo '<div class="notice notice-error">
                <p><strong>AI-Genius:</strong> ' . 
                __('Bazı veritabanı tabloları eksik. Plugin\'i deaktive edip tekrar aktive edin.', 'ai-genius') . 
                '</p>
            </div>';
        }
        
        // Başarı mesajları
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
            echo '<div class="notice notice-success is-dismissible">
                <p>' . __('Ayarlar başarıyla kaydedildi.', 'ai-genius') . '</p>
            </div>';
        }
    }

    /**
     * AJAX: Ayarları kaydet
     * 
     * @since 1.0.0
     */
    public function save_settings() {
        
        // Nonce ve yetki kontrolü
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce') || 
            !current_user_can('manage_ai_genius')) {
            wp_send_json_error('Yetkiniz bulunmuyor.');
        }
        
        $settings_type = sanitize_text_field($_POST['settings_type'] ?? '');
        $settings_data = $_POST['settings_data'] ?? array();
        
        switch ($settings_type) {
            case 'general':
                $this->save_general_settings($settings_data);
                break;
                
            case 'bot':
                $this->save_bot_settings($settings_data);
                break;
                
            case 'api':
                $this->save_api_settings($settings_data);
                break;
                
            default:
                wp_send_json_error('Geçersiz ayar türü.');
        }
        
        wp_send_json_success('Ayarlar başarıyla kaydedildi.');
    }

    /**
     * Genel ayarları kaydet
     * 
     * @since 1.0.0
     * @param array $data Ayar verileri
     */
    private function save_general_settings($data) {
        
        $allowed_settings = array(
            'ai_genius_language' => 'sanitize_text_field',
            'ai_genius_enable_logging' => 'rest_sanitize_boolean',
            'ai_genius_enable_analytics' => 'rest_sanitize_boolean',
            'ai_genius_auto_learn' => 'rest_sanitize_boolean',
            'ai_genius_rate_limit' => 'intval',
            'ai_genius_session_timeout' => 'intval'
        );
        
        foreach ($allowed_settings as $setting => $sanitize_callback) {
            if (isset($data[$setting])) {
                $value = call_user_func($sanitize_callback, $data[$setting]);
                update_option($setting, $value);
            }
        }
        
        ai_genius_log('General settings saved');
    }

    /**
     * Bot ayarlarını kaydet
     * 
     * @since 1.0.0
     * @param array $data Bot verileri
     */
    private function save_bot_settings($data) {
        
        $bot_settings = $this->sanitize_bot_settings($data);
        $result = $this->database->update_bot_settings($bot_settings);
        
        if (!$result) {
            wp_send_json_error('Bot ayarları kaydedilemedi.');
        }
        
        ai_genius_log('Bot settings saved');
    }

    /**
     * API ayarlarını kaydet
     * 
     * @since 1.0.0
     * @param array $data API verileri
     */
    private function save_api_settings($data) {
        
        $allowed_settings = array(
            'ai_genius_api_key' => 'sanitize_text_field',
            'ai_genius_api_provider' => 'sanitize_text_field',
            'ai_genius_model' => 'sanitize_text_field',
            'ai_genius_max_tokens' => 'intval',
            'ai_genius_temperature' => 'floatval'
        );
        
        foreach ($allowed_settings as $setting => $sanitize_callback) {
            if (isset($data[$setting])) {
                $value = call_user_func($sanitize_callback, $data[$setting]);
                update_option($setting, $value);
            }
        }
        
        ai_genius_log('API settings saved');
    }

    /**
     * AJAX: Veri yükle
     * 
     * @since 1.0.0
     */
    public function upload_data() {
        
        // Nonce ve yetki kontrolü
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce') || 
            !current_user_can('manage_ai_genius_data')) {
            wp_send_json_error('Yetkiniz bulunmuyor.');
        }
        
        // Dosya yükleme kontrolü
        if (!isset($_FILES['data_file']) || $_FILES['data_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Dosya yüklenirken hata oluştu.');
        }
        
        $file = $_FILES['data_file'];
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Desteklenen dosya türleri
        $allowed_types = array('csv', 'json', 'xml', 'txt');
        if (!in_array($file_type, $allowed_types)) {
            wp_send_json_error('Desteklenmeyen dosya türü.');
        }
        
        // Dosyayı işle
        $result = $this->process_uploaded_file($file, $file_type);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Yüklenen dosyayı işle
     * 
     * @since 1.0.0
     * @param array $file Dosya bilgileri
     * @param string $file_type Dosya türü
     * @return array
     */
    private function process_uploaded_file($file, $file_type) {
        
        $content = file_get_contents($file['tmp_name']);
        $processed_count = 0;
        
        switch ($file_type) {
            case 'csv':
                $result = $this->process_csv_data($content);
                break;
                
            case 'json':
                $result = $this->process_json_data($content);
                break;
                
            case 'xml':
                $result = $this->process_xml_data($content);
                break;
                
            case 'txt':
                $result = $this->process_text_data($content);
                break;
                
            default:
                return array(
                    'success' => false,
                    'message' => 'Desteklenmeyen dosya türü.'
                );
        }
        
        return $result;
    }

    /**
     * CSV verilerini işle
     * 
     * @since 1.0.0
     * @param string $content Dosya içeriği
     * @return array
     */
    private function process_csv_data($content) {
        
        $lines = array_map('str_getcsv', explode("\n", $content));
        $header = array_shift($lines);
        $processed_count = 0;
        
        foreach ($lines as $row) {
            if (empty($row) || count($row) < 2) continue;
            
            $data = array(
                'title' => $row[0] ?? '',
                'content' => $row[1] ?? '',
                'keywords' => $row[2] ?? '',
                'category' => $row[3] ?? 'genel',
                'source_type' => 'csv_import'
            );
            
            if ($this->database->add_knowledge_entry($data)) {
                $processed_count++;
            }
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('%d kayıt başarıyla eklendi.', 'ai-genius'), $processed_count)
        );
    }

    /**
     * JSON verilerini işle
     * 
     * @since 1.0.0
     * @param string $content Dosya içeriği
     * @return array
     */
    private function process_json_data($content) {
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'message' => 'Geçersiz JSON formatı.'
            );
        }
        
        $processed_count = 0;
        
        foreach ($data as $item) {
            $entry_data = array(
                'title' => $item['title'] ?? '',
                'content' => $item['content'] ?? '',
                'keywords' => $item['keywords'] ?? '',
                'category' => $item['category'] ?? 'genel',
                'source_type' => 'json_import'
            );
            
            if ($this->database->add_knowledge_entry($entry_data)) {
                $processed_count++;
            }
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('%d kayıt başarıyla eklendi.', 'ai-genius'), $processed_count)
        );
    }

    /**
     * XML verilerini işle
     * 
     * @since 1.0.0
     * @param string $content Dosya içeriği
     * @return array
     */
    private function process_xml_data($content) {
        
        $xml = simplexml_load_string($content);
        
        if ($xml === false) {
            return array(
                'success' => false,
                'message' => 'Geçersiz XML formatı.'
            );
        }
        
        $processed_count = 0;
        
        foreach ($xml->item as $item) {
            $entry_data = array(
                'title' => (string)$item->title,
                'content' => (string)$item->content,
                'keywords' => (string)$item->keywords,
                'category' => (string)$item->category ?: 'genel',
                'source_type' => 'xml_import'
            );
            
            if ($this->database->add_knowledge_entry($entry_data)) {
                $processed_count++;
            }
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('%d kayıt başarıyla eklendi.', 'ai-genius'), $processed_count)
        );
    }

    /**
     * Metin verilerini işle
     * 
     * @since 1.0.0
     * @param string $content Dosya içeriği
     * @return array
     */
    private function process_text_data($content) {
        
        // Basit metin dosyasını paragraflara böl
        $paragraphs = explode("\n\n", $content);
        $processed_count = 0;
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph) || strlen($paragraph) < 10) continue;
            
            // İlk satırı başlık olarak al
            $lines = explode("\n", $paragraph);
            $title = array_shift($lines);
            $content_text = implode("\n", $lines) ?: $paragraph;
            
            $entry_data = array(
                'title' => substr($title, 0, 100),
                'content' => $content_text,
                'keywords' => '',
                'category' => 'genel',
                'source_type' => 'text_import'
            );
            
            if ($this->database->add_knowledge_entry($entry_data)) {
                $processed_count++;
            }
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('%d kayıt başarıyla eklendi.', 'ai-genius'), $processed_count)
        );
    }

    /**
     * AJAX: Veri export et
     * 
     * @since 1.0.0
     */
    public function export_data() {
        
        // Nonce ve yetki kontrolü
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce') || 
            !current_user_can('manage_ai_genius_data')) {
            wp_send_json_error('Yetkiniz bulunmuyor.');
        }
        
        $export_type = sanitize_text_field($_POST['export_type'] ?? 'csv');
        $data_type = sanitize_text_field($_POST['data_type'] ?? 'knowledge_base');
        
        // Export dosyası oluştur
        $result = $this->create_export_file($data_type, $export_type);
        
        if ($result['success']) {
            wp_send_json_success(array(
                'download_url' => $result['download_url'],
                'filename' => $result['filename']
            ));
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * Export dosyası oluştur
     * 
     * @since 1.0.0
     * @param string $data_type Veri türü
     * @param string $export_type Export formatı
     * @return array
     */
    private function create_export_file($data_type, $export_type) {
        
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/ai-genius/exports';
        
        // Export klasörü yoksa oluştur
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        $filename = 'ai-genius-' . $data_type . '-' . date('Y-m-d-H-i-s') . '.' . $export_type;
        $filepath = $export_dir . '/' . $filename;
        
        // Verileri al
        $data = $this->get_export_data($data_type);
        
        if (empty($data)) {
            return array(
                'success' => false,
                'message' => 'Export edilecek veri bulunamadı.'
            );
        }
        
        // Dosya formatına göre export et
        $success = false;
        
        switch ($export_type) {
            case 'csv':
                $success = $this->export_to_csv($data, $filepath);
                break;
                
            case 'json':
                $success = $this->export_to_json($data, $filepath);
                break;
                
            case 'xml':
                $success = $this->export_to_xml($data, $filepath);
                break;
        }
        
        if ($success) {
            $download_url = $upload_dir['baseurl'] . '/ai-genius/exports/' . $filename;
            
            return array(
                'success' => true,
                'download_url' => $download_url,
                'filename' => $filename
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Export dosyası oluşturulamadı.'
            );
        }
    }

    /**
     * Export verilerini al
     * 
     * @since 1.0.0
     * @param string $data_type Veri türü
     * @return array
     */
    private function get_export_data($data_type) {
        
        global $wpdb;
        
        switch ($data_type) {
            case 'knowledge_base':
                $table = $wpdb->prefix . 'ai_genius_knowledge_base';
                return $wpdb->get_results("SELECT * FROM $table WHERE is_active = 1", ARRAY_A);
                
            case 'chat_history':
                $table = $wpdb->prefix . 'ai_genius_chat_history';
                return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 1000", ARRAY_A);
                
            case 'analytics':
                $table = $wpdb->prefix . 'ai_genius_analytics';
                return $wpdb->get_results("SELECT * FROM $table ORDER BY metric_date DESC", ARRAY_A);
                
            default:
                return array();
        }
    }

    /**
     * CSV formatında export et
     * 
     * @since 1.0.0
     * @param array $data Veriler
     * @param string $filepath Dosya yolu
     * @return bool
     */
    private function export_to_csv($data, $filepath) {
        
        $file = fopen($filepath, 'w');
        
        if (!$file) {
            return false;
        }
        
        // UTF-8 BOM ekle
        fwrite($file, "\xEF\xBB\xBF");
        
        // Header satırını yaz
        if (!empty($data)) {
            fputcsv($file, array_keys($data[0]));
            
            // Veri satırlarını yaz
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
        }
        
        fclose($file);
        return true;
    }

    /**
     * JSON formatında export et
     * 
     * @since 1.0.0
     * @param array $data Veriler
     * @param string $filepath Dosya yolu
     * @return bool
     */
    private function export_to_json($data, $filepath) {
        
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($filepath, $json) !== false;
    }

    /**
     * XML formatında export et
     * 
     * @since 1.0.0
     * @param array $data Veriler
     * @param string $filepath Dosya yolu
     * @return bool
     */
    private function export_to_xml($data, $filepath) {
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><data></data>');
        
        foreach ($data as $item) {
            $entry = $xml->addChild('item');
            foreach ($item as $key => $value) {
                $entry->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML($filepath) !== false;
    }

    /**
     * AJAX: API bağlantısını test et
     * 
     * @since 1.0.0
     */
    public function test_api_connection() {
        
        // Nonce ve yetki kontrolü
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce') || 
            !current_user_can('manage_ai_genius')) {
            wp_send_json_error('Yetkiniz bulunmuyor.');
        }
        
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $api_provider = sanitize_text_field($_POST['api_provider'] ?? '');
        
        if (empty($api_key)) {
            wp_send_json_error('API anahtarı gereklidir.');
        }
        
        // API bağlantısını test et
        require_once AI_GENIUS_PLUGIN_DIR . 'api/class-openai-connector.php';
        
        $connector = new AI_Genius_OpenAI_Connector($api_key);
        $test_result = $connector->test_connection();
        
        if ($test_result['success']) {
            wp_send_json_success('API bağlantısı başarılı.');
        } else {
            wp_send_json_error('API bağlantısı başarısız: ' . $test_result['message']);
        }
    }
}