<?php
/**
 * Frontend Chatbot sınıfı
 * 
 * Kullanıcı arayüzünde chatbot'u yönetir
 *
 * @package AI_Genius
 * @subpackage AI_Genius/public
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Chatbot Frontend Sınıfı
 * 
 * Chatbot'un frontend görünümünü ve davranışlarını yönetir
 */
class AI_Genius_Chatbot {

    /**
     * Bot ayarları
     * 
     * @since 1.0.0
     * @var array
     */
    private $bot_settings;

    /**
     * AI Processor
     * 
     * @since 1.0.0
     * @var AI_Genius_AI_Processor
     */
    private $ai_processor;

    /**
     * Veritabanı sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_Database
     */
    private $database;

    /**
     * Sayfa bazlı proaktif mesajlar
     * 
     * @since 1.0.0
     * @var array
     */
    private $proactive_messages = array();

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        $this->database = new AI_Genius_Database();
        $this->ai_processor = new AI_Genius_AI_Processor();
        $this->bot_settings = $this->load_bot_settings();
        $this->setup_proactive_messages();
        
        // Hook'ları ekle
        add_action('wp_footer', array($this, 'render_chatbot'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_chatbot_assets'));
        
        // AJAX handler'ları
        add_action('wp_ajax_ai_genius_chat_message', array($this, 'handle_chat_message'));
        add_action('wp_ajax_nopriv_ai_genius_chat_message', array($this, 'handle_chat_message'));
        add_action('wp_ajax_ai_genius_rate_response', array($this, 'handle_rate_response'));
        add_action('wp_ajax_nopriv_ai_genius_rate_response', array($this, 'handle_rate_response'));
        add_action('wp_ajax_ai_genius_get_proactive_message', array($this, 'get_proactive_message'));
        add_action('wp_ajax_nopriv_ai_genius_get_proactive_message', array($this, 'get_proactive_message'));
        
        ai_genius_log('Chatbot class initialized');
    }

    /**
     * Bot ayarlarını yükle
     * 
     * @since 1.0.0
     * @return array
     */
    private function load_bot_settings() {
        
        $settings = $this->database->get_bot_settings();
        
        if (!$settings) {
            // Varsayılan ayarlar
            $settings = array(
                'bot_name' => 'AI Asistan',
                'bot_personality' => 'Yardımsever ve dostane',
                'bot_avatar' => AI_GENIUS_PLUGIN_URL . 'assets/images/default-avatar.svg',
                'welcome_message' => 'Merhaba! Size nasıl yardımcı olabilirim?',
                'fallback_message' => 'Üzgünüm, bu konuda size yardımcı olamıyorum. Lütfen başka bir soru sormayı deneyin.',
                'chat_theme' => 'auto',
                'chat_position' => 'bottom-right',
                'is_active' => 1
            );
        }
        
        return $settings;
    }

    /**
     * Proaktif mesajları ayarla
     * 
     * @since 1.0.0
     */
    private function setup_proactive_messages() {
        
        $this->proactive_messages = array(
            'home' => array(
                'delay' => 5000, // 5 saniye
                'messages' => array(
                    'Siteye hoş geldiniz! Size nasıl yardımcı olabilirim?',
                    'Merhaba! Aradığınız bilgiyi bulmanıza yardımcı olayım mı?',
                    'Herhangi bir sorunuz varsa çekinmeden sorabilirsiniz!'
                )
            ),
            'product' => array(
                'delay' => 8000, // 8 saniye
                'messages' => array(
                    'Bu ürün hakkında sorularınız var mı?',
                    'Ürün özellikleri veya fiyat konusunda yardımcı olabilirim.',
                    'Karşılaştırma yapmak istediğiniz başka ürünler var mı?'
                )
            ),
            'cart' => array(
                'delay' => 3000, // 3 saniye
                'messages' => array(
                    'Sepetinizle ilgili yardıma ihtiyacınız var mı?',
                    'Kargo veya ödeme seçenekleri hakkında soru sormak ister misiniz?',
                    'Eksik bir şey mi var? Size yardımcı olayım!'
                )
            ),
            'checkout' => array(
                'delay' => 2000, // 2 saniye
                'messages' => array(
                    'Ödeme işleminde yardıma ihtiyacınız var mı?',
                    'Herhangi bir sorun yaşıyorsanız yardımcı olabilirim.',
                    'Güvenli ödeme seçenekleri hakkında bilgi almak ister misiniz?'
                )
            ),
            'blog' => array(
                'delay' => 10000, // 10 saniye
                'messages' => array(
                    'Bu konu hakkında başka sorularınız var mı?',
                    'İlgili başka makaleler önerebilirim.',
                    'Anlamadığınız bir bölüm varsa açıklayabilirim.'
                )
            ),
            'contact' => array(
                'delay' => 3000, // 3 saniye
                'messages' => array(
                    'Hemen yanıtlayabileceğim sorularınız var mı?',
                    'İletişim bilgileri veya çalışma saatleriyle ilgili yardımcı olabilirim.',
                    'Hangi departmanla iletişim kurmak istediğinizi söylerseniz yönlendirebilirim.'
                )
            ),
            '404' => array(
                'delay' => 2000,
                'messages' => array(
                    'Aradığınız sayfayı bulmanıza yardımcı olayım mı?',
                    'Ne aramaya çalışıyordunuz? Size doğru yönü gösterebilirim.',
                    'Popüler sayfalarımızı gösterebilirim.'
                )
            )
        );
        
        // WooCommerce özel sayfaları
        if (class_exists('WooCommerce')) {
            $this->proactive_messages['shop'] = array(
                'delay' => 7000,
                'messages' => array(
                    'Hangi kategoride ürün arıyorsunuz?',
                    'Bütçenize uygun ürünler önerebilirim.',
                    'Özel bir marka veya özellik mi arıyorsunuz?'
                )
            );
            
            $this->proactive_messages['account'] = array(
                'delay' => 4000,
                'messages' => array(
                    'Hesap ayarlarınızla ilgili yardıma ihtiyacınız var mı?',
                    'Sipariş geçmişinizi kontrol etmek mi istiyorsunuz?',
                    'Profil bilgilerinizi güncellemede yardımcı olabilirim.'
                )
            );
        }
    }

    /**
     * Chatbot CSS ve JS dosyalarını yükle
     * 
     * @since 1.0.0
     */
    public function enqueue_chatbot_assets() {
        
        // Bot aktif değilse yükleme
        if (!$this->bot_settings['is_active']) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'ai-genius-chatbot',
            AI_GENIUS_PLUGIN_URL . 'assets/css/chatbot.css',
            array(),
            AI_GENIUS_VERSION,
            'all'
        );
        
        // JavaScript
        wp_enqueue_script(
            'ai-genius-chatbot',
            AI_GENIUS_PLUGIN_URL . 'assets/js/chatbot.js',
            array('jquery'),
            AI_GENIUS_VERSION,
            true
        );
        
        // Animasyon kütüphanesi
        wp_enqueue_script(
            'ai-genius-animations',
            AI_GENIUS_PLUGIN_URL . 'assets/js/chatbot-animations.js',
            array('ai-genius-chatbot'),
            AI_GENIUS_VERSION,
            true
        );
        
        // JavaScript değişkenlerini localize et
        wp_localize_script(
            'ai-genius-chatbot',
            'aiGeniusChatbot',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_genius_chat_nonce'),
                'plugin_url' => AI_GENIUS_PLUGIN_URL,
                'bot_settings' => array(
                    'name' => $this->bot_settings['bot_name'],
                    'avatar' => $this->bot_settings['bot_avatar'],
                    'position' => $this->bot_settings['chat_position'],
                    'theme' => $this->bot_settings['chat_theme'],
                    'welcome_message' => $this->bot_settings['welcome_message']
                ),
                'page_info' => array(
                    'type' => $this->detect_page_type(),
                    'id' => get_queried_object_id(),
                    'url' => get_permalink(),
                    'title' => get_the_title()
                ),
                'user_info' => array(
                    'id' => get_current_user_id(),
                    'name' => wp_get_current_user()->display_name ?: 'Misafir',
                    'session_id' => $this->get_or_create_session_id()
                ),
                'strings' => array(
                    'typing' => __('Yazıyor...', 'ai-genius'),
                    'send' => __('Gönder', 'ai-genius'),
                    'minimize' => __('Küçült', 'ai-genius'),
                    'close' => __('Kapat', 'ai-genius'),
                    'placeholder' => __('Mesajınızı yazın...', 'ai-genius'),
                    'helpful' => __('Faydalı', 'ai-genius'),
                    'not_helpful' => __('Faydalı değil', 'ai-genius'),
                    'error_message' => __('Bir hata oluştu. Lütfen tekrar deneyin.', 'ai-genius'),
                    'connection_error' => __('Bağlantı hatası. İnternet bağlantınızı kontrol edin.', 'ai-genius'),
                    'thinking' => __('Düşünüyor...', 'ai-genius'),
                    'retry' => __('Tekrar Dene', 'ai-genius')
                ),
                'proactive_messages' => $this->get_page_proactive_messages(),
                'theme_colors' => $this->get_theme_colors(),
                'features' => array(
                    'voice_input' => get_option('ai_genius_voice_input', false),
                    'file_upload' => get_option('ai_genius_file_upload', false),
                    'emoji_reactions' => get_option('ai_genius_emoji_reactions', true),
                    'typing_indicator' => get_option('ai_genius_typing_indicator', true),
                    'sound_effects' => get_option('ai_genius_sound_effects', false)
                )
            )
        );
        
        ai_genius_log('Chatbot assets enqueued');
    }

    /**
     * Sayfa tipini tespit et
     * 
     * @since 1.0.0
     * @return string
     */
    private function detect_page_type() {
        
        if (is_404()) {
            return '404';
        }
        
        if (is_home() || is_front_page()) {
            return 'home';
        }
        
        if (is_single() && get_post_type() === 'post') {
            return 'blog';
        }
        
        if (is_page()) {
            $page_template = get_page_template_slug();
            if (strpos($page_template, 'contact') !== false || is_page('contact') || is_page('iletisim')) {
                return 'contact';
            }
        }
        
        // WooCommerce sayfaları
        if (class_exists('WooCommerce')) {
            if (is_shop()) {
                return 'shop';
            }
            if (is_product()) {
                return 'product';
            }
            if (is_cart()) {
                return 'cart';
            }
            if (is_checkout()) {
                return 'checkout';
            }
            if (is_account_page()) {
                return 'account';
            }
        }
        
        // Diğer durumlar
        if (is_category() || is_tag() || is_archive()) {
            return 'archive';
        }
        
        if (is_search()) {
            return 'search';
        }
        
        return 'general';
    }

    /**
     * Mevcut sayfa için proaktif mesajları al
     * 
     * @since 1.0.0
     * @return array
     */
    private function get_page_proactive_messages() {
        
        $page_type = $this->detect_page_type();
        
        if (isset($this->proactive_messages[$page_type])) {
            return $this->proactive_messages[$page_type];
        }
        
        // Varsayılan mesajlar
        return array(
            'delay' => 8000,
            'messages' => array(
                'Size nasıl yardımcı olabilirim?',
                'Herhangi bir sorunuz var mı?',
                'Yardıma ihtiyacınız olursa buradayım!'
            )
        );
    }

    /**
     * Tema renklerini al
     * 
     * @since 1.0.0
     * @return array
     */
    private function get_theme_colors() {
        
        // Kullanıcı özel renkleri ayarlamışsa
        $custom_colors = get_option('ai_genius_custom_colors', array());
        
        if (!empty($custom_colors)) {
            return $custom_colors;
        }
        
        // WordPress tema renklerini otomatik tespit et
        $theme_colors = array(
            'primary' => '#0073aa',
            'secondary' => '#005177',
            'accent' => '#00a32a',
            'background' => '#ffffff',
            'text' => '#333333',
            'border' => '#dddddd'
        );
        
        // Theme.json desteği (WordPress 5.8+)
        if (function_exists('wp_get_global_settings')) {
            $global_settings = wp_get_global_settings();
            
            if (isset($global_settings['color']['palette']['theme'])) {
                $palette = $global_settings['color']['palette']['theme'];
                
                foreach ($palette as $color) {
                    switch ($color['slug']) {
                        case 'primary':
                            $theme_colors['primary'] = $color['color'];
                            break;
                        case 'secondary':
                            $theme_colors['secondary'] = $color['color'];
                            break;
                        case 'accent':
                            $theme_colors['accent'] = $color['color'];
                            break;
                    }
                }
            }
        }
        
        // Customizer renklerini kontrol et
        $customizer_colors = array(
            'primary' => get_theme_mod('primary_color'),
            'secondary' => get_theme_mod('secondary_color'),
            'accent' => get_theme_mod('accent_color')
        );
        
        foreach ($customizer_colors as $key => $color) {
            if (!empty($color)) {
                $theme_colors[$key] = $color;
            }
        }
        
        return $theme_colors;
    }

    /**
     * Session ID al veya oluştur
     * 
     * @since 1.0.0
     * @return string
     */
    private function get_or_create_session_id() {
        
        // Oturum açmış kullanıcı için
        if (is_user_logged_in()) {
            $session_id = 'user_' . get_current_user_id() . '_' . date('Ymd');
        } else {
            // Misafir kullanıcı için cookie tabanlı session
            if (isset($_COOKIE['ai_genius_session_id'])) {
                $session_id = sanitize_text_field($_COOKIE['ai_genius_session_id']);
            } else {
                $session_id = 'guest_' . wp_generate_uuid4();
                
                // Cookie ayarla (24 saat)
                setcookie('ai_genius_session_id', $session_id, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
            }
        }
        
        // Session'ı veritabanında başlat
        $this->database->start_user_session($session_id, array(
            'page_url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));
        
        return $session_id;
    }

    /**
     * Chatbot HTML'ini render et
     * 
     * @since 1.0.0
     */
    public function render_chatbot() {
        
        // Bot aktif değilse gösterme
        if (!$this->bot_settings['is_active']) {
            return;
        }
        
        // Admin sayfalarında gösterme
        if (is_admin()) {
            return;
        }
        
        // Customize preview'da gösterme
        if (is_customize_preview()) {
            return;
        }
        
        $position_class = 'position-' . str_replace('_', '-', $this->bot_settings['chat_position']);
        $theme_class = 'theme-' . $this->bot_settings['chat_theme'];
        
        ?>
        <div id="ai-genius-chatbot" class="ai-genius-chatbot <?php echo esc_attr($position_class . ' ' . $theme_class); ?>" style="display: none;">
            
            <!-- Chatbot Maskotu -->
            <div class="chatbot-mascot" id="chatbot-mascot">
                <div class="mascot-avatar">
                    <img src="<?php echo esc_url($this->bot_settings['bot_avatar']); ?>" 
                         alt="<?php echo esc_attr($this->bot_settings['bot_name']); ?>"
                         class="mascot-image">
                    <div class="mascot-status-indicator"></div>
                </div>
                
                <!-- Proaktif Mesaj Baloncuğu -->
                <div class="proactive-bubble" id="proactive-bubble" style="display: none;">
                    <div class="bubble-content">
                        <span class="bubble-text"></span>
                        <button class="bubble-close" aria-label="<?php esc_attr_e('Kapat', 'ai-genius'); ?>">×</button>
                    </div>
                    <div class="bubble-arrow"></div>
                </div>
                
                <!-- Yeni Mesaj Badge'i -->
                <div class="message-badge" id="message-badge" style="display: none;">
                    <span class="badge-count">1</span>
                </div>
            </div>
            
            <!-- Ana Sohbet Penceresi -->
            <div class="chatbot-window" id="chatbot-window" style="display: none;">
                
                <!-- Header -->
                <div class="chatbot-header">
                    <div class="header-info">
                        <img src="<?php echo esc_url($this->bot_settings['bot_avatar']); ?>" 
                             alt="<?php echo esc_attr($this->bot_settings['bot_name']); ?>"
                             class="header-avatar">
                        <div class="header-text">
                            <h4 class="bot-name"><?php echo esc_html($this->bot_settings['bot_name']); ?></h4>
                            <span class="bot-status">Online</span>
                        </div>
                    </div>
                    
                    <div class="header-controls">
                        <button class="control-btn minimize-btn" id="minimize-btn" 
                                title="<?php esc_attr_e('Küçült', 'ai-genius'); ?>">
                            <span class="dashicons dashicons-minus"></span>
                        </button>
                        <button class="control-btn close-btn" id="close-btn"
                                title="<?php esc_attr_e('Kapat', 'ai-genius'); ?>">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>
                </div>
                
                <!-- Mesaj Listesi -->
                <div class="chatbot-messages" id="chatbot-messages">
                    <div class="welcome-message">
                        <div class="message bot-message">
                            <div class="message-avatar">
                                <img src="<?php echo esc_url($this->bot_settings['bot_avatar']); ?>" 
                                     alt="<?php echo esc_attr($this->bot_settings['bot_name']); ?>">
                            </div>
                            <div class="message-content">
                                <div class="message-text">
                                    <?php echo esc_html($this->bot_settings['welcome_message']); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo current_time('H:i'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Yazıyor Göstergesi -->
                <div class="typing-indicator" id="typing-indicator" style="display: none;">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span class="typing-text"><?php esc_html_e('yazıyor...', 'ai-genius'); ?></span>
                </div>
                
                <!-- Mesaj Giriş Alanı -->
                <div class="chatbot-input">
                    <div class="input-container">
                        <textarea id="message-input" 
                                  placeholder="<?php esc_attr_e('Mesajınızı yazın...', 'ai-genius'); ?>"
                                  rows="1"
                                  maxlength="1000"></textarea>
                        
                        <div class="input-actions">
                            <?php if (get_option('ai_genius_file_upload', false)): ?>
                            <button class="action-btn file-btn" id="file-btn" 
                                    title="<?php esc_attr_e('Dosya Ekle', 'ai-genius'); ?>">
                                <span class="dashicons dashicons-paperclip"></span>
                            </button>
                            <?php endif; ?>
                            
                            <?php if (get_option('ai_genius_voice_input', false)): ?>
                            <button class="action-btn voice-btn" id="voice-btn"
                                    title="<?php esc_attr_e('Sesli Mesaj', 'ai-genius'); ?>">
                                <span class="dashicons dashicons-microphone"></span>
                            </button>
                            <?php endif; ?>
                            
                            <button class="action-btn send-btn" id="send-btn"
                                    title="<?php esc_attr_e('Gönder', 'ai-genius'); ?>">
                                <span class="dashicons dashicons-arrow-up-alt2"></span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Dosya Yükleme Alanı -->
                    <div class="file-upload-area" id="file-upload-area" style="display: none;">
                        <input type="file" id="file-input" accept=".txt,.pdf,.doc,.docx,.jpg,.png" multiple>
                        <div class="upload-info">
                            <span class="dashicons dashicons-cloud-upload"></span>
                            <span><?php esc_html_e('Dosya seçin veya buraya sürükleyin', 'ai-genius'); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Alt Bilgi -->
                <div class="chatbot-footer">
                    <div class="footer-info">
                        <span class="powered-by">
                            <?php esc_html_e('Powered by', 'ai-genius'); ?> 
                            <strong>AI-Genius</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gizli Input'lar -->
        <input type="hidden" id="ai-genius-session-id" value="<?php echo esc_attr($this->get_or_create_session_id()); ?>">
        <input type="hidden" id="ai-genius-page-type" value="<?php echo esc_attr($this->detect_page_type()); ?>">
        
        <?php
        
        ai_genius_log('Chatbot HTML rendered');
    }

    /**
     * AJAX: Sohbet mesajını işle
     * 
     * @since 1.0.0
     */
    public function handle_chat_message() {
        
        // Nonce kontrolü
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_chat_nonce')) {
            wp_send_json_error('Güvenlik kontrolü başarısız.');
        }
        
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $page_type = sanitize_text_field($_POST['page_type'] ?? 'general');
        
        if (empty($message) || empty($session_id)) {
            wp_send_json_error('Eksik parametreler.');
        }
        
        // Mesaj uzunluğu kontrolü
        if (strlen($message) > 1000) {
            wp_send_json_error('Mesaj çok uzun. Maksimum 1000 karakter olmalı.');
        }
        
        try {
            // AI'dan yanıt al
            $context = array(
                'session_id' => $session_id,
                'page_type' => $page_type,
                'user_id' => get_current_user_id(),
                'page_url' => $_POST['page_url'] ?? '',
                'page_title' => $_POST['page_title'] ?? ''
            );
            
            $ai_response = $this->ai_processor->process_message($message, $context);
            
            if ($ai_response['success']) {
                
                // Session mesaj sayısını güncelle
                $this->database->update_user_session($session_id, array(
                    'message_count' => 'message_count + 1'
                ));
                
                wp_send_json_success(array(
                    'response' => $ai_response['response'],
                    'response_time' => $ai_response['response_time'] ?? 0,
                    'source' => $ai_response['source'] ?? 'ai',
                    'provider' => $ai_response['provider'] ?? '',
                    'model' => $ai_response['model'] ?? '',
                    'message_id' => uniqid('msg_')
                ));
                
            } else {
                wp_send_json_error($ai_response['message'] ?? 'Yanıt oluşturulamadı.');
            }
            
        } catch (Exception $e) {
            ai_genius_log('Chat message error: ' . $e->getMessage(), 'error');
            wp_send_json_error('Bir hata oluştu. Lütfen tekrar deneyin.');
        }
    }

    /**
     * AJAX: Yanıt değerlendirmesi
     * 
     * @since 1.0.0
     */
    public function handle_rate_response() {
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_chat_nonce')) {
            wp_send_json_error('Güvenlik kontrolü başarısız.');
        }
        
        $message_id = sanitize_text_field($_POST['message_id'] ?? '');
        $rating = sanitize_text_field($_POST['rating'] ?? ''); // 'helpful' veya 'not_helpful'
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        if (empty($message_id) || empty($rating) || empty($session_id)) {
            wp_send_json_error('Eksik parametreler.');
        }
        
        $is_helpful = ($rating === 'helpful') ? 1 : 0;
        
        // Son mesajı bul ve güncelle
        global $wpdb;
        $chat_table = $wpdb->prefix . 'ai_genius_chat_history';
        
        $updated = $wpdb->update(
            $chat_table,
            array('is_helpful' => $is_helpful),
            array('session_id' => $session_id),
            array('%d'),
            array('%s')
        );
        
        if ($updated !== false) {
            // Analitik güncelle
            $today = current_time('Y-m-d');
            $metric_name = $is_helpful ? 'helpful_responses' : 'unhelpful_responses';
            
            $current_count = $this->database->get_analytics($metric_name, $today, $today);
            $new_count = !empty($current_count) ? $current_count[0]['metric_value'] + 1 : 1;
            
            $this->database->save_analytics($metric_name, $new_count, $today);
            
            wp_send_json_success('Değerlendirme kaydedildi.');
        } else {
            wp_send_json_error('Değerlendirme kaydedilemedi.');
        }
    }

    /**
     * AJAX: Proaktif mesaj al
     * 
     * @since 1.0.0
     */
    public function get_proactive_message() {
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_chat_nonce')) {
            wp_send_json_error('Güvenlik kontrolü başarısız.');
        }
        
        $page_type = sanitize_text_field($_POST['page_type'] ?? 'general');
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        
        // Bu session için daha önce proaktif mesaj gösterildi mi kontrol et
        $shown_key = 'ai_genius_proactive_shown_' . md5($session_id . $page_type);
        
        if (get_transient($shown_key)) {
            wp_send_json_error('Bu sayfa için zaten mesaj gösterildi.');
        }
        
        $proactive_messages = $this->get_page_proactive_messages();
        
        if (!empty($proactive_messages['messages'])) {
            $random_message = $proactive_messages['messages'][array_rand($proactive_messages['messages'])];
            
            // Bu mesajın gösterildiğini işaretle (1 saat boyunca)
            set_transient($shown_key, true, HOUR_IN_SECONDS);
            
            wp_send_json_success(array(
                'message' => $random_message,
                'delay' => $proactive_messages['delay']
            ));
        } else {
            wp_send_json_error('Proaktif mesaj bulunamadı.');
        }
    }

    /**
     * Chatbot'un görünür olup olmayacağını kontrol et
     * 
     * @since 1.0.0
     * @return bool
     */
    public function should_display_chatbot() {
        
        // Bot aktif değilse gösterme
        if (!$this->bot_settings['is_active']) {
            return false;
        }
        
        // Lisans kontrolü
        $license = new AI_Genius_License();
        if ($license->is_license_required() && !$license->check_license_status()['is_valid']) {
            return false;
        }
        
        // Sayfa bazlı görüntüleme kuralları
        $display_rules = get_option('ai_genius_display_rules', array(
            'show_on_home' => true,
            'show_on_posts' => true,
            'show_on_pages' => true,
            'show_on_shop' => true,
            'show_on_products' => true,
            'hide_on_admin' => true,
            'hide_on_login' => true,
            'excluded_pages' => array()
        ));
        
        // Admin sayfalarında gizle
        if (is_admin() && $display_rules['hide_on_admin']) {
            return false;
        }
        
        // Login sayfalarında gizle
        if ((is_page('login') || is_page('wp-login.php')) && $display_rules['hide_on_login']) {
            return false;
        }
        
        // Dışlanan sayfalar
        $current_page_id = get_queried_object_id();
        if (in_array($current_page_id, $display_rules['excluded_pages'])) {
            return false;
        }
        
        // Sayfa tipine göre kontrol
        if (is_home() && !$display_rules['show_on_home']) {
            return false;
        }
        
        if (is_single() && !$display_rules['show_on_posts']) {
            return false;
        }
        
        if (is_page() && !$display_rules['show_on_pages']) {
            return false;
        }
        
        // WooCommerce kontrolleri
        if (class_exists('WooCommerce')) {
            if (is_shop() && !$display_rules['show_on_shop']) {
                return false;
            }
            
            if (is_product() && !$display_rules['show_on_products']) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Chatbot istatistiklerini al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_chatbot_stats() {
        
        $today = current_time('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        // Günlük istatistikler
        $daily_chats = $this->database->get_analytics('daily_chats', $today, $today);
        $daily_count = !empty($daily_chats) ? $daily_chats[0]['metric_value'] : 0;
        
        // Haftalık istatistikler
        $weekly_chats = $this->database->get_analytics('daily_chats', $week_ago, $today);
        $weekly_total = array_sum(array_column($weekly_chats, 'metric_value'));
        
        // Memnuniyet oranı
        $helpful_responses = $this->database->get_analytics('helpful_responses', $week_ago, $today);
        $unhelpful_responses = $this->database->get_analytics('unhelpful_responses', $week_ago, $today);
        
        $helpful_total = array_sum(array_column($helpful_responses, 'metric_value'));
        $unhelpful_total = array_sum(array_column($unhelpful_responses, 'metric_value'));
        $total_ratings = $helpful_total + $unhelpful_total;
        
        $satisfaction_rate = $total_ratings > 0 ? ($helpful_total / $total_ratings) * 100 : 0;
        
        // Ortalama yanıt süresi
        $response_times = $this->database->get_analytics('avg_response_time', $week_ago, $today);
        $avg_response_time = !empty($response_times) ? 
            array_sum(array_column($response_times, 'metric_value')) / count($response_times) : 0;
        
        return array(
            'daily_chats' => $daily_count,
            'weekly_chats' => $weekly_total,
            'satisfaction_rate' => round($satisfaction_rate, 1),
            'avg_response_time' => round($avg_response_time, 2),
            'total_sessions' => $this->get_total_sessions(),
            'active_now' => $this->get_active_sessions_count()
        );
    }

    /**
     * Toplam session sayısını al
     * 
     * @since 1.0.0
     * @return int
     */
    private function get_total_sessions() {
        
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'ai_genius_user_sessions';
        
        return intval($wpdb->get_var(
            "SELECT COUNT(*) FROM $sessions_table"
        ));
    }

    /**
     * Aktif session sayısını al
     * 
     * @since 1.0.0
     * @return int
     */
    private function get_active_sessions_count() {
        
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'ai_genius_user_sessions';
        $cutoff_time = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        
        return intval($wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $sessions_table 
                WHERE is_active = 1 AND session_start > %s",
                $cutoff_time
            )
        ));
    }

    /**
     * Chatbot önizlemesi için özel CSS oluştur
     * 
     * @since 1.0.0
     * @return string
     */
    public function generate_preview_css() {
        
        $theme_colors = $this->get_theme_colors();
        $custom_css = get_option('ai_genius_custom_css', '');
        
        $css = "
        :root {
            --ai-genius-primary: {$theme_colors['primary']};
            --ai-genius-secondary: {$theme_colors['secondary']};
            --ai-genius-accent: {$theme_colors['accent']};
            --ai-genius-background: {$theme_colors['background']};
            --ai-genius-text: {$theme_colors['text']};
            --ai-genius-border: {$theme_colors['border']};
        }
        
        .ai-genius-chatbot.theme-auto {
            --chatbot-primary: var(--ai-genius-primary);
            --chatbot-secondary: var(--ai-genius-secondary);
            --chatbot-accent: var(--ai-genius-accent);
        }
        
        .ai-genius-chatbot.theme-light {
            --chatbot-primary: #0073aa;
            --chatbot-secondary: #005177;
            --chatbot-accent: #00a32a;
            --chatbot-bg: #ffffff;
            --chatbot-text: #333333;
        }
        
        .ai-genius-chatbot.theme-dark {
            --chatbot-primary: #4a9eff;
            --chatbot-secondary: #2c5aa0;
            --chatbot-accent: #46d369;
            --chatbot-bg: #1a1a1a;
            --chatbot-text: #ffffff;
        }
        
        {$custom_css}
        ";
        
        return $css;
    }

    /**
     * Shortcode desteği
     * 
     * @since 1.0.0
     * @param array $atts Shortcode özellikleri
     * @return string
     */
    public function chatbot_shortcode($atts) {
        
        $atts = shortcode_atts(array(
            'inline' => 'false',
            'height' => '400px',
            'width' => '100%',
            'position' => 'bottom-right'
        ), $atts);
        
        if ($atts['inline'] === 'true') {
            ob_start();
            ?>
            <div class="ai-genius-chatbot-inline" 
                 style="height: <?php echo esc_attr($atts['height']); ?>; width: <?php echo esc_attr($atts['width']); ?>;">
                <div class="inline-chatbot-container">
                    <!-- Inline chatbot HTML'i buraya gelecek -->
                    <p><?php _e('Inline chatbot yakında aktif olacak...', 'ai-genius'); ?></p>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        
        return '<!-- AI-Genius Chatbot aktif -->';
    }

    /**
     * Widget desteği için chatbot widget sınıfı
     * 
     * @since 1.0.0
     */
    public function register_chatbot_widget() {
        
        register_widget('AI_Genius_Chatbot_Widget');
    }

    /**
     * Mobil uyumluluk kontrolü
     * 
     * @since 1.0.0
     * @return bool
     */
    private function is_mobile_device() {
        
        return wp_is_mobile();
    }

    /**
     * Chatbot'u başlat
     * 
     * @since 1.0.0
     */
    public function init_chatbot() {
        
        // Görüntüleme kontrolü
        if (!$this->should_display_chatbot()) {
            return;
        }
        
        // Shortcode kaydet
        add_shortcode('ai_genius_chatbot', array($this, 'chatbot_shortcode'));
        
        // Widget kaydet
        add_action('widgets_init', array($this, 'register_chatbot_widget'));
        
        ai_genius_log('Chatbot initialized and ready to display');
    }
}

/**
 * Chatbot Widget Sınıfı
 */
class AI_Genius_Chatbot_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'ai_genius_chatbot_widget',
            __('AI-Genius Chatbot', 'ai-genius'),
            array(
                'description' => __('Sayfaya gömülü chatbot ekler', 'ai-genius')
            )
        );
    }

    public function widget($args, $instance) {
        
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // Inline chatbot HTML'i
        echo do_shortcode('[ai_genius_chatbot inline="true" height="' . esc_attr($instance['height']) . '"]');
        
        echo $args['after_widget'];
    }

    public function form($instance) {
        
        $title = !empty($instance['title']) ? $instance['title'] : __('AI Asistan', 'ai-genius');
        $height = !empty($instance['height']) ? $instance['height'] : '400px';
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_attr_e('Başlık:', 'ai-genius'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('height')); ?>">
                <?php esc_attr_e('Yükseklik:', 'ai-genius'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('height')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('height')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($height); ?>"
                   placeholder="400px">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['height'] = (!empty($new_instance['height'])) ? sanitize_text_field($new_instance['height']) : '400px';
        
        return $instance;
    }
}