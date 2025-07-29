                <div class="stat-label"><?php esc_html_e('Son Güncelleme', 'ai-genius'); ?></div>
            </div>
        </div>
    </div>

    <!-- Aksiyon Çubuğu -->
    <div class="data-actions-bar">
        <div class="actions-left">
            <!-- Toplu İşlemler -->
            <div class="bulk-actions">
                <select id="bulk-action-selector">
                    <option value=""><?php esc_html_e('Toplu İşlemler', 'ai-genius'); ?></option>
                    <option value="delete"><?php esc_html_e('Sil', 'ai-genius'); ?></option>
                    <option value="activate"><?php esc_html_e('Aktifleştir', 'ai-genius'); ?></option>
                    <option value="deactivate"><?php esc_html_e('Pasifleştir', 'ai-genius'); ?></option>
                    <option value="export"><?php esc_html_e('Dışa Aktar', 'ai-genius'); ?></option>
                </select>
                <button type="button" class="button apply-bulk-action">
                    <?php esc_html_e('Uygula', 'ai-genius'); ?>
                </button>
            </div>

            <!-- Veri İçe/Dışa Aktarma -->
            <div class="import-export-actions">
                <button type="button" class="button import-data">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Veri İçe Aktar', 'ai-genius'); ?>
                </button>
                <button type="button" class="button export-data">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Dışa Aktar', 'ai-genius'); ?>
                </button>
            </div>
        </div>

        <div class="actions-right">
            <!-- Arama -->
            <div class="search-box">
                <input type="search" 
                       id="knowledge-search" 
                       placeholder="<?php esc_attr_e('Bilgilerde ara...', 'ai-genius'); ?>"
                       value="<?php echo esc_attr($search); ?>">
                <button type="button" class="button search-submit">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </div>

            <!-- Kategori Filtresi -->
            <div class="category-filter">
                <select id="category-filter">
                    <option value=""><?php esc_html_e('Tüm Kategoriler', 'ai-genius'); ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo esc_attr($category['category']); ?>"
                                <?php selected($category_filter, $category['category']); ?>>
                            <?php echo esc_html(ucfirst($category['category'])); ?> 
                            (<?php echo esc_html($category['count']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Veri Tablosu -->
    <div class="data-table-container">
        <table class="wp-list-table widefat fixed striped knowledge-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="select-all-knowledge">
                    </td>
                    <th class="manage-column column-title">
                        <a href="#" class="sort-link" data-field="title">
                            <?php esc_html_e('Başlık', 'ai-genius'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th class="manage-column column-category">
                        <?php esc_html_e('Kategori', 'ai-genius'); ?>
                    </th>
                    <th class="manage-column column-keywords">
                        <?php esc_html_e('Anahtar Kelimeler', 'ai-genius'); ?>
                    </th>
                    <th class="manage-column column-usage">
                        <a href="#" class="sort-link" data-field="usage_count">
                            <?php esc_html_e('Kullanım', 'ai-genius'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th class="manage-column column-priority">
                        <a href="#" class="sort-link" data-field="priority">
                            <?php esc_html_e('Öncelik', 'ai-genius'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th class="manage-column column-date">
                        <?php esc_html_e('Tarih', 'ai-genius'); ?>
                    </th>
                    <th class="manage-column column-actions">
                        <?php esc_html_e('İşlemler', 'ai-genius'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($knowledge_items)): ?>
                    <?php foreach ($knowledge_items as $item): ?>
                    <tr class="knowledge-item" data-id="<?php echo esc_attr($item['id']); ?>">
                        <th class="check-column">
                            <input type="checkbox" 
                                   name="knowledge_ids[]" 
                                   value="<?php echo esc_attr($item['id']); ?>"
                                   class="knowledge-checkbox">
                        </th>
                        <td class="column-title">
                            <strong class="row-title">
                                <a href="#" class="edit-knowledge" data-id="<?php echo esc_attr($item['id']); ?>">
                                    <?php echo esc_html($item['title']); ?>
                                </a>
                            </strong>
                            <div class="row-excerpt">
                                <?php echo esc_html(wp_trim_words($item['content'], 15)); ?>
                            </div>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="#" class="edit-knowledge" data-id="<?php echo esc_attr($item['id']); ?>">
                                        <?php esc_html_e('Düzenle', 'ai-genius'); ?>
                                    </a> |
                                </span>
                                <span class="duplicate">
                                    <a href="#" class="duplicate-knowledge" data-id="<?php echo esc_attr($item['id']); ?>">
                                        <?php esc_html_e('Kopyala', 'ai-genius'); ?>
                                    </a> |
                                </span>
                                <span class="trash">
                                    <a href="#" class="delete-knowledge" data-id="<?php echo esc_attr($item['id']); ?>">
                                        <?php esc_html_e('Sil', 'ai-genius'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="column-category">
                            <span class="category-badge category-<?php echo esc_attr($item['category']); ?>">
                                <?php echo esc_html(ucfirst($item['category'])); ?>
                            </span>
                        </td>
                        <td class="column-keywords">
                            <?php if (!empty($item['keywords'])): ?>
                                <div class="keywords-list">
                                    <?php 
                                    $keywords = explode(',', $item['keywords']);
                                    foreach (array_slice($keywords, 0, 3) as $keyword): 
                                    ?>
                                        <span class="keyword-tag"><?php echo esc_html(trim($keyword)); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($keywords) > 3): ?>
                                        <span class="keyword-more">+<?php echo count($keywords) - 3; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="no-keywords">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="column-usage">
                            <div class="usage-info">
                                <strong><?php echo number_format($item['usage_count']); ?></strong>
                                <?php if ($item['last_used']): ?>
                                    <div class="last-used">
                                        <?php echo human_time_diff(strtotime($item['last_used']), current_time('timestamp')); ?> önce
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="column-priority">
                            <div class="priority-selector" data-id="<?php echo esc_attr($item['id']); ?>">
                                <select class="priority-dropdown">
                                    <?php for ($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                                <?php selected($item['priority'], $i); ?>>
                                            <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </td>
                        <td class="column-date">
                            <div class="date-info">
                                <div class="created-date">
                                    <?php echo date_i18n('d/m/Y', strtotime($item['created_at'])); ?>
                                </div>
                                <?php if ($item['updated_at'] !== $item['created_at']): ?>
                                    <div class="updated-date">
                                        Güncellendi: <?php echo human_time_diff(strtotime($item['updated_at']), current_time('timestamp')); ?> önce
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="column-actions">
                            <div class="action-buttons">
                                <button type="button" 
                                        class="button button-small edit-knowledge" 
                                        data-id="<?php echo esc_attr($item['id']); ?>"
                                        title="<?php esc_attr_e('Düzenle', 'ai-genius'); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" 
                                        class="button button-small test-knowledge" 
                                        data-id="<?php echo esc_attr($item['id']); ?>"
                                        title="<?php esc_attr_e('Test Et', 'ai-genius'); ?>">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                </button>
                                <button type="button" 
                                        class="button button-small delete-knowledge" 
                                        data-id="<?php echo esc_attr($item['id']); ?>"
                                        title="<?php esc_attr_e('Sil', 'ai-genius'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="no-items">
                        <td colspan="8" class="no-data">
                            <div class="no-data-message">
                                <span class="dashicons dashicons-database"></span>
                                <h3><?php esc_html_e('Henüz bilgi eklenmemiş', 'ai-genius'); ?></h3>
                                <p><?php esc_html_e('AI\'ın kullanacağı ilk bilgiyi eklemek için "Yeni Bilgi Ekle" butonuna tıklayın.', 'ai-genius'); ?></p>
                                <button type="button" class="button button-primary add-new-entry">
                                    <?php esc_html_e('İlk Bilgiyi Ekle', 'ai-genius'); ?>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Sayfalama -->
    <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php 
                printf(
                    _n('%s öğe', '%s öğe', $total_items, 'ai-genius'),
                    number_format_i18n($total_items)
                );
                ?>
            </span>
            <?php
            $pagination_args = array(
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '‹',
                'next_text' => '›',
                'total' => $total_pages,
                'current' => $current_page,
                'mid_size' => 2
            );
            echo paginate_links($pagination_args);
            ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Hızlı Entegrasyonlar -->
    <div class="integration-panel">
        <h2><?php esc_html_e('Hızlı Entegrasyonlar', 'ai-genius'); ?></h2>
        <div class="integration-cards">
            <?php if (class_exists('WooCommerce')): ?>
            <div class="integration-card woocommerce">
                <div class="integration-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div class="integration-content">
                    <h3><?php esc_html_e('WooCommerce', 'ai-genius'); ?></h3>
                    <p><?php esc_html_e('Ürün bilgilerini otomatik olarak içe aktarın.', 'ai-genius'); ?></p>
                    <button type="button" class="button button-primary import-woocommerce">
                        <?php esc_html_e('Ürünleri İçe Aktar', 'ai-genius'); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="integration-card wordpress">
                <div class="integration-icon">
                    <span class="dashicons dashicons-wordpress"></span>
                </div>
                <div class="integration-content">
                    <h3><?php esc_html_e('WordPress İçeriği', 'ai-genius'); ?></h3>
                    <p><?php esc_html_e('Mevcut yazı ve sayfalarınızı bilgi tabanına ekleyin.', 'ai-genius'); ?></p>
                    <button type="button" class="button button-primary import-posts">
                        <?php esc_html_e('İçerikleri İçe Aktar', 'ai-genius'); ?>
                    </button>
                </div>
            </div>

            <div class="integration-card file-upload">
                <div class="integration-icon">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div class="integration-content">
                    <h3><?php esc_html_e('Dosya Yükleme', 'ai-genius'); ?></h3>
                    <p><?php esc_html_e('CSV, JSON, XML dosyalarından veri içe aktarın.', 'ai-genius'); ?></p>
                    <button type="button" class="button button-primary upload-file-data">
                        <?php esc_html_e('Dosya Yükle', 'ai-genius'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yeni/Düzenle Bilgi Modal -->
<div id="knowledge-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title"><?php esc_html_e('Yeni Bilgi Ekle', 'ai-genius'); ?></h2>
            <button type="button" class="modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        
        <form id="knowledge-form" class="modal-body">
            <input type="hidden" id="knowledge-id" name="knowledge_id" value="">
            
            <div class="form-row">
                <label for="knowledge-title">
                    <?php esc_html_e('Başlık', 'ai-genius'); ?>
                    <span class="required">*</span>
                </label>
                <input type="text" 
                       id="knowledge-title" 
                       name="title" 
                       required 
                       maxlength="255"
                       class="widefat">
            </div>

            <div class="form-row">
                <label for="knowledge-content">
                    <?php esc_html_e('İçerik', 'ai-genius'); ?>
                    <span class="required">*</span>
                </label>
                <textarea id="knowledge-content" 
                          name="content" 
                          required 
                          rows="6" 
                          class="widefat"></textarea>
            </div>

            <div class="form-row">
                <label for="knowledge-keywords">
                    <?php esc_html_e('Anahtar Kelimeler', 'ai-genius'); ?>
                </label>
                <input type="text" 
                       id="knowledge-keywords" 
                       name="keywords" 
                       class="widefat"
                       placeholder="<?php esc_attr_e('Virgülle ayırarak yazın...', 'ai-genius'); ?>">
                <p class="description">
                    <?php esc_html_e('AI\'ın bu bilgiyi ne zaman kullanacağını belirler.', 'ai-genius'); ?>
                </p>
            </div>

            <div class="form-row-group">
                <div class="form-row">
                    <label for="knowledge-category">
                        <?php esc_html_e('Kategori', 'ai-genius'); ?>
                    </label>
                    <select id="knowledge-category" name="category" class="widefat">
                        <option value="genel"><?php esc_html_e('Genel', 'ai-genius'); ?></option>
                        <option value="urun"><?php esc_html_e('Ürün', 'ai-genius'); ?></option>
                        <option value="destek"><?php esc_html_e('Destek', 'ai-genius'); ?></option>
                        <option value="sss"><?php esc_html_e('SSS', 'ai-genius'); ?></option>
                        <option value="hizmet"><?php esc_html_e('Hizmet', 'ai-genius'); ?></option>
                        <option value="iletisim"><?php esc_html_e('İletişim', 'ai-genius'); ?></option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="knowledge-priority">
                        <?php esc_html_e('Öncelik', 'ai-genius'); ?>
                    </label>
                    <select id="knowledge-priority" name="priority" class="widefat">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php selected($i, 5); ?>>
                                <?php echo $i; ?> <?php echo $i === 10 ? '(En Yüksek)' : ($i === 1 ? '(En Düşük)' : ''); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
        </form>

        <div class="modal-footer">
            <button type="button" class="button button-secondary modal-close">
                <?php esc_html_e('İptal', 'ai-genius'); ?>
            </button>
            <button type="button" class="button button-primary save-knowledge">
                <span class="dashicons dashicons-yes"></span>
                <?php esc_html_e('Kaydet', 'ai-genius'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php esc_html_e('Veri İçe Aktar', 'ai-genius'); ?></h2>
            <button type="button" class="modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        
        <div class="modal-body">
            <div class="import-options">
                <div class="import-option" data-type="file">
                    <div class="option-icon">
                        <span class="dashicons dashicons-media-document"></span>
                    </div>
                    <div class="option-content">
                        <h3><?php esc_html_e('Dosyadan İçe Aktar', 'ai-genius'); ?></h3>
                        <p><?php esc_html_e('CSV, JSON, XML dosyalarından veri yükleyin.', 'ai-genius'); ?></p>
                    </div>
                </div>

                <div class="import-option" data-type="woocommerce">
                    <div class="option-icon">
                        <span class="dashicons dashicons-cart"></span>
                    </div>
                    <div class="option-content">
                        <h3><?php esc_html_e('WooCommerce', 'ai-genius'); ?></h3>
                        <p><?php esc_html_e('Ürün bilgilerini otomatik içe aktar.', 'ai-genius'); ?></p>
                    </div>
                </div>

                <div class="import-option" data-type="posts">
                    <div class="option-icon">
                        <span class="dashicons dashicons-admin-post"></span>
                    </div>
                    <div class="option-content">
                        <h3><?php esc_html_e('WordPress İçeriği', 'ai-genius'); ?></h3>
                        <p><?php esc_html_e('Yazı ve sayfalardan bilgi oluştur.', 'ai-genius'); ?></p>
                    </div>
                </div>
            </div>

            <div class="import-details" id="file-import" style="display: none;">
                <div class="file-upload-area">
                    <input type="file" id="import-file" accept=".csv,.json,.xml,.txt" style="display: none;">
                    <div class="upload-placeholder">
                        <span class="dashicons dashicons-cloud-upload"></span>
                        <p><?php esc_html_e('Dosyayı seçin veya buraya sürükleyin', 'ai-genius'); ?></p>
                        <button type="button" class="button" onclick="document.getElementById('import-file').click();">
                            <?php esc_html_e('Dosya Seç', 'ai-genius'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="button button-secondary modal-close">
                <?php esc_html_e('İptal', 'ai-genius'); ?>
            </button>
            <button type="button" class="button button-primary start-import" disabled>
                <span class="dashicons dashicons-upload"></span>
                <?php esc_html_e('İçe Aktar', 'ai-genius'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Modal işlemleri
    function openModal(modalId) {
        $('#' + modalId).fadeIn(200);
        $('body').addClass('modal-open');
    }
    
    function closeModal(modalId) {
        $('#' + modalId).fadeOut(200);
        $('body').removeClass('modal-open');
    }
    
    // Modal kapatma
    $('.modal-close, .modal-overlay').on('click', function(e) {
        if (e.target === this) {
            closeModal($(this).closest('.modal-overlay').attr('id'));
        }
    });
    
    // Yeni bilgi ekle
    $('.add-new-entry').on('click', function() {
        $('#knowledge-form')[0].reset();
        $('#knowledge-id').val('');
        $('#modal-title').text('<?php esc_js(__('Yeni Bilgi Ekle', 'ai-genius')); ?>');
        openModal('knowledge-modal');
    });
    
    // Bilgi düzenle
    $(document).on('click', '.edit-knowledge', function() {
        const id = $(this).data('id');
        
        // AJAX ile bilgi detaylarını al
        $.post(ajaxurl, {
            action: 'ai_genius_get_knowledge',
            nonce: aiGeniusAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                const data = response.data;
                $('#knowledge-id').val(data.id);
                $('#knowledge-title').val(data.title);
                $('#knowledge-content').val(data.content);
                $('#knowledge-keywords').val(data.keywords);
                $('#knowledge-category').val(data.category);
                $('#knowledge-priority').val(data.priority);
                $('#modal-title').text('<?php esc_js(__('Bilgiyi Düzenle', 'ai-genius')); ?>');
                openModal('knowledge-modal');
            }
        });
    });
    
    // Bilgi kaydet
    $('.save-knowledge').on('click', function() {
        const $btn = $(this);
        const $form = $('#knowledge-form');
        const formData = $form.serialize();
        
        $btn.prop('disabled', true).find('span:last').text('<?php esc_js(__('Kaydediliyor...', 'ai-genius')); ?>');
        
        $.post(ajaxurl, {
            action: 'ai_genius_save_knowledge',
            nonce: aiGeniusAdmin.nonce,
            form_data: formData
        }, function(response) {
            if (response.success) {
                closeModal('knowledge-modal');
                location.reload();
            } else {
                alert('Hata: ' + response.data);
            }
        }).always(function() {
            $btn.prop('disabled', false).find('span:last').text('<?php esc_js(__('Kaydet', 'ai-genius')); ?>');
        });
    });
    
    // Bilgi sil
    $(document).on('click', '.delete-knowledge', function() {
        const id = $(this).data('id');
        
        if (!confirm('<?php esc_js(__('Bu bilgiyi silmek istediğinizden emin misiniz?', 'ai-genius')); ?>')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'ai_genius_delete_knowledge',
            nonce: aiGeniusAdmin.nonce,
            id: id
        }, function(response) {
            if (response.success) {
                $(`tr[data-id="${id}"]`).fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                alert('Hata: ' + response.data);
            }
        });
    });
    
    // Öncelik değiştirme
    $('.priority-dropdown').on('change', function() {
        const id = $(this).closest('.priority-selector').data('id');
        const priority = $(this).val();
        
        $.post(ajaxurl, {
            action: 'ai_genius_update_knowledge_priority',
            nonce: aiGeniusAdmin.nonce,
            id: id,
            priority: priority
        });
    });
    
    // Arama
    $('.search-submit').on('click', function() {
        const search = $('#knowledge-search').val();
        const category = $('#category-filter').val();
        const url = new URL(location.href);
        
        if (search) {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }
        
        if (category) {
            url.searchParams.set('category', category);
        } else {
            url.searchParams.delete('category');
        }
        
        url.searchParams.delete('paged');
        location.href = url.toString();
    });
    
    // Enter tuşu ile arama
    $('#knowledge-search').on('keypress', function(e) {
        if (e.which === 13) {
            $('.search-submit').click();
        }
    });
    
    // Kategori filtresi değişikliği
    $('#category-filter').on('change', function() {
        $('.search-submit').click();
    });
    
    // Tümünü seç/kaldır
    $('#select-all-knowledge').on('change', function() {
        $('.knowledge-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Tekil checkbox değişikliği
    $('.knowledge-checkbox').on('change', function() {
        const totalCheckboxes = $('.knowledge-checkbox').length;
        const checkedCheckboxes = $('.knowledge-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#select-all-knowledge').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#select-all-knowledge').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#select-all-knowledge').prop('indeterminate', true);
        }
    });
    
    // İmport modal
    $('.import-data').on('click', function() {
        openModal('import-modal');
    });
    
    // İmport seçeneği
    $('.import-option').on('click', function() {
        const type = $(this).data('type');
        $('.import-option').removeClass('selected');
        $(this).addClass('selected');
        $('.import-details').hide();
        $('#' + type + '-import').show();
        $('.start-import').prop('disabled', false);
    });
    
    // Test bilgi
    $(<?php
/**
 * Veri Yönetimi Sayfası
 * 
 * Bilgi tabanı ve veri kaynakları yönetimi
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

// Sayfalama parametreleri
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

// Arama ve filtreleme
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

// Bilgi tabanı verilerini al
global $wpdb;
$knowledge_table = $wpdb->prefix . 'ai_genius_knowledge_base';

$where_conditions = array('is_active = 1');
$where_values = array();

if (!empty($search)) {
    $where_conditions[] = '(title LIKE %s OR content LIKE %s OR keywords LIKE %s)';
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $where_values[] = $search_term;
    $where_values[] = $search_term;
    $where_values[] = $search_term;
}

if (!empty($category_filter)) {
    $where_conditions[] = 'category = %s';
    $where_values[] = $category_filter;
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Toplam kayıt sayısı
$total_query = "SELECT COUNT(*) FROM $knowledge_table $where_clause";
if (!empty($where_values)) {
    $total_query = $wpdb->prepare($total_query, $where_values);
}
$total_items = $wpdb->get_var($total_query);

// Sayfalanmış veriler
$items_query = "SELECT * FROM $knowledge_table $where_clause ORDER BY priority DESC, usage_count DESC, created_at DESC LIMIT $per_page OFFSET $offset";
if (!empty($where_values)) {
    $items_query = $wpdb->prepare($items_query, $where_values);
}
$knowledge_items = $wpdb->get_results($items_query, ARRAY_A);

// Kategorileri al
$categories = $database->get_knowledge_categories();

// Sayfalama bilgileri
$total_pages = ceil($total_items / $per_page);

// İstatistikler
$stats = array(
    'total_entries' => $wpdb->get_var("SELECT COUNT(*) FROM $knowledge_table WHERE is_active = 1"),
    'total_categories' => count($categories),
    'most_used' => $wpdb->get_var("SELECT MAX(usage_count) FROM $knowledge_table WHERE is_active = 1"),
    'last_updated' => $wpdb->get_var("SELECT MAX(updated_at) FROM $knowledge_table WHERE is_active = 1")
);
?>

<div class="wrap ai-genius-data-management">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-database" style="color: #0073aa;"></span>
        <?php esc_html_e('Veri Yönetimi', 'ai-genius'); ?>
    </h1>
    
    <a href="#" class="page-title-action add-new-entry">
        <?php esc_html_e('Yeni Bilgi Ekle', 'ai-genius'); ?>
    </a>
    
    <p class="description">
        <?php esc_html_e('AI\'ın kullanacağı bilgi tabanını yönetin. Ürün bilgileri, SSS\'ler ve diğer içerikleri ekleyin.', 'ai-genius'); ?>
    </p>

    <!-- İstatistik Kartları -->
    <div class="data-stats">
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_entries']); ?></div>
                <div class="stat-label"><?php esc_html_e('Toplam Bilgi', 'ai-genius'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_categories']); ?></div>
                <div class="stat-label"><?php esc_html_e('Kategori', 'ai-genius'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['most_used']); ?></div>
                <div class="stat-label"><?php esc_html_e('En Çok Kullanılan', 'ai-genius'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    if ($stats['last_updated']) {
                        echo human_time_diff(strtotime($stats['last_updated']), current_time('timestamp')) . ' önce';
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="stat-label"><?php esc_html_e('Son Güncelleme', 'ai-genius'); ?></div>
            </div>
        