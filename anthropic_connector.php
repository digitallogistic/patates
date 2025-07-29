<?php
/**
 * Anthropic Claude API connector sınıfı
 * 
 * Claude modelleri ile iletişim kurar
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
 * Anthropic Claude Connector Sınıfı
 * 
 * Claude API ile iletişim kurar ve yanıtları alır
 */
class AI_Genius_Anthropic_Connector {

    /**
     * Anthropic API endpoint
     * 
     * @since 1.0.0
     * @var string
     */
    private $api_endpoint = 'https://api.anthropic.com/v1/messages';

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
    private $api_version = '2023-06-01';

    /**
     * Desteklenen modeller
     * 
     * @since 1.0.0
     * @var array
     */
    private $supported_models = array(
        'claude-3-opus-20240229' => array(
            'name' => 'Claude 3 Opus',
            'max_tokens' => 4096,
            'context_window' => 200000,
            'cost_per_1k_tokens' => 0.015,
            'description' => 'En güçlü Claude modeli'
        ),
        'claude-3-sonnet-20240229' => array(
            'name' => 'Claude 3 Sonnet',
            'max_tokens' => 4096,
            'context_window' => 200000,
            'cost_per_1k_tokens' => 0.003,
            'description' => 'Dengeli performans ve hız'
        ),
        'claude-3-haiku-20240307' => array(
            'name' => 'Claude 3 Haiku',
            'max_tokens' => 4096,
            'context_window' => 200000,
            'cost_per_1k_tokens' => 0.00025,
            'description' => 'En hızlı ve ekonomik Claude'
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
     * @param string $api_key Anthropic API anahtarı
     */
    public function __construct($api_key) {
        
        $this->api_key = sanitize_text_field($api_key);
        
        if (empty($this->api_key)) {
            ai_genius_log('Anthropic API key is empty', 'warning');
        }
        
        ai_genius_log('Anthropic Connector initialized');
    }

    /**
     * Claude'dan yanıt al
     * 
     * @since 1.0.0
     * @param array $params İstek parametreleri
     * @return array
     */
    public function generate_response($params) {
        
        $defaults = array(
            'model' => 'claude-3-sonnet-20240229',
            'message' => '',
            'system_prompt' => '',
            'conversation_history' => array(),
            'max_tokens' => 500,
            'temperature' => 0.7,
            'context' => array()
        );
        
        $params = wp_parse_args($params, $defaults);
        
        // API anahtarı kontrolü
        if (empty($this->api_key)) {
            return array(
                'success' => false,
                'message' => 'Anthropic API anahtarı ayarlanmamış.',
                'error_code' => 'NO_API_KEY'
            );
        }
        
        // Model kontrolü
        if (!isset($this->supported_models[$params['model']])) {
            return array(
                'success' => false,
                'message' => 'Desteklenmeyen Claude modeli: ' . $params['model'],
                'error_code' => 'UNSUPPORTED_MODEL'
            );
        }
        
        // Mesajları Claude formatında hazırla
        $messages = $this->prepare_messages($params);
        
        // API isteği oluştur
        $request_data = array(
            'model' => $params['model'],
            'max_tokens' => intval($params['max_tokens']),
            'temperature' => floatval($params['temperature']),
            'messages' => $messages
        );
        
        // System prompt ayrı olarak eklenir
        if (!empty($params['system_prompt'])) {
            $request_data['system'] = $params['system_prompt'];
        }
        
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
     * Mesajları Claude formatında hazırla
     * 
     * @since 1.0.0
     * @param array $params Parametreler
     * @return array
     */
    private function prepare_messages($params) {
        
        $messages = array();
        
        // Sohbet geçmişini ekle (son 10 mesaj)
        if (!empty($params['conversation_history'])) {
            $history = array_slice($params['conversation_history'], -10);
            
            foreach ($history as $msg) {
                // Claude formatına dönüştür
                if ($msg['role'] === 'assistant') {
                    $messages[] = array(
                        'role' => 'assistant',
                        'content' => $msg['content']
                    );
                } elseif ($msg['role'] === 'user') {
                    $messages[] = array(
                        'role' => 'user',
                        'content' => $msg['content']
                    );
                }
            }
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
            'x-api-key' => $this->api_key,
            'anthropic-version' => $this->api_version,
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
            ai_genius_log('Anthropic API HTTP error: ' . $response->get_error_message(), 'error');
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
            ai_genius_log('Anthropic API JSON decode error: ' . json_last_error_msg(), 'error');
            return array(
                'error' => true,
                'message' => 'API yanıtı işlenirken hata oluştu.'
            );
        }
        
        // API hata kodu kontrolü
        if ($response_code !== 200) {
            $error_message = $this->get_api_error_message($response_code, $decoded_response);
            ai_genius_log('Anthropic API error: ' . $error_message, 'error');
            
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
        if (!isset($data['content']) || empty($data['content'])) {
            return array(
                'success' => false,
                'message' => 'Claude\'dan geçerli yanıt alınamadı.',
                'request_time' => $request_time
            );
        }
        
        // Claude'da content bir array olabilir
        $content = $data['content'];
        $message_text = '';
        
        if (is_array($content)) {
            foreach ($content as $block) {
                if (isset($block['type']) && $block['type'] === 'text') {
                    $message_text .= $block['text'] ?? '';
                }
            }
        } else {
            $message_text = $content;
        }
        
        if (empty($message_text)) {
            return array(
                'success' => false,
                'message' => 'Boş yanıt alındı.',
                'request_time' => $request_time
            );
        }
        
        // Kullanım bilgileri
        $usage = $data['usage'] ?? array();
        $input_tokens = $usage['input_tokens'] ?? 0;
        $output_tokens = $usage['output_tokens'] ?? 0;
        $total_tokens = $input_tokens + $output_tokens;
        
        // Maliyet hesaplama
        $cost = $this->calculate_cost($this->last_request_info['model'], $total_tokens);
        
        // Başarılı yanıt
        return array(
            'success' => true,
            'response' => trim($message_text),
            'request_time' => $request_time,
            'usage' => array(
                'input_tokens' => $input_tokens,
                'output_tokens' => $output_tokens,
                'total_tokens' => $total_tokens,
                'estimated_cost' => $cost
            ),
            'model' => $this->last_request_info['model'],
            'stop_reason' => $data['stop_reason'] ?? 'unknown'
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
        
        // Anthropic'den gelen hata mesajı
        if (isset($response_data['error']['message'])) {
            $api_error = $response_data['error']['message'];
            $error_type = $response_data['error']['type'] ?? '';
            
            return sprintf('Claude API Hatası (%s): %s', $error_type, $api_error);
        }
        
        // HTTP durum koduna göre genel mesajlar
        $error_messages = array(
            400 => 'Geçersiz istek. Parametreleri kontrol edin.',
            401 => 'API anahtarı geçersiz veya eksik.',
            403 => 'Bu işlem için yetkiniz bulunmuyor.',
            404 => 'API endpoint\'i bulunamadı.',
            429 => 'Rate limit aşıldı. Lütfen daha sonra tekrar deneyin.',
            500 => 'Claude sunucu hatası.',
            502 => 'Claude sunucusuna erişilemiyor.',
            503 => 'Claude servisi geçici olarak kullanılamıyor.',
            529 => 'Claude sistemi aşırı yüklü. Lütfen bekleyin.'
        );
        
        return $error_messages[$response_code] ?? 'Bilinmeyen API hatası (Kod: ' . $response_code . ')';
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
            'model' => 'claude-3-haiku-20240307', // En hızlı model
            'message' => 'Merhaba Claude, bu bir test mesajıdır. Lütfen kısa bir yanıt ver.',
            'max_tokens' => 50,
            'temperature' => 0.1
        );
        
        $result = $this->generate_response($test_params);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => 'Claude bağlantısı başarılı.',
                'model' => $test_params['model'],
                'response_time' => $result['request_time'] ?? 0,
                'tokens_used' => $result['usage']['total_tokens'] ?? 0
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Claude bağlantısı başarısız: ' . $result['message']
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
     * API kullanım istatistiklerini al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_usage_stats() {
        
        // Günlük kullanım verilerini transient'dan al
        $usage_key = 'ai_genius_anthropic_usage_' . current_time('Y-m-d');
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
        
        $usage_key = 'ai_genius_anthropic_usage_' . current_time('Y-m-d');
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
}