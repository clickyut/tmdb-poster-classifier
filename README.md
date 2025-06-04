# TMDb Poster Classifier

ระบบ AI สำหรับเลือกภาพ poster จาก TMDb อัตโนมัติ

## วัตถุประสงค์
- แยกภาพที่เหมาะกับปก DVD/Bluray (OK) กับภาพที่มีข้อความ 4K/Dolby/วันที่ (NG)
- ใช้ Deep Learning (MobileNetV2)
- ลดเวลาการเลือกภาพจาก TMDb

## โครงสร้างโปรเจค
tmdb-poster-classifier/
├── dataset/
│   ├── ok/         # ภาพที่เหมาะกับปก
│   └── ng/         # ภาพที่มีข้อความไม่พึงประสงค์
├── models/         # เก็บ trained models
├── label_tool.py   # เครื่องมือ label ภาพ
├── train.py        # script สำหรับ train model
├── predict.py      # script สำหรับทำนายภาพใหม่
└── web_app.py      # Flask web interface

## Requirements
- Python 3.8+
- PyTorch
- Flask
- Pillow
