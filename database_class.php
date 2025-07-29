<?php
/**
 * Veritabanı yönetimi sınıfı
 * 
 * Plugin'in veritabanı işlemlerini yöneten sınıf
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
 * Veritabanı yönetimi sınıfı
 * 
 * CRUD işlemlerini ve veritabanı sorgularını yönetir
 */
class AI_Genius_Database {

    /**
     * WordPress veritabanı nesnesi
     * 
     * @since 1.0.0
     * @var wpdb
     */
    private $wpdb;

    /**
     * Tablo isimleri
     * 
     * @since 1.0.0
     * @var array
     */
    private $tables;

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Tablo isimlerini tanımla
        $this->tables = array(
            'chat_history' => $this->wpdb->prefix . 'ai_genius_chat_history',
            'knowledge_base' => $this->wpdb->prefix . 'ai_genius_knowledge_base',
            'bot_settings' => $this->wpdb->prefix . 'ai_genius_bot_settings',
            'analytics' => $this->wpdb->prefix . 'ai_genius_analytics',
            'user_sessions' => $this->wpdb->prefix . 'ai_genius_user_sessions'
        );
        
        ai_genius_log('Database class initialized');
    }

    // ==================== CHAT HISTORY METHODS ====================

    /**
     * Sohbet geçmişi kaydet
     * 
     * @since 1.0.0
     * @param array $data Sohbet verisi
     * @return int|false Insert ID veya false
     */
    public function save_chat_history($data) {
        
        $defaults = array(
            'session_id' => '',
            'user_id' => null,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_message' => '',
            'bot_response' => '',
            'message_type' => 'text',
            'sentiment_score' => null,
            'response_time' => null,
            'is_helpful' => null
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $this->wpdb->insert(
            $this->tables['chat_history'],
            $data,
            array('%s', '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%d')
        );
        
        if ($result === false) {
            ai_genius_log('Failed to save chat history: ' . $this->wpdb->last_error, 'error');
            return false;
        }
        
        ai_genius_log('Chat history saved with ID: ' . $this->wpdb->insert_id);
        return $this->wpdb->insert_id;
    }

    /**
     * Sohbet geçmişini getir
     * 
     * @since 1.0.0
     * @param string $session_id Oturum ID'si
     * @param int $limit Kayıt limiti
     * @return array|null
     */
    public function get_chat_history($session_id, $limit = 50) {
        
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$this->tables['chat_history']} 
            WHERE session_id = %s 
            ORDER BY created_at DESC 
            LIMIT %d",
            $session_id,
            $limit
        );
        
        $results = $this->wpdb->get_results($query, ARRAY_A);
        
        if ($this->wpdb->last_error) {
            ai_genius_log('Error getting chat history: ' . $this->wpdb->last_error, 'error');
            return null;
        }
        
        return array_reverse($results); // Eski önce göster
    }

    /**
     * Kullanıcı sohbet istatistiklerini getir
     * 
     * @since 1.0.0
     * @param int $user_id Kullanıcı ID'si
     * @return array
     */
    public function get_user_chat_stats($user_id) {
        
        $query = $this->wpdb->prepare(
            "SELECT 
                COUNT(*) as total_messages,
                COUNT(CASE WHEN is_helpful = 1 THEN 1 END) as helpful_responses,
                COUNT(CASE WHEN is_helpful = 0 THEN 1 END) as unhelpful_responses,
                AVG(response_time) as avg_response_time,
                MIN(created_at) as first_interaction,
                MAX(created_at) as last_interaction
            FROM {$this->tables['chat_history']} 
            WHERE user_id = %d",
            $user_id
        );
        
        return $this->wpdb->get_row($query, ARRAY_A);
    }

    // ==================== KNOWLEDGE BASE METHODS ====================

    /**
     * Bilgi tabanına kayıt ekle
     * 
     * @since 1.0.0
     * @param array $data Bilgi verisi
     * @return int|false
     */
    public function add_knowledge_entry($data) {
        
        $defaults = array(
            'title' => '',
            'content' => '',
            'keywords' => '',
            'category' => 'genel',
            'source_type' => 'manual',
            'source_id' => null,
            'priority' => 5,
            'is_active' => 1,
            'usage_count' => 0
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Anahtar kelimeleri işle
        if (is_array($data['keywords'])) {
            $data['keywords'] = implode(', ', $data['keywords']);
        }
        
        $result = $this->wpdb->insert(
            $this->tables['knowledge_base'],
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d')
        );
        
        if ($result === false) {
            ai_genius_log('Failed to add knowledge entry: ' . $this->wpdb->last_error, 'error');
            return false;
        }
        
        ai_genius_log('Knowledge entry added with ID: ' . $this->wpdb->insert_id);
        return $this->wpdb->insert_id;
    }

    /**
     * Bilgi tabanında arama yap
     * 
     * @since 1.0.0
     * @param string $query Arama sorgusu
     * @param int $limit Sonuç limiti
     * @return array
     */
    public function search_knowledge_base($query, $limit = 10) {
        
        // Fulltext arama kullan
        $search_query = $this->wpdb->prepare(
            "SELECT *, 
                MATCH(title, content, keywords) AGAINST(%s IN NATURAL LANGUAGE MODE) as relevance
            FROM {$this->tables['knowledge_base']} 
            WHERE is_active = 1 
                AND MATCH(title, content, keywords) AGAINST(%s IN NATURAL LANGUAGE MODE)
            ORDER BY relevance DESC, priority DESC, usage_count DESC
            LIMIT %d",
            $query,
            $query,
            $limit
        );
        
        $results = $this->wpdb->get_results($search_query, ARRAY_A);
        
        // Fulltext sonuç yoksa LIKE ile ara
        if (empty($results)) {
            $like_query = '%' . $this->wpdb->esc_like($query) . '%';
            $fallback_query = $this->wpdb->prepare(
                "SELECT * FROM {$this->tables['knowledge_base']} 
                WHERE is_active = 1 
                    AND (title LIKE %s OR content LIKE %s OR keywords LIKE %s)
                ORDER BY priority DESC, usage_count DESC
                LIMIT %d",
                $like_query,
                $like_query,
                $like_query,
                $limit
            );
            
            $results = $this->wpdb->get_results($fallback_query, ARRAY_A);
        }
        
        return $results;
    }

    /**
     * Bilgi tabanı kullanım sayısını artır
     * 
     * @since 1.0.0
     * @param int $knowledge_id Bilgi ID'si
     * @return bool
     */
    public function increment_knowledge_usage($knowledge_id) {
        
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->tables['knowledge_base']} 
                SET usage_count = usage_count + 1, last_used = NOW() 
                WHERE id = %d",
                $knowledge_id
            )
        );
        
        return $result !== false;
    }

    /**
     * Kategori listesini getir
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_knowledge_categories() {
        
        $query = "SELECT DISTINCT category, COUNT(*) as count 
                 FROM {$this->tables['knowledge_base']} 
                 WHERE is_active = 1 
                 GROUP BY category 
                 ORDER BY count DESC";
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }

    // ==================== BOT SETTINGS METHODS ====================

    /**
     * Bot ayarlarını getir
     * 
     * @since 1.0.0
     * @return array|null
     */
    public function get_bot_settings() {
        
        $query = "SELECT * FROM {$this->tables['bot_settings']} 
                 WHERE is_active = 1 
                 ORDER BY id DESC 
                 LIMIT 1";
        
        $result = $this->wpdb->get_row($query, ARRAY_A);
        
        // Cache'e kaydet
        if ($result) {
            set_transient('ai_genius_bot_settings', $result, HOUR_IN_SECONDS);
        }
        
        return $result;
    }

    /**
     * Bot ayarlarını güncelle
     * 
     * @since 1.0.0
     * @param array $settings Ayarlar
     * @return bool
     */
    public function update_bot_settings($settings) {
        
        // Önceki ayarları pasif yap
        $this->wpdb->update(
            $this->tables['bot_settings'],
            array('is_active' => 0),
            array('is_active' => 1)
        );
        
        // Yeni ayarları ekle
        $defaults = array(
            'bot_name' => 'AI Asistan',
            'bot_personality' => '',
            'bot_avatar' => '',
            'welcome_message' => '',
            'fallback_message' => '',
            'chat_theme' => 'default',
            'chat_position' => 'bottom-right',
            'is_active' => 1
        );
        
        $settings = wp_parse_args($settings, $defaults);
        
        $result = $this->wpdb->insert(
            $this->tables['bot_settings'],
            $settings,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        // Cache'i temizle
        delete_transient('ai_genius_bot_settings');
        
        return $result !== false;
    }

    // ==================== ANALYTICS METHODS ====================

    /**
     * Analitik veri kaydet
     * 
     * @since 1.0.0
     * @param string $metric_name Metrik adı
     * @param float $metric_value Metrik değeri
     * @param string $date Tarih (Y-m-d formatında)
     * @param array $additional_data Ek veriler
     * @return bool
     */
    public function save_analytics($metric_name, $metric_value, $date = null, $additional_data = null) {
        
        if (!$date) {
            $date = current_time('Y-m-d');
        }
        
        $data = array(
            'metric_name' => $metric_name,
            'metric_value' => $metric_value,
            'metric_date' => $date,
            'additional_data' => $additional_data ? json_encode($additional_data) : null
        );
        
        // Mevcut kaydı güncelle veya yeni kayıt ekle
        $existing = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT id FROM {$this->tables['analytics']} 
                WHERE metric_name = %s AND metric_date = %s",
                $metric_name,
                $date
            )
        );
        
        if ($existing) {
            return $this->wpdb->update(
                $this->tables['analytics'],
                $data,
                array('id' => $existing),
                array('%s', '%f', '%s', '%s'),
                array('%d')
            ) !== false;
        } else {
            return $this->wpdb->insert(
                $this->tables['analytics'],
                $data,
                array('%s', '%f', '%s', '%s')
            ) !== false;
        }
    }

    /**
     * Analitik verilerini getir
     * 
     * @since 1.0.0
     * @param string $metric_name Metrik adı
     * @param string $start_date Başlangıç tarihi
     * @param string $end_date Bitiş tarihi
     * @return array
     */
    public function get_analytics($metric_name = null, $start_date = null, $end_date = null) {
        
        $where_conditions = array();
        $prepare_values = array();
        
        if ($metric_name) {
            $where_conditions[] = "metric_name = %s";
            $prepare_values[] = $metric_name;
        }
        
        if ($start_date) {
            $where_conditions[] = "metric_date >= %s";
            $prepare_values[] = $start_date;
        }
        
        if ($end_date) {
            $where_conditions[] = "metric_date <= %s";
            $prepare_values[] = $end_date;
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        $query = "SELECT * FROM {$this->tables['analytics']} 
                 $where_clause 
                 ORDER BY metric_date DESC, metric_name ASC";
        
        if (!empty($prepare_values)) {
            $query = $this->wpdb->prepare($query, ...$prepare_values);
        }
        
        return $this->wpdb->get_results($query, ARRAY_A);
    }

    // ==================== USER SESSIONS METHODS ====================

    /**
     * Kullanıcı oturumu başlat
     * 
     * @since 1.0.0
     * @param string $session_id Oturum ID'si
     * @param array $data Oturum verisi
     * @return int|false
     */
    public function start_user_session($session_id, $data = array()) {
        
        $defaults = array(
            'session_id' => $session_id,
            'user_id' => get_current_user_id() ?: null,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'message_count' => 0,
            'is_active' => 1
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $this->wpdb->insert(
            $this->tables['user_sessions'],
            $data,
            array('%s', '%d', '%s', '%s', '%s', '%d', '%d')
        );
        
        return $result !== false ? $this->wpdb->insert_id : false;
    }

    /**
     * Kullanıcı oturumunu güncelle
     * 
     * @since 1.0.0
     * @param string $session_id Oturum ID'si
     * @param array $updates Güncellenecek veriler
     * @return bool
     */
    public function update_user_session($session_id, $updates) {
        
        $result = $this->wpdb->update(
            $this->tables['user_sessions'],
            $updates,
            array('session_id' => $session_id, 'is_active' => 1),
            null,
            array('%s', '%d')
        );
        
        return $result !== false;
    }

    /**
     * Kullanıcı oturumunu sonlandır
     * 
     * @since 1.0.0
     * @param string $session_id Oturum ID'si
     * @return bool
     */
    public function end_user_session($session_id) {
        
        return $this->update_user_session($session_id, array(
            'is_active' => 0,
            'session_end' => current_time('mysql')
        ));
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Veritabanı tabloları mevcut mu kontrol et
     * 
     * @since 1.0.0
     * @return array
     */
    public function check_tables_exist() {
        
        $status = array();
        
        foreach ($this->tables as $key => $table_name) {
            $query = $this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
            $status[$key] = $this->wpdb->get_var($query) === $table_name;
        }
        
        return $status;
    }

    /**
     * Tablo boyutlarını getir
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_table_sizes() {
        
        $sizes = array();
        
        foreach ($this->tables as $key => $table_name) {
            $query = $this->wpdb->prepare(
                "SELECT COUNT(*) as row_count,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                 FROM information_schema.tables 
                 WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $table_name
            );
            
            $result = $this->wpdb->get_row($query, ARRAY_A);
            $sizes[$key] = $result ?: array('row_count' => 0, 'size_mb' => 0);
        }
        
        return $sizes;
    }

    /**
     * Eski kayıtları temizle
     * 
     * @since 1.0.0
     * @param int $days Gün sayısı
     * @return array
     */
    public function cleanup_old_records($days = 30) {
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $deleted = array();
        
        // Chat history temizliği (yardımcı puanı olmayan kayıtlar)
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['chat_history']} 
                WHERE created_at < %s AND is_helpful IS NULL",
                $cutoff_date
            )
        );
        $deleted['chat_history'] = $result;
        
        // Eski oturumları temizle
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['user_sessions']} 
                WHERE session_start < %s AND is_active = 0",
                $cutoff_date
            )
        );
        $deleted['user_sessions'] = $result;
        
        // Eski analitik verilerini temizle (1 yıldan eski)
        $old_date = date('Y-m-d', strtotime('-1 year'));
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$this->tables['analytics']} 
                WHERE metric_date < %s",
                $old_date
            )
        );
        $deleted['analytics'] = $result;
        
        ai_genius_log('Database cleanup completed. Deleted records: ' . json_encode($deleted));
        
        return $deleted;
    }

    /**
     * Tablo isimlerini döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_table_names() {
        return $this->tables;
    }

    /**
     * Veritabanı hatalarını kontrol et
     * 
     * @since 1.0.0
     * @return string|null
     */
    public function get_last_error() {
        return $this->wpdb->last_error ?: null;
    }
}