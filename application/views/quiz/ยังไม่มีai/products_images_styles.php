<style>
    .container { padding: 20px; }
    .product-header {
        background: #f5f5f5;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    .product-header h2 { margin: 0 0 10px 0; color: #360; }
    .product-info { display: flex; gap: 30px; }
    .product-info div { flex: 1; }
    .product-info label { font-weight: bold; color: #666; }
    
    /* Original Image Section */
    .original-image-section {
        background: #fff3cd;
        border: 2px solid #ffc107;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }
    .original-image-section h3 {
        color: #856404;
        margin: 0 0 15px 0;
    }
    .original-image-container {
        display: flex;
        gap: 20px;
        align-items: start;
    }
    .original-image-box {
        flex-shrink: 0;
    }
    .original-image-box img {
        width: 200px;
        height: auto;
        max-height: 300px;
        object-fit: contain;
        border-radius: 8px;
        border: 3px solid #ffc107;
        background: #f5f5f5;
    }
    .original-image-info {
        flex: 1;
    }
    .original-image-info p {
        margin: 5px 0;
        color: #856404;
    }
    .warning-box {
        background: #ff6b6b;
        color: white;
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
    }
    
    /* Image Grid */
    .images-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .image-item {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 10px;
        background: white;
        position: relative;
        transition: all 0.3s ease;
    }
    .image-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .image-item.primary { border-color: #37b24d; }
    .image-item.sortable-ghost { opacity: 0.4; }
    .image-item img {
        width: 100%;
        height: auto;
        max-height: 280px;
        object-fit: contain;
        border-radius: 4px;
        cursor: move;
        background: #f5f5f5;
    }
    .image-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        gap: 5px;
        flex-wrap: wrap;
    }
    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
    }
    .btn-primary { background: #360; color: white; }
    .btn-primary:hover { background: #470; }
    .btn-danger { background: #e74c3c; color: white; }
    .btn-danger:hover { background: #c0392b; }
    .btn-success { background: #37b24d; color: white; }
    .btn-success:hover { background: #2f9e41; }
    .btn-warning { background: #f39c12; color: white; }
    .btn-warning:hover { background: #e67e22; }
    .btn-default { background: #95a5a6; color: white; }
    .btn-default:hover { background: #7f8c8d; }
    .btn-info { background: #3498db; color: white; }
    .btn-info:hover { background: #2980b9; }
    .btn-set-main { 
        background: #e67e22; 
        color: white; 
        font-size: 11px;
        padding: 5px 10px;
    }
    .btn-set-main:hover { 
        background: #d35400; 
        transform: scale(1.05);
    }
    
    /* Upload Section */
    .upload-section {
        background: #f9f9f9;
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
        margin-bottom: 30px;
    }
    .upload-options {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
    }
    .upload-option {
        flex: 1;
        max-width: 300px;
        padding: 20px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .upload-option:hover {
        border-color: #360;
        transform: translateY(-2px);
    }
    
    /* TMDb Search Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
    }
    .modal-content {
        background: white;
        margin: 5% auto;
        padding: 20px;
        width: 80%;
        max-width: 800px;
        border-radius: 8px;
        max-height: 80vh;
        overflow-y: auto;
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .close:hover { color: #000; }
    
    .search-box {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    .search-box input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .search-results {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    .result-item {
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .result-item:hover {
        border-color: #360;
        transform: scale(1.02);
    }
    .result-item img {
        width: 100%;
        height: auto;
        max-height: 200px;
        object-fit: contain;
        border-radius: 4px;
        background: #f5f5f5;
    }
    .result-item .title {
        font-size: 12px;
        margin-top: 5px;
        font-weight: bold;
    }
    .result-item .year {
        font-size: 11px;
        color: #666;
    }
    
    /* Image Selection Modal */
    .modal-content.image-selection {
        display: flex;
        max-width: 1200px;
    }
    .modal-sidebar {
        position: sticky;
        top: 20px;
        width: 300px;
        padding: 20px;
        background: #f5f5f5;
        border-right: 1px solid #ddd;
        height: fit-content;
        flex-shrink: 0;
    }
    .modal-main {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
    }
    .selection-info {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .selection-info h4 {
        margin: 0 0 10px 0;
        color: #360;
    }
    .selection-count {
        font-size: 24px;
        font-weight: bold;
        color: #360;
        margin: 10px 0;
    }
    .btn-add-fixed {
        width: 100%;
        padding: 15px;
        font-size: 16px;
        font-weight: bold;
        background: #360;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-add-fixed:hover {
        background: #470;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    .image-select.disabled {
        opacity: 0.5;
        cursor: not-allowed !important;
        pointer-events: none;
    }
    .btn-add-fixed:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }
    .image-select {
        position: relative;
        border: 2px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
        cursor: pointer;
    }
    .image-select:hover { border-color: #360; }
    .image-select.selected { border-color: #37b24d; }
    .image-select img {
        width: 100%;
        height: auto;
        max-height: 300px;
        object-fit: contain;
        background: #f5f5f5;
    }
    .image-select .check {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background: #37b24d;
        color: white;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
    }
    .image-select.selected .check { display: flex; }
    .image-info {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 8px;
        font-size: 12px;
        line-height: 1.3;
    }
    .image-info .size {
        font-weight: bold;
        color: #ffc107;
    }
    .image-info .language {
        font-size: 11px;
        color: #ccc;
    }
    .image-info .quality-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 10px;
        font-weight: bold;
        margin-top: 2px;
    }
    .quality-hd { background: #28a745; }
    .quality-medium { background: #ffc107; color: #000; }
    .quality-low { background: #dc3545; }
    
    /* Current position indicator */
    .recrop-btn.btn-info {
        background: #17a2b8 !important;
        border-color: #17a2b8 !important;
        box-shadow: 0 0 0 2px rgba(23, 162, 184, 0.5);
        font-weight: bold;
    }
    .recrop-btn.btn-info:hover {
        background: #138496 !important;
    }
    
    /* Season Selection Modal */
    .season-item {
        border: 2px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: white;
        text-align: center;
    }
    .season-item:hover {
        border-color: #360;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .season-item img {
        width: 100%;
        max-width: 150px;
        height: auto;
        max-height: 225px;
        object-fit: contain;
        border-radius: 4px;
        background: #f5f5f5;
        margin-bottom: 10px;
    }
    .season-name {
        font-weight: bold;
        color: #360;
        margin-bottom: 5px;
    }
    .season-info {
        font-size: 12px;
        color: #666;
        line-height: 1.4;
    }
    .season-episodes {
        background: #e3f2fd;
        color: #1976d2;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: bold;
        margin-top: 8px;
        display: inline-block;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
    }
    .badge-type {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: bold;
        margin-left: 10px;
    }
    .badge-movie { background: #e74c3c; color: white; }
    .badge-tv { background: #3498db; color: white; }
    
    /* Delete mode styles */
    #images-grid .image-item {
        position: relative;
    }
    .delete-checkbox {
        position: absolute;
        top: 10px;
        left: 10px;
        width: 24px;
        height: 24px;
        background: white;
        border: 2px solid #333;
        border-radius: 4px;
        display: none;
        cursor: pointer;
        z-index: 10;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    body.delete-mode .delete-checkbox {
        display: block !important;
    }
    .delete-checkbox:checked {
        background: #d9534f;
        border-color: #d9534f;
    }
    .delete-checkbox:checked:after {
        content: '✓';
        color: white;
        position: absolute;
        top: -2px;
        left: 5px;
        font-weight: bold;
        font-size: 16px;
    }
    body.delete-mode .image-item.selected-for-delete {
        opacity: 0.6;
        border: 3px solid #d9534f;
    }
    .delete-toolbar {
        background: #f8f8f8;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
        display: none;
        align-items: center;
        justify-content: space-between;
    }
    body.delete-mode .delete-toolbar {
        display: flex;
    }
    /* Hide action buttons in delete mode */
    body.delete-mode .image-actions {
        display: none;
    }
    body.delete-mode .image-item img {
        cursor: default;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .modal-content.image-selection {
            flex-direction: column;
        }
        .modal-sidebar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: auto;
            border-right: none;
            border-top: 1px solid #ddd;
            z-index: 100;
            display: flex;
            align-items: center;
            padding: 10px 20px;
        }
        .selection-info {
            margin: 0;
            padding: 10px;
            flex: 1;
        }
        .selection-info h4 {
            display: none;
        }
        .selection-count {
            font-size: 16px;
            margin: 0;
        }
        .btn-add-fixed {
            width: auto;
            padding: 10px 20px;
            margin-left: 10px;
        }
        .modal-main {
            padding-bottom: 80px;
        }
    }
    #floatingPanel {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #360;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        display: none;
        z-index: 1000;
    }
    #floatingPanel.show {
        display: block;
    }
    #floatingPanel button {
        background: white;
        color: #360;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 10px;
    }
</style>