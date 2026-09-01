'use client';

import { useState, useEffect } from 'react';
import { X, Share2, Heart, ShoppingCart, Star, ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { Product, ProductColor, ProductSize } from '@/types/store';
import { useStore } from '@/lib/store';

interface ProductModalProps {
  product: Product | null;
  isOpen: boolean;
  onClose: () => void;
}

export function ProductModal({ product, isOpen, onClose }: ProductModalProps) {
  const [selectedColor, setSelectedColor] = useState<ProductColor | null>(null);
  const [selectedSize, setSelectedSize] = useState<ProductSize | null>(null);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [quantity, setQuantity] = useState(1);
  
  const { addToCart, toggleFavorite, isFavorite } = useStore();

  // Auto-select first color and size when product changes
  useEffect(() => {
    if (product && product.colors.length > 0 && !selectedColor) {
      setSelectedColor(product.colors[0]);
      if (product.colors[0].sizes.length > 0 && !selectedSize) {
        setSelectedSize(product.colors[0].sizes[0]);
      }
    }
  }, [product, selectedColor, selectedSize]);

  if (!product) return null;

  const colors = product.colors;
  const sizes = selectedColor?.sizes || [];
  const images = selectedColor?.image ? [selectedColor.image] : product.images;
  const discount = product.discountPercentage;
  const hasDiscount = discount && discount > 0;
  const currentPrice = product.discountedPrice || product.originalPrice;
  const favorite = isFavorite(product.id);

  const handleColorSelect = (color: ProductColor) => {
    setSelectedColor(color);
    setSelectedSize(color.sizes[0] || null);
    setCurrentImageIndex(0);
  };

  const handleSizeSelect = (size: ProductSize) => {
    setSelectedSize(size);
  };

  const handleAddToCart = () => {
    if (selectedColor && selectedSize) {
      addToCart(product, selectedColor, selectedSize, quantity);
      onClose();
    }
  };

  const handleShare = (platform: string) => {
    const url = window.location.href;
    const text = `Check out ${product.name} on Oneway!`;
    
    let shareUrl = '';
    switch (platform) {
      case 'whatsapp':
        shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
        break;
      case 'facebook':
        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
        break;
      case 'twitter':
        shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
        break;
    }
    
    if (shareUrl) {
      window.open(shareUrl, '_blank');
    }
  };

  const nextImage = () => {
    setCurrentImageIndex((prev) => (prev + 1) % images.length);
  };

  const prevImage = () => {
    setCurrentImageIndex((prev) => (prev - 1 + images.length) % images.length);
  };

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto p-0">
        <div className="grid md:grid-cols-2 gap-6">
          {/* Left side - Images */}
          <div className="relative">
            <div className="aspect-[3/4] overflow-hidden bg-muted">
              <img
                src={images[currentImageIndex] || '/api/placeholder/400/500'}
                alt={product.name}
                className="w-full h-full object-cover"
              />
            </div>
            
            {/* Image navigation */}
            {images.length > 1 && (
              <>
                <Button
                  variant="outline"
                  size="icon"
                  className="absolute left-2 top-1/2 transform -translate-y-1/2 bg-background/80"
                  onClick={prevImage}
                >
                  <ChevronLeft className="h-4 w-4" />
                </Button>
                <Button
                  variant="outline"
                  size="icon"
                  className="absolute right-2 top-1/2 transform -translate-y-1/2 bg-background/80"
                  onClick={nextImage}
                >
                  <ChevronRight className="h-4 w-4" />
                </Button>
              </>
            )}

            {/* Image thumbnails */}
            {images.length > 1 && (
              <div className="flex space-x-2 mt-4">
                {images.map((_, index) => (
                  <button
                    key={index}
                    className={`w-16 h-16 border-2 rounded overflow-hidden ${
                      index === currentImageIndex ? 'border-accent' : 'border-border'
                    }`}
                    onClick={() => setCurrentImageIndex(index)}
                  >
                    <img
                      src={images[index]}
                      alt={`${product.name} ${index + 1}`}
                      className="w-full h-full object-cover"
                    />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Right side - Product details */}
          <div className="p-6">
            {/* Header */}
            <div className="flex items-start justify-between mb-4">
              <div className="flex-1">
                {product.isNew && (
                  <Badge className="bg-accent text-accent-foreground mb-2">New</Badge>
                )}
                <h2 className="text-2xl font-bold mb-2">{product.name}</h2>
                <p className="text-muted-foreground">{product.description}</p>
              </div>
              <Button variant="ghost" size="icon" onClick={onClose}>
                <X className="h-4 w-4" />
              </Button>
            </div>

            {/* Rating */}
            <div className="flex items-center space-x-2 mb-4">
              <div className="flex items-center">
                {[...Array(5)].map((_, i) => (
                  <Star
                    key={i}
                    className={`h-4 w-4 ${
                      i < Math.floor(product.rating)
                        ? 'fill-yellow-400 text-yellow-400'
                        : 'fill-gray-200 text-gray-200'
                    }`}
                  />
                ))}
              </div>
              <span className="text-sm text-muted-foreground">
                {product.rating} ({product.reviews} reviews)
              </span>
            </div>

            {/* Price */}
            <div className="flex items-center space-x-3 mb-6">
              <span className="text-3xl font-bold text-primary">
                ${currentPrice}
              </span>
              {hasDiscount && (
                <>
                  <span className="text-xl text-muted-foreground line-through">
                    ${product.originalPrice}
                  </span>
                  <Badge className="bg-destructive text-destructive-foreground">
                    -{discount}%
                  </Badge>
                </>
              )}
            </div>

            {/* Color selection */}
            {colors.length > 0 && (
              <div className="mb-6">
                <h3 className="font-medium mb-3">Color: {selectedColor?.name}</h3>
                <div className="flex space-x-2">
                  {colors.map((color) => (
                    <button
                      key={color.id}
                      className={`w-10 h-10 rounded-full border-2 transition-all ${
                        selectedColor?.id === color.id
                          ? 'border-accent ring-2 ring-accent/50'
                          : 'border-border hover:border-accent/50'
                      }`}
                      style={{ backgroundColor: color.hexCode }}
                      onClick={() => handleColorSelect(color)}
                      title={color.name}
                    />
                  ))}
                </div>
              </div>
            )}

            {/* Size selection */}
            {sizes.length > 0 && (
              <div className="mb-6">
                <h3 className="font-medium mb-3">Size</h3>
                <div className="flex flex-wrap gap-2">
                  {sizes.map((size) => (
                    <button
                      key={size.id}
                      className={`px-4 py-2 border rounded-md transition-all ${
                        selectedSize?.id === size.id
                          ? 'border-accent bg-accent text-accent-foreground'
                          : 'border-border hover:border-accent/50'
                      } ${size.stock === 0 ? 'opacity-50 cursor-not-allowed' : ''}`}
                      onClick={() => size.stock > 0 && handleSizeSelect(size)}
                      disabled={size.stock === 0}
                    >
                      {size.size}
                      {size.stock === 0 && ' (Out of stock)'}
                    </button>
                  ))}
                </div>
              </div>
            )}

            {/* Quantity */}
            <div className="mb-6">
              <h3 className="font-medium mb-3">Quantity</h3>
              <div className="flex items-center space-x-3">
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => setQuantity(Math.max(1, quantity - 1))}
                >
                  -
                </Button>
                <span className="w-12 text-center">{quantity}</span>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => setQuantity(quantity + 1)}
                >
                  +
                </Button>
              </div>
            </div>

            {/* Actions */}
            <div className="flex space-x-3 mb-6">
              <Button
                className="flex-1 bg-accent hover:bg-accent/90 text-accent-foreground"
                onClick={handleAddToCart}
                disabled={!selectedColor || !selectedSize || selectedSize.stock === 0}
              >
                <ShoppingCart className="h-4 w-4 mr-2" />
                Add to Cart
              </Button>
              <Button
                variant="outline"
                size="icon"
                onClick={() => toggleFavorite(product.id)}
              >
                <Heart className={`h-4 w-4 ${favorite ? 'fill-red-500 text-red-500' : ''}`} />
              </Button>
            </div>

            {/* Share */}
            <div className="border-t pt-4">
              <h3 className="font-medium mb-3">Share</h3>
              <div className="flex space-x-2">
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleShare('whatsapp')}
                >
                  WhatsApp
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleShare('facebook')}
                >
                  Facebook
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => handleShare('twitter')}
                >
                  Twitter
                </Button>
              </div>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}