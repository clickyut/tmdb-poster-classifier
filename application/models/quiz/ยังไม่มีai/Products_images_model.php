<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Products_images_model extends CI_Model {
    
    function __construct() {
        parent::__construct();
        $this->load->library('image_lib');
    }
    
    /**
     * Get all images for a product
     */
    function get_product_images($products_id) {
        return $this->db->where('products_id', $products_id)
                       ->order_by('image_order', 'ASC')
                       ->get('hhd_products_images')
                       ->result_array();
    }
    
    /**
     * Get primary image for a product
     */
    function get_primary_image($products_id) {
        return $this->db->where('products_id', $products_id)
                       ->where('is_primary', 1)
                       ->get('hhd_products_images')
                       ->row_array();
    }
    
    /**
     * Count images for a product
     */
    function count_product_images($products_id) {
        return $this->db->where('products_id', $products_id)
                       ->count_all_results('hhd_products_images');
    }
    
    /**
     * Add new image
     */
    function add_image($data) {
        // Check if we need to set as primary
        if (!isset($data['is_primary'])) {
            $count = $this->count_product_images($data['products_id']);
            $data['is_primary'] = ($count == 0) ? 1 : 0;
        }
        
        // Get next order
        if (!isset($data['image_order'])) {
            $max_order = $this->db->select_max('image_order')
                                  ->where('products_id', $data['products_id'])
                                  ->get('hhd_products_images')
                                  ->row()->image_order;
            $data['image_order'] = ($max_order === null) ? 0 : $max_order + 1;
        }
        
        $data['created_date'] = date('Y-m-d H:i:s');
        $data['created_by'] = $this->session->userdata('username');
        
        $this->db->insert('hhd_products_images', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Update image order with concurrency control
     */
    function update_image_order($products_id, $order_data) {
        if (!$products_id || !is_array($order_data) || empty($order_data)) {
            return array('error' => 'ข้อมูลไม่ถูกต้อง');
        }
        
        $this->db->trans_start();
        
        try {
            // Lock product to prevent concurrent updates
            $this->db->query("SELECT * FROM hhd_products WHERE products_id = ? FOR UPDATE", array($products_id));
            
            // Verify all images belong to this product
            $image_ids = array_column($order_data, 'id');
            $existing = $this->db->where('products_id', $products_id)
                                 ->where_in('image_id', $image_ids)
                                 ->get('hhd_products_images')
                                 ->result_array();
            
            if (count($existing) != count($image_ids)) {
                throw new Exception('บางรูปภาพไม่ใช่ของสินค้านี้');
            }
            
            // Update order
            foreach ($order_data as $item) {
                $this->db->where('image_id', $item['id'])
                         ->where('products_id', $products_id) // Double check
                         ->update('hhd_products_images', array(
                             'image_order' => (int)$item['order']
                         ));
            }
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() === FALSE) {
                throw new Exception('ไม่สามารถอัพเดทลำดับได้');
            }
            
            return array('success' => true);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Update order error: ' . $e->getMessage());
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Set primary image
     */
    function set_primary_image($products_id, $image_id) {
        // Remove current primary in new system only
        $this->db->where('products_id', $products_id)
                 ->update('hhd_products_images', array('is_primary' => 0));
        
        // Set new primary in new system only
        $this->db->where('image_id', $image_id)
                 ->update('hhd_products_images', array('is_primary' => 1));
        
        // DO NOT update main products table - this is for new system only
        // Use set_as_main_product_image() to update old system
    }
    
    /**
     * Delete image
     */
    function delete_image($image_id) {
        // Start transaction
        $this->db->trans_start();
        
        $image = $this->db->where('image_id', $image_id)
                          ->get('hhd_products_images')
                          ->row();
        
        if ($image) {
            // Delete physical files with path validation
            $base_path = realpath('./uploads/products/');
            if (!$base_path) {
                log_message('error', 'Invalid base path for uploads');
                return false;
            }
            
            $full_path = realpath($base_path . '/' . $image->image_path);
            
            // Security: Ensure file is within allowed directory
            if ($full_path && strpos($full_path, $base_path) === 0) {
                if (file_exists($full_path)) {
                    @unlink($full_path);
                }
                
                // Delete thumbnail
                $thumb_path = dirname($full_path) . '/thumb_' . $image->image_filename;
                if (file_exists($thumb_path)) {
                    @unlink($thumb_path);
                }
                
                // Delete original if exists
                $original_path = dirname($full_path) . '/original_' . $image->image_filename;
                if (file_exists($original_path)) {
                    @unlink($original_path);
                }
            } else {
                log_message('error', 'Attempted to delete file outside uploads directory: ' . $image->image_path);
            }
            
            // Delete from database
            $this->db->where('image_id', $image_id)->delete('hhd_products_images');
            
            // If this was primary in new system, set another as primary
            if ($image->is_primary == 1) {
                $next_image = $this->db->where('products_id', $image->products_id)
                                       ->order_by('image_order', 'ASC')
                                       ->limit(1)
                                       ->get('hhd_products_images')
                                       ->row();
                if ($next_image) {
                    // Set next image as primary in new system only
                    $this->db->where('image_id', $next_image->image_id)
                             ->update('hhd_products_images', array('is_primary' => 1));
                }
                // DO NOT update products table - keep old system image intact
            }
            
            // Complete transaction
            $this->db->trans_complete();
            
            return $this->db->trans_status();
        }
        
        return false;
    }
    
    /**
     * Check if image already exists (by hash)
     */
    function check_duplicate_image($products_id, $hash) {
        $count = $this->db->where('products_id', $products_id)
                          ->where('image_hash', $hash)
                          ->count_all_results('hhd_products_images');
        return $count > 0;
    }
    
    /**
     * Check if image URL already exists for this product
     */
    function check_duplicate_url($products_id, $image_url) {
        // Extract filename from URL for comparison
        $filename = basename(parse_url($image_url, PHP_URL_PATH));
        
        // Check against existing URLs - more precise matching
        $existing = $this->db->where('products_id', $products_id)
                             ->where('image_url', $image_url)
                             ->count_all_results('hhd_products_images');
        
        if ($existing > 0) {
            return true;
        }
        
        // Also check by filename pattern to catch similar images
        $existing_filename = $this->db->where('products_id', $products_id)
                                      ->like('image_url', $filename)
                                      ->count_all_results('hhd_products_images');
        
        return $existing_filename > 0;
    }
    
    /**
     * Get TMDb mapping for product
     */
    function get_tmdb_mapping($products_id) {
        return $this->db->where('products_id', $products_id)
                       ->get('hhd_products_tmdb')
                       ->row_array();
    }
    
    /**
     * Save TMDb mapping
     */
    function save_tmdb_mapping($data) {
        $exists = $this->db->where('products_id', $data['products_id'])
                           ->count_all_results('hhd_products_tmdb');
        
        if ($exists > 0) {
            $data['modified_date'] = date('Y-m-d H:i:s');
            $data['modified_by'] = $this->session->userdata('username');
            
            $this->db->where('products_id', $data['products_id'])
                     ->update('hhd_products_tmdb', $data);
        } else {
            $data['created_date'] = date('Y-m-d H:i:s');
            $data['created_by'] = $this->session->userdata('username');
            
            $this->db->insert('hhd_products_tmdb', $data);
        }
    }
    
    /**
     * Process and save image from URL
     */
    function process_image_from_url($products_id, $image_url, $extra = 0, $crop_data = null) {
        $this->load->library('tmdb_api');
        
        // Validate inputs
        if (!$products_id || !$image_url) {
            return array('error' => 'Invalid parameters');
        }
        
        // Extract image path from URL
        $path_info = $this->extract_image_info($image_url);
        if (!$path_info) {
            return array('error' => 'Invalid image URL');
        }
        
        // Check if we've reached max images
        $current_count = $this->count_product_images($products_id);
        $max_images = $this->get_setting('max_images_per_product');
        
        if ($current_count >= $max_images) {
            return array('error' => 'Maximum images limit reached (' . $max_images . ')');
        }
        
        // Download image
        $image_data = $this->tmdb_api->download_image($path_info['path'], $path_info['size']);
        if (!$image_data) {
            return array('error' => 'Failed to download image');
        }
        
        // Calculate hash
        $hash = md5($image_data);
        
        // Check duplicate
        if ($this->check_duplicate_image($products_id, $hash)) {
            return array('error' => 'รูปนี้มีในระบบแล้ว');
        }
        
        // Generate filename
        $filename = $this->generate_filename($products_id, $path_info['extension']);
        $subfolder = date('Y-m-d');
        $relative_path = $subfolder . '/' . $filename;
        
        // Create directory
        $upload_path = './uploads/products/' . $subfolder;
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // Save original
        $full_path = $upload_path . '/' . $filename;
        if (!file_put_contents($full_path, $image_data)) {
            return array('error' => 'Failed to save image');
        }
        
        // Keep a copy of original for manual crop if needed
        $has_original = 0;
        if (in_array($extra, array(5, 6, 7, 8))) {
            $original_path = $upload_path . '/original_' . $filename;
            copy($full_path, $original_path);
            $has_original = 1;
        }
        
        // Process image based on extra
        if ($crop_data && !empty($crop_data)) {
            // Manual crop
            $process_result = $this->process_image_with_crop($full_path, $extra, $crop_data);
        } else {
            // Auto process
            $process_result = $this->process_image_by_type($full_path, $extra);
        }
        
        if (!$process_result) {
            unlink($full_path);
            if ($has_original) {
                unlink($original_path);
            }
            return array('error' => 'Failed to process image');
        }
        
        // Create thumbnail
        $this->create_thumbnail($full_path, $upload_path . '/thumb_' . $filename);
        
        // Save to database
        $image_data = array(
            'products_id' => $products_id,
            'image_url' => $image_url,
            'image_filename' => $filename,
            'image_path' => $relative_path,
            'image_type' => 'poster',
            'image_size' => $path_info['size'],
            'image_language' => $path_info['language'],
            'image_hash' => $hash,
            'has_original' => $has_original
        );
        
        $image_id = $this->add_image($image_data);
        
        return array(
            'success' => true,
            'image_id' => $image_id,
            'filename' => $filename,
            'path' => $relative_path,
            'needs_crop' => in_array($extra, array(5, 6, 7, 8))
        );
    }
    
    /**
     * Process image with manual crop data
     */
    private function process_image_with_crop($source_path, $extra, $crop_data) {
        $this->load->library('image_lib');
        
        // Get target dimensions
        if (in_array($extra, array(0, 1, 2))) {
            $target_width = 1000;
            $target_height = 1500;
        } else {
            $target_width = 1000;
            $target_height = 1149;
        }
        
        // First crop based on user selection
        $config = array(
            'image_library' => 'gd2',
            'source_image' => $source_path,
            'maintain_ratio' => false,
            'x_axis' => (int)$crop_data['x'],
            'y_axis' => (int)$crop_data['y'],
            'width' => (int)$crop_data['width'],
            'height' => (int)$crop_data['height'],
            'quality' => '95%'
        );
        
        $this->image_lib->initialize($config);
        if (!$this->image_lib->crop()) {
            log_message('error', 'Crop error: ' . $this->image_lib->display_errors());
            return false;
        }
        $this->image_lib->clear();
        
        // Then resize to target dimensions
        $config = array(
            'image_library' => 'gd2',
            'source_image' => $source_path,
            'maintain_ratio' => false,
            'width' => $target_width,
            'height' => $target_height,
            'quality' => '95%'
        );
        
        $this->image_lib->initialize($config);
        $result = $this->image_lib->resize();
        $this->image_lib->clear();
        
        return $result;
    }
    
    /**
     * Re-crop existing image
     */
    function recrop_image($image_id, $crop_data) {
        $image = $this->db->where('image_id', $image_id)->get('hhd_products_images')->row_array();
        if (!$image) {
            return array('error' => 'Image not found');
        }
        
        // Get product for extra
        $product = $this->db->where('products_id', $image['products_id'])->get('hhd_products')->row_array();
        if (!$product) {
            return array('error' => 'Product not found');
        }
        
        $original_path = './uploads/products/' . dirname($image['image_path']) . '/original_' . $image['image_filename'];
        $target_path = './uploads/products/' . $image['image_path'];
        
        if (!file_exists($original_path)) {
            return array('error' => 'Original image not found for re-crop');
        }
        
        // Copy original to target for processing
        copy($original_path, $target_path);
        
        // Process with new crop
        $result = $this->process_image_with_crop($target_path, $product['extra'], $crop_data);
        
        if ($result) {
            // Update thumbnail
            $this->create_thumbnail($target_path, './uploads/products/' . dirname($image['image_path']) . '/thumb_' . $image['image_filename']);
            
            return array('success' => true);
        } else {
            return array('error' => 'Failed to re-crop image');
        }
    }
    
    /**
     * Extract image info from TMDb URL
     */
    function extract_image_info($url) {
        // Pattern: https://image.tmdb.org/t/p/w500/path.jpg
        if (preg_match('/image\.tmdb\.org\/t\/p\/([^\/]+)\/(.+)/', $url, $matches)) {
            $size = $matches[1];
            $path = '/' . $matches[2];
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            
            // Try to extract language from URL if available
            $language = null;
            if (preg_match('/\-([a-z]{2})\./', $path, $lang_matches)) {
                $language = $lang_matches[1];
            }
            
            return array(
                'size' => $size,
                'path' => $path,
                'extension' => $extension ?: 'jpg',
                'language' => $language
            );
        }
        
        return false;
    }
    
    /**
     * Generate unique filename
     */
    private function generate_filename($products_id, $extension) {
        $timestamp = time();
        $random = substr(md5(uniqid()), 0, 8);
        return "p{$products_id}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Process image based on product type (extra field)
     */
    private function process_image_by_type($source_path, $extra) {
        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit != '-1') {
            $memory_bytes = $this->return_bytes($memory_limit);
            $image_info = getimagesize($source_path);
            $required_memory = $image_info[0] * $image_info[1] * 4; // Rough estimate
            
            if ($required_memory > $memory_bytes * 0.8) {
                @ini_set('memory_limit', '256M');
            }
        }
        
        $this->load->library('image_lib');
        
        // Get image info
        $info = getimagesize($source_path);
        if (!$info) {
            return false;
        }
        
        $width = $info[0];
        $height = $info[1];
        
        // Determine target size based on extra
        if (in_array($extra, array(0, 1, 2))) {
            // DVD: 1000x1500
            $target_width = 1000;
            $target_height = 1500;
        } else {
            // Blu-ray: 1000x1149
            $target_width = 1000;
            $target_height = 1149;
        }
        
        // Calculate aspect ratios
        $source_ratio = $width / $height;
        $target_ratio = $target_width / $target_height;
        
        // For Blu-ray, need special handling
        if (in_array($extra, array(5, 6, 7, 8))) {
            // Step 1: Resize to make width = 1000 while maintaining aspect ratio
            $new_width = $target_width;
            $new_height = round($target_width / $source_ratio);
            
            $config = array(
                'image_library' => 'gd2',
                'source_image' => $source_path,
                'maintain_ratio' => true,
                'width' => $new_width,
                'quality' => '95%'
            );
            
            $this->image_lib->initialize($config);
            if (!$this->image_lib->resize()) {
                log_message('error', 'Resize error: ' . $this->image_lib->display_errors());
                return false;
            }
            $this->image_lib->clear();
            
            // Get new dimensions after resize
            $info = getimagesize($source_path);
            $width = $info[0];
            $height = $info[1];
            
            // Step 2: If height > 1149, crop top and bottom
            if ($height > $target_height) {
                $crop_total = $height - $target_height;
                $crop_top = round($crop_total * 0.5); // 50% from top (เปลี่ยนจาก 0.2 เป็น 0.5)
                $crop_bottom = $crop_total - $crop_top; // 50% from bottom
                
                $config = array(
                    'image_library' => 'gd2',
                    'source_image' => $source_path,
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
                    return false;
                }
                $this->image_lib->clear();
            } 
            // Step 3: If height < 1149, we have a problem - need to add letterbox or stretch
            else if ($height < $target_height) {
                // Option: Just resize to exact dimensions (may distort slightly)
                $config = array(
                    'image_library' => 'gd2',
                    'source_image' => $source_path,
                    'maintain_ratio' => false,
                    'width' => $target_width,
                    'height' => $target_height,
                    'quality' => '95%'
                );
                
                $this->image_lib->initialize($config);
                $result = $this->image_lib->resize();
                $this->image_lib->clear();
                
                return $result;
            }
        } else {
            // DVD: Simple resize to exact dimensions
            $config = array(
                'image_library' => 'gd2',
                'source_image' => $source_path,
                'maintain_ratio' => false,
                'width' => $target_width,
                'height' => $target_height,
                'quality' => '95%'
            );
            
            $this->image_lib->initialize($config);
            $result = $this->image_lib->resize();
            $this->image_lib->clear();
            
            return $result;
        }
        
        return true;
    }
    
    /**
     * Create thumbnail
     */
    private function create_thumbnail($source_path, $thumb_path) {
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
        $this->image_lib->clear();
        
        return $result;
    }
    
    /**
     * Get migration log
     */
    function get_migration_log($batch_id = null, $status = null, $limit = 50, $offset = 0) {
        if ($batch_id) {
            $this->db->where('batch_id', $batch_id);
        }
        if ($status) {
            $this->db->where('migration_status', $status);
        }
        
        return $this->db->order_by('log_id', 'DESC')
                       ->limit($limit, $offset)
                       ->get('hhd_products_migration_log')
                       ->result_array();
    }
    
    /**
     * Count migration log
     */
    function count_migration_log($batch_id = null, $status = null) {
        if ($batch_id) {
            $this->db->where('batch_id', $batch_id);
        }
        if ($status) {
            $this->db->where('migration_status', $status);
        }
        
        return $this->db->count_all_results('hhd_products_migration_log');
    }
    
    /**
     * Add migration log entry
     */
    function add_migration_log($data) {
        $this->db->insert('hhd_products_migration_log', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Update migration log
     */
    function update_migration_log($log_id, $data) {
        $this->db->where('log_id', $log_id)
                 ->update('hhd_products_migration_log', $data);
    }
    
    /**
     * Get products for migration
     */
    function get_products_for_migration($limit = 50, $offset = 0) {
        $sql = "SELECT p.*, c.categories_name, 
                (SELECT COUNT(*) FROM hhd_products_images WHERE products_id = p.products_id) as image_count,
                (SELECT mapping_status FROM hhd_products_tmdb WHERE products_id = p.products_id) as tmdb_status
                FROM hhd_products p
                LEFT JOIN hhd_products_to_categories ptc ON p.products_id = ptc.products_id
                LEFT JOIN hhd_categories c ON ptc.categories_id = c.categories_id
                WHERE p.active = 1
                AND p.products_image IS NOT NULL
                AND p.products_image != ''
                ORDER BY p.products_id ASC
                LIMIT ?, ?";
        
        return $this->db->query($sql, array($offset, $limit))->result_array();
    }
    
    /**
     * Process uploaded image file
     */
    function process_uploaded_image($products_id, $uploaded_file, $extra = 0) {
        // Validate inputs
        if (!$products_id || !$uploaded_file || $uploaded_file['error'] !== UPLOAD_ERR_OK) {
            return array('error' => 'Invalid file upload');
        }
        
        // Check if we've reached max images
        $current_count = $this->count_product_images($products_id);
        $max_images = $this->get_setting('max_images_per_product');
        
        if ($current_count >= $max_images) {
            return array('error' => 'Maximum images limit reached (' . $max_images . ')');
        }
        
        // Read image data
        $image_data = file_get_contents($uploaded_file['tmp_name']);
        if (!$image_data) {
            return array('error' => 'Failed to read uploaded file');
        }
        
        // Calculate hash
        $hash = md5($image_data);
        
        // Check duplicate
        if ($this->check_duplicate_image($products_id, $hash)) {
            return array('error' => 'รูปนี้มีในระบบแล้ว');
        }
        
        // Get file extension
        $original_name = $uploaded_file['name'];
        $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        // Generate filename
        $filename = $this->generate_filename($products_id, $extension);
        $subfolder = date('Y-m-d');
        $relative_path = $subfolder . '/' . $filename;
        
        // Create directory
        $upload_path = './uploads/products/' . $subfolder;
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // Save original
        $full_path = $upload_path . '/' . $filename;
        if (!file_put_contents($full_path, $image_data)) {
            return array('error' => 'Failed to save image');
        }
        
        // Keep a copy of original for manual crop if needed
        $has_original = 0;
        if (in_array($extra, array(5, 6, 7, 8))) {
            $original_path = $upload_path . '/original_' . $filename;
            copy($full_path, $original_path);
            $has_original = 1;
        }
        
        // Process image based on extra
        $process_result = $this->process_image_by_type($full_path, $extra);
        
        if (!$process_result) {
            unlink($full_path);
            if ($has_original) {
                unlink($original_path);
            }
            return array('error' => 'Failed to process image');
        }
        
        // Create thumbnail
        $this->create_thumbnail($full_path, $upload_path . '/thumb_' . $filename);
        
        // Save to database
        $image_record = array(
            'products_id' => $products_id,
            'image_url' => 'uploaded://' . $original_name, // Mark as uploaded
            'image_filename' => $filename,
            'image_path' => $relative_path,
            'image_type' => 'poster',
            'image_size' => 'uploaded',
            'image_language' => null,
            'image_hash' => $hash,
            'has_original' => $has_original
        );
        
        $image_id = $this->add_image($image_record);
        
        return array(
            'success' => true,
            'image_id' => $image_id,
            'filename' => $filename,
            'path' => $relative_path,
            'source' => 'uploaded'
        );
    }
    
    /**
     * Process image from any URL (not just TMDb)
     */
    function process_image_from_any_url($products_id, $image_url, $extra = 0) {
        // Validate inputs
        if (!$products_id || !$image_url) {
            return array('error' => 'Invalid parameters');
        }
        
        // Check if we've reached max images
        $current_count = $this->count_product_images($products_id);
        $max_images = $this->get_setting('max_images_per_product');
        
        if ($current_count >= $max_images) {
            return array('error' => 'Maximum images limit reached (' . $max_images . ')');
        }
        
        // Download image
        $image_data = $this->download_image_from_url($image_url);
        if (!$image_data) {
            return array('error' => 'Failed to download image from URL');
        }
        
        // Calculate hash
        $hash = md5($image_data);
        
        // Check duplicate
        if ($this->check_duplicate_image($products_id, $hash)) {
            return array('error' => 'รูปนี้มีในระบบแล้ว');
        }
        
        // Get extension from URL
        $extension = strtolower(pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (!$extension) {
            $extension = 'jpg'; // Default
        }
        
        // Generate filename
        $filename = $this->generate_filename($products_id, $extension);
        $subfolder = date('Y-m-d');
        $relative_path = $subfolder . '/' . $filename;
        
        // Create directory
        $upload_path = './uploads/products/' . $subfolder;
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }
        
        // Save original
        $full_path = $upload_path . '/' . $filename;
        if (!file_put_contents($full_path, $image_data)) {
            return array('error' => 'Failed to save image');
        }
        
        // Keep a copy of original for manual crop if needed
        $has_original = 0;
        if (in_array($extra, array(5, 6, 7, 8))) {
            $original_path = $upload_path . '/original_' . $filename;
            copy($full_path, $original_path);
            $has_original = 1;
        }
        
        // Process image based on extra
        $process_result = $this->process_image_by_type($full_path, $extra);
        
        if (!$process_result) {
            unlink($full_path);
            if ($has_original) {
                unlink($original_path);
            }
            return array('error' => 'Failed to process image');
        }
        
        // Create thumbnail
        $this->create_thumbnail($full_path, $upload_path . '/thumb_' . $filename);
        
        // Determine source
        $source = 'external';
        if (strpos($image_url, 'image.tmdb.org') !== false) {
            $source = 'tmdb';
        }
        
        // Save to database
        $image_record = array(
            'products_id' => $products_id,
            'image_url' => $image_url,
            'image_filename' => $filename,
            'image_path' => $relative_path,
            'image_type' => 'poster',
            'image_size' => $source,
            'image_language' => null,
            'image_hash' => $hash,
            'has_original' => $has_original
        );
        
        $image_id = $this->add_image($image_record);
        
        return array(
            'success' => true,
            'image_id' => $image_id,
            'filename' => $filename,
            'path' => $relative_path,
            'source' => $source
        );
    }
    
    /**
     * Download image from any URL
     */
    private function download_image_from_url($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $image_data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $image_data) {
            return $image_data;
        }
        
        return false;
    }
    function get_setting($key) {
        $row = $this->db->where('setting_key', $key)
                        ->get('hhd_tmdb_settings')
                        ->row();
        
        if ($row) {
            switch ($row->setting_type) {
                case 'integer':
                    return (int)$row->setting_value;
                case 'boolean':
                    return (bool)$row->setting_value;
                case 'json':
                    return json_decode($row->setting_value, true);
                default:
                    return $row->setting_value;
            }
        }
        
        // Default values
        $defaults = array(
            'max_images_per_product' => 10,
            'batch_size' => 50,
            'cache_duration_hours' => 168,
            'image_quality_dvd' => '1000x1500',
            'image_quality_bluray' => '1000x1149',
            'auto_search_enabled' => true
        );
        
        return isset($defaults[$key]) ? $defaults[$key] : null;
    }
    
    /**
     * Update setting
     */
    function update_setting($key, $value) {
        $data = array(
            'setting_value' => $value,
            'modified_date' => date('Y-m-d H:i:s'),
            'modified_by' => $this->session->userdata('username')
        );
        
        $this->db->where('setting_key', $key)
                 ->update('hhd_tmdb_settings', $data);
    }
    
    /**
     * Get next order number with locking
     */
    private function get_next_order_safe($products_id) {
        // Use transaction with locking
        $sql = "SELECT COALESCE(MAX(image_order), 0) + 1 as next_order 
                FROM hhd_products_images 
                WHERE products_id = ? 
                FOR UPDATE";
        
        $result = $this->db->query($sql, array($products_id))->row();
        return $result ? $result->next_order : 1;
    }
    
    /**
     * Convert memory string to bytes
     */
    private function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }
    
    /**
     * Cleanup orphaned images
     */
    function cleanup_orphaned_images() {
        // Find orphaned database records
        $orphaned = $this->db->query("
            SELECT pi.* 
            FROM hhd_products_images pi
            LEFT JOIN hhd_products p ON pi.products_id = p.products_id
            WHERE p.products_id IS NULL
        ")->result_array();
        
        $cleaned = 0;
        foreach ($orphaned as $image) {
            $this->delete_image($image['image_id']);
            $cleaned++;
        }
        
        return $cleaned;
    }
    
    /**
     * Cleanup missing files
     */
    function cleanup_missing_files() {
        $images = $this->db->get('hhd_products_images')->result_array();
        $cleaned = 0;
        
        foreach ($images as $image) {
            $file_path = './uploads/products/' . $image['image_path'];
            if (!file_exists($file_path)) {
                // Remove database entry for missing file
                $this->db->where('image_id', $image['image_id'])->delete('hhd_products_images');
                $cleaned++;
                log_message('info', 'Removed missing file entry: ' . $image['image_path']);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Fix missing primary images
     */
    function fix_missing_primary_images() {
        $products = $this->db->query("
            SELECT DISTINCT p.products_id
            FROM hhd_products p
            JOIN hhd_products_images pi ON p.products_id = pi.products_id
            WHERE NOT EXISTS (
                SELECT 1 FROM hhd_products_images pi2 
                WHERE pi2.products_id = p.products_id 
                AND pi2.is_primary = 1
            )
        ")->result_array();
        
        $fixed = 0;
        foreach ($products as $product) {
            // Set first image as primary in new system only
            $first_image = $this->db->where('products_id', $product['products_id'])
                                    ->order_by('image_order', 'ASC')
                                    ->limit(1)
                                    ->get('hhd_products_images')
                                    ->row();
            
            if ($first_image) {
                $this->db->where('image_id', $first_image->image_id)
                         ->update('hhd_products_images', array('is_primary' => 1));
                $fixed++;
            }
        }
        
        return $fixed;
    }
}

/* End of file Products_images_model.php */
/* Location: ./application/models/quiz/Products_images_model.php */
