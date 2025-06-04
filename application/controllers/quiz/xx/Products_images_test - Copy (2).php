<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Products_images_test extends CI_Controller {
    
    function __construct() {
        parent::__construct();
        
        if ($this->session->userdata('logged_in') != TRUE) {
            redirect('quiz/login');
        }
        
        $this->load->model('quiz/products_model');
        $this->load->model('quiz/products_images_model');
        $this->load->library('tmdb_api');
    }
    
    /**
     * Main test page
     */
    function index() {
        $data['page_title'] = 'ทดสอบระบบรูปภาพ TMDb';
        $data['infoprogram'] = 'ทดสอบระบบ<font color="#360">รูปภาพ TMDb</font>';
        
        // Get sample products
        $data['products'] = $this->db->select('p.*, c.categories_name')
                                    ->from('hhd_products p')
                                    ->join('hhd_products_to_categories ptc', 'p.products_id = ptc.products_id', 'left')
                                    ->join('hhd_categories c', 'ptc.categories_id = c.categories_id', 'left')
                                    ->where('p.active', 1)
                                    ->order_by('p.products_id', 'DESC')
                                    ->limit(20)
                                    ->get()
                                    ->result_array();
        
        $this->load->view('quiz/products_images_test_view', $data);
    }
    
    /**
     * Manage images for a product
     */
    function manage($products_id) {
        $data['page_title'] = 'จัดการรูปภาพสินค้า';
        $data['infoprogram'] = 'จัดการ<font color="#360">รูปภาพสินค้า</font>';
        
        // Get product info
        $data['product'] = $this->products_model->getone($products_id);
        if (!$data['product']) {
            show_404();
        }
        
        // Get existing images
        $data['images'] = $this->products_images_model->get_product_images($products_id);
        
        // Get TMDb mapping
        $data['tmdb_mapping'] = $this->products_images_model->get_tmdb_mapping($products_id);
        
        // Max images setting
        $data['max_images'] = $this->products_images_model->get_setting('max_images_per_product');
        
        $this->load->view('quiz/products_images_manage_view', $data);
    }
    
    /**
     * Search TMDb (AJAX)
     */
    function search_tmdb() {
        $query = $this->input->post('query');
        $type = $this->input->post('type', true);
        $products_id = $this->input->post('products_id');
        
        if (!$query) {
            echo json_encode(array('error' => 'No query provided'));
            return;
        }
        
        // Smart search
        $results = $this->tmdb_api->smart_search($query, $type);
        
        // Add full poster URLs
        if (isset($results['results'])) {
            foreach ($results['results'] as &$item) {
                if (isset($item['poster_path']) && $item['poster_path']) {
                    $item['poster_url'] = $this->tmdb_api->get_image_url($item['poster_path'], 'w185');
                }
                if (isset($item['release_date'])) {
                    $item['year'] = substr($item['release_date'], 0, 4);
                } elseif (isset($item['first_air_date'])) {
                    $item['year'] = substr($item['first_air_date'], 0, 4);
                }
            }
        }
        
        echo json_encode($results);
    }
    
    /**
     * Get TMDb images (AJAX)
     */
    function get_tmdb_images() {
        $tmdb_id = $this->input->post('tmdb_id');
        $type = $this->input->post('type');
        $season = $this->input->post('season');
        
        if (!$tmdb_id || !$type) {
            echo json_encode(array('error' => 'Invalid parameters'));
            return;
        }
        
        // Get images based on type
        if ($type == 'tv' && $season) {
            $images = $this->tmdb_api->get_tv_season_images($tmdb_id, $season);
        } elseif ($type == 'tv') {
            $images = $this->tmdb_api->get_tv_images($tmdb_id);
        } else {
            $images = $this->tmdb_api->get_movie_images($tmdb_id);
        }
        
        // Process and add full URLs
        $processed = array('posters' => array());
        
        if ($images && isset($images['posters'])) {
            foreach ($images['posters'] as $poster) {
                $poster['preview_url'] = $this->tmdb_api->get_image_url($poster['file_path'], 'original');
                $poster['full_url'] = $this->tmdb_api->get_image_url($poster['file_path'], 'original');
                $processed['posters'][] = $poster;
            }
        }
        
        echo json_encode($processed);
    }
    
    /**
     * Add image from URL (AJAX)
     */
    function add_image() {
        // Suppress PHP warnings for AJAX response
        $old_error_reporting = error_reporting(E_ERROR | E_PARSE);
        
        $products_id = $this->input->post('products_id');
        $image_url = $this->input->post('image_url');
        
        // Log incoming request
        log_message('debug', 'Add image request - Product: ' . $products_id . ', URL: ' . $image_url);
        
        if (!$products_id || !$image_url) {
            echo json_encode(array('error' => 'Invalid parameters - products_id: ' . $products_id . ', url: ' . $image_url));
            error_reporting($old_error_reporting);
            return;
        }
        
        // Get product to determine extra
        $product = $this->products_model->getone($products_id);
        if (!$product) {
            echo json_encode(array('error' => 'Product not found'));
            error_reporting($old_error_reporting);
            return;
        }
        
        // Check max images
        $current_count = $this->products_images_model->count_product_images($products_id);
        $max_images = $this->products_images_model->get_setting('max_images_per_product');
        
        if ($current_count >= $max_images) {
            echo json_encode(array('error' => 'Maximum images reached (' . $max_images . ')'));
            error_reporting($old_error_reporting);
            return;
        }
        
        try {
            // Process image
            $result = $this->products_images_model->process_image_from_url(
                $products_id, 
                $image_url, 
                $product['extra']
            );
            
            // Log result
            log_message('debug', 'Process image result: ' . json_encode($result));
            
            echo json_encode($result);
        } catch (Exception $e) {
            log_message('error', 'Add image exception: ' . $e->getMessage());
            echo json_encode(array('error' => 'Server error: ' . $e->getMessage()));
        }
        
        // Restore error reporting
        error_reporting($old_error_reporting);
    }
	
    /**
     * Delete image (AJAX)
     */
    function delete_image() {
        $image_id = $this->input->post('image_id');
        
        if (!$image_id) {
            echo json_encode(array('error' => 'Invalid image ID'));
            return;
        }
        
        $result = $this->products_images_model->delete_image($image_id);
        
        echo json_encode(array('success' => $result));
    }
    
    /**
     * Set primary image (AJAX)
     */
    function set_primary() {
        $products_id = $this->input->post('products_id');
        $image_id = $this->input->post('image_id');
        
        if (!$products_id || !$image_id) {
            echo json_encode(array('error' => 'Invalid parameters'));
            return;
        }
        
        $this->products_images_model->set_primary_image($products_id, $image_id);
        
        echo json_encode(array('success' => true));
    }
    
    /**
     * Update image order (AJAX)
     */
    function update_order() {
        $order = $this->input->post('order');
        
        if (!is_array($order)) {
            echo json_encode(array('error' => 'Invalid order data'));
            return;
        }
        
        foreach ($order as $position => $image_id) {
            $this->products_images_model->update_image_order($image_id, $position);
        }
        
        echo json_encode(array('success' => true));
    }
    
    /**
     * Save TMDb mapping (AJAX)
     */
    function save_tmdb_mapping() {
        $data = array(
            'products_id' => $this->input->post('products_id'),
            'tmdb_id' => $this->input->post('tmdb_id'),
            'tmdb_type' => $this->input->post('tmdb_type'),
            'tmdb_title' => $this->input->post('tmdb_title'),
            'tmdb_original_title' => $this->input->post('tmdb_original_title'),
            'tmdb_release_date' => $this->input->post('tmdb_release_date'),
            'tmdb_poster_path' => $this->input->post('tmdb_poster_path'),
            'season_number' => $this->input->post('season_number'),
            'mapping_status' => 'confirmed'
        );
        
        $this->products_images_model->save_tmdb_mapping($data);
        
        echo json_encode(array('success' => true));
    }
    
    /**
     * Set image as main product image (AJAX)
     * Copy image to old system and update products table
     */
    function set_as_main_product_image() {
        $products_id = $this->input->post('products_id');
        $image_id = $this->input->post('image_id');
        
        if (!$products_id || !$image_id) {
            echo json_encode(array('error' => 'Invalid parameters'));
            return;
        }
        
        // Get image info
        $image = $this->db->where('image_id', $image_id)
                          ->where('products_id', $products_id)
                          ->get('hhd_products_images')
                          ->row_array();
        
        if (!$image) {
            echo json_encode(array('error' => 'Image not found'));
            return;
        }
        
        // Get product info
        $product = $this->products_model->getone($products_id);
        if (!$product) {
            echo json_encode(array('error' => 'Product not found'));
            return;
        }
        
        try {
            // Source image path
            $source_path = './uploads/products/' . $image['image_path'];
            
            if (!file_exists($source_path)) {
                echo json_encode(array('error' => 'Source image not found'));
                return;
            }
            
            // Generate new filename for old system
            $ext = pathinfo($image['image_filename'], PATHINFO_EXTENSION);
            $new_filename = 'p' . $products_id . '_' . time() . '.' . $ext;
            
            // Determine destination folder (use existing folder_img or create date-based)
            $dest_folder = $product['folder_img'];
            if (empty($dest_folder)) {
                $dest_folder = date('Y-m-d');
            }
            
            // Create destination directory if not exists
            $dest_dir = './uploads/products/' . $dest_folder;
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0777, true);
            }
            
            // Copy image to old system location
            $dest_path = $dest_dir . '/' . $new_filename;
            if (!copy($source_path, $dest_path)) {
                echo json_encode(array('error' => 'Failed to copy image'));
                return;
            }
            
            // Delete old main image if exists
            if (!empty($product['products_image']) && !empty($product['folder_img'])) {
                $old_image_path = './uploads/products/' . $product['folder_img'] . '/' . $product['products_image'];
                if (file_exists($old_image_path) && $old_image_path != $dest_path) {
                    @unlink($old_image_path);
                }
            }
            
            // Update products table
            $update_data = array(
                'products_image' => $new_filename,
                'folder_img' => $dest_folder,
                'change_date' => date('Y-m-d H:i:s'),
                'change_by' => $this->session->userdata('username')
            );
            
            $this->db->where('products_id', $products_id)
                     ->update('hhd_products', $update_data);
            
            // Log the change
            log_message('info', 'Set main product image - Product: ' . $products_id . ', New image: ' . $new_filename);
            
            echo json_encode(array(
                'success' => true,
                'new_image' => $new_filename,
                'folder' => $dest_folder
            ));
            
        } catch (Exception $e) {
            log_message('error', 'Set main product image error: ' . $e->getMessage());
            echo json_encode(array('error' => 'Server error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Settings page
     */
    function settings() {
        $data['page_title'] = 'ตั้งค่าระบบ TMDb';
        $data['infoprogram'] = 'ตั้งค่า<font color="#360">ระบบ TMDb</font>';
        
        if ($this->input->post('save_settings')) {
            $settings = array(
                'tmdb_api_key' => $this->input->post('tmdb_api_key'),
                'max_images_per_product' => $this->input->post('max_images_per_product'),
                'batch_size' => $this->input->post('batch_size'),
                'cache_duration_hours' => $this->input->post('cache_duration_hours'),
                'image_quality_dvd' => $this->input->post('image_quality_dvd'),
                'image_quality_bluray' => $this->input->post('image_quality_bluray'),
                'auto_search_enabled' => $this->input->post('auto_search_enabled') ? '1' : '0'
            );
            
            foreach ($settings as $key => $value) {
                $this->products_images_model->update_setting($key, $value);
            }
            
            $data['message'] = 'Settings saved successfully';
        }
        
        // Get all settings
        $data['settings'] = array();
        $query = $this->db->get('hhd_tmdb_settings');
        foreach ($query->result() as $row) {
            $data['settings'][$row->setting_key] = $row->setting_value;
        }
        
        $this->load->view('quiz/products_images_settings_view', $data);
    }
}

/* End of file Products_images_test.php */
/* Location: ./application/controllers/quiz/Products_images_test.php */
