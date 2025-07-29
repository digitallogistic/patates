<?php
/**
 * Ana AI işlemci sınıfı
 * 
 * Farklı AI servislerini yönetir ve birleştirir
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
 * AI İşlemci Sınıfı
 * 
 * Tüm AI servislerini yönetir ve kullanıcı sorularını yanıtlar
 */
class AI_Genius_AI_Processor {

    /**
     * Desteklenen AI servisleri
     * 
     * @since 1.0.0
     * @var array
     */
    private $supported_providers = array(
        // Ücretli AI Servisleri
        'openai' => array(
            'name' => 'OpenAI',
            'type' => 'premium',
            'models' => array(
                'gpt-4' => 'GPT-4 (En Gelişmiş)',
                'gpt-4-turbo' => 'GPT-4 Turbo (Hızlı)',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo (Ekonomik)'
            ),
            'description' => 'En gelişmiş ve güvenilir AI servisi',
            'requires_api_key' => true,
            'pricing_info' => 'Token başına ücretlendirme'
        ),
        'anthropic' => array(
            'name' => 'Anthropic Claude',
            'type' => 'premium',
            'models' => array(
                'claude-3-opus' => 'Claude 3 Opus (En Güçlü)',
                'claude-3-sonnet' => 'Claude 3 Sonnet (Dengeli)',
                'claude-3-haiku' => 'Claude 3 Haiku (Hızlı)'
            ),
            'description' => 'Güvenli ve etik AI çözümleri',
            'requires_api_key' => true,
            'pricing_info' => 'Token başına ücretlendirme'
        ),
        'google' => array(
            'name' => 'Google Gemini',
            'type' => 'premium',
            'models' => array(
                'gemini-pro' => 'Gemini Pro',
                'gemini-pro-vision' => 'Gemini Pro Vision'
            ),
            'description' => 'Google\'ın gelişmiş AI modeli',
            'requires_api_key' => true,
            'pricing_info' => 'Aylık ücretsiz kotası mevcut'
        ),
        
        // Ücretsiz AI Servisleri
        'huggingface' => array(
            'name' => 'Hugging Face',
            'type' => 'free',
            'models' => array(
                'microsoft/DialoGPT-medium' => 'DialoGPT Medium',
                'facebook/blenderbot-400M-distill' => 'BlenderBot',
                'microsoft/DialoGPT-small' => 'DialoGPT Small'
            ),
            'description' => 'Açık kaynak AI modelleri',
            'requires_api_key' => false,
            'pricing_info' => 'Tamamen ücretsiz (rate limit var)'
        ),
        'cohere' => array(
            'name' => 'Cohere',
            'type' => 'freemium',
            'models' => array(
                'command' => 'Command',
                'command-light' => 'Command Light'
            ),
            'description' => 'Ücretsiz kotası olan AI servisi',
            'requires_api_key' => true,
            'pricing_info' => 'Aylık ücretsiz kotası'
        ),
        'ollama' => array(
            'name' => 'Ollama (Yerel)',
            'type' => 'free',
            'models' => array(
                'llama2' => 'Llama 2',
                'codellama' => 'Code Llama',
                'mistral' => 'Mistral 7B'
            ),
            'description' => 'Yerel sunucuda çalışan AI modelleri',
            'requires_api_key' => false,
            'pricing_info' => 'Tamamen ücretsiz (kendi sunucunuzda)'
        )
    );

    /**
     * Aktif AI servisi
     * 
     * @since 1.0.0
     * @var string
     */
    private $active_provider;

    /**
     * Aktif model
     * 
     * @since 1.0.0
     * @var string
     */
    private $active_model;

    /**
     * AI connector nesneleri
     * 
     * @since 1.0.0
     * @var array
     */
    private $connectors = array();

    /**
     * Veritabanı sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_Database
     */
    private $database;

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        $this->database = new AI_Genius_Database();
        
        // Aktif servis ayarlarını yükle
        $this->load_active_settings();
        
        // AI connector'ları yükle
        $this->load_connectors();
        
        ai_genius_log('AI Processor initialized with provider: ' . $this->active_provider);
    }

    /**
     * Aktif AI ayarlarını yükle
     * 
     * @since 1.0.0
     */
    private function load_active_settings() {
        
        $this->active_provider = get_option('ai_genius_api_provider', 'huggingface');
        $this->active_model = get_option('ai_genius_model', '');
        
        // Model seçilmemişse varsayılanı ayarla
        if (empty($this->active_model) && isset($this->supported_providers[$this->active_provider])) {
            $models = array_keys($this->supported_providers[$this->active_provider]['models']);
            $this->active_model = $models[0] ?? '';
        }
    }

    /**
     * AI connector sınıflarını yükle
     * 
     * @since 1.0.0
     */
    private function load_connectors() {
        
        // Connector dosyalarını include et
        require_once AI_GENIUS_PLUGIN_DIR . 'api/connectors/class-openai-connector.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/connectors/class-anthropic-connector.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/connectors/class-google-connector.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/connectors/class-huggingface-connector.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/connectors/class-cohere-connector.php';
        require_once AI_GENIUS_PLUGIN_DIR . 'api/connectors/class-ollama-connector.php';
    }

    /**
     * Kullanıcı mesajını işle ve yanıt oluştur
     * 
     * @since 1.0.0
     * @param string $user_message Kullanıcı mesajı
     * @param array $context Sohbet bağlamı
     * @return array
     */
    public function process_message($user_message, $context = array()) {
        
        $start_time = microtime(true);
        
        // Mesajı sanitize et
        $user_message = sanitize_text_field($user_message);
        
        if (empty($user_message)) {
            return array(
                'success' => false,
                'message' => __('Mesaj boş olamaz.', 'ai-genius')
            );
        }
        
        // Rate limiting kontrolü
        if (!$this->check_rate_limit($context)) {
            return array(
                'success' => false,
                'message' => __('Çok fazla istek gönderildi. Lütfen bekleyin.', 'ai-genius')
            );
        }
        
        try {
            // 1. Önce bilgi tabanından ara
            $knowledge_result = $this->search_knowledge_base($user_message);
            
            // 2. Bilgi tabanında yanıt bulunursa bunu kullan
            if ($knowledge_result['found']) {
                $response = $this->enhance_knowledge_response($knowledge_result['response'], $user_message);
                $source = 'knowledge_base';
            } else {
                // 3. AI servisinden yanıt al
                $ai_result = $this->get_ai_response($user_message, $context);
                
                if (!$ai_result['success']) {
                    return $ai_result;
                }
                
                $response = $ai_result['response'];
                $source = 'ai_service';
            }
            
            // İşlem süresini hesapla
            $response_time = microtime(true) - $start_time;
            
            // Sohbet geçmişini kaydet
            $this->save_chat_interaction($user_message, $response, $context, $response_time, $source);
            
            // Başarılı yanıt döndür
            return array(
                'success' => true,
                'response' => $response,
                'source' => $source,
                'response_time' => $response_time,
                'provider' => $this->active_provider,
                'model' => $this->active_model
            );
            
        } catch (Exception $e) {
            ai_genius_log('Error processing message: ' . $e->getMessage(), 'error');
            
            return array(
                'success' => false,
                'message' => __('Yanıt oluşturulurken bir hata oluştu.', 'ai-genius'),
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * AI servisinden yanıt al
     * 
     * @since 1.0.0
     * @param string $message Kullanıcı mesajı
     * @param array $context Bağlam bilgileri
     * @return array
     */
    private function get_ai_response($message, $context) {
        
        // Uygun connector'ı al
        $connector = $this->get_connector($this->active_provider);
        
        if (!$connector) {
            return array(
                'success' => false,
                'message' => __('AI servisi bağlantısı kurulamadı.', 'ai-genius')
            );
        }
        
        // System prompt'u hazırla
        $system_prompt = $this->build_system_prompt($context);
        
        // Sohbet geçmişini hazırla
        $conversation_history = $this->build_conversation_history($context);
        
        // AI'dan yanıt al
        $result = $connector->generate_response(array(
            'model' => $this->active_model,
            'message' => $message,
            'system_prompt' => $system_prompt,
            'conversation_history' => $conversation_history,
            'max_tokens' => get_option('ai_genius_max_tokens', 500),
            'temperature' => floatval(get_option('ai_genius_temperature', 0.7)),
            'context' => $context
        ));
        
        // Fallback mekanizması
        if (!$result['success'] && $this->has_fallback_provider()) {
            ai_genius_log('Primary AI failed, trying fallback provider');
            $result = $this->try_fallback_provider($message, $context);
        }
        
        return $result;
    }

    /**
     * Bilgi tabanında arama yap
     * 
     * @since 1.0.0
     * @param string $query Arama sorgusu
     * @return array
     */
    private function search_knowledge_base($query) {
        
        $results = $this->database->search_knowledge_base($query, 3);
        
        if (empty($results)) {
            return array('found' => false);
        }
        
        // En iyi sonucu seç
        $best_result = $results[0];
        
        // Kullanım sayısını artır
        $this->database->increment_knowledge_usage($best_result['id']);
        
        return array(
            'found' => true,
            'response' => $best_result['content'],
            'title' => $best_result['title'],
            'relevance' => $best_result['relevance'] ?? 1.0
        );
    }

    /**
     * Bilgi tabanı yanıtını AI ile geliştir
     * 
     * @since 1.0.0
     * @param string $knowledge_response Bilgi tabanı yanıtı
     * @param string $user_question Kullanıcı sorusu
     * @return string
     */
    private function enhance_knowledge_response($knowledge_response, $user_question) {
        
        // Basit AI geliştirmesi isteniyorsa
        if (get_option('ai_genius_enhance_knowledge', false)) {
            
            $connector = $this->get_connector($this->active_provider);
            
            if ($connector) {
                $enhancement_prompt = sprintf(
                    'Kullanıcı şunu sordu: "%s"\n\nMevcut yanıtımız: "%s"\n\nBu yanıtı daha kişisel ve yardımcı hale getir, ancak temel bilgiyi değiştirme.',
                    $user_question,
                    $knowledge_response
                );
                
                $result = $connector->generate_response(array(
                    'model' => $this->active_model,
                    'message' => $enhancement_prompt,
                    'max_tokens' => 200,
                    'temperature' => 0.5
                ));
                
                if ($result['success']) {
                    return $result['response'];
                }
            }
        }
        
        return $knowledge_response;
    }

    /**
     * System prompt'u oluştur
     * 
     * @since 1.0.0
     * @param array $context Bağlam
     * @return string
     */
    private function build_system_prompt($context) {
        
        $bot_settings = $this->database->get_bot_settings();
        
        $bot_name = $bot_settings['bot_name'] ?? 'AI Asistan';
        $bot_personality = $bot_settings['bot_personality'] ?? 'Yardımsever ve dostane bir asistan';
        
        $system_prompt = sprintf(
            'Sen %s isimli bir AI asistansın. Kişiliğin: %s

Kurallar:
1. Her zaman Türkçe yanıt ver
2. Kısa ve öz yanıtlar ver
3. Eğer bir konuda emin değilsen, bunu belirt
4. Zararlı, yasadışı veya etik olmayan içerik üretme
5. Kullanıcıya saygılı davran

Mevcut tarih: %s',
            $bot_name,
            $bot_personality,
            current_time('Y-m-d H:i')
        );
        
        // Site bilgilerini ekle
        $site_info = $this->get_site_context();
        if (!empty($site_info)) {
            $system_prompt .= "\n\nSite hakkında bilgiler:\n" . $site_info;
        }
        
        return $system_prompt;
    }

    /**
     * Sohbet geçmişini hazırla
     * 
     * @since 1.0.0
     * @param array $context Bağlam
     * @return array
     */
    private function build_conversation_history($context) {
        
        $session_id = $context['session_id'] ?? '';
        
        if (empty($session_id)) {
            return array();
        }
        
        $history = $this->database->get_chat_history($session_id, 10);
        $formatted_history = array();
        
        foreach ($history as $chat) {
            $formatted_history[] = array(
                'role' => 'user',
                'content' => $chat['user_message']
            );
            $formatted_history[] = array(
                'role' => 'assistant',  
                'content' => $chat['bot_response']
            );
        }
        
        return $formatted_history;
    }

    /**
     * Site bağlam bilgilerini al
     * 
     * @since 1.0.0
     * @return string
     */
    private function get_site_context() {
        
        $context_parts = array();
        
        // Site adı ve açıklaması
        $context_parts[] = 'Site adı: ' . get_bloginfo('name');
        $context_parts[] = 'Site açıklaması: ' . get_bloginfo('description');
        
        // WooCommerce aktifse ürün bilgileri
        if (class_exists('WooCommerce')) {
            $context_parts[] = 'Bu bir e-ticaret sitesidir.';
            
            // Popüler kategoriler
            $categories = get_terms(array(
                'taxonomy' => 'product_cat',
                'hide_empty' => true,
                'number' => 5
            ));
            
            if (!empty($categories)) {
                $cat_names = array_map(function($cat) { return $cat->name; }, $categories);
                $context_parts[] = 'Ürün kategorileri: ' . implode(', ', $cat_names);
            }
        }
        
        return implode("\n", $context_parts);
    }

    /**
     * Sohbet etkileşimini kaydet
     * 
     * @since 1.0.0
     * @param string $user_message Kullanıcı mesajı
     * @param string $bot_response Bot yanıtı
     * @param array $context Bağlam
     * @param float $response_time Yanıt süresi
     * @param string $source Yanıt kaynağı
     */
    private function save_chat_interaction($user_message, $bot_response, $context, $response_time, $source) {
        
        $data = array(
            'session_id' => $context['session_id'] ?? uniqid(),
            'user_id' => get_current_user_id() ?: null,
            'user_message' => $user_message,
            'bot_response' => $bot_response,
            'response_time' => $response_time,
            'message_type' => 'text'
        );
        
        $chat_id = $this->database->save_chat_history($data);
        
        // Analitik güncelle
        if ($chat_id) {
            $this->update_analytics($source, $response_time);
        }
    }

    /**
     * Rate limiting kontrolü
     * 
     * @since 1.0.0
     * @param array $context Bağlam
     * @return bool
     */
    private function check_rate_limit($context) {
        
        $rate_limit = get_option('ai_genius_rate_limit', 10);
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $transient_key = 'ai_genius_rate_' . md5($user_ip);
        $current_requests = get_transient($transient_key);
        
        if ($current_requests === false) {
            set_transient($transient_key, 1, MINUTE_IN_SECONDS);
            return true;
        }
        
        if ($current_requests >= $rate_limit) {
            return false;
        }
        
        set_transient($transient_key, $current_requests + 1, MINUTE_IN_SECONDS);
        return true;
    }

    /**
     * Analitik verileri güncelle
     * 
     * @since 1.0.0
     * @param string $source Yanıt kaynağı
     * @param float $response_time Yanıt süresi
     */
    private function update_analytics($source, $response_time) {
        
        $today = current_time('Y-m-d');
        
        // Günlük sohbet sayısı
        $current_chats = $this->database->get_analytics('daily_chats', $today, $today);
        $chat_count = !empty($current_chats) ? $current_chats[0]['metric_value'] + 1 : 1;
        $this->database->save_analytics('daily_chats', $chat_count, $today);
        
        // Ortalama yanıt süresi
        $current_avg = $this->database->get_analytics('avg_response_time', $today, $today);
        if (!empty($current_avg)) {
            $new_avg = ($current_avg[0]['metric_value'] + $response_time) / 2;
        } else {
            $new_avg = $response_time;
        }
        $this->database->save_analytics('avg_response_time', $new_avg, $today);
        
        // Kaynak bazlı istatistikler
        $source_metric = 'responses_from_' . $source;
        $current_source = $this->database->get_analytics($source_metric, $today, $today);
        $source_count = !empty($current_source) ? $current_source[0]['metric_value'] + 1 : 1;
        $this->database->save_analytics($source_metric, $source_count, $today);
    }

    /**
     * Belirtilen provider için connector al
     * 
     * @since 1.0.0
     * @param string $provider Provider adı
     * @return object|null
     */
    private function get_connector($provider) {
        
        if (isset($this->connectors[$provider])) {
            return $this->connectors[$provider];
        }
        
        $connector = null;
        
        switch ($provider) {
            case 'openai':
                $api_key = get_option('ai_genius_openai_api_key', '');
                if (!empty($api_key)) {
                    $connector = new AI_Genius_OpenAI_Connector($api_key);
                }
                break;
                
            case 'anthropic':
                $api_key = get_option('ai_genius_anthropic_api_key', '');
                if (!empty($api_key)) {
                    $connector = new AI_Genius_Anthropic_Connector($api_key);
                }
                break;
                
            case 'google':
                $api_key = get_option('ai_genius_google_api_key', '');
                if (!empty($api_key)) {
                    $connector = new AI_Genius_Google_Connector($api_key);
                }
                break;
                
            case 'huggingface':
                $connector = new AI_Genius_HuggingFace_Connector();
                break;
                
            case 'cohere':
                $api_key = get_option('ai_genius_cohere_api_key', '');
                $connector = new AI_Genius_Cohere_Connector($api_key);
                break;
                
            case 'ollama':
                $endpoint = get_option('ai_genius_ollama_endpoint', 'http://localhost:11434');
                $connector = new AI_Genius_Ollama_Connector($endpoint);
                break;
        }
        
        if ($connector) {
            $this->connectors[$provider] = $connector;
        }
        
        return $connector;
    }

    /**
     * Fallback provider var mı kontrol et
     * 
     * @since 1.0.0
     * @return bool
     */
    private function has_fallback_provider() {
        
        $fallback_provider = get_option('ai_genius_fallback_provider', '');
        return !empty($fallback_provider) && $fallback_provider !== $this->active_provider;
    }

    /**
     * Fallback provider ile dene
     * 
     * @since 1.0.0
     * @param string $message Mesaj
     * @param array $context Bağlam
     * @return array
     */
    private function try_fallback_provider($message, $context) {
        
        $fallback_provider = get_option('ai_genius_fallback_provider', '');
        $original_provider = $this->active_provider;
        
        // Geçici olarak fallback provider'a geç
        $this->active_provider = $fallback_provider;
        
        // Fallback model ayarla
        if (isset($this->supported_providers[$fallback_provider])) {
            $models = array_keys($this->supported_providers[$fallback_provider]['models']);
            $this->active_model = $models[0] ?? '';
        }
        
        $result = $this->get_ai_response($message, $context);
        
        // Orijinal provider'a geri dön
        $this->active_provider = $original_provider;
        $this->load_active_settings();
        
        return $result;
    }

    /**
     * Desteklenen provider'ları döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_supported_providers() {
        return $this->supported_providers;
    }

    /**
     * Aktif provider bilgilerini döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_active_provider_info() {
        
        return array(
            'provider' => $this->active_provider,
            'model' => $this->active_model,
            'info' => $this->supported_providers[$this->active_provider] ?? null
        );
    }

    /**
     * Provider'ın çalışıp çalışmadığını test et
     * 
     * @since 1.0.0
     * @param string $provider Provider adı
     * @return array
     */
    public function test_provider($provider) {
        
        $connector = $this->get_connector($provider);
        
        if (!$connector) {
            return array(
                'success' => false,
                'message' => 'Connector oluşturulamadı.'
            );
        }
        
        return $connector->test_connection();
    }

    /**
     * Provider ayarlarını güncelle
     * 
     * @since 1.0.0
     * @param string $provider Provider adı
     * @param string $model Model adı
     * @return bool
     */
    public function update_provider_settings($provider, $model) {
        
        if (!isset($this->supported_providers[$provider])) {
            return false;
        }
        
        if (!isset($this->supported_providers[$provider]['models'][$model])) {
            return false;
        }
        
        update_option('ai_genius_api_provider', $provider);
        update_option('ai_genius_model', $model);
        
        $this->active_provider = $provider;
        $this->active_model = $model;
        
        // Connector cache'ini temizle
        unset($this->connectors[$provider]);
        
        ai_genius_log("Provider settings updated: $provider - $model");
        
        return true;
    }

    /**
     * Ücretsiz provider'ları al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_free_providers() {
        
        return array_filter($this->supported_providers, function($provider) {
            return in_array($provider['type'], array('free', 'freemium'));
        });
    }

    /**
     * Premium provider'ları al
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_premium_providers() {
        
        return array_filter($this->supported_providers, function($provider) {
            return $provider['type'] === 'premium';
        });
    }
}