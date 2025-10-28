from fastapi import FastAPI, APIRouter, HTTPException
from dotenv import load_dotenv
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
import os
import logging
from pathlib import Path
from pydantic import BaseModel, Field, ConfigDict
from typing import List, Optional, Dict, Any
import uuid
from datetime import datetime, timezone
from woocommerce import API
import asyncio
from functools import lru_cache

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

# MongoDB connection
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ['DB_NAME']]

# WooCommerce API setup
wcapi = API(
    url=os.environ['WOOCOMMERCE_URL'],
    consumer_key=os.environ['WOOCOMMERCE_CONSUMER_KEY'],
    consumer_secret=os.environ['WOOCOMMERCE_CONSUMER_SECRET'],
    version="wc/v3",
    timeout=30
)

# Create the main app without a prefix
app = FastAPI()

# Create a router with the /api prefix
api_router = APIRouter(prefix="/api")


# Define Models
class CartItem(BaseModel):
    model_config = ConfigDict(extra="ignore")
    
    id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    product_id: int
    variation_id: Optional[int] = None
    name: str
    license_type: str
    price: float
    audio_url: str
    image_url: Optional[str] = None
    timestamp: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))

class CartItemCreate(BaseModel):
    product_id: int
    variation_id: Optional[int] = None
    name: str
    license_type: str
    price: float
    audio_url: str
    image_url: Optional[str] = None

class CartResponse(BaseModel):
    items: List[CartItem]
    total: float


# Add your routes to the router instead of directly to app
@api_router.get("/")
async def root():
    return {"message": "RareBeats API"}


@api_router.get("/products")
async def get_products(
    genre: Optional[str] = None,
    bpm_min: Optional[int] = None,
    bpm_max: Optional[int] = None,
    mood: Optional[str] = None,
    key: Optional[str] = None,
    page: int = 1,
    per_page: int = 50
):
    """Fetch products from WooCommerce with filters"""
    try:
        params = {
            "per_page": per_page,
            "page": page,
            "status": "publish"
        }
        
        # Fetch products from WooCommerce
        response = wcapi.get("products", params=params)
        
        if response.status_code != 200:
            raise HTTPException(status_code=response.status_code, detail="Failed to fetch products from WooCommerce")
        
        products = response.json()
        
        # Filter products based on meta data
        filtered_products = []
        for product in products:
            # Extract meta data
            meta_dict = {meta['key']: meta['value'] for meta in product.get('meta_data', [])}
            
            # Apply filters
            if genre and meta_dict.get('genre', '').lower() != genre.lower():
                continue
            if mood and meta_dict.get('mood', '').lower() != mood.lower():
                continue
            if key and meta_dict.get('key', '').lower() != key.lower():
                continue
            
            # BPM filtering
            product_bpm = meta_dict.get('bpm')
            if product_bpm:
                try:
                    bpm_val = int(product_bpm)
                    if bpm_min and bpm_val < bpm_min:
                        continue
                    if bpm_max and bpm_val > bpm_max:
                        continue
                except ValueError:
                    pass
            
            # Add meta data to product for easier access
            product['genre'] = meta_dict.get('genre', '')
            product['bpm'] = meta_dict.get('bpm', '')
            product['mood'] = meta_dict.get('mood', '')
            product['music_key'] = meta_dict.get('key', '')
            product['audio_url'] = meta_dict.get('audio_url', '')
            
            filtered_products.append(product)
        
        return {
            "products": filtered_products,
            "total": len(filtered_products),
            "page": page
        }
    
    except Exception as e:
        logging.error(f"Error fetching products: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error fetching products: {str(e)}")


@api_router.get("/products/{product_id}")
async def get_product(product_id: int):
    """Get single product details with variations"""
    try:
        response = wcapi.get(f"products/{product_id}")
        
        if response.status_code != 200:
            raise HTTPException(status_code=404, detail="Product not found")
        
        product = response.json()
        
        # Get variations if product has variations
        if product.get('variations'):
            variations_response = wcapi.get(f"products/{product_id}/variations")
            if variations_response.status_code == 200:
                product['variations_data'] = variations_response.json()
        
        # Extract meta data
        meta_dict = {meta['key']: meta['value'] for meta in product.get('meta_data', [])}
        product['genre'] = meta_dict.get('genre', '')
        product['bpm'] = meta_dict.get('bpm', '')
        product['mood'] = meta_dict.get('mood', '')
        product['music_key'] = meta_dict.get('key', '')
        product['audio_url'] = meta_dict.get('audio_url', '')
        
        return product
    
    except HTTPException:
        raise
    except Exception as e:
        logging.error(f"Error fetching product: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error fetching product: {str(e)}")


@api_router.get("/filters")
async def get_available_filters():
    """Get all available filter options from products"""
    try:
        # Fetch all products to extract unique filter values
        response = wcapi.get("products", params={"per_page": 100, "status": "publish"})
        
        if response.status_code != 200:
            return {"genres": [], "moods": [], "keys": [], "bpm_range": {"min": 60, "max": 200}}
        
        products = response.json()
        
        genres = set()
        moods = set()
        keys = set()
        bpms = []
        
        for product in products:
            meta_dict = {meta['key']: meta['value'] for meta in product.get('meta_data', [])}
            
            if meta_dict.get('genre'):
                genres.add(meta_dict['genre'])
            if meta_dict.get('mood'):
                moods.add(meta_dict['mood'])
            if meta_dict.get('key'):
                keys.add(meta_dict['key'])
            if meta_dict.get('bpm'):
                try:
                    bpms.append(int(meta_dict['bpm']))
                except ValueError:
                    pass
        
        return {
            "genres": sorted(list(genres)),
            "moods": sorted(list(moods)),
            "keys": sorted(list(keys)),
            "bpm_range": {
                "min": min(bpms) if bpms else 60,
                "max": max(bpms) if bpms else 200
            }
        }
    
    except Exception as e:
        logging.error(f"Error fetching filters: {str(e)}")
        return {"genres": [], "moods": [], "keys": [], "bpm_range": {"min": 60, "max": 200}}


@api_router.post("/cart", response_model=CartItem)
async def add_to_cart(item: CartItemCreate):
    """Add item to cart"""
    cart_obj = CartItem(**item.model_dump())
    doc = cart_obj.model_dump()
    doc['timestamp'] = doc['timestamp'].isoformat()
    
    await db.cart.insert_one(doc)
    return cart_obj


@api_router.get("/cart", response_model=CartResponse)
async def get_cart():
    """Get all cart items"""
    items = await db.cart.find({}, {"_id": 0}).to_list(1000)
    
    for item in items:
        if isinstance(item['timestamp'], str):
            item['timestamp'] = datetime.fromisoformat(item['timestamp'])
    
    total = sum(item['price'] for item in items)
    
    return CartResponse(items=items, total=total)


@api_router.delete("/cart/{item_id}")
async def remove_from_cart(item_id: str):
    """Remove item from cart"""
    result = await db.cart.delete_one({"id": item_id})
    
    if result.deleted_count == 0:
        raise HTTPException(status_code=404, detail="Item not found")
    
    return {"message": "Item removed from cart"}


@api_router.delete("/cart")
async def clear_cart():
    """Clear all items from cart"""
    await db.cart.delete_many({})
    return {"message": "Cart cleared"}


@api_router.post("/checkout")
async def create_checkout_url():
    """Generate WooCommerce cart URL for checkout"""
    try:
        # Get cart items from database
        items = await db.cart.find({}, {"_id": 0}).to_list(1000)
        
        if not items:
            raise HTTPException(status_code=400, detail="Cart is empty")
        
        # Build WooCommerce cart URL with add-to-cart parameters
        base_url = os.environ['WOOCOMMERCE_URL']
        cart_params = []
        
        for item in items:
            if item.get('variation_id'):
                cart_params.append(f"add-to-cart={item['product_id']}&variation_id={item['variation_id']}")
            else:
                cart_params.append(f"add-to-cart={item['product_id']}")
        
        # WooCommerce cart URL
        checkout_url = f"{base_url}/cart/?{' &'.join(cart_params)}"
        
        return {
            "checkout_url": checkout_url,
            "total_items": len(items)
        }
    
    except HTTPException:
        raise
    except Exception as e:
        logging.error(f"Error creating checkout: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Error creating checkout: {str(e)}")


# Include the router in the main app
app.include_router(api_router)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=os.environ.get('CORS_ORIGINS', '*').split(','),
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

@app.on_event("shutdown")
async def shutdown_db_client():
    client.close()
