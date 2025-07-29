<?php
/**
 * Hook yöneticisi sınıfı
 * 
 * WordPress hook'larını kaydetmek ve çalıştırmak için kullanılır
 *
 * @package AI_Genius
 * @subpackage AI_Genius/includes
 * @version 1.0.0
 */

// WordPress dışından doğrudan erişimi engelle
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook yöneticisi sınıfı
 * 
 * Plugin'in tüm hook'larını merkezi bir yerden yönetir
 * WordPress'in add_action ve add_filter fonksiyonlarını organize eder
 */
class AI_Genius_Loader {

    /**
     * Plugin'in kaydettiği action'lar
     * 
     * @since 1.0.0
     * @var array
     */
    protected $actions;

    /**
     * Plugin'in kaydettiği filter'lar
     * 
     * @since 1.0.0
     * @var array
     */
    protected $filters;

    /**
     * Plugin'in kaydettiği shortcode'lar
     * 
     * @since 1.0.0
     * @var array
     */
    protected $shortcodes;

    /**
     * Sınıf kurucusu
     * 
     * Hook dizilerini başlat
     * 
     * @since 1.0.0
     */
    public function __construct() {
        
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
        
        ai_genius_log('Loader class initialized');
    }

    /**
     * Action hook'u ekle
     * 
     * @since 1.0.0
     * @param string $hook WordPress hook adı
     * @param object $component Hook'u çalıştıracak sınıf
     * @param string $callback Çalıştırılacak method adı
     * @param int $priority Hook önceliği (varsayılan: 10)
     * @param int $accepted_args Kabul edilecek argüman sayısı (varsayılan: 1)
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
        
        ai_genius_log("Action added: $hook -> " . get_class($component) . "::$callback");
    }

    /**
     * Filter hook'u ekle
     * 
     * @since 1.0.0
     * @param string $hook WordPress hook adı
     * @param object $component Hook'u çalıştıracak sınıf
     * @param string $callback Çalıştırılacak method adı
     * @param int $priority Hook önceliği (varsayılan: 10)
     * @param int $accepted_args Kabul edilecek argüman sayısı (varsayılan: 1)
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
        
        ai_genius_log("Filter added: $hook -> " . get_class($component) . "::$callback");
    }

    /**
     * Shortcode ekle
     * 
     * @since 1.0.0
     * @param string $tag Shortcode etiketi
     * @param object $component Shortcode'u çalıştıracak sınıf
     * @param string $callback Çalıştırılacak method adı
     */
    public function add_shortcode($tag, $component, $callback) {
        
        $this->shortcodes[$tag] = array(
            'component' => $component,
            'callback' => $callback
        );
        
        ai_genius_log("Shortcode added: $tag -> " . get_class($component) . "::$callback");
    }

    /**
     * Hook'ları hook dizisine ekle
     * 
     * @since 1.0.0
     * @param array $hooks Mevcut hook dizisi
     * @param string $hook Hook adı
     * @param object $component Sınıf
     * @param string $callback Method adı
     * @param int $priority Öncelik
     * @param int $accepted_args Argüman sayısı
     * @return array Güncellenmiş hook dizisi
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        
        $hooks[] = array(
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args
        );
        
        return $hooks;
    }

    /**
     * Tüm hook'ları WordPress'e kaydet
     * 
     * @since 1.0.0
     */
    public function run() {
        
        // Action hook'larını kaydet
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        // Filter hook'larını kaydet
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        // Shortcode'ları kaydet
        foreach ($this->shortcodes as $tag => $shortcode) {
            add_shortcode(
                $tag,
                array($shortcode['component'], $shortcode['callback'])
            );
        }
        
        ai_genius_log('All hooks registered successfully. Actions: ' . count($this->actions) . ', Filters: ' . count($this->filters) . ', Shortcodes: ' . count($this->shortcodes));
    }

    /**
     * Kayıtlı action'ları döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_actions() {
        return $this->actions;
    }

    /**
     * Kayıtlı filter'ları döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_filters() {
        return $this->filters;
    }

    /**
     * Kayıtlı shortcode'ları döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_shortcodes() {
        return $this->shortcodes;
    }

    /**
     * Belirli bir hook'u kaldır
     * 
     * @since 1.0.0
     * @param string $hook_name Hook adı
     * @param string $type Hook tipi (action, filter, shortcode)
     * @return bool Başarılı olup olmadığı
     */
    public function remove_hook($hook_name, $type = 'action') {
        
        switch ($type) {
            case 'action':
                foreach ($this->actions as $key => $action) {
                    if ($action['hook'] === $hook_name) {
                        unset($this->actions[$key]);
                        remove_action(
                            $action['hook'],
                            array($action['component'], $action['callback']),
                            $action['priority']
                        );
                        ai_genius_log("Action removed: $hook_name");
                        return true;
                    }
                }
                break;
                
            case 'filter':
                foreach ($this->filters as $key => $filter) {
                    if ($filter['hook'] === $hook_name) {
                        unset($this->filters[$key]);
                        remove_filter(
                            $filter['hook'],
                            array($filter['component'], $filter['callback']),
                            $filter['priority']
                        );
                        ai_genius_log("Filter removed: $hook_name");
                        return true;
                    }
                }
                break;
                
            case 'shortcode':
                if (isset($this->shortcodes[$hook_name])) {
                    unset($this->shortcodes[$hook_name]);
                    remove_shortcode($hook_name);
                    ai_genius_log("Shortcode removed: $hook_name");
                    return true;
                }
                break;
        }
        
        return false;
    }

    /**
     * Hook'un kayıtlı olup olmadığını kontrol et
     * 
     * @since 1.0.0
     * @param string $hook_name Hook adı
     * @param string $type Hook tipi (action, filter, shortcode)
     * @return bool Hook kayıtlı mı
     */
    public function has_hook($hook_name, $type = 'action') {
        
        switch ($type) {
            case 'action':
                foreach ($this->actions as $action) {
                    if ($action['hook'] === $hook_name) {
                        return true;
                    }
                }
                break;
                
            case 'filter':
                foreach ($this->filters as $filter) {
                    if ($filter['hook'] === $hook_name) {
                        return true;
                    }
                }
                break;
                
            case 'shortcode':
                return isset($this->shortcodes[$hook_name]);
        }
        
        return false;
    }

    /**
     * Tüm hook'ları temizle
     * 
     * @since 1.0.0
     */
    public function clear_all_hooks() {
        
        // Action'ları temizle
        foreach ($this->actions as $action) {
            remove_action(
                $action['hook'],
                array($action['component'], $action['callback']),
                $action['priority']
            );
        }
        
        // Filter'ları temizle
        foreach ($this->filters as $filter) {
            remove_filter(
                $filter['hook'],
                array($filter['component'], $filter['callback']),
                $filter['priority']
            );
        }
        
        // Shortcode'ları temizle
        foreach ($this->shortcodes as $tag => $shortcode) {
            remove_shortcode($tag);
        }
        
        // Dizileri sıfırla
        $this->actions = array();
        $this->filters = array();
        $this->shortcodes = array();
        
        ai_genius_log('All hooks cleared');
    }

    /**
     * Hook istatistiklerini döndür
     * 
     * @since 1.0.0
     * @return array
     */
    public function get_hook_stats() {
        
        return array(
            'actions' => count($this->actions),
            'filters' => count($this->filters),
            'shortcodes' => count($this->shortcodes),
            'total' => count($this->actions) + count($this->filters) + count($this->shortcodes)
        );
    }

    /**
     * Hook'ları öncelik sırasına göre sırala
     * 
     * @since 1.0.0
     */
    public function sort_hooks_by_priority() {
        
        // Action'ları sırala
        usort($this->actions, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        // Filter'ları sırala
        usort($this->filters, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        ai_genius_log('Hooks sorted by priority');
    }
}