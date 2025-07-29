<?php
/**
 * HuggingFace API connector sınıfı
 * 
 * Ücretsiz açık kaynak AI modelleri ile iletişim kurar
 *
 * @package AI_Genius
 * @subpackage AI_Genius/api/connectors
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * HuggingFace Connector Sınıfı
 * 
 * HuggingFace Inference API ile ücretsiz modelleri kullanır
 */
class AI_Genius_HuggingFace_Connector {

    /**
     * HuggingFace Inference API base URL
     * 
     * @since 1.0.0
     * @var string
     */
    private $api_base = 'https://api-inference.huggingface.co/models/';

    /**
     * API anahtarı (opsiyonel - rate limit için)
     * 
     * @since 1.0.0
     * @var string
     */
    private $api_token;

    /**
     * Desteklenen modeller
     * 
     * @since 1.0.0
     * @var array
     */
    private $supported_models = array(
        'microsoft/DialoGPT-medium' => array(
            'name' => 'DialoGPT Medium',
            'type' => 'conversational',
            'max_length' => 1000,
            'description' => 'Microsoft\'un sohbet modeli',
            'language' => 'en',
            'cost' => 'free'
        ),
        'facebook/blenderbot-400M-distill' => array(
            'name' => 'BlenderBot 400M',
            'type' => 'conversational',
            'max_length' => 512,
            'description' => 'Facebook\'un kompakt sohbet modeli',
            'language' => 'en',
            'cost' => 'free'
        ),
        'microsoft/DialoGPT-small' => array(
            'name' => 'DialoGPT Small',
            'type' => 'conversational',
            'max_length' => 512,
            'description' => 'Hızlı ve hafif sohbet modeli',
            'language' => 'en',
            'cost' => 'free'
        ),
        'Helsinki-NLP/opus-mt-en-tr' => array(
            'name' => 'English to Turkish Translator',
            'type' => 'translation',
            'max_length' => 512,
            'description' => 'İngilizce-Türkçe çeviri modeli',
            'language' => 'en-tr',
            'cost' => 'free'
        ),
        'Helsinki-NLP/opus-mt-tr-en' => array(
            'name' => 'Turkish to English Translator',
            'type' => 'translation',
            'max_length' => 512,
            'description' => 'Türkçe-İngilizce çeviri modeli',
            'language' => 'tr-en',
            'cost' => 'free'
        ),
        'microsoft/DialoGPT-large' => array(
            'name' => 'DialoGPT Large',
            'type' => 'conversational',
            'max_length' => 1000,
            'description' => 'En büyük DialoGPT modeli (yavaş)',
            'language' => 'en',
            'cost' => 'free'
        )
    );

    /**
     * Son istek bilgileri
     * 
     * @since 1.0.0
     * @var array
     */
    private $last_request_info = array();

    /**
     * Rate limiting cache
     * 
     * @since 1.0.0
     * @var array
     */
    private $rate_limit_cache = array();

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     * @param string $api_token HuggingFace API token (opsiyonel)
     */
    public function __construct($api_token = '') {
        
        $this->api_token = sanitize_text_field($api_token);
        
        // API token yoksa da çalışır ama rate limit vardır
        if (empty($this->api_token)) {
            ai_genius_log('HuggingFace API token not provided - using free tier with rate limits', 'info');
        }
        
        ai_genius_log('HuggingFace Connector initialized');
    }

    /**
     * Model'den yanıt al
     * 
     * @since 1.0.0
     * @param array $params İstek parametreleri
     * @return array
     */
    public function generate_response($params) {
        
        $defaults = array(
            'model' => 'microsoft/DialoGPT-medium',
            'message' => '',
            'system_prompt' => '',
            'conversation_history' => array(),
            'max_tokens' => 100,
            'temperature' => 0.7,
            'context' => array()
        );
        
        $params = wp_parse_args($params, $defaults);
        
        // Model kontrolü
        if (!isset($this->supported_models[$params['model']])) {
            return array(
                'success' => false,
                'message' => 'Desteklenmeyen HuggingFace modeli: ' . $params['model'],
                'error_code' => 'UNSUPPORTED_MODEL'
            );
        }
        
        // Rate limiting kontrolü (ücretsiz kullanım için)
        if (!$this->check_rate_limit($params['model'])) {
            return array(
                'success' => false,
                'message' => 'HuggingFace rate limit aşıldı. Lütfen bekleyin.',
                'error_code' => 'RATE_LIMIT_EXCEEDED'
            );
        }
        
        $model_info = $this->supported_models[$params['model']];
        
        // Model tipine göre işlem yap
        switch ($model_info['type']) {
            case 'conversational':
                return $this->generate_conversational_response($params);
                
            case 'translation':
                return $this->generate_translation_response($params);
                
            default:
                return array(
                    'success' => false,
                    'message' => 'Desteklenmeyen model tipi: ' . $model_info['type']
                );
        }
    }

    /**
     * Sohbet yanıtı oluştur
     * 
     * @since 1.0.0
     * @param array $params Parametreler
     * @return array
     */
    private function generate_conversational_response($params) {
        
        // Mesajı hazırla
        $input_text = $this->prepare_conversation_input($params);
        
        // HuggingFace Inference API formatı
        $request_data = array(
            'inputs' => $input_text,
            'parameters' => array(
                'max_length' => min(intval($params['max_tokens']), $this->supported_models[$params['model']]['max_length']),
                'temperature' => floatval($params['temperature']),
                'do_sample' => true,
                'pad_token_id' => 50256 // GPT tokenizer için
            ),
            'options' => array(
                'wait_for_model' => true,
                'use_cache' => false
            )
        );
        
        // API çağrısı yap
        $start_time = microtime(true);
        $response = $this->make_inference_request($params['model'], $request_data);
        $request_time = microtime(true) - $start_time;
        
        // İstek bilgilerini kaydet
        $this->last_request_info = array(
            'model' => $params['model'],
            'request_time' => $request_time,
            'request_data' => $request_data,
            'raw_response' => $response
        );
        
        return $this->process_conversational_response($response, $request_time, $input_text);
    }

    /**
     * Çeviri yanıtı oluştur
     * 
     * @since 1.0.0
     * @param array $params Parametreler
     * @return array
     */
    private function generate_translation_response($params) {
        
        $request_data = array(
            'inputs' => $params['message']
        );
        
        // API çağrısı yap
        $start_time = microtime(true);
        $response = $this->make_inference_request($params['model'], $request_data);
        $request_time = microtime(true) - $start_time;
        
        // İstek bilgilerini kaydet
        $this->last_request_info = array(
            'model' => $params['model'],
            'request_time' => $request_time,
            'request_data' => $request_data,
            'raw_response' => $response
        );
        
        return $this->process_translation_response($response, $request_time);
    }

    /**
     * Sohbet girdisini hazırla
     * 
     * @since 1.0.0
     * @param array $params Parametreler
     * @return string
     */
    private function prepare_conversation_input($params) {
        
        $conversation_parts = array();
        
        // System prompt varsa ekle
        if (!empty($params['system_prompt'])) {
            $conversation_parts[] = "System: " . $params['system_prompt'];
        }
        
        // Sohbet geçmişini ekle (son 3 mesaj)
        if (!empty($params['conversation_history'])) {
            $recent_history = array_slice($params['conversation_history'], -3);
            
            foreach ($recent_history as $msg) {
                if ($msg['role'] === 'user') {
                    $conversation_parts[] = "Human: " . $msg['content'];
                } elseif ($msg['role'] === 'assistant') {
                    $conversation_parts[] = "Assistant: " . $msg['content'];
                }
            }
        }
        
        // Mevcut mesajı ekle
        $conversation_parts[] = "Human: " . $params['message'];
        $conversation_parts[] = "Assistant:";
        
        return implode("\n", $conversation_parts);
    }

    /**
     * Inference API isteği gönder
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @param array $request_data İstek verisi
     * @return array
     */
    private function make_inference_request($model, $request_data) {
        
        $endpoint = $this->api_base . $model;
        
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'AI-Genius-WordPress-Plugin/' . AI_GENIUS_VERSION
        );
        
        // API token varsa ekle
        if (!empty($this->api_token)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_token;
        }
        
        // WordPress HTTP API kullan
        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body' => json_encode($request_data),
            'timeout' => 60, // HuggingFace modellerinin yüklenmesi zaman alabilir
            'sslverify' => true
        ));
        
        // HTTP hata kontrolü
        if (is_wp_error($response)) {
            ai_genius_log('HuggingFace API HTTP error: ' . $response->get_error_message(), 'error');
            return array(
                'error' => true,
                'message' => 'HTTP bağlantı hatası: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // JSON decode
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ai_genius_log('HuggingFace API JSON decode error: ' . json_last_error_msg(), 'error');
            return array(
                'error' => true,
                'message' => 'API yanıtı işlenirken hata oluştu.',
                'raw_body' => $response_body
            );
        }
        
        // HuggingFace özel durumları
        if ($response_code === 503) {
            // Model yükleniyor
            if (isset($decoded_response['error']) && strpos($decoded_response['error'], 'loading') !== false) {
                return array(
                    'error' => true,
                    'message' => 'Model yükleniyor. Lütfen 30 saniye bekleyip tekrar deneyin.',
                    'retry_after' => 30
                );
            }
        }
        
        // Diğer API hatalarını kontrol et
        if ($response_code !== 200) {
            $error_message = $this->get_api_error_message($response_code, $decoded_response);
            ai_genius_log('HuggingFace API error: ' . $error_message, 'error');
            
            return array(
                'error' => true,
                'message' => $error_message,
                'response_code' => $response_code,
                'raw_response' => $decoded_response
            );
        }
        
        return array(
            'error' => false,
            'data' => $decoded_response,
            'response_code' => $response_code
        );
    }

    /**
     * Sohbet yanıtını işle
     * 
     * @since 1.0.0
     * @param array $response API yanıtı
     * @param float $request_time İstek süresi
     * @param string $input_text Girdi metni
     * @return array
     */
    private function process_conversational_response($response, $request_time, $input_text) {
        
        if ($response['error']) {
            return array(
                'success' => false,
                'message' => $response['message'],
                'request_time' => $request_time
            );
        }
        
        $data = $response['data'];
        
        // HuggingFace conversational modelleri array döndürür
        if (!is_array($data) || empty($data)) {
            return array(
                'success' => false,
                'message' => 'HuggingFace\'den geçerli yanıt alınamadı.',
                'request_time' => $request_time
            );
        }
        
        // İlk sonucu al
        $result = $data[0];
        $generated_text = '';
        
        if (isset($result['generated_text'])) {
            $generated_text = $result['generated_text'];
        } elseif (isset($result['translation_text'])) {
            $generated_text = $result['translation_text'];
        } elseif (is_string($result)) {
            $generated_text = $result;
        }
        
        if (empty($generated_text)) {
            return array(
                'success' => false,
                'message' => 'Boş yanıt alındı.',
                'request_time' => $request_time
            );
        }
        
        // Yanıtı temizle (input text'i çıkar)
        $clean_response = $this->clean_generated_text($generated_text, $input_text);
        
        // Türkçe'ye çevir (gerekirse)
        if ($this->should_translate_to_turkish($clean_response)) {
            $clean_response = $this->translate_to_turkish($clean_response);
        }
        
        return array(
            'success' => true,
            'response' => trim($clean_response),
            'request_time' => $request_time,
            'usage' => array(
                'estimated_tokens' => str_word_count($generated_text),
                'cost' => 0.0 // Ücretsiz
            ),
            'model' => $this->last_request_info['model'],
            'provider' => 'huggingface'
        );
    }

    /**
     * Çeviri yanıtını işle
     * 
     * @since 1.0.0
     * @param array $response API yanıtı
     * @param float $request_time İstek süresi
     * @return array
     */
    private function process_translation_response($response, $request_time) {
        
        if ($response['error']) {
            return array(
                'success' => false,
                'message' => $response['message'],
                'request_time' => $request_time
            );
        }
        
        $data = $response['data'];
        
        if (!is_array($data) || empty($data)) {
            return array(
                'success' => false,
                'message' => 'Çeviri sonucu alınamadı.',
                'request_time' => $request_time
            );
        }
        
        $translation = $data[0]['translation_text'] ?? '';
        
        if (empty($translation)) {
            return array(
                'success' => false,
                'message' => 'Çeviri boş döndü.',
                'request_time' => $request_time
            );
        }
        
        return array(
            'success' => true,
            'response' => trim($translation),
            'request_time' => $request_time,
            'usage' => array(
                'estimated_tokens' => str_word_count($translation),
                'cost' => 0.0
            ),
            'model' => $this->last_request_info['model'],
            'provider' => 'huggingface'
        );
    }

    /**
     * Üretilen metni temizle
     * 
     * @since 1.0.0
     * @param string $generated_text Üretilen metin
     * @param string $input_text Girdi metni
     * @return string
     */
    private function clean_generated_text($generated_text, $input_text) {
        
        // Input text'i çıkar
        $clean_text = str_replace($input_text, '', $generated_text);
        
        // "Assistant:" etiketini çıkar
        $clean_text = preg_replace('/^Assistant:\s*/i', '', $clean_text);
        
        // Gereksiz boşlukları temizle
        $clean_text = trim($clean_text);
        
        // Çok uzun cevapları kısalt
        if (strlen($clean_text) > 500) {
            $sentences = explode('.', $clean_text);
            $result = '';
            
            foreach ($sentences as $sentence) {
                if (strlen($result . $sentence) > 400) {
                    break;
                }
                $result .= $sentence . '.';
            }
            
            return rtrim($result, '.');
        }
        
        return $clean_text;
    }

    /**
     * Türkçe'ye çeviri gerekli mi kontrol et
     * 
     * @since 1.0.0
     * @param string $text Metin
     * @return bool
     */
    private function should_translate_to_turkish($text) {
        
        // Çeviri özelliği kapalıysa false döndür
        if (!get_option('ai_genius_auto_translate', false)) {
            return false;
        }
        
        // Zaten Türkçe karakterler varsa çevirme
        if (preg_match('/[çğıöşüÇĞIİÖŞÜ]/', $text)) {
            return false;
        }
        
        // Çok kısa metinleri çevirme
        if (strlen($text) < 10) {
            return false;
        }
        
        // İngilizce kelime oranını kontrol et
        $english_words = array('the', 'and', 'is', 'are', 'was', 'were', 'a', 'an', 'to', 'of', 'in', 'for', 'with', 'on', 'at', 'by');
        $word_count = 0;
        $english_count = 0;
        
        $words = explode(' ', strtolower($text));
        foreach ($words as $word) {
            $word = trim($word, '.,!?;:');
            if (strlen($word) > 2) {
                $word_count++;
                if (in_array($word, $english_words)) {
                    $english_count++;
                }
            }
        }
        
        // %30'dan fazla İngilizce kelime varsa çevir
        return $word_count > 0 && ($english_count / $word_count) > 0.3;
    }

    /**
     * İngilizce metni Türkçe'ye çevir
     * 
     * @since 1.0.0
     * @param string $text İngilizce metin
     * @return string
     */
    private function translate_to_turkish($text) {
        
        // Helsinki çeviri modelini kullan
        $translation_params = array(
            'model' => 'Helsinki-NLP/opus-mt-en-tr',
            'message' => $text,
            'max_tokens' => 200
        );
        
        $result = $this->generate_translation_response($translation_params);
        
        if ($result['success']) {
            return $result['response'];
        }
        
        // Çeviri başarısızsa orijinal metni döndür
        return $text;
    }

    /**
     * Rate limiting kontrolü
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @return bool
     */
    private function check_rate_limit($model) {
        
        // API token varsa rate limit daha yüksek
        $rate_limit = !empty($this->api_token) ? 100 : 10; // Token varsa 100, yoksa 10 istek/saat
        
        $cache_key = 'ai_genius_hf_rate_' . md5($model . ($_SERVER['REMOTE_ADDR'] ?? ''));
        $current_requests = get_transient($cache_key);
        
        if ($current_requests === false) {
            set_transient($cache_key, 1, HOUR_IN_SECONDS);
            return true;
        }
        
        if ($current_requests >= $rate_limit) {
            return false;
        }
        
        set_transient($cache_key, $current_requests + 1, HOUR_IN_SECONDS);
        return true;
    }

    /**
     * API hata mesajını al
     * 
     * @since 1.0.0
     * @param int $response_code HTTP yanıt kodu
     * @param array $response_data Yanıt verisi
     * @return string
     */
    private function get_api_error_message($response_code, $response_data) {
        
        // HuggingFace'den gelen hata mesajı
        if (isset($response_data['error'])) {
            return 'HuggingFace API Hatası: ' . $response_data['error'];
        }
        
        // HTTP durum koduna göre genel mesajlar
        $error_messages = array(
            400 => 'Geçersiz istek parametreleri.',
            401 => 'API token geçersiz.',
            403 => 'Bu modele erişim izniniz yok.',
            404 => 'Model bulunamadı.',
            429 => 'Rate limit aşıldı. API token kullanmayı deneyin.',
            500 => 'HuggingFace sunucu hatası.',
            503 => 'Model yükleniyor veya servis kullanılamıyor.'
        );
        
        return $error_messages[$response_code] ?? 'Bilinmeyen API hatası (Kod: ' . $response_code . ')';
    }

    /**
     * Bağlantıyı test et
     * 
     * @since 1.0.0
     * @return array
     */
    public function test_connection() {
        
        // En hızlı modeli test et
        $test_params = array(
            'model' => 'microsoft/DialoGPT-small',
            'message' => 'Hello, this is a test.',
            'max_tokens' => 20,
            'temperature' => 0.1
        );
        
        $result = $this->generate_response($test_params);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => 'HuggingFace bağlantısı başarılı (Ücretsiz).',
                'model' => $test_params['model'],
                'response_time' => $result['request_time'] ?? 0,
                'has_token' => !empty($this->api_token)
            );
        } else {
            return array(
                'success' => false,
                'message' => 'HuggingFace bağlantısı başarısız: ' . $result['message']
            );
        }
    }

    /**
     * Desteklenen modelleri al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_available_models() {
        
        return $this->supported_models;
    }

    /**
     * Model durumunu kontrol et
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @return array
     */
    public function get_model_status($model) {
        
        if (!isset($this->supported_models[$model])) {
            return array(
                'status' => 'unknown',
                'message' => 'Model desteklenmiyor.'
            );
        }
        
        $endpoint = $this->api_base . $model;
        
        $headers = array(
            'User-Agent' => 'AI-Genius-WordPress-Plugin/' . AI_GENIUS_VERSION
        );
        
        if (!empty($this->api_token)) {
            $headers['Authorization'] = 'Bearer ' . $this->api_token;
        }
        
        // HEAD request ile model durumunu kontrol et
        $response = wp_remote_head($endpoint, array(
            'headers' => $headers,
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return array(
                'status' => 'error',
                'message' => 'Bağlantı hatası: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        switch ($response_code) {
            case 200:
                return array(
                    'status' => 'ready',
                    'message' => 'Model hazır.'
                );
                
            case 503:
                return array(
                    'status' => 'loading',
                    'message' => 'Model yükleniyor. Lütfen bekleyin.'
                );
                
            case 404:
                return array(
                    'status' => 'not_found',
                    'message' => 'Model bulunamadı.'
                );
                
            default:
                return array(
                    'status' => 'unknown',
                    'message' => 'Model durumu bilinmiyor.'
                );
        }
    }

    /**
     * Son istek bilgilerini al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_last_request_info() {
        return $this->last_request_info;
    }

    /**
     * Model bilgilerini al
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @return array|null
     */
    public function get_model_info($model) {
        
        return $this->supported_models[$model] ?? null;
    }

    /**
     * Ücretsiz modelleri al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_free_models() {
        
        return array_filter($this->supported_models, function($model) {
            return $model['cost'] === 'free';
        });
    }

    /**
     * Sohbet modelleri al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_conversational_models() {
        
        return array_filter($this->supported_models, function($model) {
            return $model['type'] === 'conversational';
        });
    }

    /**
     * Çeviri modelleri al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_translation_models() {
        
        return array_filter($this->supported_models, function($model) {
            return $model['type'] === 'translation';
        });
    }

    /**
     * API kullanım istatistiklerini al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_usage_stats() {
        
        $usage_key = 'ai_genius_hf_usage_' . current_time('Y-m-d');
        $daily_usage = get_transient($usage_key);
        
        if ($daily_usage === false) {
            $daily_usage = array(
                'requests' => 0,
                'tokens' => 0,
                'cost' => 0.0
            );
        }
        
        return array(
            'daily_usage' => $daily_usage,
            'last_request' => $this->last_request_info,
            'rate_limit_status' => $this->get_rate_limit_status()
        );
    }

    /**
     * Rate limit durumunu al
     * 
     * @since 1.0.0
     * @return array
     */
    private function get_rate_limit_status() {
        
        $rate_limit = !empty($this->api_token) ? 100 : 10;
        $cache_key = 'ai_genius_hf_rate_' . md5('check' . ($_SERVER['REMOTE_ADDR'] ?? ''));
        $current_requests = get_transient($cache_key);
        
        return array(
            'limit' => $rate_limit,
            'used' => $current_requests ?: 0,
            'remaining' => max(0, $rate_limit - ($current_requests ?: 0)),
            'has_token' => !empty($this->api_token)
        );
    }

    /**
     * Kullanım istatistiklerini güncelle
     * 
     * @since 1.0.0
     * @param array $usage Kullanım bilgileri
     */
    public function update_usage_stats($usage) {
        
        $usage_key = 'ai_genius_hf_usage_' . current_time('Y-m-d');
        $daily_usage = get_transient($usage_key);
        
        if ($daily_usage === false) {
            $daily_usage = array(
                'requests' => 0,
                'tokens' => 0,
                'cost' => 0.0
            );
        }
        
        $daily_usage['requests']++;
        $daily_usage['tokens'] += $usage['estimated_tokens'] ?? 0;
        // HuggingFace ücretsiz olduğu için cost 0
        
        set_transient($usage_key, $daily_usage, DAY_IN_SECONDS);
    }

    /**
     * Model önerisi al (kullanım senaryosuna göre)
     * 
     * @since 1.0.0
     * @param string $use_case Kullanım senaryosu
     * @return string
     */
    public function get_recommended_model($use_case = 'general') {
        
        switch ($use_case) {
            case 'fast':
                return 'microsoft/DialoGPT-small';
                
            case 'quality':
                return 'microsoft/DialoGPT-large';
                
            case 'balanced':
                return 'microsoft/DialoGPT-medium';
                
            case 'translation_en_tr':
                return 'Helsinki-NLP/opus-mt-en-tr';
                
            case 'translation_tr_en':
                return 'Helsinki-NLP/opus-mt-tr-en';
                
            default:
                return 'microsoft/DialoGPT-medium';
        }
    }

    /**
     * Batch işlem desteği (gelecek özellik)
     * 
     * @since 1.0.0
     * @param array $messages Mesaj listesi
     * @return array
     */
    public function generate_batch_responses($messages) {
        
        // Batch işlem henüz desteklenmiyor
        return array(
            'success' => false,
            'message' => 'Batch işlem HuggingFace için henüz desteklenmiyor.'
        );
    }
}
        