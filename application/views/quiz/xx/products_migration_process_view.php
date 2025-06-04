<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Processing Migration - TMDb</title>
    <link href="<?php echo base_url(); ?>css/style-admin.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url(); ?>js/jquery/jquery.js" type="text/javascript"></script>
    <style>
        .process-header {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .product-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .product-header {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        .product-image {
            width: 150px;
            flex-shrink: 0;
        }
        .product-image img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-info {
            flex: 1;
        }
        .product-info h3 {
            margin: 0 0 10px 0;
            color: #360;
        }
        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .result-item {
            border: 2px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        .result-item:hover {
            border-color: #360;
            transform: translateY(-2px);
        }
        .result-item.selected {
            border-color: #37b24d;
            background: #f0f8f0;
        }
        .result-item img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #360; color: white; }
        .btn-success { background: #37b24d; color: white; }
        .btn-warning { background: #f59f00; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .status-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-found { background: #d4edda; color: #155724; }
        .status-not_found { background: #f8d7da; color: #721c24; }
        .status-completed { background: #cce5ff; color: #004085; }
        .manual-search {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin-top: 15px;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="process-header">
        <h2>Processing Batch: <?php echo substr($batch_id, 0, 14); ?>...</h2>
        <div class="progress-info">
            <div>
                กำลังประมวลผล: <?php echo $current_offset + 1; ?> - <?php echo min($current_offset + $batch_size, $total_products); ?> 
                จากทั้งหมด <?php echo number_format($total_products); ?> รายการ
            </div>
            <div>
                Progress: <?php echo round((($current_offset + $batch_size) / $total_products) * 100); ?>%
            </div>
        </div>
    </div>

    <?php foreach($products as $product): ?>
    <div class="product-item" data-log-id="<?php echo $product['log_id']; ?>" data-product-id="<?php echo $product['products_id']; ?>">
        <div class="product-header">
            <div class="product-image">
                <?php 
                $img_path = base_url('uploads/products/');
                if ($product['folder_img']) {
                    $img_path .= $product['folder_img'] . '/';
                }
                if ($product['products_image']) {
                    $img_path .= $product['products_image'];
                } else {
                    $img_path = base_url('image/no-image.png');
                }
                ?>
                <img src="<?php echo $img_path; ?>" alt="">
            </div>
            <div class="product-info">
                <h3><?php echo $product['products_name']; ?></h3>
                <p><strong>รหัส:</strong> <?php echo $product['products_code']; ?></p>
                <p><strong>หมวด:</strong> <?php echo $product['categories_name']; ?></p>
                <p><strong>รูปปัจจุบัน:</strong> <?php echo $product['image_count']; ?> รูป</p>
                
                <?php if(isset($product['migration_status'])): ?>
                    <span class="status-tag status-<?php echo $product['migration_status']; ?>">
                        <?php echo ucfirst($product['migration_status']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(isset($product['search_results']) && !empty($product['search_results'])): ?>
        <h4>ผลการค้นหาจาก TMDb (พบ <?php echo count($product['search_results']); ?> รายการ)</h4>
        <div class="search-results">
            <?php foreach($product['search_results'] as $result): ?>
            <?php 
                $title = $result['title'] ?? $result['name'];
                $date = $result['release_date'] ?? $result['first_air_date'];
                $year = $date ? '(' . substr($date, 0, 4) . ')' : '';
                $type = isset($result['first_air_date']) ? 'tv' : 'movie';
                $poster = isset($result['poster_path']) ? 
                         'https://image.tmdb.org/t/p/w185' . $result['poster_path'] : 
                         base_url('image/no-image.png');
            ?>
            <div class="result-item" 
                 data-tmdb-id="<?php echo $result['id']; ?>" 
                 data-type="<?php echo $type; ?>"
                 data-title="<?php echo htmlspecialchars($title); ?>">
                <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($title); ?>">
                <div style="font-size: 12px; font-weight: bold;"><?php echo htmlspecialchars($title); ?></div>
                <div style="font-size: 11px; color: #666;"><?php echo $year; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p style="color: #999;">ไม่พบผลการค้นหาอัตโนมัติ</p>
        <?php endif; ?>
        
        <div class="manual-search">
            <h4>ค้นหาด้วยตนเอง</h4>
            <input type="text" class="search-query" placeholder="พิมพ์ชื่อหนังหรือซีรีย์..." 
                   value="<?php echo htmlspecialchars($product['products_name']); ?>" style="width: 50%; padding: 8px;">
            <select class="search-type" style="padding: 8px;">
                <option value="movie">หนัง</option>
                <option value="tv">ซีรีย์</option>
            </select>
            <button class="btn btn-primary btn-search">ค้นหา</button>
        </div>
        
        <div class="action-buttons">
            <button class="btn btn-success btn-select-tmdb" style="display: none;">เลือกและดูรูปภาพ</button>
            <button class="btn btn-warning btn-skip">ข้ามสินค้านี้</button>
            <?php if($product['migration_status'] == 'completed'): ?>
            <span style="color: #37b24d;">✓ ดำเนินการแล้ว</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <div style="text-align: center; margin: 40px 0;">
        <?php if($next_offset < $total_products): ?>
        <a href="<?php echo site_url('quiz/products_migration/process/' . $next_offset); ?>" 
           class="btn btn-primary" style="padding: 15px 30px; font-size: 16px;">
            ดำเนินการ Batch ถัดไป →
        </a>
        <?php else: ?>
        <a href="<?php echo site_url('quiz/products_migration/complete'); ?>" 
           class="btn btn-success" style="padding: 15px 30px; font-size: 16px;">
            ✓ เสร็จสิ้น
        </a>
        <?php endif; ?>
        
        <a href="<?php echo site_url('quiz/products_migration'); ?>" 
           style="margin-left: 20px; color: #666;">
            ← กลับหน้าหลัก
        </a>
    </div>

    <!-- Modal for image selection -->
    <div id="imageModal" class="modal" style="display: none;">
        <div class="modal-content" style="width: 90%; max-width: 1000px;">
            <span class="close" onclick="closeImageModal()">&times;</span>
            <h3>เลือกรูปภาพที่ต้องการ</h3>
            <div id="modal-loading" class="loading">กำลังโหลดรูปภาพ...</div>
            <div id="image-grid" style="display: none;"></div>
            <div style="margin-top: 20px;">
                <label>
                    <input type="checkbox" id="replace-old"> 
                    ลบรูปเก่าและใช้รูปใหม่แทน
                </label>
            </div>
            <div style="text-align: right; margin-top: 20px;">
                <button class="btn btn-danger" onclick="closeImageModal()">ยกเลิก</button>
                <button class="btn btn-success" onclick="importSelectedImages()">นำเข้ารูปที่เลือก</button>
            </div>
        </div>
    </div>

    <style>
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background: white;
        padding: 20px;
        border-radius: 8px;
        max-height: 90vh;
        overflow-y: auto;
    }
    .close {
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #999;
    }
    .close:hover { color: #000; }
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .image-option {
        position: relative;
        border: 2px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
        cursor: pointer;
    }
    .image-option:hover { border-color: #360; }
    .image-option.selected { border-color: #37b24d; }
    .image-option img {
        width: 100%;
        height: 280px;
        object-fit: cover;
    }
    .image-option .check {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 30px;
        height: 30px;
        background: #37b24d;
        color: white;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .image-option.selected .check { display: flex; }
    </style>

    <script>
    var currentProduct = null;
    var selectedTmdb = null;
    var selectedImages = [];
    
    // Search TMDb
    $('.btn-search').click(function() {
        var $item = $(this).closest('.product-item');
        var logId = $item.data('log-id');
        var query = $item.find('.search-query').val();
        var type = $item.find('.search-type').val();
        
        var $results = $item.find('.search-results');
        $results.html('<div class="loading">กำลังค้นหา...</div>');
        
        $.post('<?php echo site_url("quiz/products_migration/manual_search"); ?>', {
            log_id: logId,
            query: query,
            type: type
        }, function(response) {
            var data = JSON.parse(response);
            if (data.error) {
                $results.html('<p>เกิดข้อผิดพลาด: ' + data.error + '</p>');
                return;
            }
            
            var html = '';
            data.results.forEach(function(result) {
                var title = result.title || result.name;
                var date = result.release_date || result.first_air_date;
                var year = date ? '(' + date.substring(0, 4) + ')' : '';
                var poster = result.poster_url || '<?php echo base_url("image/no-image.png"); ?>';
                
                html += '<div class="result-item" data-tmdb-id="' + result.id + '" data-type="' + type + '" data-title="' + title.replace(/'/g, "\\'") + '">';
                html += '<img src="' + poster + '" alt="' + title + '">';
                html += '<div style="font-size: 12px; font-weight: bold;">' + title + '</div>';
                html += '<div style="font-size: 11px; color: #666;">' + year + '</div>';
                html += '</div>';
            });
            
            $results.html('<h4>ผลการค้นหา (พบ ' + data.results.length + ' รายการ)</h4><div class="search-results">' + html + '</div>');
        });
    });
    
    // Select TMDb item
    $(document).on('click', '.result-item', function() {
        $('.result-item').removeClass('selected');
        $(this).addClass('selected');
        
        var $item = $(this).closest('.product-item');
        $item.find('.btn-select-tmdb').show();
        
        selectedTmdb = {
            id: $(this).data('tmdb-id'),
            type: $(this).data('type'),
            title: $(this).data('title')
        };
    });
    
    // Select and show images
    $('.btn-select-tmdb').click(function() {
        var $item = $(this).closest('.product-item');
        currentProduct = {
            logId: $item.data('log-id'),
            productId: $item.data('product-id')
        };
        
        // Save TMDb mapping
        $.post('<?php echo site_url("quiz/products_migration/select_tmdb"); ?>', {
            log_id: currentProduct.logId,
            products_id: currentProduct.productId,
            tmdb_id: selectedTmdb.id,
            tmdb_type: selectedTmdb.type
        });
        
        // Load images
        $('#imageModal').show();
        $('#modal-loading').show();
        $('#image-grid').hide();
        
        $.post('<?php echo site_url("quiz/products_images_test/get_tmdb_images"); ?>', {
            tmdb_id: selectedTmdb.id,
            type: selectedTmdb.type
        }, function(response) {
            var data = JSON.parse(response);
            $('#modal-loading').hide();
            
            if (data.posters && data.posters.length > 0) {
                var html = '<div class="image-grid">';
                data.posters.forEach(function(poster, index) {
                    html += '<div class="image-option" data-url="' + poster.full_url + '">';
                    html += '<img src="' + poster.preview_url + '" alt="">';
                    html += '<div class="check">✓</div>';
                    html += '</div>';
                });
                html += '</div>';
                
                $('#image-grid').html(html).show();
                selectedImages = [];
            } else {
                $('#image-grid').html('<p>ไม่พบรูปภาพ</p>').show();
            }
        });
    });
    
    // Toggle image selection
    $(document).on('click', '.image-option', function() {
        $(this).toggleClass('selected');
        var url = $(this).data('url');
        
        if ($(this).hasClass('selected')) {
            selectedImages.push(url);
        } else {
            selectedImages = selectedImages.filter(function(u) { return u !== url; });
        }
    });
    
    // Import images
    function importSelectedImages() {
        if (selectedImages.length === 0) {
            alert('กรุณาเลือกรูปภาพ');
            return;
        }
        
        var replaceOld = $('#replace-old').is(':checked');
        
        $.post('<?php echo site_url("quiz/products_migration/import_images"); ?>', {
            log_id: currentProduct.logId,
            products_id: currentProduct.productId,
            images: selectedImages,
            replace_old: replaceOld ? 1 : 0
        }, function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                alert('นำเข้า ' + data.imported + ' รูปสำเร็จ');
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาด');
            }
        });
        
        closeImageModal();
    }
    
    // Skip product
    $('.btn-skip').click(function() {
        if (!confirm('ต้องการข้ามสินค้านี้?')) return;
        
        var logId = $(this).closest('.product-item').data('log-id');
        $.post('<?php echo site_url("quiz/products_migration/skip_product"); ?>', {
            log_id: logId
        }, function() {
            location.reload();
        });
    });
    
    function closeImageModal() {
        $('#imageModal').hide();
        selectedImages = [];
    }
    </script>
</body>
</html>