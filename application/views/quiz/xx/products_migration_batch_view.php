<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?php echo $page_title; ?></title>
    <link href="<?php echo base_url(); ?>css/style-admin.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url(); ?>js/jquery/jquery.js" type="text/javascript"></script>
    <style>
        .batch-header {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: white;
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            border-radius: 4px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            margin: 5px 0;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        .log-table th {
            background: #360;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .log-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .log-table tr:hover {
            background: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-searching { background: #cce5ff; color: #004085; }
        .status-found { background: #d1ecf1; color: #0c5460; }
        .status-not_found { background: #f8d7da; color: #721c24; }
        .status-selected { background: #e2e3e5; color: #383d41; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-skipped { background: #f5f5f5; color: #6c757d; }
        .status-error { background: #f8d7da; color: #721c24; }
        .filter-buttons {
            margin-bottom: 20px;
        }
        .filter-btn {
            padding: 8px 16px;
            margin-right: 10px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 4px;
        }
        .filter-btn.active {
            background: #360;
            color: white;
        }
        .product-thumb {
            width: 40px;
            height: 60px;
            object-fit: cover;
            border-radius: 2px;
        }
        .action-btn {
            padding: 4px 12px;
            font-size: 12px;
            text-decoration: none;
            color: #360;
            border: 1px solid #360;
            border-radius: 4px;
            display: inline-block;
        }
        .action-btn:hover {
            background: #360;
            color: white;
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
                            <div id="content" style="padding: 20px;">
                                <div class="batch-header">
                                    <h2>Batch ID: <?php echo substr($batch_id, 0, 14); ?>...</h2>
                                    <p>รายละเอียดการ Migration</p>
                                </div>
                                
                                <div class="stats-grid">
                                    <?php foreach($stats as $status => $count): ?>
                                    <?php if($status != 'total' && $count > 0): ?>
                                    <div class="stat-box">
                                        <div class="stat-label"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></div>
                                        <div class="stat-number status-<?php echo $status; ?>"><?php echo number_format($count); ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="filter-buttons">
                                    <button class="filter-btn active" data-filter="all">ทั้งหมด (<?php echo $stats['total']; ?>)</button>
                                    <?php foreach($stats as $status => $count): ?>
                                    <?php if($status != 'total' && $count > 0): ?>
                                    <button class="filter-btn" data-filter="<?php echo $status; ?>">
                                        <?php echo ucfirst($status); ?> (<?php echo $count; ?>)
                                    </button>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                                
                                <table class="log-table">
                                    <thead>
                                        <tr>
                                            <th width="80">รูป</th>
                                            <th width="100">รหัส</th>
                                            <th>ชื่อสินค้า</th>
                                            <th width="120">สถานะ</th>
                                            <th width="80">รูปใหม่</th>
                                            <th width="150">วันที่</th>
                                            <th width="100">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($logs as $log): ?>
                                        <tr class="log-row" data-status="<?php echo $log['migration_status']; ?>">
                                            <td>
                                                <?php if($log['old_image']): ?>
                                                <img src="<?php echo base_url('uploads/products/' . $log['old_image']); ?>" 
                                                     class="product-thumb" alt="">
                                                <?php else: ?>
                                                <img src="<?php echo base_url('image/no-image.png'); ?>" 
                                                     class="product-thumb" alt="">
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $log['products_code']; ?></td>
                                            <td>
                                                <?php echo $log['products_name']; ?>
                                                <?php if($log['selected_tmdb_id']): ?>
                                                <br><small style="color: #666;">TMDb ID: <?php echo $log['selected_tmdb_id']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $log['migration_status']; ?>">
                                                    <?php echo ucfirst($log['migration_status']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if($log['new_images_count'] > 0): ?>
                                                <strong style="color: #37b24d;"><?php echo $log['new_images_count']; ?></strong>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo $log['processed_date'] ? date('d/m/Y H:i', strtotime($log['processed_date'])) : '-'; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo site_url('quiz/products_images_test/manage/' . $log['products_id']); ?>" 
                                                   class="action-btn">จัดการรูป</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                
                                <div style="margin-top: 30px; text-align: center;">
                                    <a href="<?php echo site_url('quiz/products_migration'); ?>" 
                                       style="color: #666; text-decoration: none;">
                                        ← กลับหน้า Migration
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <script>
    // Filter functionality
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        
        var filter = $(this).data('filter');
        
        if (filter === 'all') {
            $('.log-row').show();
        } else {
            $('.log-row').hide();
            $('.log-row[data-status="' + filter + '"]').show();
        }
    });
    </script>
</body>
</html>