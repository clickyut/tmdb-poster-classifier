# TMDb Poster Classifier

AI system สำหรับเลือกภาพ poster จาก TMDb อัตโนมัติ

## Features
- แยกภาพ OK/NG (ภาพปกปกติ vs มีข้อความ 4K/Dolby)
- ใช้ MobileNetV2 
- Confidence threshold 70%

## Structure
tmdb-poster-classifier/
├── dataset/
│   ├── ok/
│   └── ng/
├── models/
├── train.py
├── predict.py
└── label_tool.py

## Usage
1. Label images: `python label_tool.py`
2. Train model: `python train.py`
3. Predict: `python predict.py image.jpg`
