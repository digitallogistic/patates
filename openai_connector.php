<?php
/**
 * OpenAI API connector sınıfı
 * 
 * OpenAI GPT modelleri ile iletişim kurar
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
 * OpenAI Connector Sınıfı
 * 
 * OpenAI API ile iletişim kurar ve GPT yanıtları alır
 */
class AI_Genius_OpenAI_Connector {

    /**
     * OpenAI API endpoint
     * 
     * @since 1.0.0
     * @var string
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * API anahtarı
     * 
     * @since 1.0.0
     * @var string
     */
    private $api_key;

    /**
     * API sürümü
     * 
     * @since 1.0.0
     * @var string
     */
    private $api_version = 'v1';

    /**
     * Desteklenen modeller
     * 
     * @since 1.0.0
     * @var array
     */
    private $supported_models = array(
        'gpt-4' => array(
            'max_tokens' => 8192,
            'cost_per_1k_tokens' => 0.03,
            'description' => 'En gelişmiş GPT modeli'
        ),
        'gpt-4-turbo' => array(
            'max_tokens' => 4096,
            'cost_per_1k_tokens' => 0.01,
            'description' => 'Hızlı ve ekonomik GPT-4'
        ),
        'gpt-3.5-turbo' => array(
            'max_tokens' => 4096,
            'cost_per_1k_tokens' => 0.002,
            'description' => 'Dengeli performans ve maliyet'
        ),
        'gpt-3.5-turbo-16k' => array(
            'max_tokens' => 16384,
            'cost_per_1k_tokens' => 0.004,
            'description' => 'Uzun içerik için optimize edilmiş'
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
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     * @param string $api_key OpenAI API anahtarı
     */
    public function __construct($api_key) {
        
        $this->api_key = sanitize_text_field($api_key);
        
        if (empty($this->api_key)) {
            ai_genius_log('OpenAI API key is empty', 'warning');
        }
        
        ai_genius_log('OpenAI Connector initialized');
    }

    /**
     * Chat completion API'den yanıt al
     * 
     * @since 1.0.0
     * @param array $params İstek parametreleri
     * @return array
     */
    public function generate_response($params) {
        
        $defaults = array(
            'model' => 'gpt-3.5-turbo',
            'message' => '',
            'system_prompt' => '',
            'conversation_history' => array(),
            'max_tokens' => 500,
            'temperature' => 0.7,
            'top_p' => 1.0,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'context' => array()
        );
        
        $params = wp_parse_args($params, $defaults);
        
        // API anahtarı kontrolü
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'OpenAI API anahtarı ayarlanmamış.',
                'error_code' => 'NO_API_KEY'
            );
        }
        
        // Model kontrolü
        if (!isset($this->supported_models[$params['model']])) {
            return array(
                'success' => false,
                'message' => 'Desteklenmeyen model: ' . $params['model'],
                'error_code' => 'UNSUPPORTED_MODEL'
            );
        }
        
        // Mesajları hazırla
        $messages = $this->prepare_messages($params);
        
        // API isteği oluştur
        $request_data = array(
            'model' => $params['model'],
            'messages' => $messages,
            'max_tokens' => intval($params['max_tokens']),
            'temperature' => floatval($params['temperature']),
            'top_p' => floatval($params['top_p']),
            'frequency_penalty' => floatval($params['frequency_penalty']),
            'presence_penalty' => floatval($params['presence_penalty']),
            'user' => $this->get_user_identifier($params['context'])
        );
        
        // API çağrısı yap
        $start_time = microtime(true);
        $response = $this->make_api_request($request_data);
        $request_time = microtime(true) - $start_time;
        
        // İstek bilgilerini kaydet
        $this->last_request_info = array(
            'model' => $params['model'],
            'request_time' => $request_time,
            'request_data' => $request_data,
            'raw_response' => $response
        );
        
        // Yanıtı işle
        return $this->process_api_response($response, $request_time);
    }

    /**
     * Mesajları OpenAI formatında hazırla
     * 
     * @since 1.0.0
     * @param array $params Parametreler
     * @return array
     */
    private function prepare_messages($params) {
        
        $messages = array();
        
        // System prompt ekle
        if (!empty($params['system_prompt'])) {
            $messages[] = array(
                'role' => 'system',
                'content' => $params['system_prompt']
            );
        }
        
        // Sohbet geçmişini ekle (son 10 mesaj)
        if (!empty($params['conversation_history'])) {
            $history = array_slice($params['conversation_history'], -10);
            $messages = array_merge($messages, $history);
        }
        
        // Mevcut kullanıcı mesajını ekle
        $messages[] = array(
            'role' => 'user',
            'content' => $params['message']
        );
        
        return $messages;
    }

    /**
     * API isteği gönder
     * 
     * @since 1.0.0
     * @param array $request_data İstek verisi
     * @return array
     */
    private function make_api_request($request_data) {
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key,
            'User-Agent' => 'AI-Genius-WordPress-Plugin/' . AI_GENIUS_VERSION
        );
        
        // WordPress HTTP API kullan
        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => $headers,
            'body' => json_encode($request_data),
            'timeout' => 60,
            'sslverify' => true,
            'data_format' => 'body'
        ));
        
        // HTTP hata kontrolü
        if (is_wp_error($response)) {
            ai_genius_log('OpenAI API HTTP error: ' . $response->get_error_message(), 'error');
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
            ai_genius_log('OpenAI API JSON decode error: ' . json_last_error_msg(), 'error');
            return array(
                'error' => true,
                'message' => 'API yanıtı işlenirken hata oluştu.'
            );
        }
        
        // API hata kodu kontrolü
        if ($response_code !== 200) {
            $error_message = $this->get_api_error_message($response_code, $decoded_response);
            ai_genius_log('OpenAI API error: ' . $error_message, 'error');
            
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
     * API yanıtını işle
     * 
     * @since 1.0.0
     * @param array $response API yanıtı
     * @param float $request_time İstek süresi
     * @return array
     */
    private function process_api_response($response, $request_time) {
        
        if ($response['error']) {
            return array(
                'success' => false,
                'message' => $response['message'],
                'request_time' => $request_time
            );
        }
        
        $data = $response['data'];
        
        // Yanıt kontrolü
        if (!isset($data['choices']) || empty($data['choices'])) {
            return array(
                'success' => false,
                'message' => 'API\'den geçerli yanıt alınamadı.',
                'request_time' => $request_time
            );
        }
        
        $choice = $data['choices'][0];
        $message_content = $choice['message']['content'] ?? '';
        
        if (empty($message_content)) {
            return array(
                'success' => false,
                'message' => 'Boş yanıt alındı.',
                'request_time' => $request_time
            );
        }
        
        // Kullanım bilgileri
        $usage = $data['usage'] ?? array();
        $prompt_tokens = $usage['prompt_tokens'] ?? 0;
        $completion_tokens = $usage['completion_tokens'] ?? 0;
        $total_tokens = $usage['total_tokens'] ?? 0;
        
        // Maliyet hesaplama
        $cost = $this->calculate_cost($this->last_request_info['model'], $total_tokens);
        
        // Başarılı yanıt
        return array(
            'success' => true,
            'response' => trim($message_content),
            'request_time' => $request_time,
            'usage' => array(
                'prompt_tokens' => $prompt_tokens,
                'completion_tokens' => $completion_tokens,
                'total_tokens' => $total_tokens,
                'estimated_cost' => $cost
            ),
            'model' => $this->last_request_info['model'],
            'finish_reason' => $choice['finish_reason'] ?? 'unknown'
        );
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
        
        // OpenAI'dan gelen hata mesajı
        if (isset($response_data['error']['message'])) {
            $api_error = $response_data['error']['message'];
            $error_type = $response_data['error']['type'] ?? '';
            
            return sprintf('OpenAI API Hatası (%s): %s', $error_type, $api_error);
        }
        
        // HTTP durum koduna göre genel mesajlar
        $error_messages = array(
            400 => 'Geçersiz istek. Parametreleri kontrol edin.',
            401 => 'API anahtarı geçersiz veya eksik.',
            403 => 'Bu işlem için yetkiniz bulunmuyor.',
            404 => 'API endpoint\'i bulunamadı.',
            429 => 'Rate limit aşıldı. Lütfen daha sonra tekrar deneyin.',
            500 => 'OpenAI sunucu hatası.',
            502 => 'OpenAI sunucusuna erişilemiyor.',
            503 => 'OpenAI servisi geçici olarak kullanılamıyor.',
            504 => 'OpenAI sunucusu zaman aşımına uğradı.'
        );
        
        return $error_messages[$response_code] ?? 'Bilinmeyen API hatası (Kod: ' . $response_code . ')';
    }

    /**
     * Kullanıcı kimlik bilgisi oluştur
     * 
     * @since 1.0.0
     * @param array $context Bağlam
     * @return string
     */
    private function get_user_identifier($context) {
        
        $user_id = get_current_user_id();
        
        if ($user_id > 0) {
            return 'user_' . $user_id;
        }
        
        // Misafir kullanıcı için session ID kullan
        $session_id = $context['session_id'] ?? '';
        if (!empty($session_id)) {
            return 'guest_' . substr($session_id, 0, 8);
        }
        
        // Son çare olarak IP adresi
        return 'ip_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    /**
     * Maliyet hesapla
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @param int $total_tokens Toplam token sayısı
     * @return float
     */
    private function calculate_cost($model, $total_tokens) {
        
        if (!isset($this->supported_models[$model])) {
            return 0.0;
        }
        
        $cost_per_1k = $this->supported_models[$model]['cost_per_1k_tokens'];
        return ($total_tokens / 1000) * $cost_per_1k;
    }

    /**
     * Bağlantıyı test et
     * 
     * @since 1.0.0
     * @return array
     */
    public function test_connection() {
        
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'API anahtarı ayarlanmamış.'
            );
        }
        
        // Basit test mesajı gönder
        $test_params = array(
            'model' => 'gpt-3.5-turbo',
            'message' => 'Merhaba, bu bir test mesajıdır.',
            'max_tokens' => 50,
            'temperature' => 0.1
        );
        
        $result = $this->generate_response($test_params);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => 'OpenAI bağlantısı başarılı.',
                'model' => $test_params['model'],
                'response_time' => $result['request_time'] ?? 0,
                'tokens_used' => $result['usage']['total_tokens'] ?? 0
            );
        } else {
            return array(
                'success' => false,
                'message' => 'OpenAI bağlantısı başarısız: ' . $result['message']
            );
        }
    }

    /**
     * Modelleri listele
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_available_models() {
        
        // OpenAI models endpoint'i (opsiyonel)
        $models_endpoint = 'https://api.openai.com/v1/models';
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'User-Agent' => 'AI-Genius-WordPress-Plugin/' . AI_GENIUS_VERSION
        );
        
        $response = wp_remote_get($models_endpoint, array(
            'headers' => $headers,
            'timeout' => 10
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['data'])) {
                $available_models = array();
                
                foreach ($data['data'] as $model) {
                    $model_id = $model['id'];
                    
                    // Sadece desteklediğimiz modelleri filtrele
                    if (isset($this->supported_models[$model_id])) {
                        $available_models[$model_id] = array_merge(
                            $this->supported_models[$model_id],
                            array(
                                'id' => $model_id,
                                'created' => $model['created'] ?? null,
                                'owned_by' => $model['owned_by'] ?? 'openai'
                            )
                        );
                    }
                }
                
                return $available_models;
            }
        }
        
        // API'den alınamazsa statik listeyi döndür
        return $this->supported_models;
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
     * API kullanım istatistiklerini al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_usage_stats() {
        
        // Günlük kullanım verilerini transient'dan al
        $usage_key = 'ai_genius_openai_usage_' . current_time('Y-m-d');
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
            'last_request' => $this->last_request_info
        );
    }

    /**
     * Kullanım istatistiklerini güncelle
     * 
     * @since 1.0.0
     * @param array $usage Kullanım bilgileri
     */
    public function update_usage_stats($usage) {
        
        $usage_key = 'ai_genius_openai_usage_' . current_time('Y-m-d');
        $daily_usage = get_transient($usage_key);
        
        if ($daily_usage === false) {
            $daily_usage = array(
                'requests' => 0,
                'tokens' => 0,
                'cost' => 0.0
            );
        }
        
        $daily_usage['requests']++;
        $daily_usage['tokens'] += $usage['total_tokens'] ?? 0;
        $daily_usage['cost'] += $usage['estimated_cost'] ?? 0;
        
        // 24 saat boyunca sakla
        set_transient($usage_key, $daily_usage, DAY_IN_SECONDS);
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
     * Stream modunda yanıt al (gelecek özellik)
     * 
     * @since 1.0.0
     * @param array $params Parametreler
     * @return array
     */
    public function generate_stream_response($params) {
        
        // Bu özellik daha sonra implement edilecek
        return array(
            'success' => false,
            'message' => 'Stream modu henüz desteklenmiyor.'
        );
    }

    /**
     * Mesaj moderasyon kontrolü
     * 
     * @since 1.0.0
     * @param string $text Kontrol edilecek metin
     * @return array
     */
    public function moderate_content($text) {
        
        $moderation_endpoint = 'https://api.openai.com/v1/moderations';
        
        $headers = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->api_key
        );
        
        $request_data = array(
            'input' => $text
        );
        
        $response = wp_remote_post($moderation_endpoint, array(
            'headers' => $headers,
            'body' => json_encode($request_data),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Moderasyon API\'sine erişilemedi.'
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code !== 200) {
            return array(
                'success' => false,
                'message' => 'Moderasyon kontrolü başarısız.'
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!isset($data['results'])) {
            return array(
                'success' => false,
                'message' => 'Moderasyon yanıtı işlenemedi.'
            );
        }
        
        $result = $data['results'][0];
        
        return array(
            'success' => true,
            'flagged' => $result['flagged'] ?? false,
            'categories' => $result['categories'] ?? array(),
            'category_scores' => $result['category_scores'] ?? array()
        );
    }
}