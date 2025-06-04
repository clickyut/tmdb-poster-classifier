<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Start Migration - TMDb</title>
    <link href="<?php echo base_url(); ?>css/style-admin.css" rel="stylesheet" type="text/css" />
    <style>
        .start-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .info-label {
            font-weight: bold;
            color: #666;
        }
        .info-value {
            color: #360;
            font-size: 18px;
        }
        .settings-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .btn-start {
            display: block;
            width: 100%;
            padding: 15px;
            background: #e74c3c;
            color: white;
            font-size: 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 30px;
        }
        .btn-start:hover {
            background: #c0392b;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="start-container">
        <h2 style="text-align: center; color: #360;">เริ่ม Migration Batch ใหม่</h2>
        
        <div class="info-item">
            <span class="info-label">สินค้าทั้งหมดที่รอ Migration:</span>
            <span class="info-value"><?php echo number_format($total_products); ?> รายการ</span>
        </div>
        
        <div class="info-item">
            <span class="info-label">จำนวนต่อ Batch:</span>
            <span class="info-value"><?php echo $batch_size; ?> รายการ</span>
        </div>
        
        <div class="info-item">
            <span class="info-label">จำนวน Batch ทั้งหมด:</span>
            <span class="info-value"><?php echo ceil($total_products / $batch_size); ?> Batch</span>
        </div>
        
        <div class="settings-box">
            <h4>การตั้งค่าปัจจุบัน</h4>
            <p>✓ ค้นหาอัตโนมัติ: <?php echo $auto_search ? 'เปิด' : 'ปิด'; ?></p>
            <p>✓ จำนวนรูปสูงสุด: 10 รูป/สินค้า</p>
            <p>✓ ปรับขนาดอัตโนมัติตามประเภท</p>
        </div>
        
        <div class="warning">
            <strong>⚠️ คำแนะนำ:</strong><br>
            - ควรทำทีละ Batch เพื่อตรวจสอบความถูกต้อง<br>
            - ระบบจะบันทึกสถานะ สามารถหยุดและทำต่อได้<br>
            - ใช้เวลาประมาณ 1-2 นาทีต่อสินค้า
        </div>
        
        <form action="<?php echo site_url('quiz/products_migration/process/0'); ?>" method="get">
            <button type="submit" class="btn-start">
                🚀 เริ่มดำเนินการ Batch แรก
            </button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="<?php echo site_url('quiz/products_migration'); ?>" style="color: #666;">
                ← ยกเลิก
            </a>
        </div>
    </div>
</body>
</html>