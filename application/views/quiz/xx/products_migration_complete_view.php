<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Migration Complete - TMDb</title>
    <link href="<?php echo base_url(); ?>css/style-admin.css" rel="stylesheet" type="text/css" />
    <style>
        .complete-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: #37b24d;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            margin: 0 auto 30px;
        }
        .btn-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }
        .btn {
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
        }
        .btn-primary {
            background: #360;
            color: white;
        }
        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="complete-container">
        <div class="success-icon">✓</div>
        
        <h2 style="color: #37b24d;">Migration เสร็จสิ้น!</h2>
        
        <p style="font-size: 18px; color: #666; margin: 20px 0;">
            ดำเนินการ Migration Batch เสร็จเรียบร้อยแล้ว
        </p>
        
        <div class="btn-group">
            <a href="<?php echo site_url('quiz/products_migration'); ?>" class="btn btn-primary">
                ดูสถิติทั้งหมด
            </a>
            <a href="<?php echo site_url('quiz/products_migration/start_batch'); ?>" class="btn btn-secondary">
                เริ่ม Batch ใหม่
            </a>
        </div>
    </div>
</body>
</html>