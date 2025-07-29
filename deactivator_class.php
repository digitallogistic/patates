<?php
/**
 * Plugin deaktivasyon işlemlerini yöneten sınıf
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
 * Plugin deaktivasyon sınıfı
 * 
 * Plugin deaktive edildiğinde çalışacak işlemler:
 * - Cron job'ları temizle
 * - Geçici verileri temizle
 * - Cache'i temizle
 * - NOT: Kalıcı verileri silmez (veritabanı tabloları, kullanıcı ayarları)
 */
class AI_Genius_Deactivator {

    /**
     * Plugin deaktivasyon ana fonksiyonu
     * 
     * @since 1.0.0
     */
    public static function deactivate() {
        
        // Yetki kontrolü
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Plugin güvenlik kontrolü
        $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
        check_admin_referer("deactivate-plugin_{$plugin}");
        
        // Deaktivasyon işlemlerini başlat
        self::clear_scheduled_hooks();
        self::clear_temporary_data();
        self::clear_cache();
        self::stop_active_sessions();
        
        // Deaktivasyon zamanını kaydet
        update_option('ai_genius_deactivated_time', current_time('timestamp'));
        
        // Log kaydı
        ai_genius_log('Plugin deactivated successfully');
    }

    /**
     * Zamanlanmış cron job'ları temizle
     * 
     * @since 1.0.0
     */
    private static function clear_scheduled_hooks() {
        
        // Zamanlanmış event'leri iptal et
        wp_clear_scheduled_hook('ai_genius_daily_analytics');
        wp_clear_scheduled_hook('ai_genius_weekly_cleanup');
        wp_clear_scheduled_hook('ai_genius_monthly_backup');
        wp_clear_scheduled_hook('ai_genius_session_cleanup');
        wp_clear_scheduled_hook('ai_genius_log_rotation');
        
        ai_genius_log('Scheduled hooks cleared successfully');
    }

    /**
     * Geçici verileri temizle
     * 
     * @since 1.0.0
     */
    private static function clear_temporary_data() {
        global $wpdb;
        
        // 24 saatten eski geçici session verilerini temizle
        $sessions_table = $wpdb->prefix . 'ai_genius_user_sessions';
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $sessions_table 
                SET is_active = 0, session_end = %s 
                WHERE is_active = 1 AND session_start < %s",
                current_time('mysql'),
                date('Y-m-d H:i:s', strtotime('-24 hours'))
            )
        );
        
        // Geçici cache dosyalarını temizle
        self::clear_temp_files();
        
        ai_genius_log('Temporary data cleared successfully');
    }

    /**
     * Plugin cache'ini temizle
     * 
     * @since 1.0.0
     */
    private static function clear_cache() {
        
        // WordPress cache'ini temizle
        wp_cache_flush();
        
        // Plugin özel cache'ini temizle
        delete_transient('ai_genius_knowledge_cache');
        delete_transient('ai_genius_analytics_cache');
        delete_transient('ai_genius_bot_settings_cache');
        
        // Tüm AI-Genius ile başlayan transient'ları temizle
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_ai_genius_%' 
            OR option_name LIKE '_transient_timeout_ai_genius_%'"
        );
        
        ai_genius_log('Cache cleared successfully');
    }

    /**
     * Aktif oturumları durdur
     * 
     * @since 1.0.0
     */
    private static function stop_active_sessions() {
        global $wpdb;
        
        // Tüm aktif oturumları kapat
        $sessions_table = $wpdb->prefix . 'ai_genius_user_sessions';
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $sessions_table 
                SET is_active = 0, session_end = %s 
                WHERE is_active = 1",
                current_time('mysql')
            )
        );
        
        ai_genius_log('Active sessions stopped successfully');
    }

    /**
     * Geçici dosyaları temizle
     * 
     * @since 1.0.0
     */
    private static function clear_temp_files() {
        
        $upload_dir = wp_upload_dir();
        $ai_genius_dir = $upload_dir['basedir'] . '/ai-genius';
        
        // Log dosyalarını arşivle
        $logs_dir = $ai_genius_dir . '/logs';
        if (is_dir($logs_dir)) {
            $log_files = glob($logs_dir . '/*.log');
            if (!empty($log_files)) {
                $archive_dir = $ai_genius_dir . '/logs/archived';
                if (!is_dir($archive_dir)) {
                    wp_mkdir_p($archive_dir);
                }
                
                foreach ($log_files as $log_file) {
                    $filename = basename($log_file);
                    $archived_name = 'deactivated_' . date('Y-m-d_H-i-s') . '_' . $filename;
                    rename($log_file, $archive_dir . '/' . $archived_name);
                }
            }
        }
        
        // Geçici export dosyalarını temizle
        $exports_dir = $ai_genius_dir . '/exports';
        if (is_dir($exports_dir)) {
            $export_files = glob($exports_dir . '/temp_*.csv');
            foreach ($export_files as $export_file) {
                if (file_exists($export_file)) {
                    unlink($export_file);
                }
            }
        }
        
        ai_genius_log('Temporary files cleared successfully');
    }
}