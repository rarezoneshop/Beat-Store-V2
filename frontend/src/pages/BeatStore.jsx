import { useState, useEffect, useRef } from "react";
import axios from "axios";
import { Play, Pause, ShoppingCart, X, SkipBack, SkipForward, Volume2, Search, Filter } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { toast } from "sonner";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Slider } from "@/components/ui/slider";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

const BeatStore = () => {
  const [products, setProducts] = useState([]);
  const [filteredProducts, setFilteredProducts] = useState([]);
  const [cart, setCart] = useState([]);
  const [currentPlaying, setCurrentPlaying] = useState(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [volume, setVolume] = useState(0.7);
  const [showCart, setShowCart] = useState(false);
  const [loading, setLoading] = useState(true);
  const [selectedLicenses, setSelectedLicenses] = useState({});
  
  // Filters
  const [filters, setFilters] = useState({
    genre: "",
    mood: "",
    key: "",
    bpm_min: "",
    bpm_max: "",
    search: ""
  });
  const [availableFilters, setAvailableFilters] = useState({
    genres: [],
    moods: [],
    keys: [],
    bpm_range: { min: 60, max: 200 }
  });
  
  const audioRef = useRef(null);

  useEffect(() => {
    fetchProducts();
    fetchCart();
    fetchFilters();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [filters, products]);

  useEffect(() => {
    if (audioRef.current) {
      audioRef.current.volume = volume;
    }
  }, [volume]);

  const fetchProducts = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API}/products`, {
        params: { per_page: 100 }
      });
      setProducts(response.data.products);
      setFilteredProducts(response.data.products);
    } catch (error) {
      console.error("Error fetching products:", error);
      toast.error("Failed to load beats");
    } finally {
      setLoading(false);
    }
  };

  const fetchFilters = async () => {
    try {
      const response = await axios.get(`${API}/filters`);
      setAvailableFilters(response.data);
    } catch (error) {
      console.error("Error fetching filters:", error);
    }
  };

  const fetchCart = async () => {
    try {
      const response = await axios.get(`${API}/cart`);
      setCart(response.data.items);
    } catch (error) {
      console.error("Error fetching cart:", error);
    }
  };

  const applyFilters = () => {
    let filtered = [...products];

    if (filters.genre) {
      filtered = filtered.filter(p => p.genre?.toLowerCase() === filters.genre.toLowerCase());
    }
    if (filters.mood) {
      filtered = filtered.filter(p => p.mood?.toLowerCase() === filters.mood.toLowerCase());
    }
    if (filters.key) {
      filtered = filtered.filter(p => p.music_key?.toLowerCase() === filters.key.toLowerCase());
    }
    if (filters.bpm_min) {
      filtered = filtered.filter(p => parseInt(p.bpm) >= parseInt(filters.bpm_min));
    }
    if (filters.bpm_max) {
      filtered = filtered.filter(p => parseInt(p.bpm) <= parseInt(filters.bpm_max));
    }
    if (filters.search) {
      filtered = filtered.filter(p => 
        p.name?.toLowerCase().includes(filters.search.toLowerCase()) ||
        p.genre?.toLowerCase().includes(filters.search.toLowerCase()) ||
        p.mood?.toLowerCase().includes(filters.search.toLowerCase())
      );
    }

    setFilteredProducts(filtered);
  };

  const playBeat = (product) => {
    if (!product.audio_url) {
      toast.error("Audio not available for this beat");
      return;
    }

    if (currentPlaying?.id === product.id && isPlaying) {
      audioRef.current.pause();
      setIsPlaying(false);
    } else {
      if (currentPlaying?.id !== product.id) {
        audioRef.current.src = product.audio_url;
        setCurrentPlaying(product);
      }
      audioRef.current.play();
      setIsPlaying(true);
    }
  };

  const skipTrack = (direction) => {
    const currentIndex = filteredProducts.findIndex(p => p.id === currentPlaying?.id);
    let nextIndex;
    
    if (direction === 'next') {
      nextIndex = (currentIndex + 1) % filteredProducts.length;
    } else {
      nextIndex = currentIndex - 1 < 0 ? filteredProducts.length - 1 : currentIndex - 1;
    }
    
    const nextProduct = filteredProducts[nextIndex];
    if (nextProduct?.audio_url) {
      setCurrentPlaying(nextProduct);
      audioRef.current.src = nextProduct.audio_url;
      audioRef.current.play();
      setIsPlaying(true);
    }
  };

  const handleTimeUpdate = () => {
    setCurrentTime(audioRef.current.currentTime);
    setDuration(audioRef.current.duration);
  };

  const handleProgressClick = (e) => {
    const progressBar = e.currentTarget;
    const clickPosition = e.nativeEvent.offsetX;
    const barWidth = progressBar.offsetWidth;
    const newTime = (clickPosition / barWidth) * duration;
    audioRef.current.currentTime = newTime;
  };

  const toggleLicense = (productId, variation) => {
    const key = `${productId}-${variation.id}`;
    setSelectedLicenses(prev => ({
      ...prev,
      [key]: !prev[key]
    }));
  };

  const addToCart = async (product, variation) => {
    try {
      const cartItem = {
        product_id: product.id,
        variation_id: variation.id,
        name: product.name,
        license_type: variation.attributes?.[0]?.option || variation.name || 'License',
        price: parseFloat(variation.price),
        audio_url: product.audio_url,
        image_url: product.images?.[0]?.src || ""
      };

      await axios.post(`${API}/cart`, cartItem);
      await fetchCart();
      toast.success(`${cartItem.license_type} added to cart`);
      
      // Uncheck the license after adding
      const key = `${product.id}-${variation.id}`;
      setSelectedLicenses(prev => ({ ...prev, [key]: false }));
    } catch (error) {
      console.error("Error adding to cart:", error);
      toast.error("Failed to add to cart");
    }
  };

  const removeFromCart = async (itemId) => {
    try {
      await axios.delete(`${API}/cart/${itemId}`);
      await fetchCart();
      toast.success("Removed from cart");
    } catch (error) {
      console.error("Error removing from cart:", error);
      toast.error("Failed to remove from cart");
    }
  };

  const proceedToCheckout = async () => {
    if (cart.length === 0) {
      toast.error("Your cart is empty");
      return;
    }

    try {
      const response = await axios.post(`${API}/checkout`);
      window.location.href = response.data.checkout_url;
    } catch (error) {
      console.error("Error creating checkout:", error);
      toast.error("Failed to proceed to checkout");
    }
  };

  const formatTime = (seconds) => {
    if (!seconds || isNaN(seconds)) return "0:00";
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const cartTotal = cart.reduce((sum, item) => sum + item.price, 0);

  return (
    <div className="min-h-screen bg-gradient-to-br from-[#0a0a0f] via-[#0f0f1a] to-[#0a0a0f]">
      {/* Audio Element */}
      <audio
        ref={audioRef}
        onTimeUpdate={handleTimeUpdate}
        onEnded={() => skipTrack('next')}
      />

      {/* Header */}
      <header className="border-b border-white/10 backdrop-blur-md bg-[#0a0a0f]/80 sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold bg-gradient-to-r from-blue-400 to-cyan-400 bg-clip-text text-transparent">
              RareBeats
            </h1>
            <p className="text-sm text-gray-400 mt-1">Premium Beats & Instrumentals</p>
          </div>
          
          <Button
            data-testid="cart-button"
            onClick={() => setShowCart(!showCart)}
            className="relative bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30"
          >
            <ShoppingCart className="w-5 h-5" />
            {cart.length > 0 && (
              <span className="cart-badge" data-testid="cart-count">{cart.length}</span>
            )}
          </Button>
        </div>
      </header>

      <div className="max-w-7xl mx-auto px-4 py-8">
        {/* Filters */}
        <div className="glass rounded-2xl p-6 mb-8 fade-in">
          <div className="flex items-center gap-2 mb-4">
            <Filter className="w-5 h-5 text-blue-400" />
            <h2 className="text-xl font-semibold">Filters</h2>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              <Input
                data-testid="search-input"
                placeholder="Search beats..."
                value={filters.search}
                onChange={(e) => setFilters({ ...filters, search: e.target.value })}
                className="pl-10 bg-white/5 border-white/10 text-white"
              />
            </div>
            
            <Select value={filters.genre} onValueChange={(value) => setFilters({ ...filters, genre: value })}>
              <SelectTrigger data-testid="genre-filter" className="bg-white/5 border-white/10 text-white">
                <SelectValue placeholder="Genre" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Genres</SelectItem>
                {availableFilters.genres.map(genre => (
                  <SelectItem key={genre} value={genre}>{genre}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            
            <Select value={filters.mood} onValueChange={(value) => setFilters({ ...filters, mood: value })}>
              <SelectTrigger data-testid="mood-filter" className="bg-white/5 border-white/10 text-white">
                <SelectValue placeholder="Mood" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Moods</SelectItem>
                {availableFilters.moods.map(mood => (
                  <SelectItem key={mood} value={mood}>{mood}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            
            <Select value={filters.key} onValueChange={(value) => setFilters({ ...filters, key: value })}>
              <SelectTrigger data-testid="key-filter" className="bg-white/5 border-white/10 text-white">
                <SelectValue placeholder="Key" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Keys</SelectItem>
                {availableFilters.keys.map(key => (
                  <SelectItem key={key} value={key}>{key}</SelectItem>
                ))}
              </SelectContent>
            </Select>
            
            <div className="flex gap-2">
              <Input
                data-testid="bpm-min-input"
                type="number"
                placeholder="Min BPM"
                value={filters.bpm_min}
                onChange={(e) => setFilters({ ...filters, bpm_min: e.target.value })}
                className="bg-white/5 border-white/10 text-white"
              />
              <Input
                data-testid="bpm-max-input"
                type="number"
                placeholder="Max BPM"
                value={filters.bpm_max}
                onChange={(e) => setFilters({ ...filters, bpm_max: e.target.value })}
                className="bg-white/5 border-white/10 text-white"
              />
            </div>
          </div>
          
          {(filters.genre || filters.mood || filters.key || filters.bpm_min || filters.bpm_max) && (
            <Button
              data-testid="clear-filters-button"
              onClick={() => setFilters({ genre: "", mood: "", key: "", bpm_min: "", bpm_max: "", search: "" })}
              variant="ghost"
              className="mt-4 text-blue-400 hover:text-blue-300"
            >
              Clear Filters
            </Button>
          )}
        </div>

        {/* Beat List */}
        <div className="glass rounded-2xl overflow-hidden mb-32">
          {loading ? (
            <div className="text-center py-20 text-gray-400">Loading beats...</div>
          ) : filteredProducts.length === 0 ? (
            <div className="text-center py-20 text-gray-400">No beats found</div>
          ) : (
            <div className="divide-y divide-white/10">
              {filteredProducts.map((product, index) => (
                <div
                  key={product.id}
                  data-testid={`beat-card-${product.id}`}
                  className={`beat-row fade-in ${currentPlaying?.id === product.id && isPlaying ? 'playing' : ''}`}
                >
                  {/* Main Beat Info Row */}
                  <div className="flex items-center gap-4 p-4 hover:bg-white/5 transition-colors">
                    {/* Play Button & Index */}
                    <div className="flex items-center gap-3 w-16">
                      <span className="text-gray-500 text-sm w-6 text-right">{index + 1}</span>
                      <button
                        data-testid={`play-button-${product.id}`}
                        onClick={() => playBeat(product)}
                        className="player-control-btn-small"
                        disabled={!product.audio_url}
                      >
                        {currentPlaying?.id === product.id && isPlaying ? (
                          <Pause className="w-4 h-4 text-blue-400" />
                        ) : (
                          <Play className="w-4 h-4 text-blue-400 ml-0.5" />
                        )}
                      </button>
                    </div>

                    {/* Beat Image & Name */}
                    <div className="flex items-center gap-3 flex-1 min-w-0">
                      {product.images?.[0]?.src && (
                        <img
                          src={product.images[0].src}
                          alt={product.name}
                          className="w-12 h-12 rounded object-cover"
                        />
                      )}
                      <div className="flex-1 min-w-0">
                        <h3 className="font-semibold truncate text-white">{product.name}</h3>
                        {currentPlaying?.id === product.id && isPlaying && (
                          <div className="flex items-center gap-1 mt-1">
                            {[...Array(20)].map((_, i) => (
                              <div
                                key={i}
                                className="waveform-bar-small"
                                style={{
                                  height: `${Math.random() * 12 + 4}px`,
                                  animationDelay: `${i * 0.05}s`
                                }}
                              />
                            ))}
                          </div>
                        )}
                      </div>
                    </div>

                    {/* Genre */}
                    <div className="hidden md:block w-32">
                      {product.genre && (
                        <span className="filter-badge-small">{product.genre}</span>
                      )}
                    </div>

                    {/* BPM */}
                    <div className="hidden lg:block w-24 text-sm text-gray-400">
                      {product.bpm && `${product.bpm} BPM`}
                    </div>

                    {/* Key */}
                    <div className="hidden lg:block w-20 text-sm text-gray-400">
                      {product.music_key}
                    </div>

                    {/* Mood */}
                    <div className="hidden xl:block w-28">
                      {product.mood && (
                        <span className="filter-badge-small">{product.mood}</span>
                      )}
                    </div>

                    {/* License Dropdown */}
                    <div className="w-40">
                      <LicenseSelector 
                        product={product} 
                        toggleLicense={toggleLicense} 
                        selectedLicenses={selectedLicenses} 
                        addToCart={addToCart}
                        compact={true}
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Player Bar */}
      {currentPlaying && (
        <div className="fixed bottom-0 left-0 right-0 glass border-t border-white/10 p-4 z-50">
          <div className="max-w-7xl mx-auto">
            <div className="flex items-center gap-4">
              {/* Current Track Info */}
              <div className="flex items-center gap-3 flex-1 min-w-0">
                {currentPlaying.images?.[0]?.src && (
                  <img
                    src={currentPlaying.images[0].src}
                    alt={currentPlaying.name}
                    className="w-12 h-12 rounded object-cover"
                  />
                )}
                <div className="min-w-0">
                  <p className="font-semibold truncate" data-testid="now-playing-title">{currentPlaying.name}</p>
                  <p className="text-xs text-gray-400">
                    {currentPlaying.genre} • {currentPlaying.bpm} BPM
                  </p>
                </div>
              </div>

              {/* Player Controls */}
              <div className="flex items-center gap-3">
                <button
                  data-testid="skip-back-button"
                  onClick={() => skipTrack('prev')}
                  className="player-control-btn"
                >
                  <SkipBack className="w-4 h-4 text-blue-400" />
                </button>
                
                <button
                  data-testid="play-pause-button"
                  onClick={() => playBeat(currentPlaying)}
                  className="player-control-btn play"
                >
                  {isPlaying ? (
                    <Pause className="w-6 h-6 text-white" />
                  ) : (
                    <Play className="w-6 h-6 text-white ml-0.5" />
                  )}
                </button>
                
                <button
                  data-testid="skip-forward-button"
                  onClick={() => skipTrack('next')}
                  className="player-control-btn"
                >
                  <SkipForward className="w-4 h-4 text-blue-400" />
                </button>
              </div>

              {/* Progress Bar */}
              <div className="flex-1 flex items-center gap-3">
                <span className="text-xs text-gray-400" data-testid="current-time">{formatTime(currentTime)}</span>
                <div className="flex-1 progress-bar" onClick={handleProgressClick}>
                  <div
                    className="progress-fill"
                    style={{ width: `${(currentTime / duration) * 100}%` }}
                  />
                </div>
                <span className="text-xs text-gray-400" data-testid="duration">{formatTime(duration)}</span>
              </div>

              {/* Volume Control */}
              <div className="flex items-center gap-2 w-32">
                <Volume2 className="w-4 h-4 text-gray-400" />
                <Slider
                  data-testid="volume-slider"
                  value={[volume * 100]}
                  onValueChange={([val]) => setVolume(val / 100)}
                  max={100}
                  step={1}
                  className="flex-1"
                />
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Cart Sidebar */}
      {showCart && (
        <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50" onClick={() => setShowCart(false)}>
          <div
            data-testid="cart-sidebar"
            className="absolute right-0 top-0 h-full w-full max-w-md glass border-l border-white/10"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex flex-col h-full">
              {/* Cart Header */}
              <div className="p-6 border-b border-white/10">
                <div className="flex items-center justify-between">
                  <h2 className="text-2xl font-bold">Your Cart</h2>
                  <button
                    data-testid="close-cart-button"
                    onClick={() => setShowCart(false)}
                    className="player-control-btn"
                  >
                    <X className="w-5 h-5 text-gray-400" />
                  </button>
                </div>
              </div>

              {/* Cart Items */}
              <ScrollArea className="flex-1 p-6">
                {cart.length === 0 ? (
                  <div className="text-center py-20 text-gray-400">
                    <ShoppingCart className="w-16 h-16 mx-auto mb-4 opacity-50" />
                    <p>Your cart is empty</p>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {cart.map((item) => (
                      <div
                        key={item.id}
                        data-testid={`cart-item-${item.id}`}
                        className="beat-card flex items-start gap-3"
                      >
                        {item.image_url && (
                          <img
                            src={item.image_url}
                            alt={item.name}
                            className="w-16 h-16 rounded object-cover"
                          />
                        )}
                        <div className="flex-1 min-w-0">
                          <h3 className="font-semibold truncate">{item.name}</h3>
                          <p className="text-sm text-blue-400">{item.license_type}</p>
                          <p className="text-sm text-gray-400 mt-1">£{item.price.toFixed(2)}</p>
                        </div>
                        <button
                          data-testid={`remove-item-${item.id}`}
                          onClick={() => removeFromCart(item.id)}
                          className="text-red-400 hover:text-red-300"
                        >
                          <X className="w-5 h-5" />
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </ScrollArea>

              {/* Cart Footer */}
              {cart.length > 0 && (
                <div className="p-6 border-t border-white/10 space-y-4">
                  <div className="flex items-center justify-between text-lg font-semibold">
                    <span>Total:</span>
                    <span data-testid="cart-total">£{cartTotal.toFixed(2)}</span>
                  </div>
                  <button
                    data-testid="checkout-button"
                    onClick={proceedToCheckout}
                    className="checkout-btn w-full"
                  >
                    Proceed to Checkout
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

// Component to fetch and display licenses
const LicenseSelector = ({ product, toggleLicense, selectedLicenses, addToCart }) => {
  const [variations, setVariations] = useState([]);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    const fetchVariations = async () => {
      try {
        const response = await axios.get(`${process.env.REACT_APP_BACKEND_URL}/api/products/${product.id}`);
        setVariations(response.data.variations_data || []);
      } catch (error) {
        console.error("Error fetching variations:", error);
      } finally {
        setLoading(false);
      }
    };
    
    if (product.variations && product.variations.length > 0) {
      fetchVariations();
    } else {
      setLoading(false);
    }
  }, [product.id, product.variations]);
  
  if (loading) return <div className="text-xs text-gray-400">Loading licenses...</div>;
  if (variations.length === 0) return null;
  
  return (
    <div className="space-y-2">
      {variations.map((variation) => {
        const key = `${product.id}-${variation.id}`;
        const isSelected = selectedLicenses[key];
        const licenseName = variation.attributes?.[0]?.option || 'License';
        
        return (
          <div key={variation.id} className="flex items-center justify-between p-2 rounded bg-white/5">
            <div className="flex items-center gap-2">
              <input
                data-testid={`license-checkbox-${product.id}-${variation.id}`}
                type="checkbox"
                checked={isSelected || false}
                onChange={() => toggleLicense(product.id, variation)}
                className="license-checkbox"
              />
              <div>
                <p className="text-sm font-medium">{licenseName}</p>
                <p className="text-xs text-gray-400">£{variation.price}</p>
              </div>
            </div>
            {isSelected && (
              <Button
                data-testid={`add-to-cart-${product.id}-${variation.id}`}
                size="sm"
                onClick={() => addToCart(product, variation)}
                className="bg-blue-600 hover:bg-blue-700 text-white"
              >
                Add
              </Button>
            )}
          </div>
        );
      })}
    </div>
  );
};

export default BeatStore;
