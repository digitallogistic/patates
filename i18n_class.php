<?php
/**
 * Çok dilli destek sınıfı
 * 
 * Plugin'in farklı dillerde çalışabilmesi için gerekli işlemleri yönetir
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
 * Uluslararasılaştırma sınıfı
 * 
 * Plugin'in çeviri dosyalarını yükler ve dil desteği sağlar
 */
class AI_Genius_i18n {

    /**
     * Desteklenen diller
     * 
     * @since 1.0.0
     * @var array
     */
    private $supported_languages = array(
        'tr_TR' => 'Türkçe',
        'en_US' => 'English',
        'de_DE' => 'Deutsch',
        'fr_FR' => 'Français',
        'es_ES' => 'Español',
        'it_IT' => 'Italiano',
        'ru_RU' => 'Русский',
        'ar' => 'العربية'
    );

    /**
     * Aktif dil
     * 
     * @since 1.0.0
     * @var string
     */
    private $current_language;

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        $this->current_language = get_locale();
        
        // RTL dil desteği
        add_action('wp_head', array($this, 'add_rtl_styles'));
        
        ai_genius_log('i18n class initialized for locale: ' . $this->current_language);
    }

    /**
     * Plugin çeviri dosyalarını yükle
     * 
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {
        
        // Çeviri dosyalarının yolunu belirle
        $locale = apply_filters('plugin_locale', get_locale(), AI_GENIUS_TEXT_DOMAIN);
        
        // WordPress dil klasöründen yükle
        load_textdomain(
            AI_GENIUS_TEXT_DOMAIN,
            WP_LANG_DIR . '/plugins/' . AI_GENIUS_TEXT_DOMAIN . '-' . $locale . '.mo'
        );
        
        // Plugin dil klasöründen yükle
        load_plugin_textdomain(
            AI_GENIUS_TEXT_DOMAIN,
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
        
        // Yüklenen dili kontrol et
        if (is_textdomain_loaded(AI_GENIUS_TEXT_DOMAIN)) {
            ai_genius_log('Text domain loaded successfully for locale: ' . $locale);
        } else {
            ai_genius_log('Text domain could not be loaded for locale: ' . $locale, 'warning');
        }
        
        // Admin için ek çeviriler
        if (is_admin()) {
            $this->load_admin_translations();
        }
        
        // Frontend için ek çeviriler  
        if (!is_admin()) {
            $this->load_frontend_translations();
        }
    }

    /**
     * Admin panel çevirilerini yükle
     * 
     * @since 1.0.0
     */
    private function load_admin_translations() {
        
        // JavaScript çevirileri
        wp_set_script_translations(
            'ai-genius-admin',
            AI_GENIUS_TEXT_DOMAIN,
            AI_GENIUS_PLUGIN_DIR . 'languages'
        );
        
        ai_genius_log('Admin translations loaded');
    }

    /**
     * Frontend çevirilerini yükle
     * 
     * @since 1.0.0
     */
    private function load_frontend_translations() {
        
        // JavaScript çevirileri
        wp_set_script_translations(
            'ai-genius-chatbot',
            AI_GENIUS_TEXT_DOMAIN,
            AI_GENIUS_PLUGIN_DIR . 'languages'
        );
        
        ai_genius_log('Frontend translations loaded');
    }

    /**
     * RTL diller için ek CSS ekle
     * 
     * @since 1.0.0
     */
    public function add_rtl_styles() {
        
        if (is_rtl()) {
            echo '<style type="text/css">
                .ai-genius-chatbot {
                    direction: rtl;
                    text-align: right;
                }
                .ai-genius-chatbot .chat-input {
                    text-align: right;
                }
                .ai-genius-chatbot .user-message {
                    float: left;
                    margin-left: 0;
                    margin-right: 10px;
                }
                .ai-genius-chatbot .bot-message {
                    float: right;
                    margin-right: 0;
                    margin-left: 10px;
                }
            </style>';
        }
    }

    /**
     * Desteklenen dilleri döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }

    /**
     * Aktif dili döndür
     * 
     * @since 1.0.0
     * @return string
     */
    public function get_current_language() {
        return $this->current_language;
    }

    /**
     * Dilin desteklenip desteklenmediğini kontrol et
     * 
     * @since 1.0.0
     * @param string $locale Dil kodu
     * @return bool
     */
    public function is_language_supported($locale) {
        return array_key_exists($locale, $this->supported_languages);
    }

    /**
     * Bot için uygun dili belirle
     * 
     * @since 1.0.0
     * @return string
     */
    public function get_bot_language() {
        
        // Kullanıcı tercihini kontrol et
        $user_preference = get_user_meta(get_current_user_id(), 'ai_genius_language', true);
        if (!empty($user_preference) && $this->is_language_supported($user_preference)) {
            return $user_preference;
        }
        
        // Site ayarından al
        $site_language = get_option('ai_genius_language', '');
        if (!empty($site_language) && $this->is_language_supported($site_language)) {
            return $site_language;
        }
        
        // WordPress locale'inden belirle
        $current_locale = get_locale();
        if ($this->is_language_supported($current_locale)) {
            return $current_locale;
        }
        
        // Varsayılan olarak Türkçe döndür
        return 'tr_TR';
    }

    /**
     * Çeviri dosyası var mı kontrol et
     * 
     * @since 1.0.0
     * @param string $locale Dil kodu
     * @return bool
     */
    public function translation_file_exists($locale) {
        
        $file_path = AI_GENIUS_PLUGIN_DIR . 'languages/' . AI_GENIUS_TEXT_DOMAIN . '-' . $locale . '.mo';
        return file_exists($file_path);
    }

    /**
     * Eksik çevirileri tespit et
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_missing_translations() {
        
        $missing = array();
        
        foreach ($this->supported_languages as $locale => $name) {
            if (!$this->translation_file_exists($locale)) {
                $missing[$locale] = $name;
            }
        }
        
        return $missing;
    }

    /**
     * Dil dosyası istatistiklerini döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_translation_stats() {
        
        $stats = array(
            'supported' => count($this->supported_languages),
            'available' => 0,
            'missing' => 0,
            'current' => $this->current_language
        );
        
        foreach ($this->supported_languages as $locale => $name) {
            if ($this->translation_file_exists($locale)) {
                $stats['available']++;
            } else {
                $stats['missing']++;
            }
        }
        
        return $stats;
    }

    /**
     * JavaScript çevirilerini JSON olarak al
     * 
     * @since 1.0.0
     * @return string
     */
    public function get_js_translations() {
        
        $translations = array(
            'send' => __('Gönder', 'ai-genius'),
            'typing' => __('Yazıyor...', 'ai-genius'),
            'error' => __('Bir hata oluştu', 'ai-genius'),
            'retry' => __('Tekrar Dene', 'ai-genius'),
            'close' => __('Kapat', 'ai-genius'),
            'minimize' => __('Küçült', 'ai-genius'),
            'maximize' => __('Büyüt', 'ai-genius'),
            'placeholder' => __('Mesajınızı yazın...', 'ai-genius'),
            'welcome' => __('Merhaba! Size nasıl yardımcı olabilirim?', 'ai-genius'),
            'rate_positive' => __('Faydalı', 'ai-genius'),
            'rate_negative' => __('Faydalı değil', 'ai-genius'),
            'connection_error' => __('Bağlantı hatası. Lütfen tekrar deneyin.', 'ai-genius'),
            'session_expired' => __('Oturum süresi doldu. Sayfa yenileniyor...', 'ai-genius')
        );
        
        return json_encode($translations);
    }

    /**
     * Dil yönünü döndür (LTR/RTL)
     * 
     * @since 1.0.0
     * @param string $locale Dil kodu (opsiyonel)
     * @return string
     */
    public function get_text_direction($locale = '') {
        
        if (empty($locale)) {
            $locale = $this->current_language;
        }
        
        // RTL dilleri
        $rtl_languages = array('ar', 'he_IL', 'fa_IR', 'ur');
        
        return in_array($locale, $rtl_languages) ? 'rtl' : 'ltr';
    }

    /**
     * Dil seçici HTML'ini döndür
     * 
     * @since 1.0.0
     * @return string
     */
    public function get_language_selector() {
        
        $html = '<select name="ai_genius_language" id="ai_genius_language">';
        
        foreach ($this->supported_languages as $locale => $name) {
            $selected = ($locale === $this->current_language) ? 'selected' : '';
            $available = $this->translation_file_exists($locale) ? '' : ' (Çeviri eksik)';
            
            $html .= sprintf(
                '<option value="%s" %s>%s%s</option>',
                esc_attr($locale),
                $selected,
                esc_html($name),
                $available
            );
        }
        
        $html .= '</select>';
        
        return $html;
    }
}