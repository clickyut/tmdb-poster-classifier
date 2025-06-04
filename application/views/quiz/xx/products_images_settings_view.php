<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>TMDb System Settings</title>
    <link href="<?php echo base_url(); ?>css/style-admin.css" rel="stylesheet" type="text/css" />
    <script src="<?php echo base_url(); ?>js/jquery/jquery.js" type="text/javascript"></script>
    <style>
        .settings-form {
            max-width: 600px;
            margin: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #360;
        }
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group .description {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .btn-save {
            background: #360;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-save:hover {
            background: #470;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
                                            <div class="settings-form">
                                                <?php if(isset($message)): ?>
                                                    <div class="success-message"><?php echo $message; ?></div>
                                                <?php endif; ?>
                                                
                                                <form method="post" action="">
                                                    <h3>การตั้งค่า TMDb API</h3>
                                                    
                                                    <div class="form-group">
                                                        <label>TMDb API Key *</label>
                                                        <input type="text" name="tmdb_api_key" value="<?php echo $settings['tmdb_api_key'] ?? ''; ?>" />
                                                        <div class="description">
                                                            ไปขอ API Key ฟรีที่ <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDb Settings</a>
                                                        </div>
                                                    </div>
                                                    
                                                    <h3>การตั้งค่ารูปภาพ</h3>
                                                    
                                                    <div class="form-group">
                                                        <label>จำนวนรูปสูงสุดต่อสินค้า</label>
                                                        <input type="number" name="max_images_per_product" 
                                                               value="<?php echo $settings['max_images_per_product'] ?? '10'; ?>" 
                                                               min="1" max="20" />
                                                        <div class="description">แนะนำ 10 รูป</div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label>ขนาดรูป DVD (กว้าง x สูง)</label>
                                                        <input type="text" name="image_quality_dvd" 
                                                               value="<?php echo $settings['image_quality_dvd'] ?? '1000x1500'; ?>" />
                                                        <div class="description">สำหรับ extra 0, 1, 2</div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label>ขนาดรูป Blu-ray/4K (กว้าง x สูง)</label>
                                                        <input type="text" name="image_quality_bluray" 
                                                               value="<?php echo $settings['image_quality_bluray'] ?? '1000x1149'; ?>" />
                                                        <div class="description">สำหรับ extra 5, 6, 7, 8</div>
                                                    </div>
                                                    
                                                    <h3>การตั้งค่า Migration</h3>
                                                    
                                                    <div class="form-group">
                                                        <label>จำนวนสินค้าต่อ Batch</label>
                                                        <input type="number" name="batch_size" 
                                                               value="<?php echo $settings['batch_size'] ?? '50'; ?>" 
                                                               min="10" max="200" />
                                                        <div class="description">แนะนำ 50-100 รายการ</div>
                                                    </div>
                                                    
                                                    <div class="form-group">
                                                        <label>
                                                            <input type="checkbox" name="auto_search_enabled" value="1" 
                                                                   <?php echo ($settings['auto_search_enabled'] ?? '1') == '1' ? 'checked' : ''; ?> />
                                                            เปิดใช้งานการค้นหาอัตโนมัติ
                                                        </label>
                                                        <div class="description">ค้นหา TMDb อัตโนมัติจากชื่อสินค้า</div>
                                                    </div>
                                                    
                                                    <h3>การตั้งค่า Cache</h3>
                                                    
                                                    <div class="form-group">
                                                        <label>ระยะเวลา Cache (ชั่วโมง)</label>
                                                        <input type="number" name="cache_duration_hours" 
                                                               value="<?php echo $settings['cache_duration_hours'] ?? '168'; ?>" 
                                                               min="1" max="720" />
                                                        <div class="description">168 ชั่วโมง = 7 วัน</div>
                                                    </div>
                                                    
                                                    <div class="warning-box">
                                                        <strong>หมายเหตุ:</strong><br>
                                                        - TMDb API จำกัด request ต่อวัน<br>
                                                        - ควรใช้ Cache เพื่อลด API calls<br>
                                                        - Migration จำนวนมากควรทำเป็นช่วงๆ
                                                    </div>
                                                    
                                                    <input type="hidden" name="save_settings" value="1" />
                                                    <button type="submit" class="btn-save">บันทึกการตั้งค่า</button>
                                                    
                                                    <a href="<?php echo site_url('quiz/products_images_test'); ?>" 
                                                       style="margin-left: 20px;">← กลับ</a>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>