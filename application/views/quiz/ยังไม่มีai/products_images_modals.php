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

<!-- Season Selection Modal -->
<div id="seasonModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close" onclick="closeSeasonModal()">&times;</span>
        <h3 id="season-modal-title">เลือก Season</h3>
        
        <div id="season-info" style="background: #f0f8ff; padding: 15px; margin-bottom: 20px; border-radius: 8px; border-left: 4px solid #2196F3;">
            <p id="tv-show-name" style="font-weight: bold; margin: 0 0 5px 0;"></p>
            <p id="tv-show-details" style="margin: 0; color: #666; font-size: 14px;"></p>
        </div>
        
        <div id="season-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
            <!-- Seasons will be populated here -->
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <button class="btn btn-default" onclick="closeSeasonModal()" style="padding: 10px 20px;">
                ยกเลิก
            </button>
        </div>
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

<!-- File Upload Modal -->
<div id="uploadModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <span class="close" onclick="closeUploadModal()">&times;</span>
        <h3>อัพโหลดรูปภาพ</h3>
        
        <form id="uploadForm" enctype="multipart/form-data">
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; font-weight: bold;">เลือกไฟล์รูปภาพ:</label>
                <input type="file" id="imageFile" name="image_file" accept="image/*" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <small style="color: #666; display: block; margin-top: 5px;">
                    รองรับไฟล์: JPG, PNG, GIF | ขนาดสูงสุด: 10MB
                </small>
            </div>
            
            <!-- Camera option for mobile -->
            <div style="margin-bottom: 20px;">
                <button type="button" class="btn btn-warning" onclick="captureFromCamera()" style="width: 100%; padding: 15px;">
                    📷 ถ่ายรูปจากกล้อง
                </button>
            </div>
            
            <div style="text-align: center;">
                <button type="submit" class="btn btn-primary" style="padding: 15px 30px; margin-right: 10px;">
                    ⬆️ อัพโหลด
                </button>
                <button type="button" class="btn btn-default" onclick="closeUploadModal()" style="padding: 15px 30px;">
                    ยกเลิก
                </button>
                <div style="margin-top: 10px; font-size: 12px; color: #666;">
                    <strong>หมายเหตุ:</strong> รูปใหญ่อาจใช้เวลาประมวลผลนานกว่า 1-2 นาที
                </div>
            </div>
        </form>
        
        <div id="uploadProgress" style="display: none; margin-top: 20px;">
            <div style="background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                <div id="progressBar" style="background: #360; height: 20px; width: 0%; transition: width 0.3s;"></div>
            </div>
            <p id="progressText" style="text-align: center; margin-top: 10px;">กำลังอัพโหลด...</p>
        </div>
    </div>
</div>

<!-- Re-crop Modal -->
<div id="recropModal" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close" onclick="closeRecropModal()">&times;</span>
        <h3>ปรับตำแหน่งการตัดรูป</h3>
        
        <div style="background: #e3f2fd; padding: 15px; margin-bottom: 20px; border-radius: 8px; border-left: 4px solid #2196F3;">
            <p style="margin: 0;">
                <strong>ℹ️ คำแนะนำ:</strong> เลือกทิศทางที่ต้องการเลื่อนภาพ เพื่อให้หัวนักแสดงหรือชื่อเรื่องไม่ถูกตัด
            </p>
        </div>
        
        <!-- แสดงตำแหน่งปัจจุบัน -->
        <div id="current-crop-position" style="background: #fff3cd; padding: 15px; margin-bottom: 20px; border-radius: 8px; border-left: 4px solid #ffc107; display: none;">
            <p style="margin: 0; font-size: 16px;">
                <strong>📍 ตำแหน่งปัจจุบัน:</strong> <span id="current-position-text" style="color: #856404; font-weight: bold;"></span>
            </p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #666;">
                <span id="current-position-detail"></span>
            </p>
        </div>
        
        <div id="recrop-preview" style="text-align: center; margin-bottom: 20px;">
            <img id="recrop-image" src="" alt="" style="max-width: 100%; height: auto; border: 2px solid #ddd; border-radius: 8px;">
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 20px;">
            <div style="grid-column: span 4; background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <strong>สูตรการตัด:</strong> ตัดบน = 50% - ค่า | ตัดล่าง = 50% + ค่า
                <br><small>ตัวอย่าง: เลื่อนขึ้น 20% = ตัดบน 30% (50-20) และตัดล่าง 70% (50+20)</small>
            </div>
            
            <!-- เลื่อนขึ้น -->
            <button class="btn btn-primary recrop-btn" data-position="up_50" onclick="recropImage('up_50')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 50%
            </button>
            <button class="btn btn-primary recrop-btn" data-position="up_45" onclick="recropImage('up_45')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 45%
            </button>
            <button class="btn btn-primary recrop-btn" data-position="up_40" onclick="recropImage('up_40')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 40%
            </button>
            <button class="btn btn-primary recrop-btn" data-position="up_35" onclick="recropImage('up_35')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 35%
            </button>
            
            <button class="btn btn-primary recrop-btn" data-position="up_30" onclick="recropImage('up_30')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 30%
            </button>
            <button class="btn btn-primary recrop-btn" data-position="up_25" onclick="recropImage('up_25')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 25%
            </button>
            <button class="btn btn-primary recrop-btn" data-position="up_20" onclick="recropImage('up_20')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 20%
            </button>
            <button class="btn btn-primary recrop-btn" data-position="up_15" onclick="recropImage('up_15')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 15%
            </button>
            
            <button class="btn btn-primary recrop-btn" data-position="up_10" onclick="recropImage('up_10')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 10%
            </button>
            <button class="btn btn-primary recrop-btn" data-position="up_5" onclick="recropImage('up_5')" style="padding: 12px; font-size: 13px;">
                ⬆️ ขึ้น 5%
            </button>
            
            <!-- ตรงกลาง -->
            <button class="btn btn-success recrop-btn" data-position="center" onclick="recropImage('center')" style="padding: 12px; grid-column: span 2; font-size: 14px; font-weight: bold;">
                ⚖️ ตรงกลาง
            </button>
            
            <!-- เลื่อนลง -->
            <button class="btn btn-warning recrop-btn" data-position="down_5" onclick="recropImage('down_5')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 5%
            </button>
            <button class="btn btn-warning recrop-btn" data-position="down_10" onclick="recropImage('down_10')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 10%
            </button>
            
            <button class="btn btn-warning recrop-btn" data-position="down_15" onclick="recropImage('down_15')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 15%
            </button>
            <button class="btn btn-warning recrop-btn" data-position="down_20" onclick="recropImage('down_20')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 20%
            </button>
            <button class="btn btn-warning recrop-btn" data-position="down_25" onclick="recropImage('down_25')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 25%
            </button>
            <button class="btn btn-warning recrop-btn" data-position="down_30" onclick="recropImage('down_30')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 30%
            </button>
            
            <button class="btn btn-warning recrop-btn" data-position="down_35" onclick="recropImage('down_35')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 35%
            </button>
            <button class="btn btn-warning recrop-btn" data-position="down_40" onclick="recropImage('down_40')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 40%
            </button>
            <button class="btn btn-warning recrop-btn" data-position="down_45" onclick="recropImage('down_45')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 45%
            </button>
            <button class="btn btn-warning recrop-btn" data-position="down_50" onclick="recropImage('down_50')" style="padding: 12px; font-size: 13px;">
                ⬇️ ลง 50%
            </button>
        </div>
        
        <div style="text-align: center;">
            <button class="btn btn-default" onclick="closeRecropModal()" style="padding: 10px 30px;">
                ปิด
            </button>
        </div>
        
        <div id="recropProgress" style="display: none; margin-top: 20px;">
            <div class="loading">กำลังประมวลผล...</div>
        </div>
    </div>
</div>