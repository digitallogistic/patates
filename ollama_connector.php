<?php
/**
 * Ollama API connector sınıfı
 * 
 * Yerel sunucuda çalışan Ollama AI modelleri ile iletişim kurar
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
 * Ollama Connector Sınıfı
 * 
 * Yerel Ollama sunucusu ile iletişim kurar
 */
class AI_Genius_Ollama_Connector {

    /**
     * Ollama API base URL
     * 
     * @since 1.0.0
     * @var string
     */
    private $api_base;

    /**
     * Popüler Ollama modelleri
     * 
     * @since 1.0.0
     * @var array
     */
    private $popular_models = array(
        'llama2' => array(
            'name' => 'Llama 2 7B',
            'size' => '3.8GB',
            'description' => 'Meta\'nın güçlü açık kaynak modeli',
            'language' => 'en',
            'use_case' => 'general',
            'cost' => 'free'
        ),
        'llama2:13b' => array(
            'name' => 'Llama 2 13B',
            'size' => '7.3GB',
            'description' => 'Daha büyük ve güçlü Llama 2 modeli',
            'language' => 'en',
            'use_case' => 'advanced',
            'cost' => 'free'
        ),
        'codellama' => array(
            'name' => 'Code Llama',
            'size' => '3.8GB',
            'description' => 'Kod yazma ve programlama için optimize edilmiş',
            'language' => 'code',
            'use_case' => 'coding',
            'cost' => 'free'
        ),
        'mistral' => array(
            'name' => 'Mistral 7B',
            'size' => '4.1GB',
            'description' => 'Hızlı ve verimli Avrupa menşeli model',
            'language' => 'en',
            'use_case' => 'general',
            'cost' => 'free'
        ),
        'neural-chat' => array(
            'name' => 'Neural Chat',
            'size' => '4.1GB',
            'description' => 'Sohbet için optimize edilmiş model',
            'language' => 'en',
            'use_case' => 'chat',
            'cost' => 'free'
        ),
        'orca-mini' => array(
            'name' => 'Orca Mini',
            'size' => '1.5GB',
            'description' => 'Küçük ve hızlı model',
            'language' => 'en',
            'use_case' => 'lightweight',
            'cost' => 'free'
        ),
        'phi' => array(
            'name' => 'Phi-2',
            'size' => '1.7GB',
            'description' => 'Microsoft\'un küçük ama güçlü modeli',
            'language' => 'en',
            'use_case' => 'efficient',
            'cost' => 'free'
        )
    );

    /**
     * Mevcut modeller (sunucudan alınır)
     * 
     * @since 1.0.0
     * @var array
     */
    private $available_models = array();

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
     * @param string $endpoint Ollama sunucu endpoint'i
     */
    public function __construct($endpoint = 'http://localhost:11434') {
        
        $this->api_base = rtrim(sanitize_text_field($endpoint), '/');
        
        // Mevcut modelleri yükle
        $this->load_available_models();
        
        ai_genius_log('Ollama Connector initialized with endpoint: ' . $this->api_base);
    }

    /**
     * Ollama'dan yanıt al
     * 
     * @since 1.0.0
     * @param array $params İstek parametreleri
     * @return array
     */
    public function generate_response($params) {
        
        $defaults = array(
            'model' => 'llama2',
            'message' => '',
            'system_prompt' => '',
            'conversation_history' => array(),
            'max_tokens' => 500,
            'temperature' => 0.7,
            'context' => array()
        );
        
        $params = wp_parse_args($params, $defaults);
        
        // Sunucu bağlantısını kontrol et
        if (!$this->is_server_available()) {
            return array(
                'success' => false,
                'message' => 'Ollama sunucusuna bağlanılamıyor. Sunucunun çalıştığından emin olun.',
                'error_code' => 'SERVER_UNAVAILABLE'
            );
        }
        
        // Model kontrolü
        if (!$this->is_model_available($params['model'])) {
            return array(
                'success' => false,
                'message' => 'Model mevcut değil: ' . $params['model'] . '. Önce modeli indirin.',
                'error_code' => 'MODEL_NOT_AVAILABLE',
                'suggestion' => $this->get_model_install_command($params['model'])
            );
        }
        
        // Chat API kullan
        return $this->generate_chat_response($params);
    }

    /**
     * Chat API ile yanıt oluştur
     * 
     * @since 1.0.0
     * @param array $params Parametreler
     * @return array
     */
    private function generate_chat_response($params) {
        
        // Mesajları hazırla
        $messages = $this->prepare_messages($params);
        
        // API isteği oluştur
        $request_data = array(
            'model' => $params['model'],
            'messages' => $messages,
            'stream' => false, // Stream desteği daha sonra eklenebilir
            'options' => array(
                'temperature' => floatval($params['temperature']),
                'num_predict' => intval($params['max_tokens'])
            )
        );
        
        // API çağrısı yap
        $start_time = microtime(true);
        $response = $this->make_api_request('/api/chat', $request_data);
        $request_time = microtime(true) - $start_time;
        
        // İstek bilgilerini kaydet
        $this->last_request_info = array(
            'model' => $params['model'],
            'request_time' => $request_time,
            'request_data' => $request_data,
            'raw_response' => $response
        );
        
        return $this->process_chat_response($response, $request_time);
    }

    /**
     * Mesajları Ollama formatında hazırla
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
        
        // Sohbet geçmişini ekle (son 8 mesaj)
        if (!empty($params['conversation_history'])) {
            $history = array_slice($params['conversation_history'], -8);
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
     * @param string $endpoint API endpoint'i
     * @param array $request_data İstek verisi
     * @return array
     */
    private function make_api_request($endpoint, $request_data) {
        
        $url = $this->api_base . $endpoint;
        
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'AI-Genius-WordPress-Plugin/' . AI_GENIUS_VERSION
        );
        
        // WordPress HTTP API kullan
        $response = wp_remote_post($url, array(
            'headers' => $headers,
            'body' => json_encode($request_data),
            'timeout' => 120, // Yerel model yanıtları biraz uzun sürebilir
            'sslverify' => false // Yerel sunucu için SSL doğrulama kapalı
        ));
        
        // HTTP hata kontrolü
        if (is_wp_error($response)) {
            ai_genius_log('Ollama API HTTP error: ' . $response->get_error_message(), 'error');
            return array(
                'error' => true,
                'message' => 'Ollama sunucusuna bağlantı hatası: ' . $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // JSON decode
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ai_genius_log('Ollama API JSON decode error: ' . json_last_error_msg(), 'error');
            return array(
                'error' => true,
                'message' => 'Ollama yanıtı işlenirken hata oluştu.',
                'raw_body' => $response_body
            );
        }
        
        // API hata kontrolü
        if ($response_code !== 200) {
            $error_message = $this->get_api_error_message($response_code, $decoded_response);
            ai_genius_log('Ollama API error: ' . $error_message, 'error');
            
            return array(
                'error' => true,
                'message' => $error_message,
                'response_code' => $response_code
            );
        }
        
        return array(
            'error' => false,
            'data' => $decoded_response,
            'response_code' => $response_code
        );
    }

    /**
     * Chat yanıtını işle
     * 
     * @since 1.0.0
     * @param array $response API yanıtı
     * @param float $request_time İstek süresi
     * @return array
     */
    private function process_chat_response($response, $request_time) {
        
        if ($response['error']) {
            return array(
                'success' => false,
                'message' => $response['message'],
                'request_time' => $request_time
            );
        }
        
        $data = $response['data'];
        
        // Yanıt kontrolü
        if (!isset($data['message']['content'])) {
            return array(
                'success' => false,
                'message' => 'Ollama\'dan geçerli yanıt alınamadı.',
                'request_time' => $request_time
            );
        }
        
        $message_content = $data['message']['content'];
        
        if (empty($message_content)) {
            return array(
                'success' => false,
                'message' => 'Boş yanıt alındı.',
                'request_time' => $request_time
            );
        }
        
        // Türkçe yanıt için post-processing
        $processed_content = $this->post_process_response($message_content);
        
        // Token sayısını tahmin et (yaklaşık)
        $estimated_tokens = $this->estimate_tokens($message_content);
        
        return array(
            'success' => true,
            'response' => trim($processed_content),
            'request_time' => $request_time,
            'usage' => array(
                'estimated_tokens' => $estimated_tokens,
                'cost' => 0.0 // Yerel model - ücretsiz
            ),
            'model' => $this->last_request_info['model'],
            'provider' => 'ollama',
            'done' => $data['done'] ?? true,
            'total_duration' => $data['total_duration'] ?? null,
            'load_duration' => $data['load_duration'] ?? null,
            'prompt_eval_count' => $data['prompt_eval_count'] ?? null,
            'eval_count' => $data['eval_count'] ?? null
        );
    }

    /**
     * Yanıtı post-process et
     * 
     * @since 1.0.0
     * @param string $content Yanıt içeriği
     * @return string
     */
    private function post_process_response($content) {
        
        // Gereksiz boşlukları temizle
        $content = trim($content);
        
        // Çok uzun yanıtları makul uzunlukta kes
        if (strlen($content) > 800) {
            $sentences = preg_split('/[.!?]+/', $content);
            $result = '';
            
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (empty($sentence)) continue;
                
                if (strlen($result . $sentence) > 600) {
                    break;
                }
                $result .= $sentence . '. ';
            }
            
            $content = rtrim($result);
        }
        
        // İngilizce yanıtları Türkçe'ye çevir (basit kontrol)
        if ($this->should_translate_to_turkish($content)) {
            $content = $this->simple_english_to_turkish($content);
        }
        
        return $content;
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
        if (!get_option('ai_genius_ollama_auto_translate', true)) {
            return false;
        }
        
        // Zaten Türkçe karakterler varsa çevirme
        if (preg_match('/[çğıöşüÇĞIİÖŞÜ]/', $text)) {
            return false;
        }
        
        // Çok kısa metinleri çevirme
        if (strlen($text) < 20) {
            return false;
        }
        
        // Çoğunlukla İngilizce kelimeleri tespit et
        $english_indicators = array('the', 'and', 'is', 'are', 'was', 'were', 'have', 'has', 'will', 'would', 'can', 'could');
        $text_lower = strtolower($text);
        $english_count = 0;
        
        foreach ($english_indicators as $word) {
            if (strpos($text_lower, ' ' . $word . ' ') !== false || 
                strpos($text_lower, $word . ' ') === 0) {
                $english_count++;
            }
        }
        
        return $english_count >= 2; // En az 2 İngilizce gösterge kelime varsa çevir
    }

    /**
     * Basit İngilizce-Türkçe çeviri
     * 
     * @since 1.0.0
     * @param string $text İngilizce metin
     * @return string
     */
    private function simple_english_to_turkish($text) {
        
        // Basit kelime çevirileri
        $translations = array(
            'Hello' => 'Merhaba',
            'hello' => 'merhaba',
            'Hi' => 'Selam',
            'hi' => 'selam',
            'Thank you' => 'Teşekkür ederim',
            'thank you' => 'teşekkür ederim',
            'Thanks' => 'Teşekkürler',
            'thanks' => 'teşekkürler',
            'Yes' => 'Evet',
            'yes' => 'evet',
            'No' => 'Hayır',
            'no' => 'hayır',
            'Please' => 'Lütfen',
            'please' => 'lütfen',
            'Sorry' => 'Üzgünüm',
            'sorry' => 'üzgünüm',
            'I understand' => 'Anlıyorum',
            'i understand' => 'anlıyorum',
            'I can help' => 'Yardımcı olabilirim',
            'i can help' => 'yardımcı olabilirim',
            'How can I help' => 'Nasıl yardımcı olabilirim',
            'how can i help' => 'nasıl yardımcı olabilirim',
            'What do you need' => 'Neye ihtiyacınız var',
            'what do you need' => 'neye ihtiyacınız var',
            'I don\'t know' => 'Bilmiyorum',
            'i don\'t know' => 'bilmiyorum',
            'Let me help you' => 'Size yardımcı olayım',
            'let me help you' => 'size yardımcı olayım'
        );
        
        foreach ($translations as $english => $turkish) {
            $text = str_replace($english, $turkish, $text);
        }
        
        // Başlangıç mesajlarını düzelt
        if (strpos($text, 'I am') === 0 || strpos($text, 'I\'m') === 0) {
            $text = 'Ben bir AI asistanıyım. ' . substr($text, strpos($text, ' ') + 1);
        }
        
        return $text;
    }

    /**
     * Token sayısını tahmin et
     * 
     * @since 1.0.0
     * @param string $text Metin
     * @return int
     */
    private function estimate_tokens($text) {
        
        // Basit tahmin: yaklaşık 4 karakter = 1 token
        return intval(strlen($text) / 4);
    }

    /**
     * Sunucu mevcut mu kontrol et
     * 
     * @since 1.0.0
     * @return bool
     */
    private function is_server_available() {
        
        $response = wp_remote_get($this->api_base . '/api/tags', array(
            'timeout' => 5,
            'sslverify' => false
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    /**
     * Model mevcut mu kontrol et
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @return bool
     */
    private function is_model_available($model) {
        
        if (empty($this->available_models)) {
            $this->load_available_models();
        }
        
        return in_array($model, $this->available_models);
    }

    /**
     * Mevcut modelleri yükle
     * 
     * @since 1.0.0
     */
    private function load_available_models() {
        
        // Cache'den kontrol et
        $cache_key = 'ai_genius_ollama_models_' . md5($this->api_base);
        $cached_models = get_transient($cache_key);
        
        if ($cached_models !== false) {
            $this->available_models = $cached_models;
            return;
        }
        
        $response = wp_remote_get($this->api_base . '/api/tags', array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $this->available_models = array();
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['models']) && is_array($data['models'])) {
            $this->available_models = array_map(function($model) {
                return $model['name'];
            }, $data['models']);
            
            // 5 dakika cache'le
            set_transient($cache_key, $this->available_models, 5 * MINUTE_IN_SECONDS);
        }
    }

    /**
     * Model kurulum komutunu al
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @return string
     */
    private function get_model_install_command($model) {
        
        return "ollama pull {$model}";
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
        
        // Ollama'dan gelen hata mesajı
        if (isset($response_data['error'])) {
            return 'Ollama Hatası: ' . $response_data['error'];
        }
        
        // HTTP durum koduna göre genel mesajlar
        $error_messages = array(
            404 => 'Model bulunamadı. Model kurulu olduğundan emin olun.',
            500 => 'Ollama sunucu hatası.',
            503 => 'Ollama servisi kullanılamıyor.'
        );
        
        return $error_messages[$response_code] ?? 'Bilinmeyen Ollama hatası (Kod: ' . $response_code . ')';
    }

    /**
     * Bağlantıyı test et
     * 
     * @since 1.0.0
     * @return array
     */
    public function test_connection() {
        
        // Önce sunucu erişilebilirliğini kontrol et
        if (!$this->is_server_available()) {
            return array(
                'success' => false,
                'message' => 'Ollama sunucusuna erişilemiyor. Sunucunun çalıştığından emin olun.',
                'endpoint' => $this->api_base,
                'suggestion' => 'Terminalde "ollama serve" komutunu çalıştırın.'
            );
        }
        
        // Mevcut modelleri yükle
        $this->load_available_models();
        
        if (empty($this->available_models)) {
            return array(
                'success' => false,
                'message' => 'Ollama sunucusunda model bulunamadı.',
                'suggestion' => 'Bir model indirin: ollama pull llama2'
            );
        }
        
        // İlk mevcut modeli test et
        $test_model = $this->available_models[0];
        
        $test_params = array(
            'model' => $test_model,
            'message' => 'Hello, this is a connection test.',
            'max_tokens' => 20,
            'temperature' => 0.1
        );
        
        $result = $this->generate_response($test_params);
        
        if ($result['success']) {
            return array(
                'success' => true,
                'message' => 'Ollama bağlantısı başarılı.',
                'endpoint' => $this->api_base,
                'model' => $test_model,
                'available_models' => count($this->available_models),
                'response_time' => $result['request_time'] ?? 0
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Ollama bağlantısı başarısız: ' . $result['message']
            );
        }
    }

    /**
     * Mevcut modelleri al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_available_models() {
        
        $this->load_available_models();
        
        $models_info = array();
        
        foreach ($this->available_models as $model) {
            // Popüler model bilgisi varsa kullan
            if (isset($this->popular_models[$model])) {
                $models_info[$model] = $this->popular_models[$model];
            } else {
                // Temel bilgi
                $models_info[$model] = array(
                    'name' => $model,
                    'size' => 'Bilinmiyor',
                    'description' => 'Yerel Ollama modeli',
                    'language' => 'en',
                    'use_case' => 'general',
                    'cost' => 'free'
                );
            }
        }
        
        return $models_info;
    }

    /**
     * Model bilgilerini al
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @return array|null
     */
    public function get_model_info($model) {
        
        // Önce popüler modellerden ara
        if (isset($this->popular_models[$model])) {
            return $this->popular_models[$model];
        }
        
        // Sunucudan detaylı bilgi al
        $response = wp_remote_post($this->api_base . '/api/show', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode(array('name' => $model)),
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($data) {
                return array(
                    'name' => $model,
                    'size' => $this->format_bytes($data['size'] ?? 0),
                    'description' => $data['details']['family'] ?? 'Yerel Ollama modeli',
                    'parameters' => $data['details']['parameter_size'] ?? 'Bilinmiyor',
                    'quantization' => $data['details']['quantization_level'] ?? 'Bilinmiyor',
                    'language' => 'en',
                    'cost' => 'free'
                );
            }
        }
        
        return null;
    }

    /**
     * Byte formatını düzenle
     * 
     * @since 1.0.0
     * @param int $bytes Byte sayısı
     * @return string
     */
    private function format_bytes($bytes) {
        
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Model indirme durumunu kontrol et
     * 
     * @since 1.0.0
     * @param string $model Model adı
     * @return array
     */
    public function check_model_pull_status($model) {
        
        // Bu özellik daha gelişmiş bir implementasyon gerektirir
        // Şimdilik basit kontrol yapalım
        
        if ($this->is_model_available($model)) {
            return array(
                'status' => 'ready',
                'message' => 'Model hazır.'
            );
        } else {
            return array(
                'status' => 'not_found',
                'message' => 'Model mevcut değil.',
                'install_command' => $this->get_model_install_command($model)
            );
        }
    }

    /**
     * Önerilen modelleri al
     * 
     * @since 1.0.0
     * @param string $use_case Kullanım senaryosu
     * @return array
     */
    public function get_recommended_models($use_case = 'general') {
        
        $recommendations = array();
        
        switch ($use_case) {
            case 'lightweight':
                $recommendations = array('orca-mini', 'phi');
                break;
                
            case 'coding':
                $recommendations = array('codellama');
                break;
                
            case 'chat':
                $recommendations = array('neural-chat', 'llama2');
                break;
                
            case 'advanced':
                $recommendations = array('llama2:13b', 'mistral');
                break;
                
            default: // general
                $recommendations = array('llama2', 'mistral', 'neural-chat');
        }
        
        // Sadece mevcut modelleri döndür
        $this->load_available_models();
        $available_recommendations = array();
        
        foreach ($recommendations as $model) {
            if (in_array($model, $this->available_models)) {
                $available_recommendations[] = $model;
            }
        }
        
        return $available_recommendations;
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
     * Sunucu durumunu al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_server_status() {
        
        $status = array(
            'endpoint' => $this->api_base,
            'available' => false,
            'models_count' => 0,
            'models' => array()
        );
        
        if ($this->is_server_available()) {
            $status['available'] = true;
            $this->load_available_models();
            $status['models_count'] = count($this->available_models);
            $status['models'] = $this->available_models;
        }
        
        return $status;
    }

    /**
     * API kullanım istatistiklerini al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_usage_stats() {
        
        $usage_key = 'ai_genius_ollama_usage_' . current_time('Y-m-d');
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
            'server_status' => $this->get_server_status()
        );
    }

    /**
     * Kullanım istatistiklerini güncelle
     * 
     * @since 1.0.0
     * @param array $usage Kullanım bilgileri
     */
    public function update_usage_stats($usage) {
        
        $usage_key = 'ai_genius_ollama_usage_' . current_time('Y-m-d');
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
        // Ollama yerel ve ücretsiz olduğu için cost 0
        
        set_transient($usage_key, $daily_usage, DAY_IN_SECONDS);
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
}
     