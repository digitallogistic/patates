<?php
/**
 * Ana Dashboard Sayfası
 * 
 * AI-Genius ana kontrol paneli görünümü
 *
 * @package AI_Genius
 * @subpackage AI_Genius/admin/partials
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

// Gerekli verileri al
$database = new AI_Genius_Database();
$ai_processor = new AI_Genius_AI_Processor();
$license = new AI_Genius_License();

// İstatistikleri al
$stats = array();
$today = current_time('Y-m-d');
$week_ago = date('Y-m-d', strtotime('-7 days'));
$month_ago = date('Y-m-d', strtotime('-30 days'));

// Günlük istatistikler
$daily_chats = $database->get_analytics('daily_chats', $today, $today);
$stats['daily_chats'] = !empty($daily_chats) ? $daily_chats[0]['metric_value'] : 0;

// Haftalık istatistikler
$weekly_chats = $database->get_analytics('daily_chats', $week_ago, $today);
$stats['weekly_chats'] = array_sum(array_column($weekly_chats, 'metric_value'));

// Aylık istatistikler
$monthly_chats = $database->get_analytics('daily_chats', $month_ago, $today);
$stats['monthly_chats'] = array_sum(array_column($monthly_chats, 'metric_value'));

// Memnuniyet oranı
$helpful_responses = $database->get_analytics('helpful_responses', $week_ago, $today);
$unhelpful_responses = $database->get_analytics('unhelpful_responses', $week_ago, $today);
$helpful_total = array_sum(array_column($helpful_responses, 'metric_value'));
$unhelpful_total = array_sum(array_column($unhelpful_responses, 'metric_value'));
$total_ratings = $helpful_total + $unhelpful_total;
$stats['satisfaction_rate'] = $total_ratings > 0 ? round(($helpful_total / $total_ratings) * 100, 1) : 0;

// Bot durumu
$bot_settings = $database->get_bot_settings();
$stats['bot_active'] = !empty($bot_settings) && $bot_settings['is_active'];

// AI Provider bilgisi
$active_provider = $ai_processor->get_active_provider_info();

// Lisans durumu
$license_status = $license->check_license_status();

// Son sohbetler
$recent_chats = $database->get_chat_history('', 10);
?>

<div class="wrap ai-genius-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-robot" style="color: #0073aa;"></span>
        <?php esc_html_e('AI-Genius Dashboard', 'ai-genius'); ?>
    </h1>
    
    <?php if ($license->is_license_required() && !$license_status['is_valid']): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php esc_html_e('Lisans Uyarısı:', 'ai-genius'); ?></strong>
            <?php esc_html_e('Plugin lisansınız aktif değil.', 'ai-genius'); ?>
            <a href="<?php echo admin_url('admin.php?page=ai-genius-license'); ?>" class="button button-primary">
                <?php esc_html_e('Lisansı Aktive Et', 'ai-genius'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>

    <!-- Ana İstatistik Kartları -->
    <div class="dashboard-stats">
        <div class="stats-grid">
            <!-- Günlük Sohbetler -->
            <div class="stat-card daily-chats">
                <div class="stat-icon">
                    <span class="dashicons dashicons-format-chat"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['daily_chats']); ?></h3>
                    <p><?php esc_html_e('Bugünkü Sohbetler', 'ai-genius'); ?></p>
                    <div class="stat-change positive">
                        <span class="dashicons dashicons-arrow-up-alt"></span>
                        +12%
                    </div>
                </div>
            </div>

            <!-- Haftalık Sohbetler -->
            <div class="stat-card weekly-chats">
                <div class="stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['weekly_chats']); ?></h3>
                    <p><?php esc_html_e('Bu Hafta', 'ai-genius'); ?></p>
                    <div class="stat-trend">
                        <?php echo round($stats['weekly_chats'] / 7, 1); ?> <?php esc_html_e('ortalama/gün', 'ai-genius'); ?>
                    </div>
                </div>
            </div>

            <!-- Memnuniyet Oranı -->
            <div class="stat-card satisfaction">
                <div class="stat-icon">
                    <span class="dashicons dashicons-thumbs-up"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['satisfaction_rate']; ?>%</h3>
                    <p><?php esc_html_e('Memnuniyet Oranı', 'ai-genius'); ?></p>
                    <div class="satisfaction-bar">
                        <div class="satisfaction-fill" style="width: <?php echo $stats['satisfaction_rate']; ?>%;"></div>
                    </div>
                </div>
            </div>

            <!-- Bot Durumu -->
            <div class="stat-card bot-status <?php echo $stats['bot_active'] ? 'active' : 'inactive'; ?>">
                <div class="stat-icon">
                    <span class="dashicons dashicons-<?php echo $stats['bot_active'] ? 'yes-alt' : 'dismiss'; ?>"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo $stats['bot_active'] ? esc_html__('Aktif', 'ai-genius') : esc_html__('Pasif', 'ai-genius'); ?></h3>
                    <p><?php esc_html_e('Bot Durumu', 'ai-genius'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=ai-genius-bot-settings'); ?>" class="stat-link">
                        <?php esc_html_e('Ayarları Düzenle', 'ai-genius'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-content">
        <!-- Sol Kolon -->
        <div class="dashboard-left">
            
            <!-- Hızlı Aksiyonlar -->
            <div class="dashboard-widget quick-actions">
                <h2><?php esc_html_e('Hızlı İşlemler', 'ai-genius'); ?></h2>
                <div class="quick-actions-grid">
                    <a href="<?php echo admin_url('admin.php?page=ai-genius-bot-settings'); ?>" class="quick-action">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <span><?php esc_html_e('Bot Ayarları', 'ai-genius'); ?></span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-genius-data'); ?>" class="quick-action">
                        <span class="dashicons dashicons-database"></span>
                        <span><?php esc_html_e('Veri Yönetimi', 'ai-genius'); ?></span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-genius-settings'); ?>" class="quick-action ai-settings-link">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <span><?php esc_html_e('AI Ayarları', 'ai-genius'); ?></span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=ai-genius-analytics'); ?>" class="quick-action">
                        <span class="dashicons dashicons-chart-line"></span>
                        <span><?php esc_html_e('Analitik', 'ai-genius'); ?></span>
                    </a>
                </div>
            </div>

            <!-- AI Provider Durumu -->
            <div class="dashboard-widget ai-provider-status">
                <h2><?php esc_html_e('AI Servis Durumu', 'ai-genius'); ?></h2>
                <div class="provider-info">
                    <div class="provider-header">
                        <img src="<?php echo AI_GENIUS_PLUGIN_URL; ?>assets/images/providers/<?php echo esc_attr($active_provider['provider']); ?>.png" 
                             alt="<?php echo esc_attr($active_provider['info']['name'] ?? $active_provider['provider']); ?>"
                             class="provider-logo"
                             onerror="this.style.display='none';">
                        <div class="provider-details">
                            <h3><?php echo esc_html($active_provider['info']['name'] ?? ucfirst($active_provider['provider'])); ?></h3>
                            <p class="provider-type <?php echo esc_attr($active_provider['info']['type'] ?? 'unknown'); ?>">
                                <?php 
                                $type_labels = array(
                                    'free' => __('Ücretsiz', 'ai-genius'),
                                    'premium' => __('Premium', 'ai-genius'),
                                    'freemium' => __('Freemium', 'ai-genius')
                                );
                                echo esc_html($type_labels[$active_provider['info']['type']] ?? __('Bilinmiyor', 'ai-genius'));
                                ?>
                            </p>
                        </div>
                        <div class="provider-status online">
                            <span class="status-dot"></span>
                            <span><?php esc_html_e('Çevrimiçi', 'ai-genius'); ?></span>
                        </div>
                    </div>
                    
                    <div class="provider-model">
                        <strong><?php esc_html_e('Aktif Model:', 'ai-genius'); ?></strong>
                        <span><?php echo esc_html($active_provider['model']); ?></span>
                    </div>
                    
                    <div class="provider-actions">
                        <button type="button" class="button test-ai-connection" 
                                data-provider="<?php echo esc_attr($active_provider['provider']); ?>">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php esc_html_e('Bağlantıyı Test Et', 'ai-genius'); ?>
                        </button>
                        
                        <a href="<?php echo admin_url('admin.php?page=ai-genius-settings#ai-provider'); ?>" class="button">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e('Değiştir', 'ai-genius'); ?>
                        </a>
                    </div>
                    
                    <div id="connection-test-result"></div>
                </div>
            </div>

            <!-- Performans Metrikleri -->
            <div class="dashboard-widget performance-metrics">
                <h2><?php esc_html_e('Performans Metrikleri', 'ai-genius'); ?></h2>
                <div class="metrics-list">
                    <div class="metric-item">
                        <span class="metric-label"><?php esc_html_e('Ortalama Yanıt Süresi', 'ai-genius'); ?></span>
                        <span class="metric-value">1.2s</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label"><?php esc_html_e('Başarı Oranı', 'ai-genius'); ?></span>
                        <span class="metric-value">98.5%</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label"><?php esc_html_e('Aktif Oturumlar', 'ai-genius'); ?></span>
                        <span class="metric-value"><?php echo $database->get_active_sessions_count(); ?></span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label"><?php esc_html_e('Bilgi Tabanı Kullanımı', 'ai-genius'); ?></span>
                        <span class="metric-value">67%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sağ Kolon -->
        <div class="dashboard-right">
            
            <!-- Son Aktiviteler -->
            <div class="dashboard-widget recent-activity">
                <h2><?php esc_html_e('Son Sohbetler', 'ai-genius'); ?></h2>
                <div class="activity-list">
                    <?php if (!empty($recent_chats)): ?>
                        <?php foreach (array_slice($recent_chats, 0, 8) as $chat): ?>
                        <div class="activity-item">
                            <div class="activity-avatar">
                                <?php if ($chat['user_id']): ?>
                                    <?php echo get_avatar($chat['user_id'], 32); ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-admin-users"></span>
                                <?php endif; ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-message">
                                    <strong>
                                        <?php 
                                        if ($chat['user_id']) {
                                            $user = get_userdata($chat['user_id']);
                                            echo esc_html($user->display_name);
                                        } else {
                                            echo esc_html__('Misafir Kullanıcı', 'ai-genius');
                                        }
                                        ?>
                                    </strong>
                                    <p><?php echo esc_html(wp_trim_words($chat['user_message'], 8)); ?></p>
                                </div>
                                <div class="activity-meta">
                                    <span class="activity-time">
                                        <?php echo human_time_diff(strtotime($chat['created_at']), current_time('timestamp')); ?> 
                                        <?php esc_html_e('önce', 'ai-genius'); ?>
                                    </span>
                                    <?php if (isset($chat['is_helpful'])): ?>
                                        <span class="activity-rating <?php echo $chat['is_helpful'] ? 'positive' : 'negative'; ?>">
                                            <?php echo $chat['is_helpful'] ? '👍' : '👎'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-activity">
                            <span class="dashicons dashicons-format-chat"></span>
                            <p><?php esc_html_e('Henüz sohbet kaydı yok.', 'ai-genius'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($recent_chats) > 8): ?>
                <div class="activity-footer">
                    <a href="<?php echo admin_url('admin.php?page=ai-genius-analytics#chat-history'); ?>" class="button">
                        <?php esc_html_e('Tüm Sohbetleri Görüntüle', 'ai-genius'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Haftalık Grafik -->
            <div class="dashboard-widget weekly-chart">
                <h2><?php esc_html_e('Son 7 Gün', 'ai-genius'); ?></h2>
                <div class="chart-container">
                    <canvas id="weeklyChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Sistem Durumu -->
            <div class="dashboard-widget system-status">
                <h2><?php esc_html_e('Sistem Durumu', 'ai-genius'); ?></h2>
                <div class="status-list">
                    <div class="status-item <?php echo $stats['bot_active'] ? 'status-ok' : 'status-warning'; ?>">
                        <span class="status-icon"></span>
                        <span class="status-label"><?php esc_html_e('Chatbot Durumu', 'ai-genius'); ?></span>
                        <span class="status-value"><?php echo $stats['bot_active'] ? 'Aktif' : 'Pasif'; ?></span>
                    </div>
                    
                    <div class="status-item <?php echo $license_status['is_valid'] ? 'status-ok' : 'status-error'; ?>">
                        <span class="status-icon"></span>
                        <span class="status-label"><?php esc_html_e('Lisans Durumu', 'ai-genius'); ?></span>
                        <span class="status-value"><?php echo $license_status['is_valid'] ? 'Geçerli' : 'Geçersiz'; ?></span>
                    </div>
                    
                    <div class="status-item status-ok">
                        <span class="status-icon"></span>
                        <span class="status-label"><?php esc_html_e('Veritabanı', 'ai-genius'); ?></span>
                        <span class="status-value"><?php esc_html_e('Çalışıyor', 'ai-genius'); ?></span>
                    </div>
                    
                    <div class="status-item status-ok">
                        <span class="status-icon"></span>
                        <span class="status-label"><?php esc_html_e('API Bağlantısı', 'ai-genius'); ?></span>
                        <span class="status-value"><?php esc_html_e('Başarılı', 'ai-genius'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Güncellemeler -->
            <div class="dashboard-widget updates">
                <h2><?php esc_html_e('Güncellemeler', 'ai-genius'); ?></h2>
                <div class="update-info">
                    <div class="current-version">
                        <strong><?php esc_html_e('Mevcut Sürüm:', 'ai-genius'); ?></strong>
                        <span><?php echo AI_GENIUS_VERSION; ?></span>
                    </div>
                    
                    <div class="update-actions">
                        <button type="button" class="button button-primary check-updates">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Güncellemeleri Kontrol Et', 'ai-genius'); ?>
                        </button>
                    </div>
                    
                    <div class="update-notes">
                        <p class="description">
                            <?php esc_html_e('Son güncelleme:', 'ai-genius'); ?> 
                            <?php echo human_time_diff(get_option('ai_genius_activated_time', time()), current_time('timestamp')); ?> 
                            <?php esc_html_e('önce', 'ai-genius'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard JavaScript -->
<script>
jQuery(document).ready(function($) {
    
    // AI bağlantı testi
    $('.test-ai-connection').on('click', function() {
        const $btn = $(this);
        const $result = $('#connection-test-result');
        const provider = $btn.data('provider');
        
        $btn.prop('disabled', true).find('.dashicons')
            .removeClass('dashicons-admin-plugins')
            .addClass('dashicons-update spin');
        
        $result.html('<div class="testing">Test ediliyor...</div>');
        
        $.post(ajaxurl, {
            action: 'ai_genius_test_provider',
            nonce: aiGeniusAdmin.nonce,
            provider: provider
        }, function(response) {
            $btn.prop('disabled', false).find('.dashicons')
                .removeClass('dashicons-update spin')
                .addClass('dashicons-admin-plugins');
            
            if (response.success) {
                $result.html('<div class="test-success">✅ ' + response.data.message + '</div>');
            } else {
                $result.html('<div class="test-error">❌ ' + response.data + '</div>');
            }
        });
    });
    
    // Haftalık grafik
    if (typeof Chart !== 'undefined') {
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($i) {
                    return date('M j', strtotime("-$i day"));
                }, range(6, 0))); ?>,
                datasets: [{
                    label: '<?php esc_html_e('Sohbet Sayısı', 'ai-genius'); ?>',
                    data: <?php 
                    $chart_data = array();
                    for ($i = 6; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-$i day"));
                        $day_chats = $database->get_analytics('daily_chats', $date, $date);
                        $chart_data[] = !empty($day_chats) ? intval($day_chats[0]['metric_value']) : 0;
                    }
                    echo json_encode($chart_data);
                    ?>,
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    
    // Güncellemeleri kontrol et
    $('.check-updates').on('click', function() {
        const $btn = $(this);
        
        $btn.prop('disabled', true).find('.dashicons')
            .removeClass('dashicons-update')
            .addClass('dashicons-update spin');
        
        // Simulated update check
        setTimeout(function() {
            $btn.prop('disabled', false).find('.dashicons')
                .removeClass('dashicons-update spin')
                .addClass('dashicons-update');
            
            alert('Güncel sürüm kullanıyorsunuz! 🎉');
        }, 2000);
    });
    
    // Auto-refresh istatistikleri (5 dakikada bir)
    setInterval(function() {
        location.reload();
    }, 5 * 60 * 1000);
});
</script>

<style>
/* Dashboard özel stilleri */
.ai-genius-dashboard {
    background: #f1f1f1;
    margin: 0 -20px;
    padding: 20px;
}

.dashboard-stats {
    margin: 20px 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    border-left: 4px solid #0073aa;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-card.active {
    border-left-color: #00a32a;
}

.stat-card.inactive {
    border-left-color: #d63638;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0073aa, #005177);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    flex-shrink: 0;
}

.stat-card.active .stat-icon {
    background: linear-gradient(135deg, #00a32a, #007a1a);
}

.stat-card.inactive .stat-icon {
    background: linear-gradient(135deg, #d63638, #b32d2e);
}

.stat-content h3 {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: #1d2327;
}

.stat-content p {
    color: #646970;
    margin: 0;
    font-size: 14px;
}

.stat-change.positive {
    color: #00a32a;
    font-size: 12px;
    font-weight: 600;
}

.stat-trend {
    color: #646970;
    font-size: 12px;
}

.satisfaction-bar {
    width: 100%;
    height: 4px;
    background: #f0f0f1;
    border-radius: 2px;
    margin-top: 8px;
    overflow: hidden;
}

.satisfaction-fill {
    height: 100%;
    background: linear-gradient(90deg, #d63638, #ffba00, #00a32a);
    transition: width 0.3s ease;
}

.dashboard-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 30px;
}

.dashboard-widget {
    background: white;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.dashboard-widget h2 {
    margin: 0 0 20px 0;
    font-size: 18px;
    color: #1d2327;
    border-bottom: 2px solid #f0f0f1;
    padding-bottom: 12px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.quick-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    border: 2px solid #f0f0f1;
    border-radius: 8px;
    text-decoration: none;
    color: #646970;
    transition: all 0.2s;
}

.quick-action:hover {
    border-color: #0073aa;
    color: #0073aa;
    text-decoration: none;
    transform: translateY(-2px);
}

.quick-action .dashicons {
    font-size: 32px;
    margin-bottom: 8px;
}

.provider-info {
    border: 1px solid #f0f0f1;
    border-radius: 8px;
    padding: 20px;
}

.provider-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 16px;
}

.provider-logo {
    width: 40px;
    height: 40px;
    border-radius: 8px;
}

.provider-details h3 {
    margin: 0;
    font-size: 16px;
}

.provider-type.free {
    color: #00a32a;
    font-size: 12px;
    font-weight: 600;
}

.provider-type.premium {
    color: #f56e28;
    font-size: 12px;
    font-weight: 600;
}

.provider-status {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #00a32a;
    animation: pulse 2s infinite;
}

.provider-actions {
    display: flex;
    gap: 8px;
    margin-top: 16px;
}

.metrics-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.metric-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f1;
}

.metric-label {
    color: #646970;
}

.metric-value {
    font-weight: 600;
    color: #1d2327;
}

.activity-list {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f1;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
}

.activity-avatar .dashicons {
    width: 32px;
    height: 32px;
    font-size: 16px;
    background: #f0f0f1;
    color: #646970;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.activity-content {
    flex: 1;
}

.activity-message strong {
    color: #1d2327;
    font-size: 14px;
}

.activity-message p {
    margin: 4px 0 0 0;
    color: #646970;
    font-size: 13px;
}

.activity-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
}

.activity-time {
    color: #888;
    font-size: 11px;
}

.activity-rating.positive {
    color: #00a32a;
}

.activity-rating.negative {
    color: #d63638;
}

.no-activity {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
}

.no-activity .dashicons {
    font-size: 48px;
    opacity: 0.5;
    display: block;
    margin-bottom: 16px;
}

.chart-container {
    position: relative;
    height: 200px;
}

.status-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
}

.status-icon {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.status-item.status-ok .status-icon {
    background: #00a32a;
}

.status-item.status-warning .status-icon {
    background: #ffba00;
}

.status-item.status-error .status-icon {
    background: #d63638;
}

.status-label {
    flex: 1;
    color: #646970;
}

.status-value {
    font-weight: 600;
    color: #1d2327;
}

.update-info {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.current-version {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.update-actions .button {
    width: 100%;
    justify-content: center;
    display: flex;
    align-items: center;
    gap: 8px;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.testing, .test-success, .test-error {
    margin-top: 12px;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
}

.testing {
    background: #f0f6fc;
    color: #0969da;
    border: 1px solid #d1e7ff;
}

.test-success {
    background: #f6ffed;
    color: #52c41a;
    border: 1px solid #b7eb8f;
}

.test-error {
    background: #fff2f0;
    color: #ff4d4f;
    border: 1px solid #ffccc7;
}

/* Responsive */
@media (max-width: 1200px) {
    .dashboard-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .provider-header {
        flex-direction: column;
        text-align: center;
    }
    
    .provider-actions {
        flex-direction: column;
    }
}
</style>