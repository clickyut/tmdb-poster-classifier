<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * TMDb API Library for CodeIgniter
 * 
 * @author Your Name
 * @version 1.0
 */
class Tmdb_api {
    
    private $CI;
    private $api_key;
    private $api_url;
    private $image_url;
    private $cache_duration;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->CI->load->helper('file');
        
        // Load settings from database
        $this->load_settings();
    }
    
    /**
     * Load settings from database
     */
    private function load_settings() {
        $query = $this->CI->db->get('hhd_tmdb_settings');
        $settings = array();
        
        foreach ($query->result() as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        
        $this->api_key = isset($settings['tmdb_api_key']) ? $settings['tmdb_api_key'] : '';
        $this->api_url = isset($settings['tmdb_api_url']) ? $settings['tmdb_api_url'] : 'https://api.themoviedb.org/3';
        $this->image_url = isset($settings['tmdb_image_url']) ? $settings['tmdb_image_url'] : 'https://image.tmdb.org/t/p/';
        $this->cache_duration = isset($settings['cache_duration_hours']) ? (int)$settings['cache_duration_hours'] : 168;
    }
    
    /**
     * Search for movies
     */
    public function search_movie($query, $language = 'th-TH', $page = 1) {
        $endpoint = '/search/movie';
        $params = array(
            'query' => $query,
            'language' => $language,
            'page' => $page,
            'include_adult' => 'false'
        );
        
        return $this->make_request($endpoint, $params);
    }
    
    /**
     * Search for TV shows
     */
    public function search_tv($query, $language = 'th-TH', $page = 1) {
        $endpoint = '/search/tv';
        $params = array(
            'query' => $query,
            'language' => $language,
            'page' => $page
        );
        
        return $this->make_request($endpoint, $params);
    }
    
    /**
     * Multi search (movies and TV)
     */
    public function search_multi($query, $language = 'th-TH', $page = 1) {
        $endpoint = '/search/multi';
        $params = array(
            'query' => $query,
            'language' => $language,
            'page' => $page
        );
        
        return $this->make_request($endpoint, $params);
    }
    
    /**
     * Get movie details
     */
    public function get_movie($movie_id, $language = 'th-TH') {
        $endpoint = '/movie/' . $movie_id;
        $params = array(
            'language' => $language,
            'append_to_response' => 'images,credits'
        );
        
        return $this->make_request($endpoint, $params);
    }
    
    /**
     * Get TV show details
     */
    public function get_tv($tv_id, $language = 'th-TH') {
        $endpoint = '/tv/' . $tv_id;
        $params = array(
            'language' => $language,
            'append_to_response' => 'images,credits'
        );
        
        return $this->make_request($endpoint, $params);
    }
    
    /**
     * Get movie images - แก้ไขให้ดึงทุกภาษาจริงๆ
     */
    public function get_movie_images($movie_id) {
        $endpoint = '/movie/' . $movie_id . '/images';
        
        // ไม่ระบุ include_image_language เลย จะได้ภาพจากทุกภาษา
        $params = array();
        
        $result = $this->make_request($endpoint, $params);
        
        // จัดกลุ่มภาพตามภาษา
        if ($result && isset($result['posters'])) {
            $grouped_result = $this->group_images_by_language_full($result['posters']);
            
            // เก็บข้อมูลภาษาและจำนวนภาพ
            $result['languages_summary'] = $grouped_result['summary'];
            $result['posters_grouped'] = $grouped_result['grouped'];
            
            // ยังคงเก็บ posters แบบเดิมไว้ (รวมทุกภาพ) แต่เรียงตามภาษา
            $result['posters'] = $grouped_result['all_sorted'];
            
            // Log summary
            log_message('debug', 'TMDb movie ' . $movie_id . ' images summary: ' . json_encode($grouped_result['summary']));
        }
        
        return $result;
    }
    
    /**
     * Get TV show images - แก้ไขให้ดึงทุกภาษาจริงๆ
     */
    public function get_tv_images($tv_id) {
        $endpoint = '/tv/' . $tv_id . '/images';
        
        // ไม่ระบุ include_image_language เลย จะได้ภาพจากทุกภาษา
        $params = array();
        
        $result = $this->make_request($endpoint, $params);
        
        // จัดกลุ่มภาพตามภาษา
        if ($result && isset($result['posters'])) {
            $grouped_result = $this->group_images_by_language_full($result['posters']);
            
            // เก็บข้อมูลภาษาและจำนวนภาพ
            $result['languages_summary'] = $grouped_result['summary'];
            $result['posters_grouped'] = $grouped_result['grouped'];
            
            // ยังคงเก็บ posters แบบเดิมไว้ (รวมทุกภาพ) แต่เรียงตามภาษา
            $result['posters'] = $grouped_result['all_sorted'];
            
            // Log summary
            log_message('debug', 'TMDb TV ' . $tv_id . ' images summary: ' . json_encode($grouped_result['summary']));
        }
        
        return $result;
    }
    
    /**
     * Get TV season images - แก้ไขให้ดึงทุกภาษาจริงๆ
     */
    public function get_tv_season_images($tv_id, $season_number) {
        $endpoint = '/tv/' . $tv_id . '/season/' . $season_number . '/images';
        
        // ไม่ระบุ include_image_language เลย จะได้ภาพจากทุกภาษา
        $params = array();
        
        $result = $this->make_request($endpoint, $params);
        
        // จัดกลุ่มภาพตามภาษา
        if ($result && isset($result['posters'])) {
            $grouped_result = $this->group_images_by_language_full($result['posters']);
            
            // เก็บข้อมูลภาษาและจำนวนภาพ
            $result['languages_summary'] = $grouped_result['summary'];
            $result['posters_grouped'] = $grouped_result['grouped'];
            
            // ยังคงเก็บ posters แบบเดิมไว้ (รวมทุกภาพ) แต่เรียงตามภาษา
            $result['posters'] = $grouped_result['all_sorted'];
            
            // Log summary
            log_message('debug', 'TMDb TV ' . $tv_id . ' season ' . $season_number . ' images summary: ' . json_encode($grouped_result['summary']));
        }
        
        return $result;
    }
    
    /**
     * จัดกลุ่มภาพตามภาษาแบบเต็ม พร้อมสรุป
     */
    private function group_images_by_language_full($images) {
        if (empty($images)) {
            return array(
                'summary' => array(),
                'grouped' => array(),
                'all_sorted' => array()
            );
        }
        
        // จัดกลุ่มภาพตามภาษา
        $grouped = array();
        foreach ($images as $image) {
            $lang = isset($image['iso_639_1']) && $image['iso_639_1'] ? $image['iso_639_1'] : 'xx';
            
            if (!isset($grouped[$lang])) {
                $grouped[$lang] = array();
            }
            
            // เพิ่มข้อมูลภาษาลงในภาพ
            $image['language_code'] = $lang;
            $image['language_name'] = $this->get_language_name($lang);
            $grouped[$lang][] = $image;
        }
        
        // สร้างสรุปจำนวนภาพแต่ละภาษา
        $summary = array();
        foreach ($grouped as $lang => $lang_images) {
            $summary[] = array(
                'language_code' => $lang,
                'language_name' => $this->get_language_name($lang),
                'count' => count($lang_images),
                'sample_image' => isset($lang_images[0]['file_path']) ? $lang_images[0]['file_path'] : null
            );
        }
        
        // เรียงสรุปตามจำนวนภาพ (มากไปน้อย)
        usort($summary, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        // เรียงกลุ่มภาพตามจำนวน
        uksort($grouped, function($a, $b) use ($grouped) {
            $count_diff = count($grouped[$b]) - count($grouped[$a]);
            if ($count_diff != 0) {
                return $count_diff;
            }
            
            // ถ้าจำนวนเท่ากัน เรียงตาม priority
            $priority = array('en' => 1, 'th' => 2, 'xx' => 3);
            $priority_a = isset($priority[$a]) ? $priority[$a] : 999;
            $priority_b = isset($priority[$b]) ? $priority[$b] : 999;
            
            return $priority_a - $priority_b;
        });
        
        // รวมภาพทั้งหมดแบบเรียงลำดับ
        $all_sorted = array();
        foreach ($grouped as $lang => $lang_images) {
            foreach ($lang_images as $image) {
                $all_sorted[] = $image;
            }
        }
        
        return array(
            'summary' => $summary,
            'grouped' => $grouped,
            'all_sorted' => $all_sorted
        );
    }
    
    /**
     * แปลงรหัสภาษาเป็นชื่อภาษา (เพิ่มภาษาให้ครบถ้วน)
     */
    private function get_language_name($lang_code) {
        $languages = array(
            // ภาษาหลัก
            'en' => 'English',
            'th' => 'ไทย',
            'ja' => '日本語',
            'ko' => '한국어',
            'zh' => '中文',
            'hi' => 'हिन्दी',
            'ta' => 'தமிழ்',
            'te' => 'తెలుగు',
            'ml' => 'മലയാളം',
            'kn' => 'ಕನ್ನಡ',
            'mr' => 'मराठी',
            'gu' => 'ગુજરાતી',
            'pa' => 'ਪੰਜਾਬੀ',
            'bn' => 'বাংলা',
            
            // ภาษายุโรป
            'fr' => 'Français',
            'de' => 'Deutsch',
            'es' => 'Español',
            'it' => 'Italiano',
            'pt' => 'Português',
            'ru' => 'Русский',
            'pl' => 'Polski',
            'nl' => 'Nederlands',
            'sv' => 'Svenska',
            'no' => 'Norsk',
            'da' => 'Dansk',
            'fi' => 'Suomi',
            'cs' => 'Čeština',
            'hu' => 'Magyar',
            'el' => 'Ελληνικά',
            'ro' => 'Română',
            'bg' => 'Български',
            'hr' => 'Hrvatski',
            'sr' => 'Српски',
            'sk' => 'Slovenčina',
            'sl' => 'Slovenščina',
            'uk' => 'Українська',
            'lt' => 'Lietuvių',
            'lv' => 'Latviešu',
            'et' => 'Eesti',
            'is' => 'Íslenska',
            'ga' => 'Gaeilge',
            'cy' => 'Cymraeg',
            'eu' => 'Euskera',
            'ca' => 'Català',
            'gl' => 'Galego',
            
            // ภาษาตะวันออกกลาง
            'ar' => 'العربية',
            'he' => 'עברית',
            'fa' => 'فارسی',
            'ur' => 'اردو',
            'tr' => 'Türkçe',
            
            // ภาษาเอเชียตะวันออกเฉียงใต้
            'id' => 'Bahasa Indonesia',
            'ms' => 'Bahasa Melayu',
            'vi' => 'Tiếng Việt',
            'tl' => 'Tagalog',
            'my' => 'မြန်မာ',
            'km' => 'ភាសាខ្មែរ',
            'lo' => 'ລາວ',
            
            // ภาษาแอฟริกา
            'af' => 'Afrikaans',
            'sw' => 'Kiswahili',
            'am' => 'አማርኛ',
            'ha' => 'Hausa',
            'yo' => 'Yorùbá',
            'ig' => 'Igbo',
            'zu' => 'isiZulu',
            'xh' => 'isiXhosa',
            
            // ภาษาอื่นๆ
            'mn' => 'Монгол',
            'ne' => 'नेपाली',
            'si' => 'සිංහල',
            'sq' => 'Shqip',
            'ka' => 'ქართული',
            'hy' => 'Հայերեն',
            'az' => 'Azərbaycan',
            'kk' => 'Қазақ',
            'ky' => 'Кыргызча',
            'uz' => 'Oʻzbek',
            'tg' => 'Тоҷикӣ',
            'tk' => 'Türkmen',
            'ps' => 'پښتو',
            'ku' => 'Kurdî',
            
            // พิเศษ
            'xx' => 'No Language',
            'null' => 'No Language',
            
            // ภาษาจีนแยกย่อย
            'cn' => '简体中文',
            'tw' => '繁體中文',
            'hk' => '香港',
            
            // ภาษาโปรตุเกสแยกย่อย
            'pt-BR' => 'Português (Brasil)',
            'pt-PT' => 'Português (Portugal)',
            
            // ภาษาสเปนแยกย่อย
            'es-ES' => 'Español (España)',
            'es-MX' => 'Español (México)',
            
            // ภาษาฝรั่งเศสแยกย่อย
            'fr-FR' => 'Français (France)',
            'fr-CA' => 'Français (Canada)'
        );
        
        return isset($languages[$lang_code]) ? $languages[$lang_code] : strtoupper($lang_code);
    }
    
    /**
     * Make API request with caching
     */
    private function make_request($endpoint, $params = array(), $retry = 0) {
        // Check if API key is set
        if (empty($this->api_key)) {
            log_message('error', 'TMDb API Key is not set');
            return array('error' => 'API Key not configured');
        }
        
        // Check cache first
        $cache_key = md5($endpoint . serialize($params));
        $cached = $this->get_cache($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Add API key
        $params['api_key'] = $this->api_key;
        
        // Build URL
        $url = $this->api_url . $endpoint . '?' . http_build_query($params);
        
        // Make request with retry logic
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Log request details
        log_message('debug', "TMDb API Request: {$url} - Response Code: {$http_code}");
        
        if ($http_code == 200) {
            $data = json_decode($response, true);
            
            // Save to cache
            $this->save_cache($cache_key, $data);
            
            return $data;
        } else if (($http_code == 520 || $http_code == 522 || $http_code == 0) && $retry < 3) {
            // Cloudflare errors or timeout - retry
            log_message('warning', "TMDb API Error {$http_code}, retrying... (attempt " . ($retry + 1) . ")");
            sleep(1); // Wait 1 second before retry
            return $this->make_request($endpoint, $params, $retry + 1);
        } else {
            log_message('error', "TMDb API Error: HTTP {$http_code} - {$curl_error}");
            return array(
                'error' => "API Error: HTTP {$http_code}",
                'results' => array()
            );
        }
    }
    
    /**
     * Get cached data
     */
    private function get_cache($cache_key) {
        $query = $this->CI->db->where('cache_key', $cache_key)
                              ->where('expire_date >', date('Y-m-d H:i:s'))
                              ->get('hhd_tmdb_cache');
        
        if ($query->num_rows() > 0) {
            $row = $query->row();
            return json_decode($row->cache_data, true);
        }
        
        return false;
    }
    
    /**
     * Save data to cache
     */
    private function save_cache($cache_key, $data) {
        // Delete old cache
        $this->CI->db->where('cache_key', $cache_key)->delete('hhd_tmdb_cache');
        
        // Save new cache
        $cache_data = array(
            'cache_key' => $cache_key,
            'cache_data' => json_encode($data),
            'expire_date' => date('Y-m-d H:i:s', strtotime('+' . $this->cache_duration . ' hours')),
            'created_date' => date('Y-m-d H:i:s')
        );
        
        // Extract TMDb ID if available
        if (isset($data['id'])) {
            $cache_data['tmdb_id'] = $data['id'];
            $cache_data['tmdb_type'] = isset($data['first_air_date']) ? 'tv' : 'movie';
        }
        
        $this->CI->db->insert('hhd_tmdb_cache', $cache_data);
    }
    
    /**
     * Extract TMDb ID from URL
     */
    public function extract_tmdb_id($url) {
        // Pattern for movie: https://www.themoviedb.org/movie/12345-title
        // Pattern for TV: https://www.themoviedb.org/tv/12345-title
        
        if (preg_match('/themoviedb\.org\/(movie|tv)\/(\d+)/', $url, $matches)) {
            return array(
                'type' => $matches[1],
                'id' => $matches[2]
            );
        }
        
        return false;
    }
    
    /**
     * Download image from TMDb
     */
    public function download_image($image_path, $size = 'original') {
        $url = $this->image_url . $size . $image_path;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $image_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            return $image_data;
        }
        
        return false;
    }
    
    /**
     * Get full image URL
     */
    public function get_image_url($path, $size = 'original') {
        return $this->image_url . $size . $path;
    }
    
    /**
     * Clean search query (remove common words, clean Thai/English mix)
     */
    public function clean_search_query($query) {
        // Remove common patterns
        $patterns = array(
            '/\s*\(.*?\)\s*/', // Remove content in parentheses
            '/\s*\[.*?\]\s*/', // Remove content in brackets
            '/\s+DVD\s*/i',
            '/\s+Blu-?ray\s*/i',
            '/\s+4K\s*/i',
            '/\s+UHD\s*/i',
            '/\s+BD\s*/i',
            '/\s+Season\s+\d+/i',
            '/\s+ซีซั่น\s*\d+/i',
            '/\s+ภาค\s*\d+/i',
            '/\s+EP\.\s*\d+.*$/i',
            '/\s+ตอน\s*\d+.*$/i',
            '/\s+Vol\.\s*\d+/i',
            '/\s+Disc\s*\d+/i',
            '/\s+มาสเตอร์\s*/i',
            '/\s+พากย์ไทย\s*/i',
            '/\s+ซับไทย\s*/i',
            '/\s+เสียงไทย\s*/i',
            '/\s+\d+in1\s*/i',
        );
        
        $query = preg_replace($patterns, ' ', $query);
        
        // Clean multiple spaces
        $query = preg_replace('/\s+/', ' ', $query);
        
        return trim($query);
    }
    
    /**
     * Try multiple search strategies
     */
    public function smart_search($query, $type = 'movie') {
        $results = array();
        
        // Strategy 1: Search with full query
        $search_func = ($type == 'tv') ? 'search_tv' : 'search_movie';
        $result1 = $this->$search_func($query);
        if ($result1 && isset($result1['results']) && count($result1['results']) > 0) {
            $results = array_merge($results, $result1['results']);
        }
        
        // Strategy 2: Clean query and search
        $clean_query = $this->clean_search_query($query);
        if ($clean_query != $query) {
            $result2 = $this->$search_func($clean_query);
            if ($result2 && isset($result2['results']) && count($result2['results']) > 0) {
                // Merge unique results
                foreach ($result2['results'] as $item) {
                    $exists = false;
                    foreach ($results as $existing) {
                        if ($existing['id'] == $item['id']) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $results[] = $item;
                    }
                }
            }
        }
        
        // Strategy 3: Try English only (if query contains Thai)
        if (preg_match('/[\x{0E00}-\x{0E7F}]/u', $query)) {
            // Extract English words
            $english_only = preg_replace('/[\x{0E00}-\x{0E7F}]+/u', ' ', $query);
            $english_only = preg_replace('/\s+/', ' ', trim($english_only));
            
            if (strlen($english_only) > 2) {
                $result3 = $this->$search_func($english_only, 'en-US');
                if ($result3 && isset($result3['results']) && count($result3['results']) > 0) {
                    foreach ($result3['results'] as $item) {
                        $exists = false;
                        foreach ($results as $existing) {
                            if ($existing['id'] == $item['id']) {
                                $exists = true;
                                break;
                            }
                        }
                        if (!$exists) {
                            $results[] = $item;
                        }
                    }
                }
            }
        }
        
        return array(
            'results' => $results,
            'total_results' => count($results)
        );
    }
}

/* End of file Tmdb_api.php */
/* Location: ./application/libraries/Tmdb_api.php */
