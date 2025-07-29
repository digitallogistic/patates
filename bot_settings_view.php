<?php
/**
 * Bot Ayarları Sayfası
 * 
 * Chatbot kişiselleştirme ve yapılandırma sayfası
 *
 * @package AI_Genius
 * @subpackage AI_Genius/admin/partials
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Gerekli sınıfları yükle
$database = new AI_Genius_Database();
$bot_settings = $database->get_bot_settings();

// Varsayılan değerler
$defaults = array(
    'bot_name' => 'AI Asistan',
    'bot_personality' => 'Yardımsever, dostane ve bilgili bir asistan. Kullanıcılara her zaman kibarca ve profesyonelce yaklaşır.',
    'bot_avatar' => AI_GENIUS_PLUGIN_URL . 'assets/images/default-avatar.svg',
    'welcome_message' => 'Merhaba! Size nasıl yardımcı olabilirim?',
    'fallback_message' => 'Üzgünüm, bu konuda size yardımcı olamıyorum. Lütfen başka bir soru sormayı deneyin.',
    'chat_theme' => 'auto',
    'chat_position' => 'bottom-right',
    'is_active' => 1
);

$current_settings = $bot_settings ? array_merge($defaults, $bot_settings) : $defaults;

// Tema renkleri
$theme_colors = get_option('ai_genius_custom_colors', array(
    'primary' => '#0073aa',
    'secondary' => '#005177',
    'accent' => '#00a32a',
    'background' => '#ffffff',
    'text' => '#333333',
    'border' => '#dddddd'
));
?>

<div class="wrap ai-genius-bot-settings">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-generic" style="color: #0073aa;"></span>
        <?php esc_html_e('Bot Ayarları', 'ai-genius'); ?>
    </h1>
    
    <p class="description">
        <?php esc_html_e('Chatbot\'unuzun görünümünü, kişiliğini ve davranışlarını özelleştirin.', 'ai-genius'); ?>
    </p>

    <div class="bot-settings-container">
        <!-- Sol Panel: Ayarlar -->
        <div class="settings-panel">
            <form id="bot-settings-form" method="post" action="">
                <?php wp_nonce_field('ai_genius_bot_settings', 'bot_settings_nonce'); ?>
                
                <!-- Temel Bilgiler -->
                <div class="settings-section">
                    <h2><?php esc_html_e('Temel Bilgiler', 'ai-genius'); ?></h2>
                    
                    <div class="form-group">
                        <label for="bot_name">
                            <?php esc_html_e('Bot Adı', 'ai-genius'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="bot_name" 
                               name="bot_name" 
                               value="<?php echo esc_attr($current_settings['bot_name']); ?>" 
                               class="regular-text" 
                               required 
                               maxlength="50">
                        <p class="description">
                            <?php esc_html_e('Kullanıcıların göreceği bot adı (örn: "Ayşe Asistan", "Destek Botu").', 'ai-genius'); ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="bot_personality">
                            <?php esc_html_e('Bot Kişiliği', 'ai-genius'); ?>
                        </label>
                        <textarea id="bot_personality" 
                                  name="bot_personality" 
                                  rows="4" 
                                  class="large-text"
                                  maxlength="500"><?php echo esc_textarea($current_settings['bot_personality']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Bot\'un nasıl davranacağını belirleyen kişilik açıklaması. AI bu tanıma göre yanıt verecek.', 'ai-genius'); ?>
                        </p>
                        <div class="character-counter">
                            <span id="personality-counter">0</span>/500
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bot_avatar">
                            <?php esc_html_e('Bot Avatarı', 'ai-genius'); ?>
                        </label>
                        <div class="avatar-upload">
                            <div class="avatar-preview">
                                <img id="avatar-preview" 
                                     src="<?php echo esc_url($current_settings['bot_avatar']); ?>" 
                                     alt="Bot Avatar" 
                                     width="80" 
                                     height="80">
                            </div>
                            <div class="avatar-controls">
                                <input type="hidden" 
                                       id="bot_avatar" 
                                       name="bot_avatar" 
                                       value="<?php echo esc_url($current_settings['bot_avatar']); ?>">
                                <button type="button" class="button upload-avatar">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php esc_html_e('Avatar Yükle', 'ai-genius'); ?>
                                </button>
                                <button type="button" class="button remove-avatar">
                                    <span class="dashicons dashicons-no"></span>
                                    <?php esc_html_e('Kaldır', 'ai-genius'); ?>
                                </button>
                            </div>
                        </div>
                        <p class="description">
                            <?php esc_html_e('80x80 piksel, PNG/JPG/SVG formatında olmalı. Maksimum 1MB.', 'ai-genius'); ?>
                        </p>
                    </div>
                </div>

                <!-- Mesajlar -->
                <div class="settings-section">
                    <h2><?php esc_html_e('Öntanımlı Mesajlar', 'ai-genius'); ?></h2>
                    
                    <div class="form-group">
                        <label for="welcome_message">
                            <?php esc_html_e('Karşılama Mesajı', 'ai-genius'); ?>
                            <span class="required">*</span>
                        </label>
                        <textarea id="welcome_message" 
                                  name="welcome_message" 
                                  rows="3" 
                                  class="large-text"
                                  required
                                  maxlength="200"><?php echo esc_textarea($current_settings['welcome_message']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Kullanıcı chatbot ile ilk kez etkileşime geçtiğinde gösterilecek mesaj.', 'ai-genius'); ?>
                        </p>
                        <div class="character-counter">
                            <span id="welcome-counter">0</span>/200
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fallback_message">
                            <?php esc_html_e('Varsayılan Hata Mesajı', 'ai-genius'); ?>
                        </label>
                        <textarea id="fallback_message" 
                                  name="fallback_message" 
                                  rows="3" 
                                  class="large-text"
                                  maxlength="200"><?php echo esc_textarea($current_settings['fallback_message']); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Bot yanıt veremediğinde gösterilecek mesaj.', 'ai-genius'); ?>
                        </p>
                        <div class="character-counter">
                            <span id="fallback-counter">0</span>/200
                        </div>
                    </div>

                    <!-- Hazır Mesaj Şablonları -->
                    <div class="message-templates">
                        <h4><?php esc_html_e('Hazır Şablonlar', 'ai-genius'); ?></h4>
                        <div class="template-buttons">
                            <button type="button" class="button template-btn" data-type="welcome" data-template="formal">
                                <?php esc_html_e('Resmi', 'ai-genius'); ?>
                            </button>
                            <button type="button" class="button template-btn" data-type="welcome" data-template="friendly">
                                <?php esc_html_e('Samimi', 'ai-genius'); ?>
                            </button>
                            <button type="button" class="button template-btn" data-type="welcome" data-template="professional">
                                <?php esc_html_e('Profesyonel', 'ai-genius'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Görünüm -->
                <div class="settings-section">
                    <h2><?php esc_html_e('Görünüm Ayarları', 'ai-genius'); ?></h2>
                    
                    <div class="form-group">
                        <label for="chat_position">
                            <?php esc_html_e('Chatbot Konumu', 'ai-genius'); ?>
                        </label>
                        <div class="position-selector">
                            <div class="position-grid">
                                <?php
                                $positions = array(
                                    'top-left' => __('Sol Üst', 'ai-genius'),
                                    'top-right' => __('Sağ Üst', 'ai-genius'),
                                    'bottom-left' => __('Sol Alt', 'ai-genius'),
                                    'bottom-right' => __('Sağ Alt', 'ai-genius')
                                );
                                
                                foreach ($positions as $value => $label):
                                ?>
                                <label class="position-option <?php echo $current_settings['chat_position'] === $value ? 'selected' : ''; ?>">
                                    <input type="radio" 
                                           name="chat_position" 
                                           value="<?php echo esc_attr($value); ?>"
                                           <?php checked($current_settings['chat_position'], $value); ?>>
                                    <div class="position-visual <?php echo esc_attr($value); ?>">
                                        <div class="screen">
                                            <div class="bot-icon"></div>
                                        </div>
                                    </div>
                                    <span><?php echo esc_html($label); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="chat_theme">
                            <?php esc_html_e('Tema', 'ai-genius'); ?>
                        </label>
                        <div class="theme-selector">
                            <?php
                            $themes = array(
                                'auto' => array(
                                    'name' => __('Otomatik', 'ai-genius'),
                                    'desc' => __('Site temasına uyum sağlar', 'ai-genius')
                                ),
                                'light' => array(
                                    'name' => __('Açık', 'ai-genius'),
                                    'desc' => __('Beyaz arka plan', 'ai-genius')
                                ),
                                'dark' => array(
                                    'name' => __('Koyu', 'ai-genius'),
                                    'desc' => __('Siyah arka plan', 'ai-genius')
                                )
                            );
                            
                            foreach ($themes as $value => $theme):
                            ?>
                            <label class="theme-option <?php echo $current_settings['chat_theme'] === $value ? 'selected' : ''; ?>">
                                <input type="radio" 
                                       name="chat_theme" 
                                       value="<?php echo esc_attr($value); ?>"
                                       <?php checked($current_settings['chat_theme'], $value); ?>>
                                <div class="theme-preview theme-<?php echo esc_attr($value); ?>">
                                    <div class="theme-header"></div>
                                    <div class="theme-body">
                                        <div class="theme-message bot"></div>
                                        <div class="theme-message user"></div>
                                    </div>
                                </div>
                                <div class="theme-info">
                                    <strong><?php echo esc_html($theme['name']); ?></strong>
                                    <span><?php echo esc_html($theme['desc']); ?></span>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Özel Renkler -->
                    <div class="form-group">
                        <label><?php esc_html_e('Özel Renk Paleti', 'ai-genius'); ?></label>
                        <div class="color-palette">
                            <div class="color-group">
                                <label><?php esc_html_e('Ana Renk', 'ai-genius'); ?></label>
                                <input type="color" 
                                       name="colors[primary]" 
                                       value="<?php echo esc_attr($theme_colors['primary']); ?>"
                                       class="color-picker">
                            </div>
                            <div class="color-group">
                                <label><?php esc_html_e('İkincil Renk', 'ai-genius'); ?></label>
                                <input type="color" 
                                       name="colors[secondary]" 
                                       value="<?php echo esc_attr($theme_colors['secondary']); ?>"
                                       class="color-picker">
                            </div>
                            <div class="color-group">
                                <label><?php esc_html_e('Vurgu Rengi', 'ai-genius'); ?></label>
                                <input type="color" 
                                       name="colors[accent]" 
                                       value="<?php echo esc_attr($theme_colors['accent']); ?>"
                                       class="color-picker">
                            </div>
                        </div>
                        <button type="button" class="button reset-colors">
                            <?php esc_html_e('Varsayılan Renkleri Geri Yükle', 'ai-genius'); ?>
                        </button>
                    </div>
                </div>

                <!-- Davranış -->
                <div class="settings-section">
                    <h2><?php esc_html_e('Davranış Ayarları', 'ai-genius'); ?></h2>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1" 
                                   <?php checked($current_settings['is_active'], 1); ?>>
                            <?php esc_html_e('Chatbot\'u aktif et', 'ai-genius'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Bu seçenek kapatıldığında chatbot sitede görünmez.', 'ai-genius'); ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" 
                                   name="proactive_messages" 
                                   value="1" 
                                   <?php checked(get_option('ai_genius_proactive_messages', true), 1); ?>>
                            <?php esc_html_e('Proaktif mesajları etkinleştir', 'ai-genius'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Bot belirli durumlarda otomatik olarak yardım önerir.', 'ai-genius'); ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" 
                                   name="typing_indicator" 
                                   value="1" 
                                   <?php checked(get_option('ai_genius_typing_indicator', true), 1); ?>>
                            <?php esc_html_e('Yazıyor göstergesini etkinleştir', 'ai-genius'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Bot yanıt hazırlarken "yazıyor..." göstergesi gösterilir.', 'ai-genius'); ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" 
                                   name="sound_effects" 
                                   value="1" 
                                   <?php checked(get_option('ai_genius_sound_effects', false), 1); ?>>
                            <?php esc_html_e('Ses efektlerini etkinleştir', 'ai-genius'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Mesaj gönderme/alma sırasında ses çalar.', 'ai-genius'); ?>
                        </p>
                    </div>
                </div>

                <!-- Gelişmiş -->
                <div class="settings-section advanced-section" style="display: none;">
                    <h2><?php esc_html_e('Gelişmiş Ayarlar', 'ai-genius'); ?></h2>
                    
                    <div class="form-group">
                        <label for="custom_css">
                            <?php esc_html_e('Özel CSS', 'ai-genius'); ?>
                        </label>
                        <textarea id="custom_css" 
                                  name="custom_css" 
                                  rows="6" 
                                  class="large-text code"><?php echo esc_textarea(get_option('ai_genius_custom_css', '')); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Chatbot görünümünü özelleştirmek için CSS kodları ekleyebilirsiniz.', 'ai-genius'); ?>
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="excluded_pages">
                            <?php esc_html_e('Hariç Tutulan Sayfalar', 'ai-genius'); ?>
                        </label>
                        <input type="text" 
                               id="excluded_pages" 
                               name="excluded_pages" 
                               value="<?php echo esc_attr(implode(',', get_option('ai_genius_excluded_pages', array()))); ?>"
                               class="large-text"
                               placeholder="1,5,123">
                        <p class="description">
                            <?php esc_html_e('Chatbot\'un gösterilmeyeceği sayfa ID\'lerini virgülle ayırarak yazın.', 'ai-genius'); ?>
                        </p>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="button toggle-advanced">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php esc_html_e('Gelişmiş Ayarları Göster', 'ai-genius'); ?>
                    </button>
                    
                    <div class="main-actions">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Ayarları Kaydet', 'ai-genius'); ?>
                        </button>
                        
                        <button type="button" class="button button-secondary preview-bot">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Önizleme', 'ai-genius'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Sağ Panel: Önizleme -->
        <div class="preview-panel">
            <div class="preview-header">
                <h3><?php esc_html_e('Chatbot Önizlemesi', 'ai-genius'); ?></h3>
                <button type="button" class="button-link refresh-preview">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
            
            <div class="preview-container">
                <div class="preview-screen">
                    <!-- Önizleme chatbot buraya render edilecek -->
                    <div id="chatbot-preview">
                        <div class="preview-mascot">
                            <div class="mascot-avatar">
                                <img src="<?php echo esc_url($current_settings['bot_avatar']); ?>" alt="Bot Avatar">
                                <div class="status-indicator"></div>
                            </div>
                        </div>
                        
                        <div class="preview-chat-window" style="display: none;">
                            <div class="chat-header">
                                <div class="header-info">
                                    <img src="<?php echo esc_url($current_settings['bot_avatar']); ?>" alt="Bot Avatar">
                                    <div>
                                        <h4 class="preview-bot-name"><?php echo esc_html($current_settings['bot_name']); ?></h4>
                                        <span class="bot-status">Online</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="chat-messages">
                                <div class="welcome-message">
                                    <div class="message bot-message">
                                        <div class="message-avatar">
                                            <img src="<?php echo esc_url($current_settings['bot_avatar']); ?>" alt="Bot">
                                        </div>
                                        <div class="message-content">
                                            <div class="message-text preview-welcome">
                                                <?php echo esc_html($current_settings['welcome_message']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="chat-input">
                                <div class="input-container">
                                    <input type="text" placeholder="<?php esc_attr_e('Mesajınızı yazın...', 'ai-genius'); ?>" readonly>
                                    <button class="send-btn">
                                        <span class="dashicons dashicons-arrow-up-alt2"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="preview-info">
                    <p class="description">
                        <?php esc_html_e('Bu önizleme gerçek zamanlı güncellenir. Değişiklikleri kaydetmeden önce nasıl görüneceğini kontrol edebilirsiniz.', 'ai-genius'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Templates (Hidden) -->
<div id="message-templates" style="display: none;">
    <div data-type="welcome" data-template="formal"><?php esc_html_e('İyi günler. Size nasıl yardımcı olabilirim?', 'ai-genius'); ?></div>
    <div data-type="welcome" data-template="friendly"><?php esc_html_e('Merhaba! 😊 Bugün nasıl yardımcı olabilirim?', 'ai-genius'); ?></div>
    <div data-type="welcome" data-template="professional"><?php esc_html_e('Hoş geldiniz! Müşteri destek asistanınızım. Size nasıl yardımcı olabilirim?', 'ai-genius'); ?></div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Karakter sayacları
    function updateCharCounter(textareaId, counterId, maxLength) {
        const $textarea = $('#' + textareaId);
        const $counter = $('#' + counterId);
        
        function updateCount() {
            const length = $textarea.val().length;
            $counter.text(length);
            $counter.toggleClass('over-limit', length > maxLength);
        }
        
        $textarea.on('input', updateCount);
        updateCount();
    }
    
    updateCharCounter('bot_personality', 'personality-counter', 500);
    updateCharCounter('welcome_message', 'welcome-counter', 200);
    updateCharCounter('fallback_message', 'fallback-counter', 200);
    
    // Avatar yükleme
    $('.upload-avatar').on('click', function() {
        const mediaUploader = wp.media({
            title: 'Bot Avatar Seç',
            button: {
                text: 'Avatar Seç'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#bot_avatar').val(attachment.url);
            $('#avatar-preview').attr('src', attachment.url);
            updatePreview();
        });
        
        mediaUploader.open();
    });
    
    // Avatar kaldırma
    $('.remove-avatar').on('click', function() {
        const defaultAvatar = '<?php echo AI_GENIUS_PLUGIN_URL; ?>assets/images/default-avatar.svg';
        $('#bot_avatar').val(defaultAvatar);
        $('#avatar-preview').attr('src', defaultAvatar);
        updatePreview();
    });
    
    // Konum seçimi
    $('.position-option input').on('change', function() {
        $('.position-option').removeClass('selected');
        $(this).closest('.position-option').addClass('selected');
        updatePreview();
    });
    
    // Tema seçimi
    $('.theme-option input').on('change', function() {
        $('.theme-option').removeClass('selected');
        $(this).closest('.theme-option').addClass('selected');
        updatePreview();
    });
    
    // Renk paleti
    $('.color-picker').on('change', function() {
        updatePreview();
    });
    
    // Renkleri sıfırla
    $('.reset-colors').on('click', function() {
        $('input[name="colors[primary]"]').val('#0073aa');
        $('input[name="colors[secondary]"]').val('#005177');
        $('input[name="colors[accent]"]').val('#00a32a');
        updatePreview();
    });
    
    // Mesaj şablonları
    $('.template-btn').on('click', function() {
        const type = $(this).data('type');
        const template = $(this).data('template');
        const templateText = $('#message-templates').find(`[data-type="${type}"][data-template="${template}"]`).text();
        
        if (type === 'welcome') {
            $('#welcome_message').val(templateText).trigger('input');
        }
        
        updatePreview();
    });
    
    // Gelişmiş ayarları göster/gizle
    $('.toggle-advanced').on('click', function() {
        const $section = $('.advanced-section');
        const $btn = $(this);
        
        if ($section.is(':visible')) {
            $section.slideUp();
            $btn.find('span:last').text('Gelişmiş Ayarları Göster');
        } else {
            $section.slideDown();
            $btn.find('span:last').text('Gelişmiş Ayarları Gizle');
        }
    });
    
    // Önizleme güncelleme
    function updatePreview() {
        const botName = $('#bot_name').val() || 'AI Asistan';
        const botAvatar = $('#bot_avatar').val();
        const welcomeMessage = $('#welcome_message').val() || 'Merhaba! Size nasıl yardımcı olabilirim?';
        const chatTheme = $('input[name="chat_theme"]:checked').val() || 'auto';
        const chatPosition = $('input[name="chat_position"]:checked').val() || 'bottom-right';
        
        // Avatar güncelle
        $('#chatbot-preview .mascot-avatar img, #chatbot-preview .header-info img, #chatbot-preview .message-avatar img').attr('src', botAvatar);
        
        // Bot adını güncelle
        $('#chatbot-preview .preview-bot-name').text(botName);
        
        // Karşılama mesajını güncelle
        $('#chatbot-preview .preview-welcome').text(welcomeMessage);
        
        // Tema uygula
        $('#chatbot-preview').removeClass('theme-auto theme-light theme-dark').addClass('theme-' + chatTheme);
        
        // Pozisyon uygula
        $('#chatbot-preview').removeClass('position-top-left position-top-right position-bottom-left position-bottom-right').addClass('position-' + chatPosition);
        
        // Özel renkler uygula
        const primaryColor = $('input[name="colors[primary]"]').val();
        const secondaryColor = $('input[name="colors[secondary]"]').val();
        const accentColor = $('input[name="colors[accent]"]').val();
        
        const previewStyle = `
            <style id="preview-colors">
                #chatbot-preview {
                    --preview-primary: ${primaryColor};
                    --preview-secondary: ${secondaryColor};
                    --preview-accent: ${accentColor};
                }
                #chatbot-preview .mascot-avatar {
                    background: linear-gradient(135deg, ${primaryColor}, ${secondaryColor});
                }
                #chatbot-preview .chat-header {
                    background: linear-gradient(135deg, ${primaryColor}, ${secondaryColor});
                }
                #chatbot-preview .send-btn {
                    background: ${primaryColor};
                }
                #chatbot-preview .status-indicator {
                    background: ${accentColor};
                }
            </style>
        `;
        
        $('#preview-colors').remove();
        $('head').append(previewStyle);
    }
    
    // Form değişikliklerini izle
    $('#bot-settings-form input, #bot-settings-form textarea, #bot-settings-form select').on('input change', function() {
        setTimeout(updatePreview, 100);
    });
    
    // Önizleme yenileme
    $('.refresh-preview').on('click', function() {
        $(this).addClass('spin');
        setTimeout(() => {
            updatePreview();
            $(this).removeClass('spin');
        }, 1000);
    });
    
    // Önizleme chatbot'a tıklama
    $('.preview-mascot').on('click', function() {
        $('.preview-chat-window').toggle();
    });
    
    // Önizleme testi
    $('.preview-bot').on('click', function() {
        const testMessage = 'Bu bir test mesajıdır.';
        const $messages = $('#chatbot-preview .chat-messages');
        
        // Test mesajı ekle
        const testHtml = `
            <div class="message user-message">
                <div class="message-content">
                    <div class="message-text">${testMessage}</div>
                </div>
            </div>
            <div class="message bot-message">
                <div class="message-avatar">
                    <img src="${$('#bot_avatar').val()}" alt="Bot">
                </div>
                <div class="message-content">
                    <div class="message-text">Merhaba! Bu bir test yanıtıdır. Bot bu şekilde görünecek.</div>
                </div>
            </div>
        `;
        
        $messages.append(testHtml);
        
        // 3 saniye sonra temizle
        setTimeout(() => {
            $messages.find('.message').not('.welcome-message .message').remove();
        }, 3000);
    });
    
    // Form submit
    $('#bot-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.find('span:last').text();
        
        $submitBtn.prop('disabled', true).find('span:last').text('Kaydediliyor...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_genius_save_bot_settings',
                nonce: aiGeniusAdmin.nonce,
                form_data: $form.serialize()
            },
            success: function(response) {
                if (response.success) {
                    // Success toast
                    showToast('Ayarlar başarıyla kaydedildi! ✅', 'success');
                } else {
                    showToast('Kaydetme hatası: ' + response.data, 'error');
                }
            },
            error: function() {
                showToast('Bağlantı hatası oluştu.', 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).find('span:last').text(originalText);
            }
        });
    });
    
    // Toast mesajları
    function showToast(message, type = 'info') {
        const toast = $(`
            <div class="bot-settings-toast toast-${type}">
                ${message}
            </div>
        `);
        
        $('body').append(toast);
        
        setTimeout(() => {
            toast.addClass('show');
        }, 100);
        
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Başlangıç önizlemesi
    updatePreview();
});
</script>

<style>
/* Bot Settings Sayfası Stilleri */
.ai-genius-bot-settings {
    background: #f1f1f1;
    margin: 0 -20px;
    padding: 20px;
}

.bot-settings-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
    margin-top: 20px;
}

.settings-panel {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.settings-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #f0f0f1;
}

.settings-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.settings-section h2 {
    margin: 0 0 20px 0;
    font-size: 18px;
    color: #1d2327;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 6px;
    color: #1d2327;
}

.required {
    color: #d63638;
}

.character-counter {
    text-align: right;
    font-size: 12px;
    color: #646970;
    margin-top: 4px;
}

.character-counter.over-limit {
    color: #d63638;
}

.avatar-upload {
    display: flex;
    align-items: center;
    gap: 16px;
}

.avatar-preview {
    flex-shrink: 0;
}

.avatar-preview img {
    border-radius: 50%;
    border: 3px solid #f0f0f1;
}

.avatar-controls {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.message-templates {
    margin-top: 16px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 6px;
}

.message-templates h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    color: #646970;
}

.template-buttons {
    display: flex;
    gap: 8px;
}

.template-btn {
    font-size: 12px;
    padding: 6px 12px;
}

.position-selector {
    margin-top: 8px;
}

.position-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.position-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    border: 2px solid #f0f0f1;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.position-option:hover {
    border-color: #0073aa;
}

.position-option.selected {
    border-color: #0073aa;
    background: #f0f6fc;
}

.position-option input {
    display: none;
}

.position-visual {
    width: 60px;
    height: 40px;
    border: 2px solid #ddd;
    border-radius: 4px;
    position: relative;
    background: #fff;
    margin-bottom: 8px;
}

.position-visual .bot-icon {
    width: 12px;
    height: 12px;
    background: #0073aa;
    border-radius: 50%;
    position: absolute;
}

.position-visual.top-left .bot-icon {
    top: 4px;
    left: 4px;
}

.position-visual.top-right .bot-icon {
    top: 4px;
    right: 4px;
}

.position-visual.bottom-left .bot-icon {
    bottom: 4px;
    left: 4px;
}

.position-visual.bottom-right .bot-icon {
    bottom: 4px;
    right: 4px;
}

.theme-selector {
    display: flex;
    gap: 16px;
    margin-top: 8px;
}

.theme-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    border: 2px solid #f0f0f1;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    flex: 1;
}

.theme-option:hover {
    border-color: #0073aa;
}

.theme-option.selected {
    border-color: #0073aa;
    background: #f0f6fc;
}

.theme-option input {
    display: none;
}

.theme-preview {
    width: 60px;
    height: 40px;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
    border: 1px solid #ddd;
}

.theme-header {
    height: 12px;
}

.theme-body {
    height: 28px;
    padding: 4px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.theme-message {
    height: 4px;
    border-radius: 2px;
}

.theme-message.bot {
    width: 70%;
    background: #f0f0f1;
}

.theme-message.user {
    width: 50%;
    margin-left: auto;
}

.theme-auto .theme-header {
    background: #0073aa;
}

.theme-auto .theme-body {
    background: #fff;
}

.theme-auto .theme-message.user {
    background: #0073aa;
}

.theme-light .theme-header {
    background: #0073aa;
}

.theme-light .theme-body {
    background: #fff;
}

.theme-light .theme-message.user {
    background: #0073aa;
}

.theme-dark .theme-header {
    background: #4a9eff;
}

.theme-dark .theme-body {
    background: #1a1a1a;
}

.theme-dark .theme-message.bot {
    background: #2a2a2a;
}

.theme-dark .theme-message.user {
    background: #4a9eff;
}

.theme-info {
    text-align: center;
}

.theme-info strong {
    display: block;
    font-size: 13px;
    color: #1d2327;
}

.theme-info span {
    font-size: 11px;
    color: #646970;
}

.color-palette {
    display: flex;
    gap: 16px;
    margin: 12px 0;
}

.color-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.color-group label {
    font-size: 12px;
    color: #646970;
    margin: 0;
}

.color-picker {
    width: 40px;
    height: 40px;
    border: none;
    border-radius: 50%;
    cursor: pointer;
}

.form-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 2px solid #f0f0f1;
}

.main-actions {
    display: flex;
    gap: 12px;
}

.preview-panel {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    height: fit-content;
    position: sticky;
    top: 32px;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f1;
}

.preview-header h3 {
    margin: 0;
    font-size: 16px;
    color: #1d2327;
}

.refresh-preview {
    color: #0073aa;
    text-decoration: none;
}

.refresh-preview.spin .dashicons {
    animation: spin 1s linear infinite;
}

.preview-container {
    text-align: center;
}

.preview-screen {
    width: 300px;
    height: 400px;
    background: #f8f9fa;
    border: 2px solid #ddd;
    border-radius: 12px;
    margin: 0 auto 16px;
    position: relative;
    overflow: hidden;
}

#chatbot-preview {
    position: absolute;
    bottom: 20px;
    right: 20px;
}

.preview-mascot {
    cursor: pointer;
}

.mascot-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0073aa, #005177);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.mascot-avatar img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
}

.status-indicator {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 12px;
    height: 12px;
    background: #00a32a;
    border: 2px solid white;
    border-radius: 50%;
}

.preview-chat-window {
    position: absolute;
    bottom: 60px;
    right: 0;
    width: 280px;
    height: 320px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.chat-header {
    background: linear-gradient(135deg, #0073aa, #005177);
    color: white;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.header-info img {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.header-info h4 {
    margin: 0;
    font-size: 14px;
}

.bot-status {
    font-size: 11px;
    opacity: 0.8;
}

.chat-messages {
    flex: 1;
    padding: 12px;
    overflow-y: auto;
}

.welcome-message .message {
    display: flex;
    gap: 8px;
    align-items: flex-start;
}

.message-avatar img {
    width: 20px;
    height: 20px;
    border-radius: 50%;
}

.message-text {
    background: #f0f0f1;
    padding: 8px 12px;
    border-radius: 12px;
    font-size: 12px;
    line-height: 1.3;
}

.chat-input {
    border-top: 1px solid #f0f0f1;
    padding: 8px;
}

.input-container {
    display: flex;
    gap: 4px;
    align-items: center;
}

.input-container input {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 16px;
    padding: 6px 12px;
    font-size: 12px;
}

.send-btn {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: none;
    background: #0073aa;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

/* Toast mesajları */
.bot-settings-toast {
    position: fixed;
    top: 50px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 6px;
    color: white;
    font-weight: 500;
    z-index: 100000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.bot-settings-toast.show {
    transform: translateX(0);
}

.toast-success {
    background: #00a32a;
}

.toast-error {
    background: #d63638;
}

.toast-info {
    background: #0073aa;
}

/* Responsive */
@media (max-width: 1200px) {
    .bot-settings-container {
        grid-template-columns: 1fr;
    }
    
    .preview-panel {
        order: -1;
    }
}

@media (max-width: 768px) {
    .theme-selector {
        flex-direction: column;
    }
    
    .color-palette {
        justify-content: center;
    }
    
    .form-actions {
        flex-direction: column;
        gap: 16px;
    }
    
    .main-actions {
        width: 100%;
        justify-content: center;
    }
    
    .preview-screen {
        width: 250px;
        height: 350px;
    }
    
    .preview-chat-window {
        width: 220px;
        height: 280px;
    }
}
</style>