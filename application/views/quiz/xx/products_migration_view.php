<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Products Migration to TMDb</title>
    <link href="<?php echo base_url(); ?>css/style-admin.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url(); ?>js/jquery/jquery.js" type="text/javascript"></script>
    <style>
        .dashboard {
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #360;
            margin: 10px 0;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill {
            height: 100%;
            background: #37b24d;
            transition: width 0.3s ease;
        }
        .batch-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .batch-table th {
            background: #f5f5f5;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        .batch-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .status-skipped { background: #e2e3e5; color: #383d41; }
        .btn-start {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 18px;
            margin: 20px 0;
        }
        .btn-start:hover {
            background: #c0392b;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
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
                            <div id="content" class="dashboard">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0">
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
                                </table>
                                
                                <h2>สถิติภาพรวม</h2>
                                
                                <div class="stats-grid">
                                    <div class="stat-card">
                                        <div class="stat-label">สินค้าทั้งหมด</div>
                                        <div class="stat-number"><?php echo number_format($stats['total_products']); ?></div>
                                        <div class="stat-label">รายการ</div>
                                    </div>
                                    
                                    <div class="stat-card">
                                        <div class="stat-label">มีรูปใหม่แล้ว</div>
                                        <div class="stat-number"><?php echo number_format($stats['with_new_images']); ?></div>
                                        <?php 
                                        $percent = $stats['total_products'] > 0 ? 
                                                  round(($stats['with_new_images'] / $stats['total_products']) * 100, 1) : 0;
                                        ?>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percent; ?>%"></div>
                                        </div>
                                        <div class="stat-label"><?php echo $percent; ?>%</div>
                                    </div>
                                    
                                    <div class="stat-card">
                                        <div class="stat-label">Mapping TMDb</div>
                                        <div class="stat-number"><?php echo number_format($stats['with_tmdb']); ?></div>
                                        <div class="stat-label">รายการ</div>
                                    </div>
                                    
                                    <div class="stat-card">
                                        <div class="stat-label">รอดำเนินการ</div>
                                        <div class="stat-number">
                                            <?php echo number_format($stats['total_products'] - $stats['with_new_images']); ?>
                                        </div>
                                        <div class="stat-label">รายการ</div>
                                    </div>
                                </div>
                                
                                <?php if(!empty($stats['migration_status'])): ?>
                                <h3>สถานะ Migration</h3>
                                <div class="stats-grid">
                                    <?php foreach($stats['migration_status'] as $status => $count): ?>
                                    <div class="stat-card">
                                        <div class="stat-label"><?php echo ucfirst($status); ?></div>
                                        <div class="stat-number"><?php echo number_format($count); ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-box">
                                    <h3>📌 วิธีการทำงาน</h3>
                                    <ol>
                                        <li>ระบบจะดึงสินค้าที่ active และมีรูปอยู่แล้ว</li>
                                        <li>ค้นหาอัตโนมัติจาก TMDb ตามชื่อสินค้า</li>
                                        <li>แสดงผลให้เลือกว่าตรงกับเรื่องไหน</li>
                                        <li>เลือกรูปที่ต้องการ (สูงสุด 10 รูป)</li>
                                        <li>ระบบจะปรับขนาดอัตโนมัติตามประเภท</li>
                                    </ol>
                                </div>
                                
                                <div style="text-align: center;">
                                    <a href="<?php echo site_url('quiz/products_migration/start_batch'); ?>" class="btn-start">
                                        🚀 เริ่ม Migration Batch ใหม่
                                    </a>
                                </div>
                                
                                <?php if(!empty($recent_batches)): ?>
                                <h3>Batch ล่าสุด</h3>
                                <table class="batch-table">
                                    <thead>
                                        <tr>
                                            <th>Batch ID</th>
                                            <th>วันที่เริ่ม</th>
                                            <th>วันที่สิ้นสุด</th>
                                            <th>จำนวน</th>
                                            <th>เสร็จแล้ว</th>
                                            <th>สถานะ</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($recent_batches as $batch): ?>
                                        <tr>
                                            <td><?php echo substr($batch['batch_id'], 0, 14); ?>...</td>
                                            <td><?php echo $batch['start_date'] ? date('d/m/Y H:i', strtotime($batch['start_date'])) : '-'; ?></td>
                                            <td><?php echo $batch['end_date'] ? date('d/m/Y H:i', strtotime($batch['end_date'])) : '-'; ?></td>
                                            <td><?php echo number_format($batch['total']); ?></td>
                                            <td><?php echo number_format($batch['completed']); ?></td>
                                            <td>
                                                <?php 
                                                $percent_complete = $batch['total'] > 0 ? 
                                                                   round(($batch['completed'] / $batch['total']) * 100) : 0;
                                                $status_class = $percent_complete == 100 ? 'completed' : 
                                                               ($percent_complete > 0 ? 'pending' : 'error');
                                                ?>
                                                <span class="status-badge status-<?php echo $status_class; ?>">
                                                    <?php echo $percent_complete; ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo site_url('quiz/products_migration/batch/' . $batch['batch_id']); ?>">
                                                    ดูรายละเอียด
                                                </a>
                                                <?php if($percent_complete < 100): ?>
                                                | <a href="<?php echo site_url('quiz/products_migration/process/0?batch_id=' . $batch['batch_id']); ?>">
                                                    ทำต่อ
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                                
                                <div style="margin-top: 30px;">
                                    <a href="<?php echo site_url('quiz/products_images_test'); ?>" style="color: #666;">
                                        ← กลับหน้าจัดการรูปภาพ
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>