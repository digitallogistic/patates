<?php
/**
 * Plugin aktivasyon işlemlerini yöneten sınıf
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
 * Plugin aktivasyon sınıfı
 * 
 * Plugin aktive edildiğinde çalışacak tüm işlemleri içerir:
 * - Veritabanı tablolarının oluşturulması
 * - Varsayılan ayarların tanımlanması
 * - Gerekli klasörlerin oluşturulması
 * - Başlangıç verilerinin eklenmesi
 */
class AI_Genius_Activator {

    /**
     * Plugin aktivasyon ana fonksiyonu
     * 
     * @since 1.0.0
     */
    public static function activate() {
        
        // Yetki kontrolü
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Plugin güvenlik kontrolü
        $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
        check_admin_referer("activate-plugin_{$plugin}");
        
        // Aktivasyon işlemlerini başlat
        self::create_database_tables();
        self::create_default_options();
        self::create_upload_directories();
        self::insert_default_data();
        self::schedule_cron_jobs();
        self::create_capabilities();
        
        // Aktivasyon zamanını kaydet
        update_option('ai_genius_activated_time', current_time('timestamp'));
        update_option('ai_genius_db_version', AI_GENIUS_DB_VERSION);
        
        // Cache'i temizle
        wp_cache_flush();
        
        // Log kaydı
        ai_genius_log('Plugin activated successfully');
    }

    /**
     * Veritabanı tablolarını oluştur
     * 
     * @since 1.0.0
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sohbet geçmişi tablosu
        $chat_history_table = $wpdb->prefix . 'ai_genius_chat_history';
        $sql_chat_history = "CREATE TABLE $chat_history_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            user_ip varchar(45) NOT NULL,
            user_message text NOT NULL,
            bot_response text NOT NULL,
            message_type varchar(50) DEFAULT 'text',
            sentiment_score decimal(3,2) DEFAULT NULL,
            response_time decimal(10,3) DEFAULT NULL,
            is_helpful tinyint(1) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Bilgi tabanı tablosu
        $knowledge_base_table = $wpdb->prefix . 'ai_genius_knowledge_base';
        $sql_knowledge_base = "CREATE TABLE $knowledge_base_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            keywords text DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            source_type varchar(50) DEFAULT 'manual',
            source_id bigint(20) UNSIGNED DEFAULT NULL,
            priority tinyint(3) UNSIGNED DEFAULT 5,
            is_active tinyint(1) DEFAULT 1,
            usage_count int(11) DEFAULT 0,
            last_used datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY source_type (source_type),
            KEY is_active (is_active),
            KEY priority (priority),
            FULLTEXT KEY content_search (title, content, keywords)
        ) $charset_collate;";
        
        // Bot ayarları tablosu
        $bot_settings_table = $wpdb->prefix . 'ai_genius_bot_settings';
        $sql_bot_settings = "CREATE TABLE $bot_settings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            bot_name varchar(100) NOT NULL DEFAULT 'AI Asistan',
            bot_personality text DEFAULT NULL,
            bot_avatar varchar(255) DEFAULT NULL,
            welcome_message text DEFAULT NULL,
            fallback_message text DEFAULT NULL,
            chat_theme varchar(50) DEFAULT 'default',
            chat_position varchar(20) DEFAULT 'bottom-right',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Analytics tablosu
        $analytics_table = $wpdb->prefix . 'ai_genius_analytics';
        $sql_analytics = "CREATE TABLE $analytics_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            metric_name varchar(100) NOT NULL,
            metric_value decimal(15,4) NOT NULL,
            metric_date date NOT NULL,
            additional_data text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY metric_name (metric_name),
            KEY metric_date (metric_date),
            UNIQUE KEY unique_metric_date (metric_name, metric_date)
        ) $charset_collate;";
        
        // Kullanıcı oturumları tablosu
        $user_sessions_table = $wpdb->prefix . 'ai_genius_user_sessions';
        $sql_user_sessions = "CREATE TABLE $user_sessions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            user_ip varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            page_url varchar(500) DEFAULT NULL,
            session_start datetime DEFAULT CURRENT_TIMESTAMP,
            session_end datetime DEFAULT NULL,
            message_count int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_id (user_id),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Tablları oluştur
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_chat_history);
        dbDelta($sql_knowledge_base);
        dbDelta($sql_bot_settings);
        dbDelta($sql_analytics);
        dbDelta($sql_user_sessions);
        
        ai_genius_log('Database tables created successfully');
    }

    /**
     * Varsayılan seçenekleri oluştur
     * 
     * @since 1.0.0
     */
    private static function create_default_options() {
        
        // Genel ayarlar
        $default_options = array(
            'ai_genius_api_key' => '',
            'ai_genius_api_provider' => 'openai',
            'ai_genius_model' => 'gpt-3.5-turbo',
            'ai_genius_max_tokens' => 500,
            'ai_genius_temperature' => 0.7,
            'ai_genius_language' => 'tr',
            'ai_genius_enable_logging' => true,
            'ai_genius_enable_analytics' => true,
            'ai_genius_auto_learn' => false,
            'ai_genius_rate_limit' => 10,
            'ai_genius_session_timeout' => 30,
        );
        
        foreach ($default_options as $option => $value) {
            add_option($option, $value);
        }
        
        // Bot varsayılan ayarları
        $bot_defaults = array(
            'name' => 'AI Asistan',
            'personality' => 'Yardımsever, dostane ve bilgili bir asistan. Kullanıcılara her zaman kibarca ve profesyonelce yaklaşır.',
            'welcome_message' => 'Merhaba! Size nasıl yardımcı olabilirim?',
            'fallback_message' => 'Üzgünüm, bu konuda size yardımcı olamıyorum. Lütfen başka bir soru sormayı deneyin.',
            'theme' => 'default',
            'position' => 'bottom-right',
            'avatar' => AI_GENIUS_PLUGIN_URL . 'assets/images/default-avatar.png'
        );
        
        add_option('ai_genius_bot_settings', $bot_defaults);
        
        ai_genius_log('Default options created successfully');
    }

    /**
     * Upload klasörlerini oluştur
     * 
     * @since 1.0.0
     */
    private static function create_upload_directories() {
        
        $upload_dir = wp_upload_dir();
        $ai_genius_dir = $upload_dir['basedir'] . '/ai-genius';
        
        // Ana klasör
        if (!file_exists($ai_genius_dir)) {
            wp_mkdir_p($ai_genius_dir);
        }
        
        // Alt klasörler
        $subdirs = array('avatars', 'data', 'logs', 'exports', 'backups');
        
        foreach ($subdirs as $subdir) {
            $dir_path = $ai_genius_dir . '/' . $subdir;
            if (!file_exists($dir_path)) {
                wp_mkdir_p($dir_path);
            }
            
            // .htaccess güvenlik dosyası ekle
            $htaccess_path = $dir_path . '/.htaccess';
            if (!file_exists($htaccess_path)) {
                file_put_contents($htaccess_path, "deny from all\n");
            }
        }
        
        ai_genius_log('Upload directories created successfully');
    }

    /**
     * Varsayılan verileri ekle
     * 
     * @since 1.0.0
     */
    private static function insert_default_data() {
        global $wpdb;
        
        // Varsayılan bilgi tabanı girişleri
        $default_knowledge = array(
            array(
                'title' => 'Hoş Geldiniz',
                'content' => 'AI-Genius yapay zeka asistanına hoş geldiniz! Size nasıl yardımcı olabilirim?',
                'keywords' => 'hoş geldin, merhaba, selam, başlangıç',
                'category' => 'genel',
                'priority' => 10
            ),
            array(
                'title' => 'İletişim Bilgileri',
                'content' => 'Bize ulaşmak için iletişim sayfamızı ziyaret edebilir veya destek ekibimizle konuşabilirsiniz.',
                'keywords' => 'iletişim, telefon, email, adres, ulaşım',
                'category' => 'iletişim',
                'priority' => 8
            ),
            array(
                'title' => 'Çalışma Saatleri',
                'content' => 'Müşteri hizmetlerimiz hafta içi 09:00-18:00 saatleri arasında hizmet vermektedir.',
                'keywords' => 'çalışma saatleri, açık, kapalı, mesai',
                'category' => 'genel',
                'priority' => 7
            )
        );
        
        $knowledge_table = $wpdb->prefix . 'ai_genius_knowledge_base';
        
        foreach ($default_knowledge as $knowledge) {
            $wpdb->insert(
                $knowledge_table,
                $knowledge,
                array('%s', '%s', '%s', '%s', '%d')
            );
        }
        
        // Varsayılan bot ayarları
        $bot_table = $wpdb->prefix . 'ai_genius_bot_settings';
        $wpdb->insert(
            $bot_table,
            array(
                'bot_name' => 'AI Asistan',
                'bot_personality' => 'Yardımsever, dostane ve bilgili bir asistan.',
                'welcome_message' => 'Merhaba! Size nasıl yardımcı olabilirim?',
                'fallback_message' => 'Üzgünüm, bu konuda size yardımcı olamıyorum.',
                'chat_theme' => 'default',
                'chat_position' => 'bottom-right'
            )
        );
        
        ai_genius_log('Default data inserted successfully');
    }

    /**
     * Cron job'ları planla
     * 
     * @since 1.0.0
     */
    private static function schedule_cron_jobs() {
        
        // Günlük analitik raporu
        if (!wp_next_scheduled('ai_genius_daily_analytics')) {
            wp_schedule_event(time(), 'daily', 'ai_genius_daily_analytics');
        }
        
        // Haftalık veri temizliği
        if (!wp_next_scheduled('ai_genius_weekly_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'ai_genius_weekly_cleanup');
        }
        
        // Aylık yedekleme
        if (!wp_next_scheduled('ai_genius_monthly_backup')) {
            wp_schedule_event(time(), 'monthly', 'ai_genius_monthly_backup');
        }
        
        ai_genius_log('Cron jobs scheduled successfully');
    }

    /**
     * Kullanıcı yetkilerini oluştur
     * 
     * @since 1.0.0
     */
    private static function create_capabilities() {
        
        // Admin rolüne AI-Genius yetkilerini ekle
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_ai_genius');
            $admin_role->add_cap('view_ai_genius_analytics');
            $admin_role->add_cap('manage_ai_genius_data');
            $admin_role->add_cap('configure_ai_genius_bot');
        }
        
        // Editor rolüne sınırlı yetki ver
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('view_ai_genius_analytics');
            $editor_role->add_cap('manage_ai_genius_data');
        }
        
        ai_genius_log('User capabilities created successfully');
    }
}