<script>
// === Initialize Variables ===
var selectedTmdb = null;
var selectedImages = [];
var deleteMode = false;
var selectedForDelete = [];

// === Document Ready ===
$(document).ready(function() {
    console.log('Page loaded, jQuery version:', $.fn.jquery);
    
    addDeleteModeElements();
    rebindDeleteButtons();
    
    // Initialize Sortable
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
                
                $.post(ajaxUrls.updateOrder, {
                    order: order
                });
            }
        });
        
        window.sortableInstance = sortable;
    }
    
    // Handle file upload form
    $('#uploadForm').submit(function(e) {
        e.preventDefault();
        
        var fileInput = document.getElementById('imageFile');
        if (!fileInput.files || !fileInput.files[0]) {
            alert('กรุณาเลือกไฟล์รูปภาพ');
            return false;
        }
        
        var file = fileInput.files[0];
        
        // Validate file size (10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert('ไฟล์ใหญ่เกินไป ขนาดสูงสุด 10MB');
            return false;
        }
        
        // Validate file type
        if (!file.type.match(/^image\/(jpeg|jpg|png|gif)$/)) {
            alert('รองรับเฉพาะไฟล์รูปภาพ JPG, PNG, GIF เท่านั้น');
            return false;
        }
        
        // Show progress
        $('#uploadProgress').show();
        $('#progressText').text('กำลังอัพโหลด...');
        
        // Create FormData
        var formData = new FormData();
        formData.append('image_file', file);
        formData.append('products_id', productsId);
        
        // Use vanilla JavaScript for upload since jQuery 1.3 doesn't support FormData well
        var xhr = new XMLHttpRequest();
        
        // Progress handler
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                var percentComplete = (e.loaded / e.total) * 100;
                if (percentComplete >= 100) {
                    $('#progressBar').css('width', '100%');
                    $('#progressText').text('อัพโหลดเสร็จ กำลังประมวลผลรูปภาพ...');
                } else {
                    $('#progressBar').css('width', percentComplete + '%');
                    $('#progressText').text('กำลังอัพโหลด... ' + Math.round(percentComplete) + '%');
                }
            }
        });
        
        // Response handler
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = xhr.responseText;
                        var jsonMatch = response.match(/\{.*\}$/);
                        if (jsonMatch) {
                            var data = JSON.parse(jsonMatch[0]);
                            
                            if (data.error) {
                                alert('เกิดข้อผิดพลาด: ' + data.error);
                                closeUploadModal();
                            } else if (data.success) {
                                $('#progressText').text('อัพโหลดสำเร็จ!');
                                setTimeout(function() {
                                    closeUploadModal();
                                    location.reload();
                                }, 1000);
                            } else {
                                alert('เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ');
                                closeUploadModal();
                            }
                        } else {
                            var data = JSON.parse(response);
                            
                            if (data.error) {
                                alert('เกิดข้อผิดพลาด: ' + data.error);
                                closeUploadModal();
                            } else if (data.success) {
                                $('#progressText').text('อัพโหลดสำเร็จ!');
                                setTimeout(function() {
                                    closeUploadModal();
                                    location.reload();
                                }, 1000);
                            } else {
                                alert('เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ');
                                closeUploadModal();
                            }
                        }
                    } catch(e) {
                        console.error('JSON parse error:', e);
                        console.log('Raw response:', xhr.responseText);
                        
                        var responseText = xhr.responseText;
                        var lastBraceIndex = responseText.lastIndexOf('}');
                        if (lastBraceIndex !== -1) {
                            var jsonStart = responseText.lastIndexOf('{', lastBraceIndex);
                            if (jsonStart !== -1) {
                                try {
                                    var cleanJson = responseText.substring(jsonStart, lastBraceIndex + 1);
                                    var data = JSON.parse(cleanJson);
                                    
                                    if (data.success) {
                                        $('#progressText').text('อัพโหลดสำเร็จ!');
                                        setTimeout(function() {
                                            closeUploadModal();
                                            location.reload();
                                        }, 1000);
                                        return;
                                    }
                                } catch(e2) {
                                    // ล้มเหลวในการดึง JSON
                                }
                            }
                        }
                        
                        alert('เกิดข้อผิดพลาดในการประมวลผล');
                        closeUploadModal();
                    }
                } else {
                    alert('เกิดข้อผิดพลาดในการอัพโหลด: HTTP ' + xhr.status);
                    closeUploadModal();
                }
            }
        };
        
        // Send request
        xhr.open('POST', ajaxUrls.uploadImage);
        xhr.send(formData);
        
        return false;
    });
});

// === Image Management Functions ===
window.deleteImage = function(imageId) {
    if (window.deletingImage) {
        console.log('Already deleting, skip duplicate call');
        return false;
    }
    
    if (!confirm('ต้องการลบรูปนี้?')) return false;
    
    window.deletingImage = true;
    console.log('Deleting image ID:', imageId);
    
    jQuery.ajax({
        url: ajaxUrls.deleteImage,
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
        url: ajaxUrls.setMainProductImage,
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
        url: ajaxUrls.setPrimary,
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

// === Delete Mode Functions ===
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
                url: ajaxUrls.deleteImage,
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
                '<span style="color: #666; margin-right: 10px;">จำนวนรูปเพิ่มเติม: ' + currentImagesCount + ' / ' + maxAdditionalImages + '</span>' +
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

// === TMDb Functions ===
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
        url: ajaxUrls.searchTmdb,
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
                var poster = item.poster_url || noImageUrl;
                
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
    
    // สำหรับซีรีย์ ให้แสดง Season selection ก่อน
    if (type === 'tv') {
        $('#search-results').html('<div class="loading">กำลังโหลดรายการ Season...</div>');
        
        $.ajax({
            url: ajaxUrls.getTvSeasons,
            type: 'POST',
            data: {
                tmdb_id: tmdbId
            },
            success: function(response) {
                console.log('Get seasons response:', response);
                
                var data;
                try {
                    data = typeof response === 'string' ? JSON.parse(response) : response;
                } catch(e) {
                    console.error('JSON parse error:', e);
                    alert('เกิดข้อผิดพลาดในการโหลดข้อมูล Season');
                    return;
                }
                
                if (data.error) {
                    alert('เกิดข้อผิดพลาด: ' + data.error);
                    return;
                }
                
                closeTmdbModal();
                showSeasonSelection(data.seasons, data.tv_info);
                
            },
            error: function(xhr, status, error) {
                console.error('Load seasons error:', xhr.responseText);
                alert('ไม่สามารถโหลดข้อมูล Season ได้: ' + error);
            }
        });
    } else {
        // สำหรับหนัง ดำเนินการตามปกติ
        $('#search-results').html('<div class="loading">กำลังโหลดรูปภาพ...</div>');
        
        $.ajax({
            url: ajaxUrls.getTmdbImages,
            type: 'POST',
            data: {
                tmdb_id: tmdbId,
                type: type,
                products_id: productsId
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
    
    var remainingSlots = maxAdditionalImages - currentImagesCount;
    
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
            '<p>จำนวนรูปเต็มแล้ว (' + maxAdditionalImages + ' รูป)</p>' +
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
            // Get image dimensions and quality info
            var width = poster.width || 0;
            var height = poster.height || 0;
            var sizeText = width > 0 && height > 0 ? width + 'x' + height : 'ไม่ทราบขนาด';
            
            // Determine quality level
            var qualityClass = 'quality-low';
            var qualityText = 'ต่ำ';
            if (width >= 2000) {
                qualityClass = 'quality-hd';
                qualityText = 'HD';
            } else if (width >= 1000) {
                qualityClass = 'quality-medium';
                qualityText = 'ปกติ';
            }
            
            // Language info
            var langText = poster.iso_639_1 || 'xx';
            var langName = getLanguageName(langText);
            
            htmlParts.push('<div class="image-select"');
            htmlParts.push(' data-url="' + fullUrl + '"');
            htmlParts.push(' data-index="' + index + '">');
            htmlParts.push('<img src="' + previewUrl + '" alt="">');
            htmlParts.push('<div class="check">✓</div>');
            htmlParts.push('<div class="image-info">');
            htmlParts.push('<div class="size">' + sizeText + '</div>');
            htmlParts.push('<div class="language">' + langName + '</div>');
            htmlParts.push('<div class="quality-badge ' + qualityClass + '">' + qualityText + '</div>');
            htmlParts.push('</div>');
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

// Season selection functions
function showSeasonSelection(seasons, tvInfo) {
    console.log('showSeasonSelection - seasons:', seasons, 'tvInfo:', tvInfo);
    
    // Update modal title and info
    $('#season-modal-title').text('เลือก Season - ' + tvInfo.name);
    $('#tv-show-name').text(tvInfo.name + (tvInfo.original_name && tvInfo.original_name !== tvInfo.name ? ' (' + tvInfo.original_name + ')' : ''));
    
    var detailsText = '';
    if (tvInfo.first_air_date) {
        var year = tvInfo.first_air_date.substring(0, 4);
        detailsText += 'ออกอากาศ: ' + year + ' • ';
    }
    detailsText += 'จำนวน Season: ' + tvInfo.number_of_seasons;
    $('#tv-show-details').text(detailsText);
    
    // Generate seasons grid
    var html = '';
    seasons.forEach(function(season) {
        var posterUrl = season.poster_path ? 
            'https://image.tmdb.org/t/p/w300' + season.poster_path : 
            noImageUrl;
        
        var seasonName = season.name || 'Season ' + season.season_number;
        var airYear = season.air_date ? season.air_date.substring(0, 4) : '';
        
        html += '<div class="season-item" onclick="selectSeason(' + season.season_number + ', \'' + seasonName.replace(/'/g, "\\'") + '\')">';
        html += '<img src="' + posterUrl + '" alt="' + seasonName + '">';
        html += '<div class="season-name">' + seasonName + '</div>';
        if (airYear) {
            html += '<div class="season-info">ปี ' + airYear + '</div>';
        }
        html += '<div class="season-episodes">' + season.episode_count + ' ตอน</div>';
        html += '</div>';
    });
    
    $('#season-grid').html(html);
    $('#seasonModal').show();
}

function selectSeason(seasonNumber, seasonName) {
    console.log('selectSeason - Season:', seasonNumber, 'Name:', seasonName);
    
    // Save season info to selectedTmdb
    selectedTmdb.season_number = seasonNumber;
    selectedTmdb.season_name = seasonName;
    
    closeSeasonModal();
    
    // Now load images for this specific season
    $.ajax({
        url: ajaxUrls.getTmdbImages,
        type: 'POST',
        data: {
            tmdb_id: selectedTmdb.id,
            type: selectedTmdb.type,
            season: seasonNumber,
            products_id: productsId
        },
        success: function(response) {
            console.log('Get season images response:', response);
            
            var data;
            try {
                data = typeof response === 'string' ? JSON.parse(response) : response;
            } catch(e) {
                console.error('JSON parse error:', e);
                alert('เกิดข้อผิดพลาดในการโหลดรูปภาพ');
                return;
            }
            
            showImageSelection(data.posters || [], data.info || {});
            
            // Save mapping with season info
            if (selectedTmdb.id && selectedTmdb.type && selectedTmdb.title) {
                saveTmdbMapping(selectedTmdb.id, selectedTmdb.type, selectedTmdb.title + ' - ' + seasonName, seasonNumber);
            }
        },
        error: function(xhr, status, error) {
            console.error('Load season images error:', xhr.responseText);
            alert('ไม่สามารถโหลดรูปภาพ Season ได้: ' + error);
        }
    });
}

function closeSeasonModal() {
    $('#seasonModal').hide();
}

// Helper function to get language name
function getLanguageName(code) {
    var languages = {
        'en': 'English',
        'th': 'ไทย',
        'ja': '日本語',
        'ko': '한국어',
        'zh': '中文',
        'fr': 'Français',
        'de': 'Deutsch',
        'es': 'Español',
        'it': 'Italiano',
        'pt': 'Português',
        'ru': 'Русский',
        'xx': 'ไม่ระบุ'
    };
    return languages[code] || code.toUpperCase();
}

function toggleImageSelection(element) {
    var $el = $(element);
    var url = $el.attr('data-url');
    var index = $el.attr('data-index');
    var remainingSlots = maxAdditionalImages - currentImagesCount;
    
    console.log('Toggle selection - Index:', index, 'URL:', url);
    console.log('Current images:', currentImagesCount, 'Max:', maxAdditionalImages, 'Remaining:', remainingSlots);
    
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
    var remainingSlots = maxAdditionalImages - currentImagesCount;
    
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
                url: ajaxUrls.addImage,
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

function saveTmdbMapping(tmdbId, type, title, seasonNumber) {
    var data = {
        products_id: productsId,
        tmdb_id: tmdbId,
        tmdb_type: type,
        tmdb_title: title
    };
    
    if (seasonNumber) {
        data.season_number = seasonNumber;
    }
    
    $.post(ajaxUrls.saveTmdbMapping, data);
}

function showUrlInput() {
    var url = prompt('วาง URL รูปภาพ (รองรับทุกเว็บไซต์):');
    if (url && url.trim()) {
        // แสดง loading
        var loadingDiv = $('<div id="url-loading" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 10000;"><div class="loading">กำลังดาวน์โหลดรูป...</div></div>');
        $('body').append(loadingDiv);
        
        $.ajax({
            url: ajaxUrls.addImageFromUrl,
            type: 'POST',
            data: {
                products_id: productsId,
                image_url: url
            },
            dataType: 'json',
            success: function(data) {
                $('#url-loading').remove();
                if (data.error) {
                    alert('เกิดข้อผิดพลาด: ' + data.error);
                } else {
                    alert('✅ เพิ่มรูปสำเร็จ!');
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                $('#url-loading').remove();
                alert('เกิดข้อผิดพลาด: ' + error);
            }
        });
    }
}

// File upload functions
function showFileUpload() {
    $('#uploadModal').show();
}

function closeUploadModal() {
    $('#uploadModal').hide();
    $('#uploadForm')[0].reset();
    $('#uploadProgress').hide();
    $('#progressBar').css('width', '0%');
    $('#progressText').text('');
    
    // Re-enable submit button
    $('#uploadForm input[type="submit"], #uploadForm button[type="submit"]').attr('disabled', false);
}

function captureFromCamera() {
    // For mobile devices, this will open camera
    $('#imageFile').attr('capture', 'camera');
    $('#imageFile').click();
}

// Close modals on outside click
window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
    
    // Close season modal specifically
    if (event.target.id === 'seasonModal') {
        closeSeasonModal();
    }
    
    // Close recrop modal
    if (event.target.id === 'recropModal') {
        closeRecropModal();
    }
}

// Re-crop functions
var currentRecropImageId = null;

function showRecropModal(imageId) {
    currentRecropImageId = imageId;
    
    // Get image element
    var imageElement = $('.image-item[data-id="' + imageId + '"] img');
    if (imageElement.length > 0) {
        $('#recrop-image').attr('src', imageElement.attr('src'));
        
        // Reset button styles
        $('.recrop-btn').removeClass('btn-info').addClass('btn-primary');
        $('.recrop-btn[data-position^="down_"]').removeClass('btn-primary').addClass('btn-warning');
        $('.recrop-btn[data-position="center"]').removeClass('btn-primary').addClass('btn-success');
        
        // Show default position (center) - เพราะเราไม่มีวิธีรู้ตำแหน่งจริงจากรูปที่ครอปแล้ว
        $('#current-crop-position').show();
        $('#current-position-text').text('ไม่ทราบตำแหน่งปัจจุบัน');
        $('#current-position-detail').html('<strong>คำแนะนำ:</strong> ดูจากรูปว่าหัวนักแสดงหรือชื่อเรื่องถูกตัดหรือไม่<br>• ถ้าหัวถูกตัด = เลือก "เลื่อนลง"<br>• ถ้าชื่อเรื่องด้านล่างถูกตัด = เลือก "เลื่อนขึ้น"<br>• ถ้าพอดีแล้ว = เลือก "ตรงกลาง"');
        
        $('#recropModal').show();
    }
}

function closeRecropModal() {
    $('#recropModal').hide();
    currentRecropImageId = null;
}

function recropImage(position) {
    if (!currentRecropImageId) {
        alert('เกิดข้อผิดพลาด: ไม่พบ ID รูปภาพ');
        return;
    }
    
    // แสดงตำแหน่งที่กำลังจะครอป
    var posText = 'ตรงกลาง';
    var detailText = '';
    
    if (position.startsWith('up_')) {
        var percent = position.replace('up_', '');
        posText = 'เลื่อนขึ้น ' + percent + '%';
        var topCrop = 50 - parseInt(percent);
        var bottomCrop = 50 + parseInt(percent);
        detailText = ' (ตัดบน ' + topCrop + '% : ตัดล่าง ' + bottomCrop + '%)';
    } else if (position.startsWith('down_')) {
        var percent = position.replace('down_', '');
        posText = 'เลื่อนลง ' + percent + '%';
        var topCrop = 50 + parseInt(percent);
        var bottomCrop = 50 - parseInt(percent);
        detailText = ' (ตัดบน ' + topCrop + '% : ตัดล่าง ' + bottomCrop + '%)';
    } else {
        detailText = ' (ตัดบน 50% : ตัดล่าง 50%)';
    }
    
    if (!confirm('ต้องการปรับตำแหน่งเป็น "' + posText + '"' + detailText + ' ใช่หรือไม่?')) {
        return;
    }
    
    // Show loading
    $('#recropProgress').show();
    $('.recrop-btn').attr('disabled', true);
    
    // jQuery 1.3 compatible AJAX
    jQuery.ajax({
        url: ajaxUrls.recropImage,
        type: 'POST',
        data: {
            image_id: currentRecropImageId,
            position: position
        },
        dataType: 'text', // เปลี่ยนเป็น text ก่อน แล้วค่อย parse
        success: function(response) {
            console.log('Recrop raw response:', response);
            
            $('#recropProgress').hide();
            $('.recrop-btn').attr('disabled', false);
            
            try {
                // jQuery 1.3 ไม่มี parseJSON ต้องใช้ eval หรือ JSON.parse
                var data;
                if (window.JSON && window.JSON.parse) {
                    // ถ้ามี native JSON.parse ใช้ตัวนี้
                    data = JSON.parse(response);
                } else {
                    // ถ้าไม่มี ใช้ eval (ระวังเรื่อง security)
                    data = eval('(' + response + ')');
                }
                console.log('Parsed data:', data);
                
                if (data.error) {
                    alert('เกิดข้อผิดพลาด: ' + data.error);
                } else if (data.success) {
                    alert('✅ ปรับตำแหน่งรูปสำเร็จ!');
                    // Reload to see new image
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุ');
                }
            } catch(e) {
                console.error('JSON parse error:', e);
                console.log('Response text:', response);
                
                // ลองหา JSON ในส่วนท้าย (กรณีมี PHP warning)
                var jsonMatch = response.match(/\{.*\}$/);
                if (jsonMatch) {
                    try {
                        var data;
                        if (window.JSON && window.JSON.parse) {
                            data = JSON.parse(jsonMatch[0]);
                        } else {
                            data = eval('(' + jsonMatch[0] + ')');
                        }
                        if (data.success) {
                            alert('✅ ปรับตำแหน่งรูปสำเร็จ!');
                            location.reload();
                            return;
                        }
                    } catch(e2) {
                        // ไม่สามารถ parse ได้
                    }
                }
                
                alert('เกิดข้อผิดพลาดในการประมวลผล');
            }
        },
        error: function(xhr, status, error) {
            $('#recropProgress').hide();
            $('.recrop-btn').attr('disabled', false);
            
            console.error('Re-crop AJAX error:');
            console.error('Status:', xhr.status);
            console.error('Error:', error);
            console.error('Response Text:', xhr.responseText);
            
            if (xhr.status === 0) {
                alert('เกิดข้อผิดพลาด: ไม่สามารถเชื่อมต่อกับ server ได้');
            } else if (xhr.status == 404) {
                alert('เกิดข้อผิดพลาด: ไม่พบ URL ที่ต้องการ');
            } else if (xhr.status == 500) {
                alert('เกิดข้อผิดพลาด: Server error');
            } else {
                alert('เกิดข้อผิดพลาด: ' + (error || 'Unknown error'));
            }
        }
    });
}
</script>