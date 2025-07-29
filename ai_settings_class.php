                                <select id="ai_model" name="ai_model" class="regular-text">
                                    <!-- JavaScript ile doldurulacak -->
                                </select>
                                <p class="description">
                                    <?php _e('Seçili provider için kullanılacak AI modelini seçin.', 'ai-genius'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="max_tokens"><?php _e('Maksimum Token', 'ai-genius'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="max_tokens" name="max_tokens" 
                                       value="<?php echo esc_attr(get_option('ai_genius_max_tokens', 500)); ?>" 
                                       min="50" max="4000" class="regular-text">
                                <p class="description">
                                    <?php _e('AI\'ın üretebileceği maksimum token sayısı (kelime sayısını etkiler).', 'ai-genius'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="temperature"><?php _e('Yaratıcılık Seviyesi', 'ai-genius'); ?></label>
                            </th>
                            <td>
                                <input type="range" id="temperature" name="temperature" 
                                       value="<?php echo esc_attr(get_option('ai_genius_temperature', 0.7)); ?>" 
                                       min="0" max="1" step="0.1" class="temperature-slider">
                                <span class="temperature-value">0.7</span>
                                <p class="description">
                                    <?php _e('0 = Tutarlı ve öngörülebilir yanıtlar, 1 = Yaratıcı ve çeşitli yanıtlar', 'ai-genius'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="fallback_provider"><?php _e('Yedek AI Servisi', 'ai-genius'); ?></label>
                            </th>
                            <td>
                                <select id="fallback_provider" name="fallback_provider" class="regular-text">
                                    <option value=""><?php _e('Yedek servis yok', 'ai-genius'); ?></option>
                                    <?php 
                                    $fallback_provider = get_option('ai_genius_fallback_provider', '');
                                    foreach ($supported_providers as $provider_id => $provider_info): 
                                        if ($provider_id !== $active_provider_info['provider']):
                                    ?>
                                        <option value="<?php echo esc_attr($provider_id); ?>" 
                                                <?php selected($fallback_provider, $provider_id); ?>>
                                            <?php echo esc_html($provider_info['name']); ?>
                                        </option>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </select>
                                <p class="description">
                                    <?php _e('Ana servis çalışmazsa kullanılacak yedek AI servisi.', 'ai-genius'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="auto_translate"><?php _e('Otomatik Çeviri', 'ai-genius'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="auto_translate" name="auto_translate" 
                                           <?php checked(get_option('ai_genius_auto_translate', false)); ?>>
                                    <?php _e('İngilizce yanıtları otomatik olarak Türkçe\'ye çevir', 'ai-genius'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="enhance_knowledge"><?php _e('Bilgi Tabanı Geliştirme', 'ai-genius'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="enhance_knowledge" name="enhance_knowledge" 
                                           <?php checked(get_option('ai_genius_enhance_knowledge', false)); ?>>
                                    <?php _e('Bilgi tabanı yanıtlarını AI ile geliştir', 'ai-genius'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('Bu özellik ek token maliyeti yaratabilir.', 'ai-genius'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button-primary">
                            <?php _e('Ayarları Kaydet', 'ai-genius'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <!-- Maliyet Tahmini -->
            <div class="cost-estimation">
                <h2><?php _e('Maliyet Tahmini', 'ai-genius'); ?></h2>
                <div id="cost-calculator">
                    <p><?php _e('Aylık tahmini kullanım:', 'ai-genius'); ?></p>
                    
                    <label>
                        <?php _e('Günlük mesaj sayısı:', 'ai-genius'); ?>
                        <input type="number" id="daily_messages" value="100" min="1" max="10000">
                    </label>
                    
                    <label>
                        <?php _e('Ortalama yanıt uzunluğu:', 'ai-genius'); ?>
                        <select id="response_length">
                            <option value="short"><?php _e('Kısa (50 token)', 'ai-genius'); ?></option>
                            <option value="medium" selected><?php _e('Orta (150 token)', 'ai-genius'); ?></option>
                            <option value="long"><?php _e('Uzun (300 token)', 'ai-genius'); ?></option>
                        </select>
                    </label>
                    
                    <button type="button" id="calculate-costs" class="button">
                        <?php _e('Maliyeti Hesapla', 'ai-genius'); ?>
                    </button>
                    
                    <div id="cost-results" style="display: none;">
                        <h3><?php _e('Aylık Maliyet Tahmini', 'ai-genius'); ?></h3>
                        <div id="cost-breakdown"></div>
                    </div>
                </div>
            </div>
            
            <!-- Kullanım İstatistikleri -->
            <div class="usage-statistics">
                <h2><?php _e('Güncel Kullanım İstatistikleri', 'ai-genius'); ?></h2>
                <div id="usage-stats">
                    <?php $this->render_usage_stats(); ?>
                </div>
            </div>
        </div>
        
        <style>
        .ai-genius-settings .providers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .provider-card {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fff;
            transition: all 0.3s ease;
        }
        
        .provider-card:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .provider-card.active {
            border-color: #00a32a;
            background: #f0fff4;
        }
        
        .provider-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .provider-type.free {
            background: #00a32a;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .provider-type.premium {
            background: #f56e28;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .section-title.free {
            color: #00a32a;
        }
        
        .section-title.premium {
            color: #f56e28;
        }
        
        .provider-models ul {
            font-size: 13px;
            margin: 5px 0;
        }
        
        .provider-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .api-key-section {
            margin: 15px 0;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        
        .api-key-section label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .api-key-input {
            width: calc(100% - 80px);
            margin-right: 10px;
        }
        
        .temperature-slider {
            width: 200px;
            margin-right: 10px;
        }
        
        .temperature-value {
            font-weight: bold;
            color: #0073aa;
        }
        
        .cost-estimation {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        #cost-calculator label {
            display: block;
            margin: 10px 0;
        }
        
        #cost-calculator input, #cost-calculator select {
            margin-left: 10px;
        }
        
        #cost-results {
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 4px;
            border-left: 4px solid #0073aa;
        }
        
        .usage-statistics {
            margin-top: 30px;
        }
        
        .provider-status {
            margin-top: 10px;
            padding: 8px;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .provider-status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .provider-status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .provider-status.testing {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Provider seçimi
            $('.select-provider').on('click', function() {
                const provider = $(this).data('provider');
                const model = $('.provider-card[data-provider="' + provider + '"]').find('.provider-models li').first().text();
                
                if ($(this).hasClass('disabled')) return;
                
                // AJAX ile provider ayarla
                $.post(ajaxurl, {
                    action: 'ai_genius_save_ai_settings',
                    nonce: aiGeniusAdmin.nonce,
                    provider: provider,
                    model: model
                }, function(response) {
                    if (response.success) {
                        location.reload(); // Sayfayı yenile
                    } else {
                        alert('Hata: ' + response.data);
                    }
                });
            });
            
            // Provider test etme
            $('.test-provider').on('click', function() {
                const provider = $(this).data('provider');
                const $status = $('#status-' + provider);
                const $button = $(this);
                
                if ($button.hasClass('disabled')) return;
                
                $button.prop('disabled', true).text('Test Ediliyor...');
                $status.removeClass('success error').addClass('testing')
                       .text('Bağlantı test ediliyor...');
                
                $.post(ajaxurl, {
                    action: 'ai_genius_test_provider',
                    nonce: aiGeniusAdmin.nonce,
                    provider: provider
                }, function(response) {
                    $button.prop('disabled', false).text('Test Et');
                    
                    if (response.success) {
                        $status.removeClass('testing error').addClass('success')
                               .text('✓ Bağlantı başarılı: ' + response.data.message);
                    } else {
                        $status.removeClass('testing success').addClass('error')
                               .text('✗ Bağlantı başarısız: ' + response.data);
                    }
                });
            });
            
            // API anahtarı kaydetme
            $('.save-api-key').on('click', function() {
                const provider = $(this).data('provider');
                const apiKey = $('#api-key-' + provider).val();
                const $button = $(this);
                
                if (!apiKey.trim()) {
                    alert('API anahtarı boş olamaz.');
                    return;
                }
                
                $button.prop('disabled', true).text('Kaydediliyor...');
                
                $.post(ajaxurl, {
                    action: 'ai_genius_save_ai_settings',
                    nonce: aiGeniusAdmin.nonce,
                    save_api_key: true,
                    provider: provider,
                    api_key: apiKey
                }, function(response) {
                    $button.prop('disabled', false).text('Kaydet');
                    
                    if (response.success) {
                        alert('API anahtarı kaydedildi.');
                        // Test ve seç butonlarını aktif et
                        $('.provider-card[data-provider="' + provider + '"] .test-provider, .provider-card[data-provider="' + provider + '"] .select-provider')
                            .prop('disabled', false);
                    } else {
                        alert('Kaydetme hatası: ' + response.data);
                    }
                });
            });
            
            // Temperature slider
            $('#temperature').on('input', function() {
                $('.temperature-value').text($(this).val());
            });
            
            // Detaylı ayarlar formu
            $('#ai-detailed-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                
                $.post(ajaxurl, {
                    action: 'ai_genius_save_ai_settings',
                    nonce: aiGeniusAdmin.nonce,
                    detailed_settings: true,
                    form_data: formData
                }, function(response) {
                    if (response.success) {
                        alert('Ayarlar kaydedildi.');
                    } else {
                        alert('Kaydetme hatası: ' + response.data);
                    }
                });
            });
            
            // Maliyet hesaplama
            $('#calculate-costs').on('click', function() {
                const dailyMessages = $('#daily_messages').val();
                const responseLength = $('#response_length').val();
                
                $.post(ajaxurl, {
                    action: 'ai_genius_estimate_costs',
                    nonce: aiGeniusAdmin.nonce,
                    daily_messages: dailyMessages,
                    response_length: responseLength
                }, function(response) {
                    if (response.success) {
                        $('#cost-results').show();
                        $('#cost-breakdown').html(response.data.html);
                    } else {
                        alert('Maliyet hesaplama hatası: ' + response.data);
                    }
                });
            });
            
            // Aktif provider için detaylı ayarları göster
            if ($('.provider-card.active').length > 0) {
                $('.ai-detailed-settings').show();
                loadProviderModels($('.provider-card.active').data('provider'));
            }
            
            function loadProviderModels(provider) {
                $.post(ajaxurl, {
                    action: 'ai_genius_get_provider_models',
                    nonce: aiGeniusAdmin.nonce,
                    provider: provider
                }, function(response) {
                    if (response.success) {
                        const $select = $('#ai_model');
                        $select.empty();
                        
                        $.each(response.data.models, function(modelId, modelName) {
                            $select.append('<option value="' + modelId + '">' + modelName + '</option>');
                        });
                        
                        $select.val(response.data.current_model);
                    }
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Kullanım istatistiklerini render et
     * 
     * @since 1.0.0
     */
    private function render_usage_stats() {
        
        $active_provider_info = $this->ai_processor->get_active_provider_info();
        $provider = $active_provider_info['provider'];
        
        if (!$provider) {
            echo '<p>' . __('Henüz bir AI servisi seçilmemiş.', 'ai-genius') . '</p>';
            return;
        }
        
        // Provider'a göre connector al ve istatistikleri göster
        $connector = $this->get_provider_connector($provider);
        
        if ($connector && method_exists($connector, 'get_usage_stats')) {
            $stats = $connector->get_usage_stats();
            
            echo '<div class="stats-grid">';
            echo '<div class="stat-item">';
            echo '<h4>' . __('Bugünkü İstekler', 'ai-genius') . '</h4>';
            echo '<span class="stat-number">' . ($stats['daily_usage']['requests'] ?? 0) . '</span>';
            echo '</div>';
            
            echo '<div class="stat-item">';
            echo '<h4>' . __('Kullanılan Token', 'ai-genius') . '</h4>';
            echo '<span class="stat-number">' . number_format($stats['daily_usage']['tokens'] ?? 0) . '</span>';
            echo '</div>';
            
            echo '<div class="stat-item">';
            echo '<h4>' . __('Tahmini Maliyet', 'ai-genius') . '</h4>';
            echo '<span class="stat-number"><?php
/**
 * AI Settings yönetici sınıfı
 * 
 * AI provider seçimi ve ayarlarını yönetir
 *
 * @package AI_Genius
 * @subpackage AI_Genius/admin
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Settings Sınıfı
 * 
 * AI provider'ları ve modellerini yönetir, test eder
 */
class AI_Genius_AI_Settings {

    /**
     * AI Processor sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_AI_Processor
     */
    private $ai_processor;

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        $this->ai_processor = new AI_Genius_AI_Processor();
        
        // Admin AJAX hook'ları
        add_action('wp_ajax_ai_genius_test_provider', array($this, 'ajax_test_provider'));
        add_action('wp_ajax_ai_genius_save_ai_settings', array($this, 'ajax_save_ai_settings'));
        add_action('wp_ajax_ai_genius_get_provider_models', array($this, 'ajax_get_provider_models'));
        add_action('wp_ajax_ai_genius_estimate_costs', array($this, 'ajax_estimate_costs'));
        
        ai_genius_log('AI Settings class initialized');
    }

    /**
     * AI provider ayarları sayfasını render et
     * 
     * @since 1.0.0
     */
    public function render_ai_settings_page() {
        
        $supported_providers = $this->ai_processor->get_supported_providers();
        $active_provider_info = $this->ai_processor->get_active_provider_info();
        $free_providers = $this->ai_processor->get_free_providers();
        $premium_providers = $this->ai_processor->get_premium_providers();
        
        ?>
        <div class="wrap ai-genius-settings">
            <h1><?php _e('AI Provider Ayarları', 'ai-genius'); ?></h1>
            
            <div class="ai-provider-comparison">
                <h2><?php _e('AI Servisi Seçimi', 'ai-genius'); ?></h2>
                <p class="description">
                    <?php _e('AI-Genius birden fazla AI servisini destekler. İhtiyacınıza göre ücretsiz veya ücretli servisleri seçebilirsiniz.', 'ai-genius'); ?>
                </p>
                
                <!-- Provider Karşılaştırma Tablosu -->
                <div class="provider-comparison-table">
                    
                    <!-- Ücretsiz Servisler -->
                    <div class="provider-section">
                        <h3 class="section-title free">
                            <span class="dashicons dashicons-heart"></span>
                            <?php _e('Ücretsiz AI Servisleri', 'ai-genius'); ?>
                        </h3>
                        
                        <div class="providers-grid">
                            <?php foreach ($free_providers as $provider_id => $provider_info): ?>
                                <div class="provider-card <?php echo $active_provider_info['provider'] === $provider_id ? 'active' : ''; ?>" 
                                     data-provider="<?php echo esc_attr($provider_id); ?>">
                                     
                                    <div class="provider-header">
                                        <h4><?php echo esc_html($provider_info['name']); ?></h4>
                                        <span class="provider-type free"><?php _e('Ücretsiz', 'ai-genius'); ?></span>
                                    </div>
                                    
                                    <div class="provider-description">
                                        <p><?php echo esc_html($provider_info['description']); ?></p>
                                    </div>
                                    
                                    <div class="provider-pricing">
                                        <strong><?php echo esc_html($provider_info['pricing_info']); ?></strong>
                                    </div>
                                    
                                    <div class="provider-models">
                                        <label><?php _e('Mevcut Modeller:', 'ai-genius'); ?></label>
                                        <ul>
                                            <?php foreach ($provider_info['models'] as $model_id => $model_name): ?>
                                                <li><?php echo esc_html($model_name); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <div class="provider-actions">
                                        <button type="button" class="button button-primary select-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php echo $active_provider_info['provider'] === $provider_id ? 
                                                __('Seçili', 'ai-genius') : __('Seç', 'ai-genius'); ?>
                                        </button>
                                        
                                        <button type="button" class="button test-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('Test Et', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="provider-status" id="status-<?php echo esc_attr($provider_id); ?>"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Premium Servisler -->
                    <div class="provider-section">
                        <h3 class="section-title premium">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php _e('Premium AI Servisleri', 'ai-genius'); ?>
                        </h3>
                        
                        <div class="providers-grid">
                            <?php foreach ($premium_providers as $provider_id => $provider_info): ?>
                                <div class="provider-card <?php echo $active_provider_info['provider'] === $provider_id ? 'active' : ''; ?>" 
                                     data-provider="<?php echo esc_attr($provider_id); ?>">
                                     
                                    <div class="provider-header">
                                        <h4><?php echo esc_html($provider_info['name']); ?></h4>
                                        <span class="provider-type premium"><?php _e('Premium', 'ai-genius'); ?></span>
                                    </div>
                                    
                                    <div class="provider-description">
                                        <p><?php echo esc_html($provider_info['description']); ?></p>
                                    </div>
                                    
                                    <div class="provider-pricing">
                                        <strong><?php echo esc_html($provider_info['pricing_info']); ?></strong>
                                    </div>
                                    
                                    <div class="provider-models">
                                        <label><?php _e('Mevcut Modeller:', 'ai-genius'); ?></label>
                                        <ul>
                                            <?php foreach ($provider_info['models'] as $model_id => $model_name): ?>
                                                <li><?php echo esc_html($model_name); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <?php if ($provider_info['requires_api_key']): ?>
                                    <div class="api-key-section">
                                        <label for="api-key-<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('API Anahtarı:', 'ai-genius'); ?>
                                        </label>
                                        <input type="password" 
                                               id="api-key-<?php echo esc_attr($provider_id); ?>" 
                                               class="regular-text api-key-input" 
                                               data-provider="<?php echo esc_attr($provider_id); ?>"
                                               value="<?php echo esc_attr($this->get_provider_api_key($provider_id)); ?>"
                                               placeholder="<?php _e('API anahtarınızı girin...', 'ai-genius'); ?>">
                                        <button type="button" class="button save-api-key" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('Kaydet', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="provider-actions">
                                        <button type="button" class="button button-primary select-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>"
                                                <?php echo !$this->is_provider_configured($provider_id) ? 'disabled' : ''; ?>>
                                            <?php echo $active_provider_info['provider'] === $provider_id ? 
                                                __('Seçili', 'ai-genius') : __('Seç', 'ai-genius'); ?>
                                        </button>
                                        
                                        <button type="button" class="button test-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>"
                                                <?php echo !$this->is_provider_configured($provider_id) ? 'disabled' : ''; ?>>
                                            <?php _e('Test Et', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="provider-status" id="status-<?php echo esc_attr($provider_id); ?>"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detaylı Ayarlar -->
            <div class="ai-detailed-settings" style="display: none;">
                <h2><?php _e('Gelişmiş AI Ayarları', 'ai-genius'); ?></h2>
                
                <form id="ai-detailed-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ai_model"><?php _e('Model Seçimi', 'ai-genius'); ?></label>
                            </th>
                            <td>
                                <select id="ai_model" name="ai_model" class="regular-text">
                                    <!-- JavaScript ile doldurulacak -->
                                 . number_format($stats['daily_usage']['cost'] ?? 0, 4) . '</span>';
            echo '</div>';
            echo '</div>';
            
            // Rate limit durumu (eğer varsa)
            if (isset($stats['rate_limit_status'])) {
                $rate_limit = $stats['rate_limit_status'];
                echo '<div class="rate-limit-info">';
                echo '<h4>' . __('Rate Limit Durumu', 'ai-genius') . '</h4>';
                echo '<p>' . sprintf(__('%d/%d istek kullanıldı', 'ai-genius'), 
                    $rate_limit['used'], $rate_limit['limit']) . '</p>';
                echo '</div>';
            }
        }
    }

    /**
     * Provider'ın API anahtarını al
     * 
     * @since 1.0.0
     * @param string $provider Provider adı
     * @return string
     */
    private function get_provider_api_key($provider) {
        
        $option_names = array(
            'openai' => 'ai_genius_openai_api_key',
            'anthropic' => 'ai_genius_anthropic_api_key',
            'google' => 'ai_genius_google_api_key',
            'cohere' => 'ai_genius_cohere_api_key'
        );
        
        if (isset($option_names[$provider])) {
            $key = get_option($option_names[$provider], '');
            return $key ? str_repeat('*', strlen($key) - 8) . substr($key, -8) : '';
        }
        
        return '';
    }

    /**
     * Provider yapılandırılmış mı kontrol et
     * 
     * @since 1.0.0
     * @param string $provider Provider adı
     * @return bool
     */
    private function is_provider_configured($provider) {
        
        $supported_providers = $this->ai_processor->get_supported_providers();
        
        if (!isset($supported_providers[$provider])) {
            return false;
        }
        
        $provider_info = $supported_providers[$provider];
        
        // API anahtarı gerekmeyen provider'lar için true döndür
        if (!$provider_info['requires_api_key']) {
            return true;
        }
        
        // API anahtarının varlığını kontrol et
        $option_names = array(
            'openai' => 'ai_genius_openai_api_key',
            'anthropic' => 'ai_genius_anthropic_api_key',
            'google' => 'ai_genius_google_api_key',
            'cohere' => 'ai_genius_cohere_api_key'
        );
        
        if (isset($option_names[$provider])) {
            $api_key = get_option($option_names[$provider], '');
            return !empty($api_key);
        }
        
        return false;
    }

    /**
     * Provider connector'ını al
     * 
     * @since 1.0.0
     * @param string $provider Provider adı
     * @return object|null
     */
    private function get_provider_connector($provider) {
        
        switch ($provider) {
            case 'openai':
                $api_key = get_option('ai_genius_openai_api_key', '');
                return !empty($api_key) ? new AI_Genius_OpenAI_Connector($api_key) : null;
                
            case 'anthropic':
                $api_key = get_option('ai_genius_anthropic_api_key', '');
                return !empty($api_key) ? new AI_Genius_Anthropic_Connector($api_key) : null;
                
            case 'huggingface':
                $api_token = get_option('ai_genius_huggingface_token', '');
                return new AI_Genius_HuggingFace_Connector($api_token);
                
            case 'ollama':
                $endpoint = get_option('ai_genius_ollama_endpoint', 'http://localhost:11434');
                return new AI_Genius_Ollama_Connector($endpoint);
                
            default:
                return null;
        }
    }

    // AJAX İşleyicileri

    /**
     * AJAX: Provider test et
     * 
     * @since 1.0.0
     */
    public function ajax_test_provider() {
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce') || 
            !current_user_can('manage_ai_genius')) {
            wp_send_json_error('Yetkiniz bulunmuyor.');
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error('Provider belirtilmemiş.');
        }
        
        $result = $this->ai_processor->test_provider($provider);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX: AI ayarları kaydet
     * 
     * @since 1.0.0
     */
    public function ajax_save_ai_settings() {
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce') || 
            !current_user_can('manage_ai_genius')) {
            wp_send_json_error('Yetkiniz bulunmuyor.');
        }
        
        // API anahtarı kaydetme
        if (isset($_POST['save_api_key']) && $_POST['save_api_key']) {
            $provider = sanitize_text_field($_POST['provider'] ?? '');
            $api_key = sanitize_text_field($_POST['api_key'] ?? '');
            
            $option_names = array(
                'openai' => 'ai_genius_openai_api_key',
                'anthropic' => 'ai_genius_anthropic_api_key',
                'google' => 'ai_genius_google_api_key',
                'cohere' => 'ai_genius_cohere_api_key'
            );
            
            if (isset($option_names[$provider]) && !empty($api_key)) {
                update_option($option_names[$provider], $api_key);
                wp_send_json_success('API anahtarı kaydedildi.');
            } else {
                wp_send_json_error('Geçersiz provider veya API anahtarı.');
            }
            return;
        }
        
        // Provider seçimi
        if (isset($_POST['provider'])) {
            $provider = sanitize_text_field($_POST['provider']);
            $model = sanitize_text_field($_POST['model'] ?? '');
            
            if ($this->ai_processor->update_provider_settings($provider, $model)) {
                wp_send_json_success('Provider güncellendi.');
            } else {
                wp_send_json_error('Provider güncellenemedi.');
            }
            return;
        }
        
        // Detaylı ayarlar
        if (isset($_POST['detailed_settings'])) {
            parse_str($_POST['form_data'], $form_data);
            
            $allowed_settings = array(
                'ai_genius_model' => 'sanitize_text_field',
                'ai_genius_max_tokens' => 'intval',
                'ai_genius_temperature' => 'floatval',
                'ai_genius_fallback_provider' => 'sanitize_text_field',
                'ai_genius_auto_translate' => 'rest_sanitize_boolean',
                'ai_genius_enhance_knowledge' => 'rest_sanitize_boolean'
            );
            
            foreach ($allowed_settings as $setting => $sanitize_callback) {
                if (isset($form_data[$setting])) {
                    $value = call_user_func($sanitize_callback, $form_data[$setting]);
                    update_option($setting, $value);
                }
            }
            
            wp_send_json_success('Detaylı ayarlar kaydedildi.');
            return;
        }
        
        wp_send_json_error('Geçersiz istek.');
    }

    /**
     * AJAX: Provider modellerini al
     * 
     * @since 1.0.0
     */
    public function ajax_get_provider_models() {
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce') || 
            !current_user_can('manage_ai_genius')) {
            wp_send_json_error('Yetkiniz bulunmuyor.');
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $supported_providers = $this->ai_processor->get_supported_providers();
        
        if (!isset($supported_providers[$provider])) {
            wp_send_json_error('Geçersiz provider.');
        }
        
        $models = $supported_providers[$provider]['models'];
        $current_model = get_option('ai_genius_model', '');
        
        wp_send_json_success(array(
            'models' => $models,
            'current_model' => $current_model
        ));
    }

    /**
     * AJAX: Maliyet tahmini
     * 
     * @since 1.0.0
     */
    public function ajax_estimate_costs() {
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ai_genius_nonce') || 
            !current_user_can('manage_ai_genius')) {
            wp_send_json_error('Yetkiniz bulunmuyor.');
        }
        
        $daily_messages = intval($_POST['daily_messages'] ?? 100);
        $response_length = sanitize_text_field($_POST['response_length'] ?? 'medium');
        
        // Token sayıları
        $token_counts = array(
            'short' => 50,
            'medium' => 150,
            'long' => 300
        );
        
        $tokens_per_response = $token_counts[$response_length] ?? 150;
        $monthly_messages = $daily_messages * 30;
        $monthly_tokens = $monthly_messages * $tokens_per_response;
        
        // Provider'lara göre maliyet hesaplama
        $supported_providers = $this->ai_processor->get_supported_providers();
        $cost_breakdown = array();
        
        foreach ($supported_providers as $provider_id => $provider_info) {
            if ($provider_info['type'] === 'free') {
                $cost_breakdown[$provider_id] = array(
                    'name' => $provider_info['name'],
                    'monthly_cost' => 0,
                    'type' => 'Ücretsiz'
                );
            } else {
                // Basit maliyet tahmini (gerçek maliyetler değişebilir)
                $cost_per_1k_token = $this->get_estimated_cost_per_1k_token($provider_id);
                $monthly_cost = ($monthly_tokens / 1000) * $cost_per_1k_token;
                
                $cost_breakdown[$provider_id] = array(
                    'name' => $provider_info['name'],
                    'monthly_cost' => $monthly_cost,
                    'type' => 'Premium'
                );
            }
        }
        
        // HTML oluştur
        $html = '<div class="cost-comparison">';
        
        foreach ($cost_breakdown as $provider_id => $cost_info) {
            $html .= '<div class="cost-item">';
            $html .= '<strong>' . esc_html($cost_info['name']) . '</strong><br>';
            
            if ($cost_info['monthly_cost'] == 0) {
                $html .= '<span class="cost-free">Ücretsiz</span>';
            } else {
                $html .= '<span class="cost-amount"><?php
/**
 * AI Settings yönetici sınıfı
 * 
 * AI provider seçimi ve ayarlarını yönetir
 *
 * @package AI_Genius
 * @subpackage AI_Genius/admin
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Settings Sınıfı
 * 
 * AI provider'ları ve modellerini yönetir, test eder
 */
class AI_Genius_AI_Settings {

    /**
     * AI Processor sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_AI_Processor
     */
    private $ai_processor;

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        $this->ai_processor = new AI_Genius_AI_Processor();
        
        // Admin AJAX hook'ları
        add_action('wp_ajax_ai_genius_test_provider', array($this, 'ajax_test_provider'));
        add_action('wp_ajax_ai_genius_save_ai_settings', array($this, 'ajax_save_ai_settings'));
        add_action('wp_ajax_ai_genius_get_provider_models', array($this, 'ajax_get_provider_models'));
        add_action('wp_ajax_ai_genius_estimate_costs', array($this, 'ajax_estimate_costs'));
        
        ai_genius_log('AI Settings class initialized');
    }

    /**
     * AI provider ayarları sayfasını render et
     * 
     * @since 1.0.0
     */
    public function render_ai_settings_page() {
        
        $supported_providers = $this->ai_processor->get_supported_providers();
        $active_provider_info = $this->ai_processor->get_active_provider_info();
        $free_providers = $this->ai_processor->get_free_providers();
        $premium_providers = $this->ai_processor->get_premium_providers();
        
        ?>
        <div class="wrap ai-genius-settings">
            <h1><?php _e('AI Provider Ayarları', 'ai-genius'); ?></h1>
            
            <div class="ai-provider-comparison">
                <h2><?php _e('AI Servisi Seçimi', 'ai-genius'); ?></h2>
                <p class="description">
                    <?php _e('AI-Genius birden fazla AI servisini destekler. İhtiyacınıza göre ücretsiz veya ücretli servisleri seçebilirsiniz.', 'ai-genius'); ?>
                </p>
                
                <!-- Provider Karşılaştırma Tablosu -->
                <div class="provider-comparison-table">
                    
                    <!-- Ücretsiz Servisler -->
                    <div class="provider-section">
                        <h3 class="section-title free">
                            <span class="dashicons dashicons-heart"></span>
                            <?php _e('Ücretsiz AI Servisleri', 'ai-genius'); ?>
                        </h3>
                        
                        <div class="providers-grid">
                            <?php foreach ($free_providers as $provider_id => $provider_info): ?>
                                <div class="provider-card <?php echo $active_provider_info['provider'] === $provider_id ? 'active' : ''; ?>" 
                                     data-provider="<?php echo esc_attr($provider_id); ?>">
                                     
                                    <div class="provider-header">
                                        <h4><?php echo esc_html($provider_info['name']); ?></h4>
                                        <span class="provider-type free"><?php _e('Ücretsiz', 'ai-genius'); ?></span>
                                    </div>
                                    
                                    <div class="provider-description">
                                        <p><?php echo esc_html($provider_info['description']); ?></p>
                                    </div>
                                    
                                    <div class="provider-pricing">
                                        <strong><?php echo esc_html($provider_info['pricing_info']); ?></strong>
                                    </div>
                                    
                                    <div class="provider-models">
                                        <label><?php _e('Mevcut Modeller:', 'ai-genius'); ?></label>
                                        <ul>
                                            <?php foreach ($provider_info['models'] as $model_id => $model_name): ?>
                                                <li><?php echo esc_html($model_name); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <div class="provider-actions">
                                        <button type="button" class="button button-primary select-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php echo $active_provider_info['provider'] === $provider_id ? 
                                                __('Seçili', 'ai-genius') : __('Seç', 'ai-genius'); ?>
                                        </button>
                                        
                                        <button type="button" class="button test-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('Test Et', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="provider-status" id="status-<?php echo esc_attr($provider_id); ?>"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Premium Servisler -->
                    <div class="provider-section">
                        <h3 class="section-title premium">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php _e('Premium AI Servisleri', 'ai-genius'); ?>
                        </h3>
                        
                        <div class="providers-grid">
                            <?php foreach ($premium_providers as $provider_id => $provider_info): ?>
                                <div class="provider-card <?php echo $active_provider_info['provider'] === $provider_id ? 'active' : ''; ?>" 
                                     data-provider="<?php echo esc_attr($provider_id); ?>">
                                     
                                    <div class="provider-header">
                                        <h4><?php echo esc_html($provider_info['name']); ?></h4>
                                        <span class="provider-type premium"><?php _e('Premium', 'ai-genius'); ?></span>
                                    </div>
                                    
                                    <div class="provider-description">
                                        <p><?php echo esc_html($provider_info['description']); ?></p>
                                    </div>
                                    
                                    <div class="provider-pricing">
                                        <strong><?php echo esc_html($provider_info['pricing_info']); ?></strong>
                                    </div>
                                    
                                    <div class="provider-models">
                                        <label><?php _e('Mevcut Modeller:', 'ai-genius'); ?></label>
                                        <ul>
                                            <?php foreach ($provider_info['models'] as $model_id => $model_name): ?>
                                                <li><?php echo esc_html($model_name); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <?php if ($provider_info['requires_api_key']): ?>
                                    <div class="api-key-section">
                                        <label for="api-key-<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('API Anahtarı:', 'ai-genius'); ?>
                                        </label>
                                        <input type="password" 
                                               id="api-key-<?php echo esc_attr($provider_id); ?>" 
                                               class="regular-text api-key-input" 
                                               data-provider="<?php echo esc_attr($provider_id); ?>"
                                               value="<?php echo esc_attr($this->get_provider_api_key($provider_id)); ?>"
                                               placeholder="<?php _e('API anahtarınızı girin...', 'ai-genius'); ?>">
                                        <button type="button" class="button save-api-key" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('Kaydet', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="provider-actions">
                                        <button type="button" class="button button-primary select-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>"
                                                <?php echo !$this->is_provider_configured($provider_id) ? 'disabled' : ''; ?>>
                                            <?php echo $active_provider_info['provider'] === $provider_id ? 
                                                __('Seçili', 'ai-genius') : __('Seç', 'ai-genius'); ?>
                                        </button>
                                        
                                        <button type="button" class="button test-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>"
                                                <?php echo !$this->is_provider_configured($provider_id) ? 'disabled' : ''; ?>>
                                            <?php _e('Test Et', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="provider-status" id="status-<?php echo esc_attr($provider_id); ?>"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detaylı Ayarlar -->
            <div class="ai-detailed-settings" style="display: none;">
                <h2><?php _e('Gelişmiş AI Ayarları', 'ai-genius'); ?></h2>
                
                <form id="ai-detailed-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ai_model"><?php _e('Model Seçimi', 'ai-genius'); ?></label>
                            </th>
                            <td>
                                <select id="ai_model" name="ai_model" class="regular-text">
                                    <!-- JavaScript ile doldurulacak -->
                                 . number_format($cost_info['monthly_cost'], 2) . '/ay</span>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $html .= '<div class="cost-details">';
        $html .= '<p><strong>Hesaplama Detayları:</strong></p>';
        $html .= '<ul>';
        $html .= '<li>Günlük mesaj: ' . number_format($daily_messages) . '</li>';
        $html .= '<li>Aylık mesaj: ' . number_format($monthly_messages) . '</li>';
        $html .= '<li>Ortalama token/yanıt: ' . $tokens_per_response . '</li>';
        $html .= '<li>Toplam aylık token: ' . number_format($monthly_tokens) . '</li>';
        $html .= '</ul>';
        $html .= '<p class="description">Bu tahminler yaklaşıktır ve gerçek kullanıma göre değişebilir.</p>';
        $html .= '</div>';
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * Provider için tahmini maliyet al
     * 
     * @since 1.0.0
     * @param string $provider_id Provider ID
     * @return float
     */
    private function get_estimated_cost_per_1k_token($provider_id) {
        
        // Bu değerler yaklaşık ve değişebilir
        $estimated_costs = array(
            'openai' => 0.002, // GPT-3.5-turbo ortalama
            'anthropic' => 0.008, // Claude ortalama
            'google' => 0.001, // Gemini Pro
            'cohere' => 0.001 // Cohere ortalama
        );
        
        return $estimated_costs[$provider_id] ?? 0.002;
    }
}<?php
/**
 * AI Settings yönetici sınıfı
 * 
 * AI provider seçimi ve ayarlarını yönetir
 *
 * @package AI_Genius
 * @subpackage AI_Genius/admin
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Settings Sınıfı
 * 
 * AI provider'ları ve modellerini yönetir, test eder
 */
class AI_Genius_AI_Settings {

    /**
     * AI Processor sınıfı
     * 
     * @since 1.0.0
     * @var AI_Genius_AI_Processor
     */
    private $ai_processor;

    /**
     * Sınıf kurucusu
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        $this->ai_processor = new AI_Genius_AI_Processor();
        
        // Admin AJAX hook'ları
        add_action('wp_ajax_ai_genius_test_provider', array($this, 'ajax_test_provider'));
        add_action('wp_ajax_ai_genius_save_ai_settings', array($this, 'ajax_save_ai_settings'));
        add_action('wp_ajax_ai_genius_get_provider_models', array($this, 'ajax_get_provider_models'));
        add_action('wp_ajax_ai_genius_estimate_costs', array($this, 'ajax_estimate_costs'));
        
        ai_genius_log('AI Settings class initialized');
    }

    /**
     * AI provider ayarları sayfasını render et
     * 
     * @since 1.0.0
     */
    public function render_ai_settings_page() {
        
        $supported_providers = $this->ai_processor->get_supported_providers();
        $active_provider_info = $this->ai_processor->get_active_provider_info();
        $free_providers = $this->ai_processor->get_free_providers();
        $premium_providers = $this->ai_processor->get_premium_providers();
        
        ?>
        <div class="wrap ai-genius-settings">
            <h1><?php _e('AI Provider Ayarları', 'ai-genius'); ?></h1>
            
            <div class="ai-provider-comparison">
                <h2><?php _e('AI Servisi Seçimi', 'ai-genius'); ?></h2>
                <p class="description">
                    <?php _e('AI-Genius birden fazla AI servisini destekler. İhtiyacınıza göre ücretsiz veya ücretli servisleri seçebilirsiniz.', 'ai-genius'); ?>
                </p>
                
                <!-- Provider Karşılaştırma Tablosu -->
                <div class="provider-comparison-table">
                    
                    <!-- Ücretsiz Servisler -->
                    <div class="provider-section">
                        <h3 class="section-title free">
                            <span class="dashicons dashicons-heart"></span>
                            <?php _e('Ücretsiz AI Servisleri', 'ai-genius'); ?>
                        </h3>
                        
                        <div class="providers-grid">
                            <?php foreach ($free_providers as $provider_id => $provider_info): ?>
                                <div class="provider-card <?php echo $active_provider_info['provider'] === $provider_id ? 'active' : ''; ?>" 
                                     data-provider="<?php echo esc_attr($provider_id); ?>">
                                     
                                    <div class="provider-header">
                                        <h4><?php echo esc_html($provider_info['name']); ?></h4>
                                        <span class="provider-type free"><?php _e('Ücretsiz', 'ai-genius'); ?></span>
                                    </div>
                                    
                                    <div class="provider-description">
                                        <p><?php echo esc_html($provider_info['description']); ?></p>
                                    </div>
                                    
                                    <div class="provider-pricing">
                                        <strong><?php echo esc_html($provider_info['pricing_info']); ?></strong>
                                    </div>
                                    
                                    <div class="provider-models">
                                        <label><?php _e('Mevcut Modeller:', 'ai-genius'); ?></label>
                                        <ul>
                                            <?php foreach ($provider_info['models'] as $model_id => $model_name): ?>
                                                <li><?php echo esc_html($model_name); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <div class="provider-actions">
                                        <button type="button" class="button button-primary select-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php echo $active_provider_info['provider'] === $provider_id ? 
                                                __('Seçili', 'ai-genius') : __('Seç', 'ai-genius'); ?>
                                        </button>
                                        
                                        <button type="button" class="button test-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('Test Et', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="provider-status" id="status-<?php echo esc_attr($provider_id); ?>"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Premium Servisler -->
                    <div class="provider-section">
                        <h3 class="section-title premium">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php _e('Premium AI Servisleri', 'ai-genius'); ?>
                        </h3>
                        
                        <div class="providers-grid">
                            <?php foreach ($premium_providers as $provider_id => $provider_info): ?>
                                <div class="provider-card <?php echo $active_provider_info['provider'] === $provider_id ? 'active' : ''; ?>" 
                                     data-provider="<?php echo esc_attr($provider_id); ?>">
                                     
                                    <div class="provider-header">
                                        <h4><?php echo esc_html($provider_info['name']); ?></h4>
                                        <span class="provider-type premium"><?php _e('Premium', 'ai-genius'); ?></span>
                                    </div>
                                    
                                    <div class="provider-description">
                                        <p><?php echo esc_html($provider_info['description']); ?></p>
                                    </div>
                                    
                                    <div class="provider-pricing">
                                        <strong><?php echo esc_html($provider_info['pricing_info']); ?></strong>
                                    </div>
                                    
                                    <div class="provider-models">
                                        <label><?php _e('Mevcut Modeller:', 'ai-genius'); ?></label>
                                        <ul>
                                            <?php foreach ($provider_info['models'] as $model_id => $model_name): ?>
                                                <li><?php echo esc_html($model_name); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    
                                    <?php if ($provider_info['requires_api_key']): ?>
                                    <div class="api-key-section">
                                        <label for="api-key-<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('API Anahtarı:', 'ai-genius'); ?>
                                        </label>
                                        <input type="password" 
                                               id="api-key-<?php echo esc_attr($provider_id); ?>" 
                                               class="regular-text api-key-input" 
                                               data-provider="<?php echo esc_attr($provider_id); ?>"
                                               value="<?php echo esc_attr($this->get_provider_api_key($provider_id)); ?>"
                                               placeholder="<?php _e('API anahtarınızı girin...', 'ai-genius'); ?>">
                                        <button type="button" class="button save-api-key" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>">
                                            <?php _e('Kaydet', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="provider-actions">
                                        <button type="button" class="button button-primary select-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>"
                                                <?php echo !$this->is_provider_configured($provider_id) ? 'disabled' : ''; ?>>
                                            <?php echo $active_provider_info['provider'] === $provider_id ? 
                                                __('Seçili', 'ai-genius') : __('Seç', 'ai-genius'); ?>
                                        </button>
                                        
                                        <button type="button" class="button test-provider" 
                                                data-provider="<?php echo esc_attr($provider_id); ?>"
                                                <?php echo !$this->is_provider_configured($provider_id) ? 'disabled' : ''; ?>>
                                            <?php _e('Test Et', 'ai-genius'); ?>
                                        </button>
                                    </div>
                                    
                                    <div class="provider-status" id="status-<?php echo esc_attr($provider_id); ?>"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detaylı Ayarlar -->
            <div class="ai-detailed-settings" style="display: none;">
                <h2><?php _e('Gelişmiş AI Ayarları', 'ai-genius'); ?></h2>
                
                <form id="ai-detailed-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ai_model"><?php _e('Model Seçimi', 'ai-genius'); ?></label>
                            </th>
                            <td>
                                <select id="ai_model" name="ai_model" class="regular-text">
                                    <!-- JavaScript ile doldurulacak -->
                                