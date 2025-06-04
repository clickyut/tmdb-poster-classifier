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
     * Get TV show seasons (AJAX)
     */
    function get_tv_seasons() {
        $tmdb_id = $this->input->post('tmdb_id');
        
        if (!$tmdb_id) {
            echo json_encode(array('error' => 'Invalid TMDb ID'));
            return;
        }
        
        // Get TV show details with seasons
        $tv_details = $this->tmdb_api->get_tv($tmdb_id);
        
        if (!$tv_details || !isset($tv_details['seasons'])) {
            echo json_encode(array('error' => 'Failed to get TV show details'));
            return;
        }
        
        // Process seasons data
        $seasons = array();
        foreach ($tv_details['seasons'] as $season) {
            // Skip special seasons (season 0) unless it has significant episodes
            if ($season['season_number'] == 0 && $season['episode_count'] < 5) {
                continue;
            }
            
            $seasons[] = array(
                'season_number' => $season['season_number'],
                'name' => $season['name'],
                'overview' => isset($season['overview']) ? $season['overview'] : '',
                'poster_path' => $season['poster_path'],
                'episode_count' => $season['episode_count'],
                'air_date' => isset($season['air_date']) ? $season['air_date'] : null
            );
        }
        
        echo json_encode(array(
            'seasons' => $seasons,
            'tv_info' => array(
                'name' => $tv_details['name'],
                'original_name' => isset($tv_details['original_name']) ? $tv_details['original_name'] : '',
                'first_air_date' => isset($tv_details['first_air_date']) ? $tv_details['first_air_date'] : '',
                'number_of_seasons' => count($seasons)
            )
        ));
    }
    
    /**
     * Get TMDb images (AJAX)
     */
    function get_tmdb_images() {
        $tmdb_id = $this->input->post('tmdb_id');
        $type = $this->input->post('type');
        $season = $this->input->post('season');
        $products_id = $this->input->post('products_id');
        
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
        
        // Get existing images for this product to filter out duplicates
        $existing_urls = array();
        $existing_file_paths = array();
        if ($products_id) {
            $existing_images = $this->products_images_model->get_product_images($products_id);
            foreach ($existing_images as $img) {
                if (!empty($img['image_url'])) {
                    // Store full URL for exact matching
                    $existing_urls[] = $img['image_url'];
                    
                    // Extract TMDb file path from URL for comparison
                    // Example: https://image.tmdb.org/t/p/original/abc123.jpg -> /abc123.jpg
                    if (preg_match('/image\.tmdb\.org\/t\/p\/[^\/]+(\/.+)$/', $img['image_url'], $matches)) {
                        $existing_file_paths[] = $matches[1];
                    }
                }
            }
        }
        
        // Process and filter images
        $processed = array('posters' => array());
        $total_count = 0;
        $filtered_count = 0;
        $existing_count = 0;
        
        if ($images && isset($images['posters'])) {
            $total_count = count($images['posters']);
            
            foreach ($images['posters'] as $poster) {
                $full_url = $this->tmdb_api->get_image_url($poster['file_path'], 'original');
                
                // Check if this image already exists
                $is_duplicate = in_array($full_url, $existing_urls) || in_array($poster['file_path'], $existing_file_paths);
                
                if (!$is_duplicate) {
                    $poster['preview_url'] = $this->tmdb_api->get_image_url($poster['file_path'], 'original');
                    $poster['full_url'] = $full_url;
                    $processed['posters'][] = $poster;
                    $filtered_count++;
                } else {
                    $existing_count++;
                    // Log for debugging
                    log_message('debug', 'Filtered duplicate image: ' . $poster['file_path']);
                }
            }
        }
        
        // Add filter information
        $processed['info'] = array(
            'total' => $total_count,
            'filtered' => $filtered_count,
            'existing' => $existing_count
        );
        
        echo json_encode($processed);
    }
    
    /**
     * Analyze images using Python CLIP service and auto-select best ones (AJAX)
     * ฟังก์ชันใหม่สำหรับเลือกภาพอัตโนมัติด้วย AI
     */
    function analyze_images_auto() {
        $images = $this->input->post('images');
        $max_select = $this->input->post('max_select', true);
        
        if (!$images || !is_array($images)) {
            echo json_encode(array('error' => 'No images provided'));
            return;
        }
        
        if (!$max_select || $max_select < 1) {
            $max_select = 10;
        }
        
        // Prepare data for Python service
        $request_data = array(
            'images' => $images,
            'max_select' => $max_select
        );
        
        // Call Python CLIP service (must be running on localhost:5000)
        $ch = curl_init('http://localhost:5000/analyze');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 seconds timeout for processing
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || !$response) {
            echo json_encode(array(
                'error' => 'Failed to analyze images. Make sure Python CLIP service is running.',
                'fallback' => true
            ));
            return;
        }
        
        $result = json_decode($response, true);
        if (!$result || !isset($result['selected_indices'])) {
            echo json_encode(array(
                'error' => 'Invalid response from analysis service',
                'fallback' => true
            ));
            return;
        }
        
        echo json_encode($result);
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
        
        // Check if this URL/image already exists for this product
        if ($this->products_images_model->check_duplicate_url($products_id, $image_url)) {
            echo json_encode(array('error' => 'รูปนี้มีในระบบแล้ว (URL ซ้ำ)'));
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
                
                // Delete old thumbnail if exists
                $old_thumb_path = './uploads/products/' . $product['folder_img'] . '/thumb_' . $product['products_image'];
                if (file_exists($old_thumb_path)) {
                    @unlink($old_thumb_path);
                }
            }
            
            // Create thumbnail for main product image
            $this->create_main_product_thumbnail($dest_path, $dest_folder, $new_filename);
            
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
            log_message('info', 'Set main product image - Product: ' . $products_id . ', New image: ' . $new_filename . ', Thumbnail created');
            
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
     * Create thumbnail for main product image
     */
    private function create_main_product_thumbnail($source_path, $dest_folder, $filename) {
        $this->load->library('image_lib');
        
        // Create thumbnail directory if not exists
        $thumb_dir = './uploads/products/' . $dest_folder;
        if (!is_dir($thumb_dir)) {
            mkdir($thumb_dir, 0777, true);
        }
        
        $thumb_path = $thumb_dir . '/thumb_' . $filename;
        
        // สร้าง thumbnail ขนาด 90x129 สำหรับรูปหลัก
        $config = array(
            'image_library' => 'gd2',
            'source_image' => $source_path,
            'new_image' => $thumb_path,
            'maintain_ratio' => true,
            'width' => 90,
            'height' => 129,
            'quality' => '90%'
        );
        
        $this->image_lib->initialize($config);
        $result = $this->image_lib->resize();
        
        if (!$result) {
            log_message('error', 'Failed to create thumbnail: ' . $this->image_lib->display_errors());
        } else {
            log_message('info', 'Thumbnail created successfully: ' . $thumb_path);
        }
        
        $this->image_lib->clear();
        
        return $result;
    }
    
    /**
     * Upload image from file (AJAX)
     */
    function upload_image() {
        // Suppress PHP warnings for clean JSON response
        $old_error_reporting = error_reporting(E_ERROR | E_PARSE);
        
        // Check if file was uploaded
        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(array('error' => 'No file uploaded or upload error'));
            error_reporting($old_error_reporting);
            return;
        }
        
        $products_id = $this->input->post('products_id');
        
        if (!$products_id) {
            echo json_encode(array('error' => 'Invalid product ID'));
            error_reporting($old_error_reporting);
            return;
        }
        
        // Get product info
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
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        $file_type = $_FILES['image_file']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(array('error' => 'Invalid file type. Only JPG, PNG, GIF allowed.'));
            error_reporting($old_error_reporting);
            return;
        }
        
        // Check file size (max 10MB)
        if ($_FILES['image_file']['size'] > 10 * 1024 * 1024) {
            echo json_encode(array('error' => 'File too large. Maximum size is 10MB.'));
            error_reporting($old_error_reporting);
            return;
        }
        
        try {
            // Process uploaded file
            $result = $this->products_images_model->process_uploaded_image(
                $products_id,
                $_FILES['image_file'],
                $product['extra']
            );
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            log_message('error', 'Upload image exception: ' . $e->getMessage());
            echo json_encode(array('error' => 'Server error: ' . $e->getMessage()));
        }
        
        // Restore error reporting
        error_reporting($old_error_reporting);
    }
    
    /**
     * Add image from any URL (AJAX) - not just TMDb
     */
    function add_image_from_url() {
        $products_id = $this->input->post('products_id');
        $image_url = $this->input->post('image_url');
        
        if (!$products_id || !$image_url) {
            echo json_encode(array('error' => 'Invalid parameters'));
            return;
        }
        
        // Validate URL
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            echo json_encode(array('error' => 'Invalid URL'));
            return;
        }
        
        // Check if URL points to image
        $image_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $url_extension = strtolower(pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        if (!in_array($url_extension, $image_extensions)) {
            echo json_encode(array('error' => 'URL must point to an image file (jpg, png, gif, webp)'));
            return;
        }
        
        // Get product info
        $product = $this->products_model->getone($products_id);
        if (!$product) {
            echo json_encode(array('error' => 'Product not found'));
            return;
        }
        
        // Check max images
        $current_count = $this->products_images_model->count_product_images($products_id);
        $max_images = $this->products_images_model->get_setting('max_images_per_product');
        
        if ($current_count >= $max_images) {
            echo json_encode(array('error' => 'Maximum images reached (' . $max_images . ')'));
            return;
        }
        
        try {
            // Process image from URL
            $result = $this->products_images_model->process_image_from_any_url(
                $products_id,
                $image_url,
                $product['extra']
            );
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            log_message('error', 'Add image from URL exception: ' . $e->getMessage());
            echo json_encode(array('error' => 'Server error: ' . $e->getMessage()));
        }
    }
    
    /**
     * Re-crop image with position adjustment (AJAX)
     */
    function recrop_image() {
        // Suppress PHP warnings for AJAX
        $old_error_reporting = error_reporting(E_ERROR | E_PARSE);
        
        $image_id = $this->input->post('image_id');
        $position = $this->input->post('position', true); // up_10, up_20, up_30, down_10, down_20, down_30, center
        
        // Log incoming request
        log_message('debug', 'Recrop request - Image ID: ' . $image_id . ', Position: ' . $position);
        
        if (!$image_id || !$position) {
            echo json_encode(array('error' => 'Invalid parameters'));
            error_reporting($old_error_reporting);
            return;
        }
        
        // Get image info
        $image = $this->db->where('image_id', $image_id)->get('hhd_products_images')->row_array();
        if (!$image) {
            echo json_encode(array('error' => 'Image not found'));
            error_reporting($old_error_reporting);
            return;
        }
        
        // Check if has original for re-crop
        if (!$image['has_original']) {
            echo json_encode(array('error' => 'ไม่มีไฟล์ต้นฉบับสำหรับปรับตำแหน่ง'));
            error_reporting($old_error_reporting);
            return;
        }
        
        // Get product for extra type
        $product = $this->products_model->getone($image['products_id']);
        if (!$product) {
            echo json_encode(array('error' => 'Product not found'));
            error_reporting($old_error_reporting);
            return;
        }
        
        // Only allow re-crop for Blu-ray types
        if (!in_array($product['extra'], array(5, 6, 7, 8))) {
            echo json_encode(array('error' => 'Re-crop ใช้ได้เฉพาะ Blu-ray เท่านั้น'));
            error_reporting($old_error_reporting);
            return;
        }
        
        try {
            // Load image library first
            $this->load->library('image_lib');
            
            // Calculate crop position based on adjustment
            $original_path = './uploads/products/' . dirname($image['image_path']) . '/original_' . $image['image_filename'];
            $target_path = './uploads/products/' . $image['image_path'];
            
            if (!file_exists($original_path)) {
                echo json_encode(array('error' => 'Original file not found: ' . $original_path));
                error_reporting($old_error_reporting);
                return;
            }
            
            // Copy original to target for processing
            if (!copy($original_path, $target_path)) {
                echo json_encode(array('error' => 'Failed to copy original file'));
                error_reporting($old_error_reporting);
                return;
            }
            
            // First resize to 1000px width
            $config = array(
                'image_library' => 'gd2',
                'source_image' => $target_path,
                'maintain_ratio' => true,
                'width' => 1000,
                'quality' => '95%'
            );
            
            $this->image_lib->initialize($config);
            if (!$this->image_lib->resize()) {
                log_message('error', 'Resize error: ' . $this->image_lib->display_errors());
                echo json_encode(array('error' => 'Failed to resize: ' . $this->image_lib->display_errors()));
                error_reporting($old_error_reporting);
                return;
            }
            $this->image_lib->clear();
            
            // Get new dimensions after resize
            $info = getimagesize($target_path);
            if (!$info) {
                echo json_encode(array('error' => 'Failed to get image info after resize'));
                error_reporting($old_error_reporting);
                return;
            }
            
            $width = $info[0];
            $height = $info[1];
            
            // Calculate crop position
            $target_height = 1149;
            $crop_total = $height - $target_height;
            
            log_message('debug', 'Image dimensions after resize - Width: ' . $width . ', Height: ' . $height . ', Crop total: ' . $crop_total);
            
            if ($crop_total <= 0) {
                echo json_encode(array('error' => 'Image height is already smaller than target'));
                error_reporting($old_error_reporting);
                return;
            }
            
            // Default center crop (50:50)
            $crop_top = round($crop_total * 0.5);
            
            // Adjust based on position
            switch($position) {
                // เลื่อนขึ้น
                case 'up_50':
                    $crop_top = round($crop_total * 0.0); // ตัดจากล่างทั้งหมด
                    break;
                case 'up_45':
                    $crop_top = round($crop_total * 0.05);
                    break;
                case 'up_40':
                    $crop_top = round($crop_total * 0.1);
                    break;
                case 'up_35':
                    $crop_top = round($crop_total * 0.15);
                    break;
                case 'up_30':
                    $crop_top = round($crop_total * 0.2);
                    break;
                case 'up_25':
                    $crop_top = round($crop_total * 0.25);
                    break;
                case 'up_20':
                    $crop_top = round($crop_total * 0.3);
                    break;
                case 'up_15':
                    $crop_top = round($crop_total * 0.35);
                    break;
                case 'up_10':
                    $crop_top = round($crop_total * 0.4);
                    break;
                case 'up_5':
                    $crop_top = round($crop_total * 0.45);
                    break;
                    
                // เลื่อนลง
                case 'down_5':
                    $crop_top = round($crop_total * 0.55);
                    break;
                case 'down_10':
                    $crop_top = round($crop_total * 0.6);
                    break;
                case 'down_15':
                    $crop_top = round($crop_total * 0.65);
                    break;
                case 'down_20':
                    $crop_top = round($crop_total * 0.7);
                    break;
                case 'down_25':
                    $crop_top = round($crop_total * 0.75);
                    break;
                case 'down_30':
                    $crop_top = round($crop_total * 0.8);
                    break;
                case 'down_35':
                    $crop_top = round($crop_total * 0.85);
                    break;
                case 'down_40':
                    $crop_top = round($crop_total * 0.9);
                    break;
                case 'down_45':
                    $crop_top = round($crop_total * 0.95);
                    break;
                case 'down_50':
                    $crop_top = round($crop_total * 1.0); // ตัดจากบนทั้งหมด
                    break;
                    
                // ตรงกลาง
                case 'center':
                default:
                    $crop_top = round($crop_total * 0.5); // Center
                    break;
            }
            
            log_message('debug', 'Crop calculation - Position: ' . $position . ', Crop top: ' . $crop_top);
            
            // Crop the image
            $config = array(
                'image_library' => 'gd2',
                'source_image' => $target_path,
                'maintain_ratio' => false,
                'x_axis' => 0,
                'y_axis' => $crop_top,
                'width' => $width,
                'height' => $target_height,
                'quality' => '95%'
            );
            
            $this->image_lib->initialize($config);
            if (!$this->image_lib->crop()) {
                log_message('error', 'Crop error: ' . $this->image_lib->display_errors());
                echo json_encode(array('error' => 'Failed to crop: ' . $this->image_lib->display_errors()));
                error_reporting($old_error_reporting);
                return;
            }
            $this->image_lib->clear();
            
            // Update thumbnail
            $thumb_path = './uploads/products/' . dirname($image['image_path']) . '/thumb_' . $image['image_filename'];
            
            $config = array(
                'image_library' => 'gd2',
                'source_image' => $target_path,
                'new_image' => $thumb_path,
                'maintain_ratio' => true,
                'width' => 90,
                'height' => 129,
                'quality' => '90%'
            );
            
            $this->image_lib->initialize($config);
            $this->image_lib->resize();
            $this->image_lib->clear();
            
            log_message('info', 'Recrop successful - Image ID: ' . $image_id . ', Position: ' . $position);
            
            echo json_encode(array('success' => true));
            
        } catch (Exception $e) {
            log_message('error', 'Re-crop exception: ' . $e->getMessage());
            echo json_encode(array('error' => 'Server error: ' . $e->getMessage()));
        }
        
        // Restore error reporting
        error_reporting($old_error_reporting);
    }
    
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