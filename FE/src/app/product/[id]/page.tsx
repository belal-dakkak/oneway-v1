'use client';

import { useState } from 'react';
import { useParams } from 'next/navigation';
import { Header } from '@/components/header';
import { Footer } from '@/components/footer';
import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { useStore } from '@/lib/store';
import { mockProducts } from '@/lib/mock-data';
import { Product, ProductColor, ProductSize } from '@/types/store';
import { 
  Star, 
  Heart, 
  ShoppingCart, 
  Share2, 
  ChevronLeft, 
  ChevronRight,
  Truck,
  Shield,
  RefreshCw,
  Minus,
  Plus
} from 'lucide-react';

export default function ProductDetailPage() {
  const params = useParams();
  const productId = params.id as string;
  
  const [selectedColor, setSelectedColor] = useState<ProductColor | null>(null);
  const [selectedSize, setSelectedSize] = useState<ProductSize | null>(null);
  const [currentImageIndex, setCurrentImageIndex] = useState(0);
  const [quantity, setQuantity] = useState(1);
  
  const { addToCart, toggleFavorite, isFavorite } = useStore();

  const product = mockProducts.find(p => p.id === productId);
  
  if (!product) {
    return (
      <div className="min-h-screen">
        <Header />
        <main className="container mx-auto px-4 py-16 text-center">
          <h1 className="text-3xl font-bold mb-4">Product not found</h1>
          <Button onClick={() => window.history.back()}>Go Back</Button>
        </main>
        <Footer />
      </div>
    );
  }

  const colors = product.colors;
  const sizes = selectedColor?.sizes || [];
  const images = selectedColor?.image ? [selectedColor.image] : product.images;
  const discount = product.discountPercentage;
  const hasDiscount = discount && discount > 0;
  const currentPrice = product.discountedPrice || product.originalPrice;
  const favorite = isFavorite(product.id);

  // Auto-select first color and size
  if (colors.length > 0 && !selectedColor) {
    setSelectedColor(colors[0]);
    if (colors[0].sizes.length > 0 && !selectedSize) {
      setSelectedSize(colors[0].sizes[0]);
    }
  }

  const relatedProducts = mockProducts
    .filter(p => p.id !== product.id && p.category === product.category)
    .slice(0, 4);

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
    <div className="min-h-screen">
      <Header />

      <main className="container mx-auto px-4 py-8">
        {/* Breadcrumb */}
        <nav className="mb-8 text-sm text-muted-foreground">
          <a href="/" className="hover:text-primary">Home</a>
          <span className="mx-2">/</span>
          <a href="/products" className="hover:text-primary">Products</a>
          <span className="mx-2">/</span>
          <span className="text-foreground">{product.name}</span>
        </nav>

        <div className="grid lg:grid-cols-2 gap-12 mb-16">
          {/* Product Images */}
          <div className="space-y-4">
            <div className="relative aspect-[3/4] overflow-hidden bg-muted rounded-lg">
              <img
                src={images[currentImageIndex] || '/api/placeholder/600/800'}
                alt={product.name}
                className="w-full h-full object-cover"
              />
              
              {/* Image navigation */}
              {images.length > 1 && (
                <>
                  <Button
                    variant="outline"
                    size="icon"
                    className="absolute left-4 top-1/2 transform -translate-y-1/2 bg-background/80"
                    onClick={prevImage}
                  >
                    <ChevronLeft className="h-4 w-4" />
                  </Button>
                  <Button
                    variant="outline"
                    size="icon"
                    className="absolute right-4 top-1/2 transform -translate-y-1/2 bg-background/80"
                    onClick={nextImage}
                  >
                    <ChevronRight className="h-4 w-4" />
                  </Button>
                </>
              )}

              {/* Badges */}
              <div className="absolute top-4 left-4 flex flex-col space-y-2">
                {product.isNew && (
                  <Badge className="bg-accent text-accent-foreground">New</Badge>
                )}
                {hasDiscount && (
                  <Badge className="bg-destructive text-destructive-foreground">
                    -{discount}%
                  </Badge>
                )}
              </div>
            </div>

            {/* Image thumbnails */}
            {images.length > 1 && (
              <div className="flex space-x-2">
                {images.map((_, index) => (
                  <button
                    key={index}
                    className={`w-20 h-20 border-2 rounded overflow-hidden ${
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

          {/* Product Details */}
          <div className="space-y-6">
            {/* Title and Price */}
            <div>
              {product.isNew && (
                <Badge className="bg-accent text-accent-foreground mb-2">New</Badge>
              )}
              <h1 className="text-3xl font-bold mb-4">{product.name}</h1>
              
              <div className="flex items-center space-x-4 mb-4">
                <div className="flex items-center">
                  {[...Array(5)].map((_, i) => (
                    <Star
                      key={i}
                      className={`h-5 w-5 ${
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

              <div className="flex items-center space-x-4">
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
            </div>

            <Separator />

            {/* Description */}
            <div>
              <h3 className="font-semibold mb-2">Description</h3>
              <p className="text-muted-foreground">{product.description}</p>
            </div>

            {/* Color Selection */}
            {colors.length > 0 && (
              <div>
                <h3 className="font-semibold mb-3">Color: {selectedColor?.name}</h3>
                <div className="flex space-x-3">
                  {colors.map((color) => (
                    <button
                      key={color.id}
                      className={`w-12 h-12 rounded-full border-2 transition-all ${
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

            {/* Size Selection */}
            {sizes.length > 0 && (
              <div>
                <h3 className="font-semibold mb-3">Size</h3>
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
            <div>
              <h3 className="font-semibold mb-3">Quantity</h3>
              <div className="flex items-center space-x-3">
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => setQuantity(Math.max(1, quantity - 1))}
                >
                  <Minus className="h-4 w-4" />
                </Button>
                <span className="w-12 text-center font-medium">{quantity}</span>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => setQuantity(quantity + 1)}
                >
                  <Plus className="h-4 w-4" />
                </Button>
              </div>
            </div>

            {/* Actions */}
            <div className="flex space-x-4">
              <Button
                className="flex-1 bg-accent hover:bg-accent/90 text-accent-foreground"
                size="lg"
                onClick={handleAddToCart}
                disabled={!selectedColor || !selectedSize || selectedSize.stock === 0}
              >
                <ShoppingCart className="h-4 w-4 mr-2" />
                Add to Cart
              </Button>
              <Button
                variant="outline"
                size="lg"
                onClick={() => toggleFavorite(product.id)}
              >
                <Heart className={`h-4 w-4 ${favorite ? 'fill-red-500 text-red-500' : ''}`} />
              </Button>
            </div>

            {/* Share */}
            <div>
              <h3 className="font-semibold mb-3">Share this product</h3>
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

            {/* Features */}
            <div className="border-t pt-6">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="flex items-center space-x-3">
                  <Truck className="h-5 w-5 text-accent" />
                  <div>
                    <p className="font-medium text-sm">Free Shipping</p>
                    <p className="text-xs text-muted-foreground">On orders over $100</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <Shield className="h-5 w-5 text-accent" />
                  <div>
                    <p className="font-medium text-sm">Secure Payment</p>
                    <p className="text-xs text-muted-foreground">100% secure transactions</p>
                  </div>
                </div>
                <div className="flex items-center space-x-3">
                  <RefreshCw className="h-5 w-5 text-accent" />
                  <div>
                    <p className="font-medium text-sm">Easy Returns</p>
                    <p className="text-xs text-muted-foreground">30-day return policy</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Related Products */}
        {relatedProducts.length > 0 && (
          <section>
            <h2 className="text-2xl font-bold mb-8">Related Products</h2>
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              {relatedProducts.map((relatedProduct) => (
                <ProductCard
                  key={relatedProduct.id}
                  product={relatedProduct}
                  onQuickAdd={() => {
                    if (relatedProduct.colors.length > 0 && relatedProduct.colors[0].sizes.length > 0) {
                      const firstColor = relatedProduct.colors[0];
                      const firstSize = firstColor.sizes[0];
                      addToCart(relatedProduct, firstColor, firstSize, 1);
                    }
                  }}
                />
              ))}
            </div>
          </section>
        )}
      </main>

      <Footer />
    </div>
  );
}