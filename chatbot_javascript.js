/**
 * AI-Genius Chatbot JavaScript
 * 
 * Frontend chatbot etkileşimlerini yönetir
 */

(function($) {
    'use strict';

    class AIGeniusChatbot {
        constructor() {
            this.isOpen = false;
            this.isMinimized = false;
            this.currentSession = null;
            this.messageHistory = [];
            this.proactiveShown = false;
            this.typingTimeout = null;
            this.soundEnabled = aiGeniusChatbot.features.sound_effects;
            
            this.init();
        }

        init() {
            this.setupElements();
            this.bindEvents();
            this.setupTheme();
            this.startSession();
            this.scheduleProactiveMessage();
            
            // Chatbot'u göster
            $('#ai-genius-chatbot').fadeIn(300);
            
            console.log('AI-Genius Chatbot initialized');
        }

        setupElements() {
            this.$chatbot = $('#ai-genius-chatbot');
            this.$mascot = $('#chatbot-mascot');
            this.$window = $('#chatbot-window');
            this.$messages = $('#chatbot-messages');
            this.$input = $('#message-input');
            this.$sendBtn = $('#send-btn');
            this.$typingIndicator = $('#typing-indicator');
            this.$proactiveBubble = $('#proactive-bubble');
            this.$messageBadge = $('#message-badge');
            this.$minimizeBtn = $('#minimize-btn');
            this.$closeBtn = $('#close-btn');
            
            // File upload elements
            this.$fileBtn = $('#file-btn');
            this.$fileInput = $('#file-input');
            this.$fileUploadArea = $('#file-upload-area');
            
            // Voice elements
            this.$voiceBtn = $('#voice-btn');
        }

        bindEvents() {
            // Maskot tıklama
            this.$mascot.on('click', () => this.toggleWindow());
            
            // Pencere kontrolleri
            this.$minimizeBtn.on('click', () => this.minimizeWindow());
            this.$closeBtn.on('click', () => this.closeWindow());
            
            // Mesaj gönderme
            this.$sendBtn.on('click', () => this.sendMessage());
            this.$input.on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            // Input otomatik boyutlandırma
            this.$input.on('input', () => this.autoResizeTextarea());
            
            // Proaktif mesaj kapatma
            $(document).on('click', '.bubble-close', () => this.closeProactiveBubble());
            
            // Mesaj değerlendirme
            $(document).on('click', '.helpful-btn', (e) => this.rateMessage(e, true));
            $(document).on('click', '.not-helpful-btn', (e) => this.rateMessage(e, false));
            
            // File upload
            if (this.$fileBtn.length) {
                this.$fileBtn.on('click', () => this.toggleFileUpload());
                this.$fileInput.on('change', (e) => this.handleFileUpload(e));
            }
            
            // Voice input
            if (this.$voiceBtn.length && 'webkitSpeechRecognition' in window) {
                this.$voiceBtn.on('click', () => this.toggleVoiceInput());
            }
            
            // Drag & Drop
            this.$fileUploadArea.on('dragover', (e) => {
                e.preventDefault();
                $(e.currentTarget).addClass('dragover');
            });
            
            this.$fileUploadArea.on('dragleave', (e) => {
                $(e.currentTarget).removeClass('dragover');
            });
            
            this.$fileUploadArea.on('drop', (e) => {
                e.preventDefault();
                $(e.currentTarget).removeClass('dragover');
                this.handleFileDrop(e.originalEvent.dataTransfer.files);
            });
            
            // Pencere dışı tıklama
            $(document).on('click', (e) => {
                if (!$(e.target).closest('#ai-genius-chatbot').length && this.isOpen && !this.isMinimized) {
                    // Pencere açıkken dışarı tıklanırsa minimize et
                    this.minimizeWindow();
                }
            });
            
            // Escape tuşu
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.minimizeWindow();
                }
            });
        }

        setupTheme() {
            const colors = aiGeniusChatbot.theme_colors;
            
            if (colors && Object.keys(colors).length > 0) {
                const root = document.documentElement;
                root.style.setProperty('--chatbot-primary', colors.primary);
                root.style.setProperty('--chatbot-secondary', colors.secondary);
                root.style.setProperty('--chatbot-accent', colors.accent);
                root.style.setProperty('--chatbot-bg', colors.background);
                root.style.setProperty('--chatbot-text', colors.text);
                root.style.setProperty('--chatbot-border', colors.border);
            }
        }

        startSession() {
            this.currentSession = aiGeniusChatbot.user_info.session_id;
            console.log('Chat session started:', this.currentSession);
        }

        scheduleProactiveMessage() {
            if (this.proactiveShown) return;
            
            const proactiveData = aiGeniusChatbot.proactive_messages;
            
            if (proactiveData && proactiveData.messages && proactiveData.messages.length > 0) {
                setTimeout(() => {
                    this.showProactiveMessage();
                }, proactiveData.delay || 5000);
            }
        }

        showProactiveMessage() {
            if (this.proactiveShown || this.isOpen) return;
            
            const proactiveData = aiGeniusChatbot.proactive_messages;
            const randomMessage = proactiveData.messages[Math.floor(Math.random() * proactiveData.messages.length)];
            
            this.$proactiveBubble.find('.bubble-text').text(randomMessage);
            this.$proactiveBubble.fadeIn(300);
            this.proactiveShown = true;
            
            // 10 saniye sonra otomatik kapat
            setTimeout(() => {
                this.closeProactiveBubble();
            }, 10000);
            
            this.playSound('notification');
        }

        closeProactiveBubble() {
            this.$proactiveBubble.fadeOut(200);
        }

        toggleWindow() {
            if (this.isOpen) {
                if (this.isMinimized) {
                    this.restoreWindow();
                } else {
                    this.minimizeWindow();
                }
            } else {
                this.openWindow();
            }
        }

        openWindow() {
            this.closeProactiveBubble();
            this.$window.slideDown(400, 'easeOutCubic');
            this.isOpen = true;
            this.isMinimized = false;
            this.$mascot.addClass('window-open');
            
            // Focus input
            setTimeout(() => {
                this.$input.focus();
            }, 500);
            
            this.playSound('open');
        }

        minimizeWindow() {
            this.$window.slideUp(300, 'easeInCubic');
            this.isMinimized = true;
            this.$mascot.removeClass('window-open');
            
            // Badge göster
            this.showMessageBadge();
            
            this.playSound('minimize');
        }

        restoreWindow() {
            this.$window.slideDown(400, 'easeOutCubic');
            this.isMinimized = false;
            this.$mascot.addClass('window-open');
            this.hideMessageBadge();
            
            // Focus input
            setTimeout(() => {
                this.$input.focus();
            }, 500);
        }

        closeWindow() {
            this.$window.slideUp(300, 'easeInCubic');
            this.isOpen = false;
            this.isMinimized = false;
            this.$mascot.removeClass('window-open');
            
            this.playSound('close');
        }

        showMessageBadge() {
            this.$messageBadge.fadeIn(200);
        }

        hideMessageBadge() {
            this.$messageBadge.fadeOut(200);
        }

        sendMessage() {
            const message = this.$input.val().trim();
            
            if (!message) return;
            
            // Kullanıcı mesajını göster
            this.addMessage(message, 'user');
            this.$input.val('').trigger('input');
            
            // Yazıyor göstergesini başlat
            this.showTypingIndicator();
            
            // AJAX ile mesajı gönder
            this.sendToAI(message);
            
            this.playSound('send');
        }

        addMessage(content, type = 'bot', options = {}) {
            const messageId = 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            const time = new Date().toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit' });
            const avatar = type === 'bot' ? aiGeniusChatbot.bot_settings.avatar : null;
            
            let messageHtml = `
                <div class="message ${type}-message" data-message-id="${messageId}">
                    ${type === 'bot' ? `
                        <div class="message-avatar">
                            <img src="${avatar}" alt="${aiGeniusChatbot.bot_settings.name}">
                        </div>
                    ` : ''}
                    <div class="message-content">
                        <div class="message-text ${options.error ? 'error' : ''}">${content}</div>
                        <div class="message-time">${time}</div>
                        ${type === 'bot' && !options.error ? this.getMessageActions(messageId) : ''}
                        ${options.error ? '<button class="retry-btn" onclick="aiGeniusChatbot.retryMessage()">Tekrar Dene</button>' : ''}
                    </div>
                </div>
            `;
            
            this.$messages.append(messageHtml);
            this.scrollToBottom();
            
            // Message history'ye ekle
            this.messageHistory.push({
                id: messageId,
                content: content,
                type: type,
                timestamp: Date.now()
            });
        }

        getMessageActions(messageId) {
            if (!aiGeniusChatbot.features.emoji_reactions) return '';
            
            return `
                <div class="message-actions">
                    <button class="action-btn helpful-btn" data-message-id="${messageId}" title="${aiGeniusChatbot.strings.helpful}">
                        👍
                    </button>
                    <button class="action-btn not-helpful-btn" data-message-id="${messageId}" title="${aiGeniusChatbot.strings.not_helpful}">
                        👎
                    </button>
                </div>
            `;
        }

        showTypingIndicator() {
            if (aiGeniusChatbot.features.typing_indicator) {
                this.$typingIndicator.fadeIn(200);
                this.scrollToBottom();
            }
        }

        hideTypingIndicator() {
            this.$typingIndicator.fadeOut(200);
        }

        sendToAI(message) {
            const requestData = {
                action: 'ai_genius_chat_message',
                nonce: aiGeniusChatbot.nonce,
                message: message,
                session_id: this.currentSession,
                page_type: aiGeniusChatbot.page_info.type,
                page_url: window.location.href,
                page_title: document.title
            };

            $.ajax({
                url: aiGeniusChatbot.ajaxurl,
                type: 'POST',
                data: requestData,
                timeout: 30000,
                success: (response) => {
                    this.hideTypingIndicator();
                    
                    if (response.success) {
                        this.addMessage(response.data.response, 'bot', {
                            provider: response.data.provider,
                            model: response.data.model,
                            response_time: response.data.response_time
                        });
                        
                        this.playSound('receive');
                    } else {
                        this.addMessage(
                            response.data || aiGeniusChatbot.strings.error_message, 
                            'bot', 
                            { error: true }
                        );
                        this.playSound('error');
                    }
                },
                error: (xhr, status, error) => {
                    this.hideTypingIndicator();
                    
                    let errorMessage = aiGeniusChatbot.strings.connection_error;
                    
                    if (status === 'timeout') {
                        errorMessage = 'Yanıt çok uzun sürdü. Lütfen tekrar deneyin.';
                    } else if (xhr.status === 429) {
                        errorMessage = 'Çok fazla istek gönderildi. Lütfen bekleyin.';
                    }
                    
                    this.addMessage(errorMessage, 'bot', { error: true });
                    this.playSound('error');
                    
                    console.error('Chat AJAX error:', error);
                }
            });
        }

        rateMessage(e, isHelpful) {
            const $btn = $(e.currentTarget);
            const messageId = $btn.data('message-id');
            
            if ($btn.hasClass('rated')) return;
            
            $.ajax({
                url: aiGeniusChatbot.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ai_genius_rate_response',
                    nonce: aiGeniusChatbot.nonce,
                    message_id: messageId,
                    rating: isHelpful ? 'helpful' : 'not_helpful',
                    session_id: this.currentSession
                },
                success: (response) => {
                    if (response.success) {
                        $btn.addClass('rated').siblings().removeClass('rated');
                        $btn.addClass(isHelpful ? 'helpful' : 'not-helpful');
                        
                        // Teşekkür mesajı göster
                        this.showFeedbackToast('Geri bildiriminiz kaydedildi. Teşekkürler!');
                    }
                }
            });
        }

        showFeedbackToast(message) {
            const toast = $(`
                <div class="feedback-toast" style="
                    position: fixed;
                    bottom: 100px;
                    right: 20px;
                    background: var(--chatbot-accent);
                    color: white;
                    padding: 12px 16px;
                    border-radius: 8px;
                    font-size: 13px;
                    z-index: 1000000;
                    opacity: 0;
                    transform: translateY(10px);
                    transition: all 0.3s ease;
                ">
                    ${message}
                </div>
            `);
            
            $('body').append(toast);
            
            setTimeout(() => {
                toast.css({ opacity: 1, transform: 'translateY(0)' });
            }, 100);
            
            setTimeout(() => {
                toast.css({ opacity: 0, transform: 'translateY(-10px)' });
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        autoResizeTextarea() {
            const textarea = this.$input[0];
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
        }

        scrollToBottom() {
            const messagesContainer = this.$messages[0];
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        toggleFileUpload() {
            this.$fileUploadArea.toggle();
            if (this.$fileUploadArea.is(':visible')) {
                this.$fileInput.trigger('click');
            }
        }

        handleFileUpload(e) {
            const files = e.target.files;
            this.processFiles(files);
        }

        handleFileDrop(files) {
            this.processFiles(files);
        }

        processFiles(files) {
            Array.from(files).forEach(file => {
                if (this.validateFile(file)) {
                    this.uploadFile(file);
                }
            });
            
            this.$fileUploadArea.hide();
        }

        validateFile(file) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['text/plain', 'application/pdf', 'image/jpeg', 'image/png', 'application/msword'];
            
            if (file.size > maxSize) {
                this.addMessage('Dosya çok büyük. Maksimum 10MB olmalı.', 'bot', { error: true });
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                this.addMessage('Desteklenmeyen dosya türü.', 'bot', { error: true });
                return false;
            }
            
            return true;
        }

        uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'ai_genius_upload_file');
            formData.append('nonce', aiGeniusChatbot.nonce);
            formData.append('session_id', this.currentSession);
            
            // Dosya mesajı ekle
            this.addMessage(`📎 ${file.name} yükleniyor...`, 'user');
            
            $.ajax({
                url: aiGeniusChatbot.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.addMessage(`✅ ${file.name} başarıyla yüklendi.`, 'bot');
                    } else {
                        this.addMessage(`❌ Dosya yüklenemedi: ${response.data}`, 'bot', { error: true });
                    }
                },
                error: () => {
                    this.addMessage('❌ Dosya yükleme hatası.', 'bot', { error: true });
                }
            });
        }

        toggleVoiceInput() {
            if (!this.recognition) {
                this.setupVoiceRecognition();
            }
            
            if (this.isListening) {
                this.stopVoiceRecognition();
            } else {
                this.startVoiceRecognition();
            }
        }

        setupVoiceRecognition() {
            this.recognition = new webkitSpeechRecognition();
            this.recognition.continuous = false;
            this.recognition.interimResults = false;
            this.recognition.lang = 'tr-TR';
            
            this.recognition.onstart = () => {
                this.isListening = true;
                this.$voiceBtn.addClass('listening');
                this.addMessage('🎤 Dinliyorum...', 'bot');
            };
            
            this.recognition.onresult = (event) => {
                const transcript = event.results[0][0].transcript;
                this.$input.val(transcript);
                this.sendMessage();
            };
            
            this.recognition.onerror = (event) => {
                this.addMessage('🎤 Ses tanıma hatası: ' + event.error, 'bot', { error: true });
                this.stopVoiceRecognition();
            };
            
            this.recognition.onend = () => {
                this.stopVoiceRecognition();
            };
        }

        startVoiceRecognition() {
            this.recognition.start();
        }

        stopVoiceRecognition() {
            this.isListening = false;
            this.$voiceBtn.removeClass('listening');
            if (this.recognition) {
                this.recognition.stop();
            }
        }

        playSound(type) {
            if (!this.soundEnabled) return;
            
            const sounds = {
                'notification': 'data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAt',
                'send': 'data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAt',
                'receive': 'data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAt',
                'open': 'data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAt',
                'close': 'data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAt',
                'minimize': 'data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAt',
                'error': 'data:audio/mpeg;base64,SUQzBAAAAAABEVRYWFgAAAAt'
            };
            
            if (sounds[type]) {
                const audio = new Audio(sounds[type]);
                audio.volume = 0.3;
                audio.play().catch(() => {
                    // Ses çalma hatası - sessizce devam et
                });
            }
        }

        retryMessage() {
            const lastUserMessage = this.messageHistory
                .filter(msg => msg.type === 'user')
                .pop();
            
            if (lastUserMessage) {
                this.sendToAI(lastUserMessage.content);
            }
        }

        // Keyboard shortcuts
        handleKeyboardShortcuts(e) {
            // Ctrl/Cmd + Enter: Send message
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                this.sendMessage();
            }
            
            // Ctrl/Cmd + M: Toggle minimize
            if ((e.ctrlKey || e.metaKey) && e.key === 'm') {
                e.preventDefault();
                this.toggleWindow();
            }
        }

        // Accessibility improvements
        setupAccessibility() {
            // ARIA labels
            this.$mascot.attr('aria-label', 'Chatbot\'u aç/kapat');
            this.$input.attr('aria-label', 'Mesaj yazın');
            this.$sendBtn.attr('aria-label', 'Mesaj gönder');
            
            // Keyboard navigation
            this.$chatbot.on('keydown', (e) => this.handleKeyboardShortcuts(e));
            
            // Focus management
            this.$chatbot.on('focusin', () => {
                this.$chatbot.addClass('focused');
            });
            
            this.$chatbot.on('focusout', () => {
                setTimeout(() => {
                    if (!this.$chatbot.find(':focus').length) {
                        this.$chatbot.removeClass('focused');
                    }
                }, 100);
            });
        }

        // Mobile optimizations
        setupMobileOptimizations() {
            if (/Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                this.$chatbot.addClass('mobile-device');
                
                // Viewport height fix for mobile
                const setVH = () => {
                    const vh = window.innerHeight * 0.01;
                    document.documentElement.style.setProperty('--vh', `${vh}px`);
                };
                
                setVH();
                window.addEventListener('resize', setVH);
                window.addEventListener('orientationchange', setVH);
                
                // Prevent zoom on input focus
                this.$input.attr('autocomplete', 'off');
                this.$input.attr('autocorrect', 'off');
                this.$input.attr('autocapitalize', 'off');
                this.$input.attr('spellcheck', 'false');
            }
        }

        // Theme detection
        detectSystemTheme() {
            if (!window.matchMedia) return;
            
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            const handleThemeChange = (e) => {
                if (aiGeniusChatbot.bot_settings.theme === 'auto') {
                    this.$chatbot.removeClass('theme-light theme-dark');
                    this.$chatbot.addClass(e.matches ? 'theme-dark' : 'theme-light');
                }
            };
            
            handleThemeChange(mediaQuery);
            mediaQuery.addListener(handleThemeChange);
        }

        // Performance optimizations
        optimizePerformance() {
            // Debounce scroll events
            let scrollTimeout;
            this.$messages.on('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    // Handle scroll end
                }, 100);
            });
            
            // Lazy load old messages
            if (this.messageHistory.length > 50) {
                this.implementVirtualScrolling();
            }
        }

        implementVirtualScrolling() {
            // Simple virtual scrolling for better performance with many messages
            const visibleMessages = 30;
            const totalMessages = this.messageHistory.length;
            
            if (totalMessages > visibleMessages) {
                // Hide old messages but keep them in DOM for search
                this.$messages.find('.message').slice(0, totalMessages - visibleMessages)
                    .addClass('hidden-message').hide();
            }
        }

        // Analytics tracking
        trackEvent(eventName, eventData = {}) {
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, {
                    'event_category': 'AI_Genius_Chatbot',
                    'custom_map': eventData
                });
            }
            
            // Custom analytics can be added here
        }

        // Error handling and logging
        handleError(error, context = '') {
            console.error('AI-Genius Chatbot Error:', error, context);
            
            // Send error to server for debugging (optional)
            if (aiGeniusChatbot.debug_mode) {
                $.post(aiGeniusChatbot.ajaxurl, {
                    action: 'ai_genius_log_error',
                    nonce: aiGeniusChatbot.nonce,
                    error: error.toString(),
                    context: context,
                    url: window.location.href,
                    user_agent: navigator.userAgent
                });
            }
        }

        // Cleanup and destroy
        destroy() {
            // Remove event listeners
            this.$chatbot.off();
            $(document).off('click keydown');
            
            // Stop voice recognition
            if (this.recognition) {
                this.recognition.stop();
            }
            
            // Clear timeouts
            clearTimeout(this.typingTimeout);
            
            // Remove DOM elements
            this.$chatbot.remove();
            
            console.log('AI-Genius Chatbot destroyed');
        }
    }

    // Quick reply functionality
    function setupQuickReplies() {
        $(document).on('click', '.quick-reply-btn', function() {
            const text = $(this).text();
            window.aiGeniusInstance.$input.val(text);
            window.aiGeniusInstance.sendMessage();
            $(this).parent().fadeOut();
        });
    }

    // Emoji picker (if enabled)
    function setupEmojiPicker() {
        if (!aiGeniusChatbot.features.emoji_reactions) return;
        
        const commonEmojis = ['👍', '👎', '😊', '😢', '😮', '🤔', '❤️', '👏'];
        
        $(document).on('click', '.emoji-btn', function() {
            const $picker = $('<div class="emoji-picker"></div>');
            
            commonEmojis.forEach(emoji => {
                $picker.append(`<button class="emoji-option">${emoji}</button>`);
            });
            
            $(this).after($picker);
            $picker.fadeIn(200);
        });
        
        $(document).on('click', '.emoji-option', function() {
            const emoji = $(this).text();
            const messageId = $(this).closest('.message').data('message-id');
            
            // Add reaction to message
            $(this).closest('.message').find('.message-reactions')
                .append(`<span class="reaction">${emoji}</span>`);
            
            $('.emoji-picker').remove();
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Check if chatbot should be displayed
        if ($('#ai-genius-chatbot').length === 0) {
            return;
        }
        
        try {
            // Initialize main chatbot
            window.aiGeniusInstance = new AIGeniusChatbot();
            
            // Setup additional features
            setupQuickReplies();
            setupEmojiPicker();
            
            // Setup accessibility
            window.aiGeniusInstance.setupAccessibility();
            
            // Setup mobile optimizations
            window.aiGeniusInstance.setupMobileOptimizations();
            
            // Detect system theme
            window.aiGeniusInstance.detectSystemTheme();
            
            // Optimize performance
            window.aiGeniusInstance.optimizePerformance();
            
            // Track initialization
            window.aiGeniusInstance.trackEvent('chatbot_initialized', {
                'page_type': aiGeniusChatbot.page_info.type,
                'user_type': aiGeniusChatbot.user_info.id > 0 ? 'logged_in' : 'guest'
            });
            
        } catch (error) {
            console.error('Failed to initialize AI-Genius Chatbot:', error);
            
            // Fallback: Show simple error message
            $('#ai-genius-chatbot').html(`
                <div style="padding: 20px; text-align: center; color: #666;">
                    Chatbot yüklenirken bir hata oluştu.<br>
                    <small>Lütfen sayfayı yenileyin veya site yöneticisiyle iletişime geçin.</small>
                </div>
            `);
        }
    });

    // Global functions for external access
    window.AIGeniusChatbot = {
        getInstance: () => window.aiGeniusInstance,
        
        openChat: () => {
            if (window.aiGeniusInstance) {
                window.aiGeniusInstance.openWindow();
            }
        },
        
        closeChat: () => {
            if (window.aiGeniusInstance) {
                window.aiGeniusInstance.closeWindow();
            }
        },
        
        sendMessage: (message) => {
            if (window.aiGeniusInstance && message) {
                window.aiGeniusInstance.$input.val(message);
                window.aiGeniusInstance.sendMessage();
                window.aiGeniusInstance.openWindow();
            }
        },
        
        showProactive: (message) => {
            if (window.aiGeniusInstance && message) {
                window.aiGeniusInstance.$proactiveBubble.find('.bubble-text').text(message);
                window.aiGeniusInstance.$proactiveBubble.fadeIn(300);
            }
        }
    };

    // Expose to global scope for WordPress admin or other scripts
    window.aiGeniusChatbot = window.aiGeniusChatbot || {};
    window.aiGeniusChatbot.API = window.AIGeniusChatbot;

})(jQuery);

// CSS animations and utilities
jQuery(document).ready(function($) {
    
    // Add smooth scrolling to all internal links in chat
    $(document).on('click', '.message-text a[href^="#"]', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });
    
    // Handle external links in chat messages
    $(document).on('click', '.message-text a[href^="http"]', function(e) {
        e.preventDefault();
        window.open(this.href, '_blank', 'noopener,noreferrer');
    });
    
    // Auto-expand textarea on mobile
    if (window.innerWidth <= 768) {
        $(document).on('focus', '#message-input', function() {
            $(this).closest('.chatbot-input').css('background', '#f0f0f0');
        });
        
        $(document).on('blur', '#message-input', function() {
            $(this).closest('.chatbot-input').css('background', '');
        });
    }
    
    // Prevent chat window from closing when clicking inside
    $(document).on('click', '.chatbot-window', function(e) {
        e.stopPropagation();
    });
    
    // Handle print styles
    window.addEventListener('beforeprint', function() {
        $('.ai-genius-chatbot').hide();
    });
    
    window.addEventListener('afterprint', function() {
        $('.ai-genius-chatbot').show();
    });
});

// Service Worker support for offline functionality (optional)
if ('serviceWorker' in navigator && aiGeniusChatbot.features.offline_support) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/wp-content/plugins/ai-genius/assets/js/sw.js')
            .then(function(registration) {
                console.log('AI-Genius SW registered:', registration.scope);
            })
            .catch(function(error) {
                console.log('AI-Genius SW registration failed:', error);
            });
    });
}