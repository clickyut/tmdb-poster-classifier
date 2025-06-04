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
    
    <!-- Include Styles -->
    <?php include('products_images_styles.php'); ?>
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
                                        <img src="<?php echo base_url('uploads/products/' . $image['image_path']); ?>?t=<?php echo time(); ?>" alt="">
                                        <div class="image-actions">
                                            <?php if($image['is_primary']): ?>
                                                <span style="color: #37b24d; font-size: 12px;">✓ รูปหลัก (ระบบใหม่)</span>
                                            <?php else: ?>
                                                <button class="btn btn-success btn-set-primary" data-id="<?php echo $image['image_id']; ?>" onclick="setPrimaryImage(<?php echo $image['image_id']; ?>); return false;">
                                                    ตั้งเป็นรูปหลัก
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if($image['has_original'] && in_array($product['extra'], array(5,6,7,8))): ?>
                                                <button class="btn btn-info btn-recrop" data-id="<?php echo $image['image_id']; ?>" onclick="showRecropModal(<?php echo $image['image_id']; ?>); return false;" title="ปรับตำแหน่งการตัดรูป">
                                                    ✂️ ปรับตำแหน่ง
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
                                        <div class="upload-option" onclick="showFileUpload()">
                                            <h4>📁 อัพโหลดจากคอมพิวเตอร์</h4>
                                            <p>เลือกไฟล์รูปจากเครื่องคอมพิวเตอร์</p>
                                        </div>
                                        <div class="upload-option" onclick="showUrlInput()">
                                            <h4>🔗 วาง URL รูปภาพ</h4>
                                            <p>Copy URL รูปจากเว็บไซต์อื่นๆ</p>
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

    <!-- Include All Modals -->
    <?php include('products_images_modals.php'); ?>

    <!-- JavaScript Variables (ตัวแปรที่ต้องใช้ใน JavaScript) -->
    <script>
    // PHP Variables to JavaScript
    var productsId = <?php echo $product['products_id']; ?>;
    var maxAdditionalImages = <?php echo $max_images; ?>;
    var currentImagesCount = <?php echo count($images); ?>;
    var baseUrl = '<?php echo base_url(); ?>';
    var siteUrl = '<?php echo site_url(); ?>';
    var noImageUrl = '<?php echo base_url("image/no-image.png"); ?>';
    
    // URLs for AJAX calls
    var ajaxUrls = {
        deleteImage: '<?php echo site_url("quiz/products_images_test/delete_image"); ?>',
        setMainProductImage: '<?php echo site_url("quiz/products_images_test/set_as_main_product_image"); ?>',
        setPrimary: '<?php echo site_url("quiz/products_images_test/set_primary"); ?>',
        updateOrder: '<?php echo site_url("quiz/products_images_test/update_order"); ?>',
        searchTmdb: '<?php echo site_url("quiz/products_images_test/search_tmdb"); ?>',
        getTvSeasons: '<?php echo site_url("quiz/products_images_test/get_tv_seasons"); ?>',
        getTmdbImages: '<?php echo site_url("quiz/products_images_test/get_tmdb_images"); ?>',
        addImage: '<?php echo site_url("quiz/products_images_test/add_image"); ?>',
        saveTmdbMapping: '<?php echo site_url("quiz/products_images_test/save_tmdb_mapping"); ?>',
        addImageFromUrl: '<?php echo site_url("quiz/products_images_test/add_image_from_url"); ?>',
        uploadImage: '<?php echo site_url("quiz/products_images_test/upload_image"); ?>',
        recropImage: '<?php echo site_url("quiz/products_images_test/recrop_image"); ?>'
    };
    </script>

    <!-- Include All Scripts -->
    <?php include('products_images_scripts.php'); ?>
</body>
</html>