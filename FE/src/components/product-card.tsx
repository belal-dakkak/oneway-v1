'use client';

import { Star, Heart, ShoppingCart } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Product } from '@/types/store';
import { useStore } from '@/lib/store';

interface ProductCardProps {
  product: Product;
  onQuickAdd?: (product: Product) => void;
}

export function ProductCard({ product, onQuickAdd }: ProductCardProps) {
  const { toggleFavorite, isFavorite } = useStore();
  
  const discount = product.discountPercentage;
  const hasDiscount = discount && discount > 0;
  const currentPrice = product.discountedPrice || product.originalPrice;
  const favorite = isFavorite(product.id);

  const handleQuickAdd = (e: React.MouseEvent) => {
    e.stopPropagation();
    if (product.colors.length > 0 && product.colors[0].sizes.length > 0) {
      onQuickAdd?.(product);
    }
  };

  const handleProductClick = () => {
    // Navigate to product details page instead of opening modal
    window.location.href = `/product/${product.id}`;
  };

  const handleFavoriteToggle = (e: React.MouseEvent) => {
    e.stopPropagation();
    toggleFavorite(product.id);
  };

  return (
    <Card className="group cursor-pointer overflow-hidden transition-all-300 hover:shadow-lg" onClick={handleProductClick}>
      <div className="relative">
        {/* Product image */}
        <div className="aspect-[3/4] overflow-hidden bg-muted">
          <img
            src={product.colors[0]?.image || '/api/placeholder/300/400'}
            alt={product.name}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>

        {/* Badges */}
        <div className="absolute top-2 left-2 flex flex-col space-y-2">
          {product.isNew && (
            <Badge className="bg-accent text-accent-foreground">New</Badge>
          )}
          {hasDiscount && (
            <Badge className="bg-destructive text-destructive-foreground">
              -{discount}%
            </Badge>
          )}
        </div>

        {/* Favorite button */}
        <Button
          variant="ghost"
          size="icon"
          className="absolute top-2 right-2 bg-background/80 hover:bg-background text-foreground"
          onClick={handleFavoriteToggle}
        >
          <Heart 
            className={`h-4 w-4 ${favorite ? 'fill-red-500 text-red-500' : ''}`} 
          />
        </Button>

        {/* Quick add button */}
        <Button
          size="sm"
          className="absolute bottom-2 left-2 right-2 bg-accent hover:bg-accent/90 text-accent-foreground opacity-0 group-hover:opacity-100 transition-opacity"
          onClick={handleQuickAdd}
        >
          <ShoppingCart className="h-4 w-4 mr-2" />
          Quick Add
        </Button>
      </div>

      <CardContent className="p-4">
        {/* Product name */}
        <h3 className="font-medium text-sm mb-2 line-clamp-2 group-hover:text-accent-foreground transition-colors">
          {product.name}
        </h3>

        {/* Rating */}
        <div className="flex items-center space-x-1 mb-2">
          <div className="flex items-center">
            {[...Array(5)].map((_, i) => (
              <Star
                key={i}
                className={`h-3 w-3 ${
                  i < Math.floor(product.rating)
                    ? 'fill-yellow-400 text-yellow-400'
                    : 'fill-gray-200 text-gray-200'
                }`}
              />
            ))}
          </div>
          <span className="text-xs text-muted-foreground">
            ({product.reviews})
          </span>
        </div>

        {/* Price */}
        <div className="flex items-center space-x-2">
          <span className="font-bold text-primary">
            ${currentPrice}
          </span>
          {hasDiscount && (
            <span className="text-sm text-muted-foreground line-through">
              ${product.originalPrice}
            </span>
          )}
        </div>

        {/* Colors */}
        {product.colors.length > 1 && (
          <div className="flex items-center space-x-1 mt-2">
            {product.colors.slice(0, 3).map((color) => (
              <div
                key={color.id}
                className="w-4 h-4 rounded-full border border-border"
                style={{ backgroundColor: color.hexCode }}
                title={color.name}
              />
            ))}
            {product.colors.length > 3 && (
              <span className="text-xs text-muted-foreground">
                +{product.colors.length - 3}
              </span>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  );
}