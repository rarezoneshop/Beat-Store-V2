#!/usr/bin/env python3
"""
Quick script to add sample beat products to WooCommerce
Run: python3 add_sample_products.py
"""

from woocommerce import API
import os
from dotenv import load_dotenv

load_dotenv('/app/backend/.env')

wcapi = API(
    url=os.environ['WOOCOMMERCE_URL'],
    consumer_key=os.environ['WOOCOMMERCE_CONSUMER_KEY'],
    consumer_secret=os.environ['WOOCOMMERCE_CONSUMER_SECRET'],
    version="wc/v3",
    timeout=30
)

# Sample beats with free audio URLs for testing
sample_beats = [
    {
        "name": "Dark Trap Beat",
        "description": "Hard-hitting trap beat with dark melodies and 808s",
        "genre": "Trap",
        "bpm": "140",
        "mood": "Dark",
        "key": "Am",
        "audio_url": "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3"
    },
    {
        "name": "Chill Lo-Fi Beat",
        "description": "Relaxing lo-fi beat perfect for studying or relaxing",
        "genre": "Lo-Fi",
        "bpm": "85",
        "mood": "Chill",
        "key": "C",
        "audio_url": "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3"
    },
    {
        "name": "Uplifting Pop Beat",
        "description": "Energetic pop beat with catchy melodies",
        "genre": "Pop",
        "bpm": "120",
        "mood": "Uplifting",
        "key": "G",
        "audio_url": "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3"
    },
    {
        "name": "Aggressive Drill Beat",
        "description": "Heavy drill beat with hard-hitting drums",
        "genre": "Drill",
        "bpm": "145",
        "mood": "Aggressive",
        "key": "F#m",
        "audio_url": "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3"
    },
    {
        "name": "Smooth R&B Beat",
        "description": "Smooth R&B instrumental with soulful vibes",
        "genre": "R&B",
        "bpm": "90",
        "mood": "Smooth",
        "key": "Dm",
        "audio_url": "https://www.soundhelix.com/examples/mp3/SoundHelix-Song-5.mp3"
    }
]

print("Adding sample products to WooCommerce...")
print("-" * 50)

for beat in sample_beats:
    try:
        # Create variable product
        product_data = {
            "name": beat["name"],
            "type": "variable",
            "status": "publish",
            "description": beat["description"],
            "meta_data": [
                {"key": "genre", "value": beat["genre"]},
                {"key": "bpm", "value": beat["bpm"]},
                {"key": "mood", "value": beat["mood"]},
                {"key": "key", "value": beat["key"]},
                {"key": "audio_url", "value": beat["audio_url"]}
            ],
            "attributes": [
                {
                    "name": "License Type",
                    "variation": True,
                    "visible": True,
                    "options": ["Basic", "Premium", "Exclusive"]
                }
            ]
        }
        
        response = wcapi.post("products", product_data)
        
        if response.status_code not in [200, 201]:
            print(f"❌ Failed to create {beat['name']}: {response.text}")
            continue
            
        product = response.json()
        product_id = product['id']
        
        # Create license variations
        variations_data = [
            {
                "regular_price": "29.99",
                "description": "MP3 lease, unlimited streams",
                "attributes": [{"name": "License Type", "option": "Basic"}]
            },
            {
                "regular_price": "79.99",
                "description": "WAV + Stems, unlimited distribution",
                "attributes": [{"name": "License Type", "option": "Premium"}]
            },
            {
                "regular_price": "299.99",
                "description": "Full exclusive rights, all files included",
                "attributes": [{"name": "License Type", "option": "Exclusive"}]
            }
        ]
        
        for var_data in variations_data:
            var_response = wcapi.post(f"products/{product_id}/variations", var_data)
            if var_response.status_code not in [200, 201]:
                print(f"  ⚠️ Warning: Failed to create variation for {beat['name']}")
        
        print(f"✅ Created: {beat['name']} (ID: {product_id})")
        print(f"   Genre: {beat['genre']} | BPM: {beat['bpm']} | Mood: {beat['mood']} | Key: {beat['key']}")
        
    except Exception as e:
        print(f"❌ Error creating {beat['name']}: {str(e)}")

print("-" * 50)
print("✅ Sample products added successfully!")
print("\nNote: These products use sample audio URLs.")
print("Replace 'audio_url' meta field with your actual audio file URLs.")
