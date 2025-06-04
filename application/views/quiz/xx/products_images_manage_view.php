<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Images - <?php echo htmlspecialchars($product['products_name']); ?></title>
    <link href="<?php echo base_url(); ?>css/style-admin.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url(); ?>js/jquery/jquery.js" type="text/javascript"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

    <style>
        .container { padding: 20px; }
        .product-header {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .product-header h2 { margin: 0 0 10px 0; color: #360; }
        .product-info { display: flex; gap: 30px; }
        .product-info div { flex: 1; }
        .product-info label { font-weight: bold; color: #666; }
        
        /* Original Image Section */
        .original-image-section {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .original-image-section h3 {
            color: #856404;
            margin: 0 0 15px 0;
        }
        .original-image-container {
            display: flex;
            gap: 20px;
            align-items: start;
        }
        .original-image-box {
            flex-shrink: 0;
        }
        .original-image-box img {
            width: 200px;
            height: auto;
            max-height: 300px;
            object-fit: contain;
            border-radius: 8px;
            border: 3px solid #ffc107;
            background: #f5f5f5;
        }
        .original-image-info {
            flex: 1;
        }
        .original-image-info p {
            margin: 5px 0;
            color: #856404;
        }
        .warning-box {
            background: #ff6b6b;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
        
        /* Image Grid */
        .images-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .image-item {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background: white;
            position: relative;
            transition: all 0.3s ease;
        }
        .image-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .image-item.primary { border-color: #37b24d; }
        .image-item.sortable-ghost { opacity: 0.4; }
        .image-item img {
            width: 100%;
            height: auto;
            max-height: 280px;
            object-fit: contain;
            border-radius: 4px;
            cursor: move;
            background: #f5f5f5;
        }
        .image-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }
        .btn-primary { background: #360; color: white; }
        .btn-primary:hover { background: #470; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-success { background: #37b24d; color: white; }
        .btn-success:hover { background: #2f9e41; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-warning:hover { background: #e67e22; }
        .btn-default { background: #95a5a6; color: white; }
        .btn-default:hover { background: #7f8c8d; }
        .btn-set-main { 
            background: #e67e22; 
            color: white; 
            font-size: 11px;
            padding: 5px 10px;
        }
        .btn-set-main:hover { 
            background: #d35400; 
            transform: scale(1.05);
        }
        
        /* Upload Section */
        .upload-section {
            background: #f9f9f9;
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        .upload-options {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        .upload-option {
            flex: 1;
            max-width: 300px;
            padding: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-option:hover {
            border-color: #360;
            transform: translateY(-2px);
        }
        
        /* TMDb Search Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 20px;
            width: 80%;
            max-width: 800px;
            border-radius: 8px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover { color: #000; }
        
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }
        .result-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .result-item:hover {
            border-color: #360;
            transform: scale(1.02);
        }
        .result-item img {
            width: 100%;
            height: auto;
            max-height: 200px;
            object-fit: contain;
            border-radius: 4px;
            background: #f5f5f5;
        }
        .result-item .title {
            font-size: 12px;
            margin-top: 5px;
            font-weight: bold;
        }
        .result-item .year {
            font-size: 11px;
            color: #666;
        }
        
        /* Image Selection Modal */
        .modal-content.image-selection {
            display: flex;
            max-width: 1200px;
        }
        .modal-sidebar {
            position: sticky;
            top: 20px;
            width: 300px;
            padding: 20px;
            background: #f5f5f5;
            border-right: 1px solid #ddd;
            height: fit-content;
            flex-shrink: 0;
        }
        .modal-main {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .selection-info {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .selection-info h4 {
            margin: 0 0 10px 0;
            color: #360;
        }
        .selection-count {
            font-size: 24px;
            font-weight: bold;
            color: #360;
            margin: 10px 0;
        }
        .btn-add-fixed {
            width: 100%;
            padding: 15px;
            font-size: 16px;
            font-weight: bold;
            background: #360;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-add-fixed:hover {
            background: #470;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .image-select.disabled {
            opacity: 0.5;
            cursor: not-allowed !important;
            pointer-events: none;
        }
        .btn-add-fixed:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        .image-select {
            position: relative;
            border: 2px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            cursor: pointer;
        }
        .image-select:hover { border-color: #360; }
        .image-select.selected { border-color: #37b24d; }
        .image-select img {
            width: 100%;
            height: auto;
            max-height: 300px;
            object-fit: contain;
            background: #f5f5f5;
        }
        .image-select .check {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            background: #37b24d;
            color: white;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .image-select.selected .check { display: flex; }
        
        .loading {
            text-align: center;
            padding: 40px;
        }
        .badge-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        .badge-movie { background: #e74c3c; color: white; }
        .badge-tv { background: #3498db; color: white; }
        
        /* Delete mode styles */
        #images-grid .image-item {
            position: relative;
        }
        .delete-checkbox {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 24px;
            height: 24px;
            background: white;
            border: 2px solid #333;
            border-radius: 4px;
            display: none;
            cursor: pointer;
            z-index: 10;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        body.delete-mode .delete-checkbox {
            display: block !important;
        }
        .delete-checkbox:checked {
            background: #d9534f;
            border-color: #d9534f;
        }
        .delete-checkbox:checked:after {
            content: '✓';
            color: white;
            position: absolute;
            top: -2px;
            left: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        body.delete-mode .image-item.selected-for-delete {
            opacity: 0.6;
            border: 3px solid #d9534f;
        }
        .delete-toolbar {
            background: #f8f8f8;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }
        body.delete-mode .delete-toolbar {
            display: flex;
        }
        /* Hide action buttons in delete mode */
        body.delete-mode .image-actions {
            display: none;
        }
        body.delete-mode .image-item img {
            cursor: default;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-content.image-selection {
                flex-direction: column;
            }
            .modal-sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: auto;
                border-right: none;
                border-top: 1px solid #ddd;
                z-index: 100;
                display: flex;
                align-items: center;
                padding: 10px 20px;
            }
            .selection-info {
                margin: 0;
                padding: 10px;
                flex: 1;
            }
            .selection-info h4 {
                display: none;
            }
            .selection-count {
                font-size: 16px;
                margin: 0;
            }
            .btn-add-fixed {
                width: auto;
                padding: 10px 20px;
                margin-left: 10px;
            }
            .modal-main {
                padding-bottom: 80px;
            }
        }
        #floatingPanel {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #360;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: none;
            z-index: 1000;
        }
        #floatingPanel.show {
            display: block;
        }
        #floatingPanel button {
            background: white;
            color: #360;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td valign="top"><?php include('header.php'); ?></td>
        </tr>
        <tr>
            <td valign="top" style="min-height:600px;">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="22%" valign="top" id="nav" bgcolor="#F1F1F1"><?php include('menuleft.php'); ?></td>
                        <td width="1%" valign="top" style="border-left:#DEDEDE 1px solid;">
                            <img id="ctrlMnu" src="<?php echo base_url(); ?>image/quiz/show_hide.png" style="cursor:pointer;" />
                        </td>
                        <td width="77%" valign="top" bgcolor="#FFFFFF">
                            <div id="content" class="container">
                                <!-- Product Header -->
                                <div class="product-header">
                                    <h2><?php echo $product['products_name']; ?></h2>
                                    <div class="product-info">
                                        <div>
                                            <label>รหัสสินค้า:</label> <?php echo $product['products_code']; ?>
                                        </div>
                                        <div>
                                            <label>ประเภท:</label> 
                                            <?php 
                                            $type_labels = array(
                                                '0' => 'DVD ปกติ', '1' => 'DVD ไม่มีปก', '2' => 'DVD ขาว',
                                                '5' => '4K-UHD 100GB', '6' => '4K-UHD 50GB',
                                                '7' => 'Blu-ray 25GB', '8' => 'Blu-ray 50GB'
                                            );
                                            echo isset($type_labels[$product['extra']]) ? $type_labels[$product['extra']] : 'Unknown';
                                            ?>
                                        </div>
                                        <div>
                                            <label>จำนวนรูปเพิ่มเติม:</label> 
                                            <span id="image-count"><?php echo count($images); ?></span> / <?php echo $max_images; ?>
                                        </div>
                                    </div>
                                    <?php if($tmdb_mapping && $tmdb_mapping['mapping_status'] == 'confirmed'): ?>
                                    <div style="margin-top: 10px;">
                                        <label>TMDb:</label> 
                                        <?php echo $tmdb_mapping['tmdb_title']; ?>
                                        <span class="badge-type badge-<?php echo $tmdb_mapping['tmdb_type']; ?>">
                                            <?php echo strtoupper($tmdb_mapping['tmdb_type']); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Original Image Section -->
                                <?php if($product['products_image'] && $product['folder_img']): ?>
                                <div class="original-image-section">
                                    <h3>รูปหลักเดิม (จากระบบเดิม)</h3>
                                    <div class="original-image-container">
                                        <div class="original-image-box">
                                            <?php 
                                            $original_image_path = base_url('uploads/products/' . $product['folder_img'] . '/' . $product['products_image']);
                                            ?>
                                            <img src="<?php echo $original_image_path; ?>" 
                                                 alt="รูปหลักเดิม" 
                                                 onerror="this.src='<?php echo base_url('image/no-image.png'); ?>'">
                                        </div>
                                        <div class="original-image-info">
                                            <p><strong>ไฟล์:</strong> <?php echo $product['products_image']; ?></p>
                                            <p><strong>โฟลเดอร์:</strong> <?php echo $product['folder_img']; ?></p>
                                            <p><strong>สถานะ:</strong> <span style="color: #28a745;">✓ รูปหลักที่ใช้แสดงหน้าเว็บ</span></p>
                                            <div class="warning-box">
                                                <strong>⚠️ คำเตือน:</strong> รูปนี้เป็นรูปหลักที่ใช้แสดงสินค้าหน้าเว็บ ห้ามลบจากระบบเดิม!
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="warning-box" style="margin-bottom: 20px;">
                                    <strong>⚠️ เตือน:</strong> สินค้านี้ไม่มีรูปหลักในระบบเดิม กรุณาเพิ่มรูปใหม่และตั้งเป็นรูปหลัก
                                </div>
                                <?php endif; ?>

                                <!-- Current Additional Images -->
                                <h3>รูปภาพเพิ่มเติม (จากระบบใหม่)</h3>
                                <?php if(empty($images)): ?>
                                    <p style="color: #999;">ยังไม่มีรูปภาพเพิ่มเติม</p>
                                <?php else: ?>
                                <div id="images-grid" class="images-container">
                                    <?php foreach($images as $image): ?>
                                    <div class="image-item <?php echo $image['is_primary'] ? 'primary' : ''; ?>" data-id="<?php echo $image['image_id']; ?>">
                                        <img src="<?php echo base_url('uploads/products/' . $image['image_path']); ?>" alt="">
                                        <div class="image-actions">
                                            <?php if($image['is_primary']): ?>
                                                <span style="color: #37b24d; font-size: 12px;">✓ รูปหลัก (ระบบใหม่)</span>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-set-primary" data-id="<?php echo $image['image_id']; ?>" onclick="setPrimaryImage(<?php echo $image['image_id']; ?>); return false;">
                                                    ตั้งเป็นรูปหลัก
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-warning btn-set-main" data-id="<?php echo $image['image_id']; ?>" onclick="setAsMainProductImage(<?php echo $image['image_id']; ?>); return false;" title="ตั้งเป็นรูปหลักในระบบเก่า">
                                                📷 ใช้เป็นรูปหลักของเว็บ
                                            </button>
                                            <button class="btn btn-danger btn-delete" data-id="<?php echo $image['image_id']; ?>" onclick="deleteImage(<?php echo $image['image_id']; ?>); return false;">
                                                ลบ
                                            </button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <!-- Upload Section -->
                                <?php if(count($images) < $max_images): ?>
                                <div class="upload-section">
                                    <h3>เพิ่มรูปภาพใหม่</h3>
                                    <p style="color: #666;">สามารถเพิ่มได้อีก <?php echo $max_images - count($images); ?> รูป (ไม่นับรูปหลักเดิม)</p>
                                    <div class="upload-options">
                                        <div class="upload-option" onclick="showTmdbSearch()">
                                            <h4>🔍 ค้นหาจาก TMDb</h4>
                                            <p>ค้นหาและเลือกรูปจาก The Movie Database</p>
                                        </div>
                                        <div class="upload-option" onclick="showUrlInput()">
                                            <h4>🔗 วาง URL รูปภาพ</h4>
                                            <p>Copy URL จาก TMDb แล้ววางได้เลย</p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div style="margin-top: 30px;">
                                    <a href="<?php echo site_url('quiz/products_images_test'); ?>" class="btn btn-primary">
                                        ← กลับ
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Floating Add Button -->
    <div id="floatingPanel">
        <span class="count">เลือกแล้ว <span id="selectedCount">0</span> รูป</span>
        <button type="button" onclick="addSelectedImages()">
            <i class="fa fa-plus"></i> เพิ่มรูปที่เลือก
        </button>
    </div>

    <!-- TMDb Search Modal -->
    <div id="tmdbModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeTmdbModal()">&times;</span>
            <h3>ค้นหาจาก TMDb</h3>
            
            <div class="search-box">
                <input type="text" id="tmdb-search" placeholder="พิมพ์ชื่อหนังหรือซีรีย์..." value="<?php echo htmlspecialchars($product['products_name']); ?>">
                <select id="tmdb-type">
                    <option value="movie">หนัง</option>
                    <option value="tv">ซีรีย์</option>
                </select>
                <button class="btn btn-primary" onclick="searchTmdb()">ค้นหา</button>
            </div>
            
            <div id="search-results"></div>
        </div>
    </div>

    <!-- Image Selection Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content image-selection">
            <div class="modal-sidebar">
                <span class="close" onclick="closeImageModal()" style="float: right; margin: -10px -10px 0 0;">&times;</span>
                <div class="selection-info">
                    <h4>เลือกรูปภาพ</h4>
                    <div class="selection-count">
                        <span id="sidebar-count">0</span> รูป
                    </div>
                    <p style="color: #666; font-size: 14px;">
                        คลิกที่รูปเพื่อเลือก/ยกเลิก<br>
                        <small>เหลือที่ว่างอีก <span id="remaining-slots" style="color: #360; font-weight: bold;"><?php echo $max_images - count($images); ?></span> รูป</small>
                    </p>
                </div>
                <button class="btn-add-fixed" id="btn-add-sidebar" onclick="addSelectedImages()" disabled>
                    เพิ่มรูปที่เลือก
                </button>
                <div style="margin-top: 20px; text-align: center;">
                    <button class="btn btn-danger" onclick="closeImageModal()">ยกเลิก</button>
                </div>
            </div>
            <div class="modal-main">
                <h3 style="margin-top: 0;">รูปภาพทั้งหมด</h3>
                <div id="image-results"></div>
            </div>
        </div>
    </div>

    <script>
    var productsId = <?php echo $product['products_id']; ?>;
    var selectedTmdb = null;
    var selectedImages = [];
    var deleteMode = false;
    var selectedForDelete = [];
    var maxAdditionalImages = <?php echo $max_images; ?>; // ไม่นับรูปหลักเดิม
    
    // Global functions for onclick handlers
    window.deleteImage = function(imageId) {
        if (window.deletingImage) {
            console.log('Already deleting, skip duplicate call');
            return false;
        }
        
        if (!confirm('ต้องการลบรูปนี้?')) return false;
        
        window.deletingImage = true;
        console.log('Deleting image ID:', imageId);
        
        jQuery.ajax({
            url: '<?php echo site_url("quiz/products_images_test/delete_image"); ?>',
            type: 'POST',
            data: {
                image_id: imageId
            },
            dataType: 'text',
            success: function(response) {
                console.log('Delete raw response:', response);
                
                try {
                    var data = jQuery.parseJSON(response);
                    console.log('Parsed response:', data);
                    
                    if (data && data.success !== false) {
                        location.reload();
                    } else {
                        alert('ไม่สามารถลบรูปได้: ' + (data.error || 'Unknown error'));
                        window.deletingImage = false;
                    }
                } catch(e) {
                    console.log('Parse error, but might be successful');
                    if (response.indexOf('"success":true') > -1) {
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาดในการลบรูป');
                        window.deletingImage = false;
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Delete error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                
                if (xhr.responseText && xhr.responseText.indexOf('"success":true') > -1) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + error);
                    window.deletingImage = false;
                }
            }
        });
        
        return false;
    };
    
    window.setAsMainProductImage = function(imageId) {
        if (!confirm('ต้องการใช้รูปนี้เป็นรูปหลักแสดงหน้าเว็บ?\n\n⚠️ รูปหลักเดิมจะถูกแทนที่')) {
            return false;
        }
        
        console.log('Setting as main product image ID:', imageId);
        
        jQuery.ajax({
            url: '<?php echo site_url("quiz/products_images_test/set_as_main_product_image"); ?>',
            type: 'POST',
            data: {
                products_id: productsId,
                image_id: imageId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Set main product image response:', response);
                
                if (response.success) {
                    alert('✅ ตั้งเป็นรูปหลักสำเร็จ!\n\nรูปนี้จะถูกใช้แสดงหน้าเว็บแล้ว');
                    location.reload();
                } else {
                    alert('❌ เกิดข้อผิดพลาด: ' + (response.error || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Set main product image error:', error);
                console.error('Response:', xhr.responseText);
                
                if (xhr.status == 200) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + error);
                }
            }
        });
        
        return false;
    };
    
    window.setPrimaryImage = function(imageId) {
        console.log('Setting primary image ID:', imageId);
        
        jQuery.ajax({
            url: '<?php echo site_url("quiz/products_images_test/set_primary"); ?>',
            type: 'POST',
            data: {
                products_id: productsId,
                image_id: imageId
            },
            dataType: 'json',
            success: function(response) {
                console.log('Set primary response:', response);
                location.reload();
            },
            error: function(xhr, status, error) {
                console.error('Set primary error:', error);
                console.error('Response:', xhr.responseText);
                
                if (xhr.status == 200) {
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาด: ' + error);
                }
            }
        });
        
        return false;
    };
    
    window.toggleDeleteMode = function() {
        deleteMode = !deleteMode;
        selectedForDelete = [];
        
        console.log('Toggle delete mode:', deleteMode);
        
        if (deleteMode) {
            $('body').addClass('delete-mode');
            $('#toggle-delete-mode .delete-text').text('ยกเลิกการเลือก');
            $('#toggle-delete-mode').removeClass('btn-danger').addClass('btn-warning');
            
            if (window.sortableInstance) {
                window.sortableInstance.option('disabled', true);
            }
            
            console.log('Checkboxes found:', $('.delete-checkbox').length);
            console.log('Body has delete-mode class:', $('body').hasClass('delete-mode'));
        } else {
            $('body').removeClass('delete-mode');
            $('#toggle-delete-mode .delete-text').text('เลือกลบรูป');
            $('#toggle-delete-mode').removeClass('btn-warning').addClass('btn-danger');
            $('.delete-checkbox').prop('checked', false);
            $('#select-all-delete').prop('checked', false);
            
            if (window.sortableInstance) {
                window.sortableInstance.option('disabled', false);
            }
        }
        
        updateDeleteCount();
    };
    
    window.updateDeleteCount = function() {
        selectedForDelete = [];
        $('.delete-checkbox:checked').each(function() {
            selectedForDelete.push($(this).val());
        });
        
        $('#delete-count').text(selectedForDelete.length);
        
        $('.image-item').removeClass('selected-for-delete');
        $('.delete-checkbox:checked').closest('.image-item').addClass('selected-for-delete');
    };
    
    window.toggleSelectAll = function() {
        var isChecked = $('#select-all-delete').prop('checked');
        console.log('Toggle select all:', isChecked);
        console.log('Delete checkboxes found:', $('.delete-checkbox').length);
        console.log('Delete mode active:', deleteMode);
        
        // Make sure we're in delete mode
        if (!deleteMode) {
            console.log('Not in delete mode, cannot select all');
            return;
        }
        
        // Set checked property for all visible checkboxes
        $('.delete-checkbox').each(function() {
            $(this).prop('checked', isChecked);
            console.log('Setting checkbox:', $(this).val(), 'to', isChecked);
        });
        
        // Update count after setting checkboxes
        updateDeleteCount();
    };
    
    window.deleteSelected = function() {
        if (selectedForDelete.length === 0) {
            alert('กรุณาเลือกรูปที่ต้องการลบ');
            return;
        }
        
        if (!confirm('ต้องการลบรูปที่เลือก ' + selectedForDelete.length + ' รูป?')) {
            return;
        }
        
        var deleted = 0;
        var errors = [];
        
        selectedForDelete.forEach(function(imageId, index) {
            setTimeout(function() {
                $.ajax({
                    url: '<?php echo site_url("quiz/products_images_test/delete_image"); ?>',
                    type: 'POST',
                    data: {
                        image_id: imageId
                    },
                    success: function(response) {
                        deleted++;
                        if (deleted + errors.length === selectedForDelete.length) {
                            if (errors.length > 0) {
                                alert('ลบสำเร็จ ' + deleted + ' รูป\nลบไม่สำเร็จ ' + errors.length + ' รูป');
                            }
                            location.reload();
                        }
                    },
                    error: function() {
                        errors.push(imageId);
                        if (deleted + errors.length === selectedForDelete.length) {
                            alert('ลบสำเร็จ ' + deleted + ' รูป\nลบไม่สำเร็จ ' + errors.length + ' รูป');
                            if (deleted > 0) {
                                location.reload();
                            }
                        }
                    }
                });
            }, index * 100);
        });
    };
    
    // Initialize jQuery
    $(document).ready(function() {
        console.log('Page loaded, jQuery version:', $.fn.jquery);
        
        addDeleteModeElements();
        rebindDeleteButtons();
        
        console.log('Delete button exists:', $('#toggle-delete-mode').length);
        console.log('Images count:', $('.image-item').length);
        console.log('Delete buttons count:', $('.btn-delete').length);
        
        if (document.getElementById('images-grid')) {
            var sortable = Sortable.create(document.getElementById('images-grid'), {
                animation: 150,
                ghostClass: 'sortable-ghost',
                disabled: false,
                onEnd: function(evt) {
                    if (deleteMode) return;
                    
                    var order = [];
                    $('#images-grid .image-item').each(function(index) {
                        order.push($(this).data('id'));
                    });
                    
                    $.post('<?php echo site_url("quiz/products_images_test/update_order"); ?>', {
                        order: order
                    });
                }
            });
            
            window.sortableInstance = sortable;
        }
    });
    
    function addDeleteModeElements() {
        console.log('Adding delete mode elements...');
        
        var $currentImagesHeader = $('h3:contains("รูปภาพเพิ่มเติม")');
        
        if ($currentImagesHeader.length > 0 && !$('#toggle-delete-mode').length) {
            console.log('Found images header, adding buttons...');
            
            var buttonBar = '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">' +
                '<div>' +
                    '<button class="btn btn-danger" id="toggle-delete-mode" onclick="toggleDeleteMode()" style="margin-right: 10px;">' +
                        '<span class="delete-text">เลือกลบรูป</span>' +
                    '</button>' +
                    '<span style="color: #666;">คลิกเพื่อเลือกลบหลายรูป</span>' +
                '</div>' +
                '<div>' +
                    '<span style="color: #666; margin-right: 10px;">จำนวนรูปเพิ่มเติม: <?php echo count($images); ?> / <?php echo $max_images; ?></span>' +
                '</div>' +
            '</div>';
            
            $currentImagesHeader.after(buttonBar);
        }
        
        if (!$('#delete-toolbar').length && $('#images-grid').length > 0) {
            console.log('Adding delete toolbar...');
            
            var deleteToolbar = '<div class="delete-toolbar" id="delete-toolbar">' +
                '<div>' +
                    '<span style="margin-left: 20px;">เลือกแล้ว <span id="delete-count">0</span> รูป</span>' +
                '</div>' +
                '<div>' +
                    '<button class="btn btn-danger" onclick="deleteSelected()">ลบรูปที่เลือก</button>' +
                    '<button class="btn btn-default" onclick="toggleDeleteMode()" style="margin-left: 10px;">ยกเลิก</button>' +
                '</div>' +
            '</div>';
            
            $('#images-grid').before(deleteToolbar);
        }
        
        $('.image-item').each(function() {
            if (!$(this).find('.delete-checkbox').length) {
                var imageId = $(this).attr('data-id');
                var checkbox = '<input type="checkbox" class="delete-checkbox" value="' + imageId + '" onchange="updateDeleteCount()">';
                $(this).prepend(checkbox);
                $(this).css('position', 'relative');
            }
        });
    }
    
    function rebindDeleteButtons() {
        console.log('Rebinding delete buttons...');
        
        $('.btn-delete').removeAttr('onclick');
        $('.btn-set-primary').removeAttr('onclick');
        
        $('.btn-delete').unbind('click');
        
        $('.btn-delete').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var imageId = $(this).attr('data-id');
            console.log('Delete button clicked for image:', imageId);
            
            if (imageId) {
                deleteImage(imageId);
            }
            
            return false;
        });
        
        $('.btn-set-primary').unbind('click');
        $('.btn-set-primary').click(function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var imageId = $(this).attr('data-id');
            console.log('Set primary button clicked for image:', imageId);
            
            if (imageId) {
                setPrimaryImage(imageId);
            }
            
            return false;
        });
    }
    
    // TMDb Functions
    function showTmdbSearch() {
        $('#tmdbModal').show();
        setTimeout(function() {
            $('#tmdb-search').focus().select();
        }, 100);
    }
    
    function closeTmdbModal() {
        $('#tmdbModal').hide();
    }
    
    function searchTmdb() {
        var query = $('#tmdb-search').val();
        var type = $('#tmdb-type').val();
        
        if (!query) {
            alert('กรุณาใส่คำค้นหา');
            return;
        }
        
        $('#search-results').html('<div class="loading">กำลังค้นหา...</div>');
        
        $.ajax({
            url: '<?php echo site_url("quiz/products_images_test/search_tmdb"); ?>',
            type: 'POST',
            data: {
                query: query,
                type: type,
                products_id: productsId
            },
            success: function(response) {
                var data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch(e) {
                    console.error('JSON parse error:', e);
                    $('#search-results').html('<p style="color: red;">เกิดข้อผิดพลาดในการประมวลผล</p>');
                    return;
                }
                
                console.log('Search response:', data);
                
                if (data.error) {
                    $('#search-results').html('<p style="color: red;">เกิดข้อผิดพลาด: ' + data.error + '</p>');
                    return;
                }
                
                if (!data.results || data.results.length === 0) {
                    $('#search-results').html('<p>ไม่พบผลการค้นหา</p>');
                    return;
                }
                
                var html = '<div class="search-results">';
                data.results.forEach(function(item) {
                    var title = item.title || item.name;
                    var date = item.release_date || item.first_air_date;
                    var year = date ? date.substring(0, 4) : '';
                    var poster = item.poster_url || '<?php echo base_url("image/no-image.png"); ?>';
                    
                    // Escape title for JavaScript
                    var escapedTitle = title.replace(/\\/g, '\\\\')
                                           .replace(/'/g, "\\'")
                                           .replace(/"/g, '\\"')
                                           .replace(/\n/g, '\\n')
                                           .replace(/\r/g, '\\r');
                    
                    html += '<div class="result-item" onclick="selectTmdbItem(' + item.id + ', \'' + (item.media_type || type) + '\', \'' + escapedTitle + '\')">';
                    html += '<img src="' + poster + '" alt="' + title.replace(/"/g, '&quot;') + '">';
                    html += '<div class="title">' + title + '</div>';
                    html += '<div class="year">' + year + '</div>';
                    html += '</div>';
                });
                html += '</div>';
                
                $('#search-results').html(html);
            },
            error: function(xhr, status, error) {
                console.error('Search error:', xhr.responseText);
                $('#search-results').html('<p style="color: red;">เกิดข้อผิดพลาด: ' + error + '</p>');
            }
        });
    }
    
function selectTmdbItem(tmdbId, type, title) {
    console.log('selectTmdbItem - ID:', tmdbId, 'Type:', type, 'Title:', title);
    
    // Validate parameters
    if (!tmdbId || !type) {
        alert('เกิดข้อผิดพลาด: ข้อมูลไม่ครบถ้วน');
        return;
    }
    
    selectedTmdb = {
        id: tmdbId,
        type: type,
        title: title || 'Unknown'
    };
    
    $('#search-results').html('<div class="loading">กำลังโหลดรูปภาพ...</div>');
    
    $.ajax({
        url: '<?php echo site_url("quiz/products_images_test/get_tmdb_images"); ?>',
        type: 'POST',
        data: {
            tmdb_id: tmdbId,
            type: type,
            products_id: productsId  // เพิ่ม products_id เพื่อให้ฝั่ง server กรองรูปที่มีแล้ว
        },
        success: function(response) {
            console.log('Get images response:', response);
            
            var data;
            try {
                data = typeof response === 'string' ? JSON.parse(response) : response;
            } catch(e) {
                console.error('JSON parse error:', e);
                alert('เกิดข้อผิดพลาดในการโหลดรูปภาพ');
                return;
            }
            
            console.log('Parsed data:', data);
            
            closeTmdbModal();
            showImageSelection(data.posters || [], data.info || {});
            
            // Save mapping only if we have valid data
            if (tmdbId && type && title && title !== 'undefined') {
                saveTmdbMapping(tmdbId, type, title);
            }
        },
        error: function(xhr, status, error) {
            console.error('Load images error:', xhr.responseText);
            alert('ไม่สามารถโหลดรูปภาพได้: ' + error);
        }
    });
}    

function showImageSelection(posters, info) {
    console.log('showImageSelection - posters count:', posters.length);
    console.log('Filter info:', info);
    
    if (!posters || posters.length === 0) {
        var message = '<p>ไม่พบรูปภาพ';
        if (info && info.existing > 0) {
            message += '<br><span style="color: #666;">(' + info.existing + ' รูปที่มีอยู่แล้วถูกซ่อน)</span>';
        }
        message += '</p>';
        $('#image-results').html(message);
        $('#imageModal').show();
        return;
    }
    
    var currentImages = <?php echo count($images); ?>;
    var maxImages = <?php echo $max_images; ?>;
    var remainingSlots = maxImages - currentImages;
    
    // Add filter info message
    var filterMessage = '';
    if (info && info.existing > 0) {
        filterMessage = '<div style="background: #e3f2fd; color: #1976d2; padding: 15px; margin-bottom: 20px; border-radius: 8px; border-left: 4px solid #1976d2;">' +
            '<strong>ℹ️ ข้อมูลการกรอง:</strong> พบรูปทั้งหมด ' + info.total + ' รูป<br>' +
            '• แสดง ' + info.filtered + ' รูป (ที่ยังไม่มีในระบบ)<br>' +
            '• ซ่อน ' + info.existing + ' รูป (ที่มีอยู่แล้ว)' +
            '</div>';
    }
    
    // Add warning if no slots available
    if (remainingSlots <= 0) {
        $('#image-results').html('<div class="warning-box" style="margin: 20px; padding: 20px; background: #ff6b6b; color: white; border-radius: 8px; text-align: center;">' +
            '<h3>⚠️ ไม่สามารถเพิ่มรูปได้</h3>' +
            '<p>จำนวนรูปเต็มแล้ว (' + maxImages + ' รูป)</p>' +
            '<p>กรุณาลบรูปเดิมก่อนเพิ่มรูปใหม่</p>' +
            '</div>');
        $('#imageModal').show();
        return;
    }
    
    var htmlParts = [filterMessage, '<div class="image-grid">'];
    
    posters.forEach(function(poster, index) {
        if (index < 5) {
            console.log('Poster ' + index + ':', poster);
        }
        
        var fullUrl = poster.full_url;
        var previewUrl = poster.preview_url;
        
        if (!fullUrl && poster.file_path) {
            fullUrl = 'https://image.tmdb.org/t/p/original' + poster.file_path;
        }
        if (!previewUrl && poster.file_path) {
            previewUrl = 'https://image.tmdb.org/t/p/original' + poster.file_path;
        }
        
        if (fullUrl && previewUrl) {
            htmlParts.push('<div class="image-select"');
            htmlParts.push(' data-url="' + fullUrl + '"');
            htmlParts.push(' data-index="' + index + '">');
            htmlParts.push('<img src="' + previewUrl + '" alt="">');
            htmlParts.push('<div class="check">✓</div>');
            htmlParts.push('</div>');
        }
    });
    
    htmlParts.push('</div>');
    
    $('#image-results').html(htmlParts.join(''));
    
    $('#image-results .image-select').click(function() {
        if (!$(this).hasClass('disabled')) {
            toggleImageSelection(this);
        }
    });
    
    $('#imageModal').show();
    
    selectedImages = [];
    updateSelectionCount();
}

    function toggleImageSelection(element) {
        var $el = $(element);
        var url = $el.attr('data-url');
        var index = $el.attr('data-index');
        var currentImages = <?php echo count($images); ?>;
        var maxImages = <?php echo $max_images; ?>;
        var remainingSlots = maxImages - currentImages;
        
        console.log('Toggle selection - Index:', index, 'URL:', url);
        console.log('Current images:', currentImages, 'Max:', maxImages, 'Remaining:', remainingSlots);
        
        if (!url || url === 'undefined') {
            console.error('No valid URL found!');
            return;
        }
        
        // Check if trying to select more than available slots
        if (!$el.hasClass('selected') && selectedImages.length >= remainingSlots) {
            alert('ไม่สามารถเลือกเพิ่มได้\n\nเหลือที่ว่างเพียง ' + remainingSlots + ' รูป\nคุณเลือกไปแล้ว ' + selectedImages.length + ' รูป');
            return;
        }
        
        $el.toggleClass('selected');
        
        if ($el.hasClass('selected')) {
            selectedImages.push(url);
            console.log('Added image, total:', selectedImages.length);
        } else {
            selectedImages = selectedImages.filter(function(u) { 
                return u !== url; 
            });
            console.log('Removed image, total:', selectedImages.length);
        }
        
        updateSelectionCount();
        
        // Disable unselected images if reached limit
        if (selectedImages.length >= remainingSlots) {
            $('.image-select:not(.selected)').addClass('disabled').css({
                'opacity': '0.5',
                'cursor': 'not-allowed'
            });
        } else {
            $('.image-select.disabled').removeClass('disabled').css({
                'opacity': '1',
                'cursor': 'pointer'
            });
        }
    }
    
    function updateSelectionCount() {
        var count = selectedImages.length;
        var currentImages = <?php echo count($images); ?>;
        var maxImages = <?php echo $max_images; ?>;
        var remainingSlots = maxImages - currentImages;
        
        $('#selectedCount').text(count);
        $('#sidebar-count').text(count);
        $('#modal-selected-count').text(count > 0 ? '(เลือกแล้ว ' + count + ' รูป)' : '');
        $('#btn-count').text(count);
        
        // Update remaining slots display
        var displayRemaining = remainingSlots - count;
        $('#remaining-slots').text(displayRemaining >= 0 ? displayRemaining : 0);
        
        // Update button state and text
        var btnSidebar = document.getElementById('btn-add-sidebar');
        if (btnSidebar) {
            if (count === 0) {
                btnSidebar.disabled = true;
                btnSidebar.textContent = 'เพิ่มรูปที่เลือก';
            } else if (count > remainingSlots) {
                btnSidebar.disabled = true;
                btnSidebar.textContent = 'เลือกเกินจำนวน! (เกิน ' + (count - remainingSlots) + ' รูป)';
                btnSidebar.style.background = '#e74c3c';
            } else {
                btnSidebar.disabled = false;
                btnSidebar.textContent = 'เพิ่ม ' + count + ' รูปที่เลือก';
                btnSidebar.style.background = '#360';
            }
        }
        
        if (count > 0) {
            $('#floatingPanel').addClass('show');
        } else {
            $('#floatingPanel').removeClass('show');
        }
    }
    
    function closeImageModal() {
        $('#imageModal').hide();
        selectedImages = [];
        $('.image-select').removeClass('selected');
        updateSelectionCount();
    }
    
    function addSelectedImages() {
        console.log('addSelectedImages called, selectedImages:', selectedImages);
        
        if (!selectedImages || selectedImages.length === 0) {
            alert('กรุณาเลือกรูปภาพ');
            return;
        }
        
        var loadingHtml = '<div id="loading-overlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); display: flex; align-items: center; justify-content: center; z-index: 9999;">';
        loadingHtml += '<div style="text-align: center;">';
        loadingHtml += '<div class="loading">กำลังเพิ่มรูปภาพ...</div>';
        loadingHtml += '<div id="progress-text">0 / ' + selectedImages.length + '</div>';
        loadingHtml += '</div></div>';
        
        $('#imageModal').append(loadingHtml);
        
        var processed = 0;
        var success = 0;
        var errors = [];
        
        selectedImages.forEach(function(url, index) {
            setTimeout(function() {
                console.log('Processing image ' + (index + 1) + ':', url);
                
                $.ajax({
                    url: '<?php echo site_url("quiz/products_images_test/add_image"); ?>',
                    type: 'POST',
                    data: {
                        products_id: productsId,
                        image_url: url
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Image ' + (index + 1) + ' response:', response);
                        
                        var data;
                        try {
                            if (typeof response === 'string') {
                                var jsonMatch = response.match(/\{.*\}$/);
                                if (jsonMatch) {
                                    data = JSON.parse(jsonMatch[0]);
                                } else {
                                    data = JSON.parse(response);
                                }
                            } else {
                                data = response;
                            }
                        } catch(e) {
                            console.error('Failed to parse response:', e);
                            errors.push('รูปที่ ' + (index + 1) + ': Response parse error');
                            return;
                        }
                        
                        if (data && data.success) {
                            success++;
                        } else {
                            errors.push('รูปที่ ' + (index + 1) + ': ' + (data.error || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', xhr.responseText);
                        
                        var jsonMatch = xhr.responseText.match(/\{.*\}$/);
                        if (jsonMatch) {
                            try {
                                var data = JSON.parse(jsonMatch[0]);
                                if (data && data.success) {
                                    success++;
                                    return;
                                }
                            } catch(e) {
                                // Not valid JSON
                            }
                        }
                        
                        errors.push('รูปที่ ' + (index + 1) + ': เกิดข้อผิดพลาด');
                    },
                    complete: function() {
                        processed++;
                        $('#progress-text').text(processed + ' / ' + selectedImages.length);
                        
                        if (processed === selectedImages.length) {
                            $('#loading-overlay').remove();
                            
                            if (errors.length > 0) {
                                alert('เพิ่มรูปสำเร็จ ' + success + ' รูป\n\nข้อผิดพลาด:\n' + errors.join('\n'));
                            } else {
                                alert('เพิ่มรูปสำเร็จทั้งหมด ' + success + ' รูป');
                            }
                            
                            if (success > 0) {
                                location.reload();
                            } else {
                                closeImageModal();
                            }
                        }
                    }
                });
            }, index * 500);
        });
    }
    
    function saveTmdbMapping(tmdbId, type, title) {
        $.post('<?php echo site_url("quiz/products_images_test/save_tmdb_mapping"); ?>', {
            products_id: productsId,
            tmdb_id: tmdbId,
            tmdb_type: type,
            tmdb_title: title
        });
    }
    
    function showUrlInput() {
        var url = prompt('วาง URL รูปภาพจาก TMDb:');
        if (url && url.includes('image.tmdb.org')) {
            $.ajax({
                url: '<?php echo site_url("quiz/products_images_test/add_image"); ?>',
                type: 'POST',
                data: {
                    products_id: productsId,
                    image_url: url
                },
                dataType: 'json',
                success: function(data) {
                    if (data.error) {
                        alert('เกิดข้อผิดพลาด: ' + data.error);
                    } else {
                        location.reload();
                    }
                },
                error: function(xhr, status, error) {
                    alert('เกิดข้อผิดพลาด: ' + error);
                }
            });
        }
    }
    
    // Close modals on outside click
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html>
