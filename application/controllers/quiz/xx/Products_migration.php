<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Products_migration extends CI_Controller {
    
    private $batch_id;
    
    function __construct() {
        parent::__construct();
        
        if ($this->session->userdata('logged_in') != TRUE) {
            redirect('quiz/login');
        }
        
        $this->load->model('quiz/products_model');
        $this->load->model('quiz/products_images_model');
        $this->load->library('tmdb_api');
        
        // Generate batch ID
        $this->batch_id = date('YmdHis') . '_' . $this->session->userdata('username');
    }
    
    /**
     * Migration dashboard
     */
    function index() {
        $data['page_title'] = 'Migration รูปภาพสินค้าเก่า';
        $data['infoprogram'] = 'Migration <font color="#360">รูปภาพสินค้าเก่า</font>';
        
        // Get statistics
        $data['stats'] = $this->get_migration_stats();
        
        // Get recent batches
        $data['recent_batches'] = $this->get_recent_batches();
        
        $this->load->view('quiz/products_migration_view', $data);
    }
    
    /**
     * Start new batch
     */
    function start_batch() {
        $data['page_title'] = 'เริ่ม Migration Batch ใหม่';
        $data['infoprogram'] = 'เริ่ม <font color="#360">Migration Batch</font>';
        
        // Get batch settings
        $data['batch_size'] = $this->products_images_model->get_setting('batch_size');
        $data['auto_search'] = $this->products_images_model->get_setting('auto_search_enabled');
        
        // Count available products
        $data['total_products'] = $this->count_products_for_migration();
        
        $this->load->view('quiz/products_migration_start_view', $data);
    }
    
    /**
     * Process batch
     */
    function process($offset = 0) {
        $batch_size = $this->products_images_model->get_setting('batch_size');
        $auto_search = $this->products_images_model->get_setting('auto_search_enabled');
        
        // Get products
        $products = $this->products_images_model->get_products_for_migration($batch_size, $offset);
        
        if (empty($products)) {
            redirect('quiz/products_migration/complete');
        }
        
        // Process each product
        foreach ($products as &$product) {
            // Check if already processed
            $existing_log = $this->db->where('products_id', $product['products_id'])
                                     ->where('batch_id', $this->batch_id)
                                     ->get('hhd_products_migration_log')
                                     ->row();
            
            if (!$existing_log) {
                // Create log entry
                $log_data = array(
                    'batch_id' => $this->batch_id,
                    'products_id' => $product['products_id'],
                    'products_code' => $product['products_code'],
                    'products_name' => $product['products_name'],
                    'old_image' => $product['products_image'],
                    'migration_status' => 'pending'
                );
                
                $log_id = $this->products_images_model->add_migration_log($log_data);
                $product['log_id'] = $log_id;
                
                // Auto search if enabled
                if ($auto_search) {
                    $this->auto_search_product($product);
                }
            } else {
                $product['log_id'] = $existing_log->log_id;
                $product['migration_status'] = $existing_log->migration_status;
                $product['tmdb_results'] = json_decode($existing_log->tmdb_results_count, true);
            }
        }
        
        $data['page_title'] = 'Processing Migration Batch';
        $data['infoprogram'] = 'Processing <font color="#360">Migration Batch</font>';
        $data['products'] = $products;
        $data['batch_id'] = $this->batch_id;
        $data['current_offset'] = $offset;
        $data['next_offset'] = $offset + $batch_size;
        $data['batch_size'] = $batch_size;
        $data['total_products'] = $this->count_products_for_migration();
        
        $this->load->view('quiz/products_migration_process_view', $data);
    }
    
    /**
     * Auto search product
     */
    private function auto_search_product(&$product) {
        // Clean product name
        $search_query = $this->tmdb_api->clean_search_query($product['products_name']);
        
        // Try to determine type (movie or tv)
        $is_tv = false;
        if (preg_match('/(season|ซีซั่น|ภาค|ep\.|ตอน)/i', $product['products_name'])) {
            $is_tv = true;
        }
        
        // Smart search
        $results = $this->tmdb_api->smart_search($search_query, $is_tv ? 'tv' : 'movie');
        
        // Update log
        $update_data = array(
            'tmdb_search_query' => $search_query,
            'tmdb_results_count' => count($results['results']),
            'migration_status' => count($results['results']) > 0 ? 'found' : 'not_found',
            'processed_date' => date('Y-m-d H:i:s')
        );
        
        $this->products_images_model->update_migration_log($product['log_id'], $update_data);
        
        // Add results to product
        $product['search_results'] = $results['results'];
        $product['migration_status'] = $update_data['migration_status'];
    }
    
    /**
     * Manual search (AJAX)
     */
    function manual_search() {
        $log_id = $this->input->post('log_id');
        $query = $this->input->post('query');
        $type = $this->input->post('type');
        
        if (!$log_id || !$query) {
            echo json_encode(array('error' => 'Invalid parameters'));
            return;
        }
        
        // Search
        $results = $this->tmdb_api->smart_search($query, $type);
        
        // Update log
        $update_data = array(
            'tmdb_search_query' => $query,
            'tmdb_results_count' => count($results['results']),
            'migration_status' => count($results['results']) > 0 ? 'found' : 'not_found',
            'processed_date' => date('Y-m-d H:i:s')
        );
        
        $this->products_images_model->update_migration_log($log_id, $update_data);
        
        // Add poster URLs
        foreach ($results['results'] as &$item) {
            if (isset($item['poster_path']) && $item['poster_path']) {
                $item['poster_url'] = $this->tmdb_api->get_image_url($item['poster_path'], 'w185');
            }
        }
        
        echo json_encode($results);
    }
    
    /**
     * Select TMDb item (AJAX)
     */
    function select_tmdb() {
        $log_id = $this->input->post('log_id');
        $products_id = $this->input->post('products_id');
        $tmdb_id = $this->input->post('tmdb_id');
        $tmdb_type = $this->input->post('tmdb_type');
        
        if (!$log_id || !$products_id || !$tmdb_id) {
            echo json_encode(array('error' => 'Invalid parameters'));
            return;
        }
        
        // Get TMDb details
        if ($tmdb_type == 'tv') {
            $details = $this->tmdb_api->get_tv($tmdb_id);
        } else {
            $details = $this->tmdb_api->get_movie($tmdb_id);
        }
        
        if (!$details) {
            echo json_encode(array('error' => 'Failed to get TMDb details'));
            return;
        }
        
        // Save TMDb mapping
        $mapping_data = array(
            'products_id' => $products_id,
            'tmdb_id' => $tmdb_id,
            'tmdb_type' => $tmdb_type,
            'tmdb_title' => $details['title'] ?? $details['name'],
            'tmdb_original_title' => $details['original_title'] ?? $details['original_name'],
            'tmdb_release_date' => $details['release_date'] ?? $details['first_air_date'],
            'tmdb_poster_path' => $details['poster_path'],
            'mapping_status' => 'confirmed'
        );
        
        $this->products_images_model->save_tmdb_mapping($mapping_data);
        
        // Update migration log
        $update_data = array(
            'selected_tmdb_id' => $tmdb_id,
            'migration_status' => 'selected',
            'processed_by' => $this->session->userdata('username')
        );
        
        $this->products_images_model->update_migration_log($log_id, $update_data);
        
        echo json_encode(array('success' => true));
    }
    
    /**
     * Import images (AJAX)
     */
    function import_images() {
        $log_id = $this->input->post('log_id');
        $products_id = $this->input->post('products_id');
        $image_urls = $this->input->post('images');
        $replace_old = $this->input->post('replace_old');
        
        if (!$log_id || !$products_id || !is_array($image_urls)) {
            echo json_encode(array('error' => 'Invalid parameters'));
            return;
        }
        
        // Get product info
        $product = $this->products_model->getone($products_id);
        if (!$product) {
            echo json_encode(array('error' => 'Product not found'));
            return;
        }
        
        // Delete old images if requested
        if ($replace_old) {
            $old_images = $this->products_images_model->get_product_images($products_id);
            foreach ($old_images as $old_image) {
                $this->products_images_model->delete_image($old_image['image_id']);
            }
        }
        
        // Import new images
        $success_count = 0;
        $errors = array();
        
        foreach ($image_urls as $url) {
            $result = $this->products_images_model->process_image_from_url(
                $products_id,
                $url,
                $product['extra']
            );
            
            if (isset($result['success']) && $result['success']) {
                $success_count++;
            } else {
                $errors[] = $result['error'] ?? 'Unknown error';
            }
        }
        
        // Update migration log
        $update_data = array(
            'new_images_count' => $success_count,
            'migration_status' => 'completed',
            'processed_date' => date('Y-m-d H:i:s'),
            'processed_by' => $this->session->userdata('username')
        );
        
        if (!empty($errors)) {
            $update_data['error_message'] = implode(', ', $errors);
        }
        
        $this->products_images_model->update_migration_log($log_id, $update_data);
        
        echo json_encode(array(
            'success' => true,
            'imported' => $success_count,
            'errors' => $errors
        ));
    }
    
    /**
     * Skip product (AJAX)
     */
    function skip_product() {
        $log_id = $this->input->post('log_id');
        
        if (!$log_id) {
            echo json_encode(array('error' => 'Invalid log ID'));
            return;
        }
        
        $update_data = array(
            'migration_status' => 'skipped',
            'processed_date' => date('Y-m-d H:i:s'),
            'processed_by' => $this->session->userdata('username')
        );
        
        $this->products_images_model->update_migration_log($log_id, $update_data);
        
        echo json_encode(array('success' => true));
    }
    
    /**
     * View batch details
     */
    function batch($batch_id) {
        $data['page_title'] = 'รายละเอียด Batch';
        $data['infoprogram'] = 'รายละเอียด <font color="#360">Migration Batch</font>';
        
        $data['batch_id'] = $batch_id;
        $data['logs'] = $this->products_images_model->get_migration_log($batch_id);
        
        // Calculate statistics
        $stats = array(
            'total' => count($data['logs']),
            'pending' => 0,
            'searching' => 0,
            'found' => 0,
            'not_found' => 0,
            'selected' => 0,
            'completed' => 0,
            'skipped' => 0,
            'error' => 0
        );
        
        foreach ($data['logs'] as $log) {
            $stats[$log['migration_status']]++;
        }
        
        $data['stats'] = $stats;
        
        $this->load->view('quiz/products_migration_batch_view', $data);
    }
    
    /**
     * Complete page
     */
    function complete() {
        $data['page_title'] = 'Migration เสร็จสิ้น';
        $data['infoprogram'] = 'Migration <font color="#360">เสร็จสิ้น</font>';
        
        $this->load->view('quiz/products_migration_complete_view', $data);
    }
    
    /**
     * Get migration statistics
     */
    private function get_migration_stats() {
        $stats = array();
        
        // Total products
        $stats['total_products'] = $this->db->where('active', 1)
                                           ->where('products_image !=', '')
                                           ->count_all_results('hhd_products');
        
        // Products with new images
        $stats['with_new_images'] = $this->db->query("
            SELECT COUNT(DISTINCT products_id) as count 
            FROM hhd_products_images
        ")->row()->count;
        
        // Products with TMDb mapping
        $stats['with_tmdb'] = $this->db->where('mapping_status', 'confirmed')
                                      ->count_all_results('hhd_products_tmdb');
        
        // Migration status counts
        $status_counts = $this->db->select('migration_status, COUNT(*) as count')
                                  ->group_by('migration_status')
                                  ->get('hhd_products_migration_log')
                                  ->result_array();
        
        $stats['migration_status'] = array();
        foreach ($status_counts as $status) {
            $stats['migration_status'][$status['migration_status']] = $status['count'];
        }
        
        return $stats;
    }
    
    /**
     * Get recent batches
     */
    private function get_recent_batches() {
        return $this->db->select('batch_id, COUNT(*) as total, 
                                 SUM(CASE WHEN migration_status = "completed" THEN 1 ELSE 0 END) as completed,
                                 MIN(processed_date) as start_date,
                                 MAX(processed_date) as end_date')
                       ->group_by('batch_id')
                       ->order_by('start_date', 'DESC')
                       ->limit(10)
                       ->get('hhd_products_migration_log')
                       ->result_array();
    }
    
    /**
     * Count products for migration
     */
    private function count_products_for_migration() {
        return $this->db->where('active', 1)
                       ->where('products_image !=', '')
                       ->count_all_results('hhd_products');
    }
}

/* End of file Products_migration.php */
/* Location: ./application/controllers/quiz/Products_migration.php */