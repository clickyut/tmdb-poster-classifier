# clip_service.py
# บริการวิเคราะห์ภาพด้วย CLIP model (CPU-only)
# รันด้วย: python clip_service.py

from flask import Flask, request, jsonify
import torch
import clip
from PIL import Image
import requests
from io import BytesIO
import numpy as np
import re
from collections import defaultdict
import logging

# Setup logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Load CLIP model (CPU-only)
device = "cpu"
model = None
preprocess = None

def load_clip_model():
    global model, preprocess
    try:
        logger.info("Loading CLIP model...")
        model, preprocess = clip.load("ViT-B/32", device=device)
        model.eval()
        logger.info("CLIP model loaded successfully")
    except Exception as e:
        logger.error(f"Failed to load CLIP model: {e}")
        raise

# Load model on startup
load_clip_model()

# Text prompts for DVD/Blu-ray cover detection
POSITIVE_PROMPTS = [
    "movie poster",
    "film poster", 
    "theatrical poster",
    "official movie poster",
    "clean movie poster without text overlays",
    "professional movie artwork",
    "high quality film poster"
]

NEGATIVE_PROMPTS = [
    "4K Ultra HD logo",
    "Blu-ray disc logo",
    "DVD logo",
    "Dolby Atmos logo",
    "release date text",
    "coming soon text",
    "special edition banner",
    "collector's edition",
    "movie rating badge",
    "studio logos at bottom",
    "streaming service logo",
    "watermark",
    "low quality image",
    "blurry image",
    "fan art",
    "behind the scenes photo"
]

def download_image(url):
    """Download image from URL"""
    try:
        response = requests.get(url, timeout=10)
        response.raise_for_status()
        img = Image.open(BytesIO(response.content)).convert('RGB')
        return img
    except Exception as e:
        logger.error(f"Error downloading image {url}: {e}")
        return None

def analyze_image_quality(image, image_info):
    """Analyze image with CLIP model"""
    try:
        # Preprocess image
        image_tensor = preprocess(image).unsqueeze(0).to(device)
        
        # Encode text prompts
        positive_tokens = clip.tokenize(POSITIVE_PROMPTS).to(device)
        negative_tokens = clip.tokenize(NEGATIVE_PROMPTS).to(device)
        
        with torch.no_grad():
            # Get image features
            image_features = model.encode_image(image_tensor)
            
            # Get text features
            positive_features = model.encode_text(positive_tokens)
            negative_features = model.encode_text(negative_tokens)
            
            # Calculate similarities
            positive_sim = (image_features @ positive_features.T).cpu().numpy()[0]
            negative_sim = (image_features @ negative_features.T).cpu().numpy()[0]
            
        # Calculate scores
        positive_score = float(np.mean(positive_sim))
        negative_score = float(np.mean(negative_sim))
        
        # Quality score (higher is better)
        quality_score = positive_score - negative_score
        
        # Dimension score (prefer standard poster dimensions)
        width = image_info.get('width', 0)
        height = image_info.get('height', 0)
        
        dimension_score = 0
        if width > 0 and height > 0:
            aspect_ratio = width / height
            # Standard poster aspect ratio is around 0.675 (2:3)
            dimension_score = 1.0 - abs(aspect_ratio - 0.675)
            # Prefer higher resolution
            if width >= 2000:
                dimension_score += 0.3
            elif width >= 1000:
                dimension_score += 0.1
        
        # Combined score
        final_score = quality_score * 0.7 + dimension_score * 0.3
        
        return {
            'quality_score': quality_score,
            'dimension_score': dimension_score,
            'final_score': final_score,
            'positive_scores': positive_sim.tolist(),
            'negative_scores': negative_sim.tolist()
        }
        
    except Exception as e:
        logger.error(f"Error analyzing image: {e}")
        return None

@app.route('/analyze', methods=['POST'])
def analyze_images():
    """Analyze and auto-select best images"""
    try:
        data = request.json
        images = data.get('images', [])
        max_select = data.get('max_select', 10)
        
        if not images:
            return jsonify({'error': 'No images provided'}), 400
        
        logger.info(f"Analyzing {len(images)} images, max select: {max_select}")
        
        # Group images by language
        images_by_language = defaultdict(list)
        
        for img_info in images:
            language = img_info.get('language', 'xx')
            images_by_language[language].append(img_info)
        
        # Analyze images and calculate scores
        analyzed_images = []
        
        # Process in batches to manage memory
        batch_size = 10
        
        for language, lang_images in images_by_language.items():
            for i in range(0, len(lang_images), batch_size):
                batch = lang_images[i:i + batch_size]
                
                for img_info in batch:
                    url = img_info.get('url')
                    if not url:
                        continue
                    
                    # Download and analyze image
                    image = download_image(url)
                    if image is None:
                        continue
                    
                    scores = analyze_image_quality(image, img_info)
                    if scores:
                        analyzed_images.append({
                            'index': img_info['index'],
                            'language': language,
                            'scores': scores,
                            'url': url
                        })
        
        # Sort by quality score
        analyzed_images.sort(key=lambda x: x['scores']['final_score'], reverse=True)
        
        # Select best images with language priority
        selected_indices = []
        selected_languages = defaultdict(int)
        
        # Priority: English > Thai > Others > No language
        language_priority = {'en': 0, 'th': 1, 'xx': 999}
        
        # Sort by language priority first, then by score
        analyzed_images.sort(key=lambda x: (
            language_priority.get(x['language'], 100),
            -x['scores']['final_score']
        ))
        
        # Select images
        for img in analyzed_images:
            if len(selected_indices) >= max_select:
                break
            
            # Limit per language to ensure diversity
            lang = img['language']
            if lang == 'en' and selected_languages[lang] >= max_select:
                continue
            elif lang != 'en' and selected_languages[lang] >= max(3, max_select // 3):
                continue
            
            selected_indices.append(img['index'])
            selected_languages[lang] += 1
        
        # Create reasoning explanation
        reasoning = f"เลือกภาพ {len(selected_indices)} ภาพ: "
        lang_summary = []
        for lang, count in selected_languages.items():
            if count > 0:
                lang_name = {
                    'en': 'อังกฤษ',
                    'th': 'ไทย', 
                    'xx': 'ไม่ระบุภาษา'
                }.get(lang, lang.upper())
                lang_summary.append(f"{lang_name} {count} ภาพ")
        reasoning += ", ".join(lang_summary)
        
        logger.info(f"Selected {len(selected_indices)} images")
        
        return jsonify({
            'selected_indices': selected_indices,
            'reasoning': reasoning,
            'total_analyzed': len(analyzed_images)
        })
        
    except Exception as e:
        logger.error(f"Error in analyze_images: {e}")
        return jsonify({'error': str(e)}), 500

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'model_loaded': model is not None,
        'device': device
    })

if __name__ == '__main__':
    logger.info("Starting CLIP service on http://localhost:5000")
    app.run(host='0.0.0.0', port=5000, debug=False)