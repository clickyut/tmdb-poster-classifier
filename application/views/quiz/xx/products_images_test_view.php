<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Product Images Management - TMDb System</title>
    <link href="<?php echo base_url(); ?>css/style-admin.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url(); ?>js/jquery/jquery.js" type="text/javascript"></script>
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .product-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .product-code {
            font-weight: bold;
            color: #360;
            margin-bottom: 5px;
        }
        .product-name {
            font-size: 14px;
            margin-bottom: 10px;
            height: 40px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .manage-btn {
            background: #360;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .manage-btn:hover {
            background: #470;
        }
        .settings-link {
            float: right;
            margin: 20px;
            padding: 10px 20px;
            background: #f0f0f0;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .settings-link:hover {
            background: #e0e0e0;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
        .badge-dvd {
            background: #ff6b6b;
            color: white;
        }
        .badge-bluray {
            background: #4dabf7;
            color: white;
        }
        .badge-4k {
            background: #8b5cf6;
            color: white;
        }
        .image-count {
            background: #37b24d;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-top: 5px;
            display: inline-block;
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
                            <div id="content">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td style="height:10px;">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td valign="top">
                                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="42" align="left" valign="top" height="52">
                                                        <img src="<?php echo base_url(); ?>image/quiz/present-48x48.png" width="48" height="48" />
                                                    </td>
                                                    <td width="100%" align="left" valign="bottom">
                                                        <h1 class="infoprogram"><?php echo $infoprogram; ?></h1>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td valign="top">
                                            <a href="<?php echo site_url('quiz/products_images_test/settings'); ?>" class="settings-link">
                                                ⚙️ ตั้งค่าระบบ
                                            </a>
                                            <div style="clear:both;"></div>
                                            
                                            <h3 style="margin: 20px;">สินค้าล่าสุด (คลิกเพื่อจัดการรูปภาพ)</h3>
                                            
                                            <div class="product-grid">
                                                <?php foreach($products as $product): ?>
                                                <?php 
                                                    $image_count = $this->products_images_model->count_product_images($product['products_id']);
                                                    $tmdb_mapping = $this->products_images_model->get_tmdb_mapping($product['products_id']);
                                                    
                                                    // Determine badge class
                                                    $badge_class = 'badge-dvd';
                                                    $badge_text = 'DVD';
                                                    if (in_array($product['extra'], array(5, 6))) {
                                                        $badge_class = 'badge-4k';
                                                        $badge_text = '4K';
                                                    } elseif (in_array($product['extra'], array(7, 8))) {
                                                        $badge_class = 'badge-bluray';
                                                        $badge_text = 'BD';
                                                    }
                                                    
                                                    // Image path
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
                                                <div class="product-card">
                                                    <img src="<?php echo $img_path; ?>" alt="" class="product-image">
                                                    <div class="product-code">
                                                        <?php echo $product['products_code']; ?>
                                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                                    </div>
                                                    <div class="product-name"><?php echo $product['products_name']; ?></div>
                                                    <div class="product-info">หมวด: <?php echo $product['categories_name']; ?></div>
                                                    <?php if($image_count > 0): ?>
                                                        <span class="image-count">📷 <?php echo $image_count; ?> รูป</span>
                                                    <?php endif; ?>
                                                    <?php if($tmdb_mapping && $tmdb_mapping['mapping_status'] == 'confirmed'): ?>
                                                        <span class="image-count" style="background: #3498db;">✓ TMDb</span>
                                                    <?php endif; ?>
                                                    <a href="<?php echo site_url('quiz/products_images_test/manage/' . $product['products_id']); ?>" 
                                                       class="manage-btn">จัดการรูปภาพ</a>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            
                                            <div style="text-align: center; margin: 40px;">
                                                <a href="<?php echo site_url('quiz/products_migration'); ?>" 
                                                   style="padding: 15px 30px; background: #e74c3c; color: white; 
                                                          text-decoration: none; border-radius: 4px; font-size: 16px;">
                                                    🔄 Migration สินค้าเก่า 42,000 รายการ
                                                </a>
                                            </div>