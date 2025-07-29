<?php
/**
 * Envato lisans yönetimi sınıfı
 * 
 * CodeCanyon Envato Market API ile lisans doğrulama ve yönetimi
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
 * Lisans yönetimi sınıfı
 * 
 * Envato Market API ile entegrasyon sağlar
 * Satın alma kodu doğrulama ve lisans durumu kontrolü yapar
 */
class AI_Genius_License {

    /**
     * Envato Market API URL'i
     * 
     * @since 1.0.0
     * @var string
     */
    private $api_url = 'https://api.envato.com/v3/market/';

    /**
     * Item ID (CodeCanyon'daki ürün ID'si)
     * 
     * @since 1.0.0
     * @var string
     */
    private $item_id = 'ai-genius-wordpress-plugin'; // Gerçek ID ile değiştirilecek

    /**
     * Autor username
     * 
     * @since 1.0.0
     * @var string
     */
    private $author_username = 'ai-genius-team';

    /**
     * Lisans durumları
     * 
     * @since 1.0.0
     * @var array
     */
    private $license_statuses = array(
        'valid' => 'Geçerli',
        'invalid' => 'Geçersiz',
        'expired' => 'Süresi Dolmuş',
        'suspended' => 'Askıya Alınmış',
        'refunded' => 'İade Edilmiş'
    );

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        // Lisans kontrol cron job'ı
        add_action('ai_genius_license_check', array($this, 'scheduled_license_check'));
        
        // Günde bir kez lisans kontrolü yap
        if (!wp_next_scheduled('ai_genius_license_check')) {
            wp_schedule_event(time(), 'daily', 'ai_genius_license_check');
        }
        
        ai_genius_log('License class initialized');
    }

    /**
     * Lisans kodunu doğrula
     * 
     * @since 1.0.0
     * @param string $purchase_code Satın alma kodu
     * @param string $personal_token Kişisel API token (opsiyonel)
     * @return array
     */
    public function validate_license($purchase_code, $personal_token = '') {
        
        // Nonce güvenlik kontrolü
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce')) {
            return array(
                'success' => false,
                'message' => 'Güvenlik kontrolü başarısız.'
            );
        }
        
        if (empty($purchase_code)) {
            return array(
                'success' => false,
                'message' => 'Satın alma kodu gereklidir.'
            );
        }
        
        // Cache'den kontrol et
        $cache_key = 'ai_genius_license_' . md5($purchase_code);
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            ai_genius_log('License validation returned from cache');
            return $cached_result;
        }
        
        // API'den doğrula
        $validation_result = $this->verify_purchase_code($purchase_code, $personal_token);
        
        if ($validation_result['success']) {
            // Lisans bilgilerini kaydet
            $this->save_license_data($purchase_code, $validation_result['data']);
            
            // Cache'e kaydet (24 saat)
            set_transient($cache_key, $validation_result, DAY_IN_SECONDS);
            
            ai_genius_log('License validated successfully');
        } else {
            ai_genius_log('License validation failed: ' . $validation_result['message'], 'error');
        }
        
        return $validation_result;
    }

    /**
     * Envato API ile satın alma kodunu doğrula
     * 
     * @since 1.0.0
     * @param string $purchase_code Satın alma kodu
     * @param string $personal_token Kişisel token
     * @return array
     */
    private function verify_purchase_code($purchase_code, $personal_token) {
        
        // Envato API endpoint'i
        $url = $this->api_url . 'author/sale?code=' . $purchase_code;
        
        // API request headers
        $headers = array(
            'Authorization' => 'Bearer ' . $personal_token,
            'User-Agent' => 'AI-Genius WordPress Plugin'
        );
        
        // HTTP request gönder
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => true
        ));
        
        // HTTP hatası kontrolü
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'API bağlantı hatası: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Response kodu kontrolü
        if ($response_code !== 200) {
            $error_message = $this->get_api_error_message($response_code);
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        // JSON decode
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array(
                'success' => false,
                'message' => 'API yanıtı işlenirken hata oluştu.'
            );
        }
        
        // Item ID kontrolü (eğer ayarlanmışsa)
        if (!empty($this->item_id) && isset($data['item']['id'])) {
            if ($data['item']['id'] !== $this->item_id) {
                return array(
                    'success' => false,
                    'message' => 'Bu satın alma kodu bu ürün için geçerli değil.'
                );
            }
        }
        
        // License type kontrolü
        $license_type = $data['license'] ?? '';
        if (!in_array($license_type, array('regular', 'extended'))) {
            return array(
                'success' => false,
                'message' => 'Geçersiz lisans türü.'
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Lisans başarıyla doğrulandı.',
            'data' => array(
                'purchase_code' => $purchase_code,
                'buyer' => $data['buyer'] ?? '',
                'license' => $license_type,
                'purchase_date' => $data['sold_at'] ?? '',
                'item_name' => $data['item']['name'] ?? '',
                'item_id' => $data['item']['id'] ?? '',
                'support_until' => $data['support_amount'] > 0 ? $data['supported_until'] : null
            )
        );
    }

    /**
     * API hata mesajlarını döndür
     * 
     * @since 1.0.0
     * @param int $response_code HTTP response kodu
     * @return string
     */
    private function get_api_error_message($response_code) {
        
        $error_messages = array(
            400 => 'Geçersiz istek. Lütfen satın alma kodunuzu kontrol edin.',
            401 => 'API yetkilendirme hatası. Personal token gerekli.',
            403 => 'Bu işlem için yetkiniz bulunmuyor.',
            404 => 'Satın alma kodu bulunamadı veya geçersiz.',
            429 => 'Çok fazla istek gönderildi. Lütfen daha sonra tekrar deneyin.',
            500 => 'Envato sunucu hatası. Lütfen daha sonra tekrar deneyin.'
        );
        
        return $error_messages[$response_code] ?? 'Bilinmeyen API hatası (Kod: ' . $response_code . ')';
    }

    /**
     * Lisans verilerini kaydet
     * 
     * @since 1.0.0
     * @param string $purchase_code Satın alma kodu
     * @param array $license_data Lisans verileri
     * @return bool
     */
    private function save_license_data($purchase_code, $license_data) {
        
        $license_info = array(
            'purchase_code' => $purchase_code,
            'buyer' => $license_data['buyer'] ?? '',
            'license_type' => $license_data['license'] ?? 'regular',
            'purchase_date' => $license_data['purchase_date'] ?? '',
            'item_name' => $license_data['item_name'] ?? '',
            'item_id' => $license_data['item_id'] ?? '',
            'support_until' => $license_data['support_until'] ?? null,
            'activated_at' => current_time('mysql'),
            'activated_domain' => home_url(),
            'status' => 'active'
        );
        
        // Lisans bilgilerini options tablosuna kaydet
        update_option('ai_genius_license_data', $license_info);
        update_option('ai_genius_license_status', 'valid');
        update_option('ai_genius_last_license_check', current_time('timestamp'));
        
        // Aktivasyon logunu kaydet
        ai_genius_log('License activated for domain: ' . home_url() . ' with purchase code: ' . substr($purchase_code, 0, 8) . '...');
        
        return true;
    }

    /**
     * Lisansı deaktive et
     * 
     * @since 1.0.0
     * @return array
     */
    public function deactivate_license() {
        
        // Nonce güvenlik kontrolü
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce')) {
            return array(
                'success' => false,
                'message' => 'Güvenlik kontrolü başarısız.'
            );
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            return array(
                'success' => false,
                'message' => 'Bu işlem için yetkiniz bulunmuyor.'
            );
        }
        
        // Lisans verilerini temizle
        delete_option('ai_genius_license_data');
        update_option('ai_genius_license_status', 'inactive');
        
        // Cache'i temizle
        $license_data = get_option('ai_genius_license_data', array());
        if (!empty($license_data['purchase_code'])) {
            $cache_key = 'ai_genius_license_' . md5($license_data['purchase_code']);
            delete_transient($cache_key);
        }
        
        ai_genius_log('License deactivated for domain: ' . home_url());
        
        return array(
            'success' => true,
            'message' => 'Lisans başarıyla deaktive edildi.'
        );
    }

    /**
     * Lisans durumunu kontrol et
     * 
     * @since 1.0.0
     * @return array
     */
    public function check_license_status() {
        
        $license_data = get_option('ai_genius_license_data', array());
        $license_status = get_option('ai_genius_license_status', 'inactive');
        
        if (empty($license_data) || $license_status !== 'valid') {
            return array(
                'is_valid' => false,
                'status' => 'inactive',
                'message' => 'Lisans aktif değil.'
            );
        }
        
        // Support süresini kontrol et
        $support_expired = false;
        if (!empty($license_data['support_until'])) {
            $support_until = strtotime($license_data['support_until']);
            $support_expired = $support_until < time();
        }
        
        return array(
            'is_valid' => true,
            'status' => 'active',
            'license_type' => $license_data['license_type'] ?? 'regular',
            'buyer' => $license_data['buyer'] ?? '',
            'purchase_date' => $license_data['purchase_date'] ?? '',
            'support_until' => $license_data['support_until'] ?? null,
            'support_expired' => $support_expired,
            'activated_domain' => $license_data['activated_domain'] ?? '',
            'message' => 'Lisans aktif ve geçerli.'
        );
    }

    /**
     * Zamanlanmış lisans kontrolü
     * 
     * @since 1.0.0
     */
    public function scheduled_license_check() {
        
        $license_data = get_option('ai_genius_license_data', array());
        
        if (empty($license_data['purchase_code'])) {
            return;
        }
        
        // Son kontrolden 24 saat geçmişse tekrar kontrol et
        $last_check = get_option('ai_genius_last_license_check', 0);
        if ((time() - $last_check) < DAY_IN_SECONDS) {
            return;
        }
        
        // Lisansı tekrar doğrula
        $validation_result = $this->verify_purchase_code($license_data['purchase_code'], '');
        
        if (!$validation_result['success']) {
            // Lisans geçersizse durumu güncelle
            update_option('ai_genius_license_status', 'invalid');
            ai_genius_log('Scheduled license check failed: ' . $validation_result['message'], 'warning');
        } else {
            update_option('ai_genius_license_status', 'valid');
            ai_genius_log('Scheduled license check passed');
        }
        
        update_option('ai_genius_last_license_check', time());
    }

    /**
     * Lisans gerekli mi kontrol et
     * 
     * @since 1.0.0
     * @return bool
     */
    public function is_license_required() {
        
        // Development modunda lisans zorunlu değil
        if (defined('WP_DEBUG') && WP_DEBUG && defined('AI_GENIUS_DEV_MODE') && AI_GENIUS_DEV_MODE) {
            return false;
        }
        
        // Localhost'ta lisans zorunlu değil
        $domain = parse_url(home_url(), PHP_URL_HOST);
        $local_domains = array('localhost', '127.0.0.1', '::1');
        
        if (in_array($domain, $local_domains) || strpos($domain, '.local') !== false) {
            return false;
        }
        
        return true;
    }

    /**
     * Lisans uyarı mesajlarını göster
     * 
     * @since 1.0.0
     */
    public function show_license_notices() {
        
        // Sadece admin sayfalarında göster
        if (!is_admin()) {
            return;
        }
        
        // Lisans gerekli değilse uyarı gösterme
        if (!$this->is_license_required()) {
            return;
        }
        
        $license_status = $this->check_license_status();
        
        // Lisans aktif değilse
        if (!$license_status['is_valid']) {
            echo '<div class="notice notice-error is-dismissible">
                <p><strong>AI-Genius:</strong> Plugin lisansınız aktif değil. 
                <a href="' . admin_url('admin.php?page=ai-genius-license') . '">Lisansınızı aktive edin</a> 
                veya <a href="https://codecanyon.net/item/ai-genius" target="_blank">satın alın</a>.</p>
            </div>';
            return;
        }
        
        // Support süresi dolmuşsa
        if ($license_status['support_expired']) {
            echo '<div class="notice notice-warning is-dismissible">
                <p><strong>AI-Genius:</strong> Destek süreniz sona ermiş. 
                Güncellemeler ve destek almak için 
                <a href="https://codecanyon.net/item/ai-genius" target="_blank">desteğinizi yenileyin</a>.</p>
            </div>';
        }
        
        // Support süresi 30 gün içinde dolacaksa
        if (!empty($license_status['support_until'])) {
            $support_until = strtotime($license_status['support_until']);
            $days_left = ceil(($support_until - time()) / DAY_IN_SECONDS);
            
            if ($days_left > 0 && $days_left <= 30) {
                echo '<div class="notice notice-info is-dismissible">
                    <p><strong>AI-Genius:</strong> Destek süreniz ' . $days_left . ' gün içinde sona erecek. 
                    <a href="https://codecanyon.net/item/ai-genius" target="_blank">Desteğinizi yenileyin</a>.</p>
                </div>';
            }
        }
    }

    /**
     * Plugin güncellemelerini kontrol et
     * 
     * @since 1.0.0
     * @return bool
     */
    public function can_update_plugin() {
        
        $license_status = $this->check_license_status();
        
        // Lisans geçersizse güncelleme yok
        if (!$license_status['is_valid']) {
            return false;
        }
        
        // Support süresi dolmuşsa güncelleme yok
        if ($license_status['support_expired']) {
            return false;
        }
        
        return true;
    }

    /**
     * Lisans API endpoint'leri için AJAX handler
     * 
     * @since 1.0.0
     */
    public function ajax_validate_license() {
        
        $purchase_code = sanitize_text_field($_POST['purchase_code'] ?? '');
        $personal_token = sanitize_text_field($_POST['personal_token'] ?? '');
        
        $result = $this->validate_license($purchase_code, $personal_token);
        wp_send_json($result);
    }

    /**
     * Lisans deaktivasyonu için AJAX handler
     * 
     * @since 1.0.0
     */
    public function ajax_deactivate_license() {
        
        $result = $this->deactivate_license();
        wp_send_json($result);
    }

    /**
     * Lisans durumunu döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_license_info() {
        
        $license_data = get_option('ai_genius_license_data', array());
        $license_status = get_option('ai_genius_license_status', 'inactive');
        
        return array(
            'status' => $license_status,
            'data' => $license_data,
            'last_check' => get_option('ai_genius_last_license_check', 0),
            'is_required' => $this->is_license_required()
        );
    }

    /**
     * Lisans kısıtlamalarını kontrol et
     * 
     * @since 1.0.0
     * @param string $feature Özellik adı
     * @return bool
     */
    public function check_feature_access($feature) {
        
        // Lisans gerekli değilse tüm özellikler açık
        if (!$this->is_license_required()) {
            return true;
        }
        
        $license_status = $this->check_license_status();
        
        // Lisans geçersizse temel özellikler kısıtlı
        if (!$license_status['is_valid']) {
            $restricted_features = array(
                'advanced_analytics',
                'custom_ai_models',
                'api_access',
                'premium_integrations',
                'priority_support'
            );
            
            return !in_array($feature, $restricted_features);
        }
        
        // Extended license kontrolü
        $license_type = $license_status['license_type'] ?? 'regular';
        
        if ($license_type === 'regular') {
            $extended_only_features = array(
                'white_label',
                'multisite_support',
                'advanced_customization'
            );
            
            return !in_array($feature, $extended_only_features);
        }
        
        // Extended license - tüm özellikler açık
        return true;
    }

    /**
     * Lisans bilgilerini export et
     * 
     * @since 1.0.0
     * @return array
     */
    public function export_license_data() {
        
        if (!current_user_can('manage_options')) {
            return array('error' => 'Yetkiniz bulunmuyor.');
        }
        
        $license_info = $this->get_license_info();
        
        // Hassas bilgileri gizle
        if (isset($license_info['data']['purchase_code'])) {
            $license_info['data']['purchase_code'] = substr($license_info['data']['purchase_code'], 0, 8) . '...';
        }
        
        return $license_info;
    }
}