'use client';

import { ProductCard } from './product-card';
import { Product } from '@/types/store';
import { useStore } from '@/lib/store';

interface FeaturedProductsProps {
  products: Product[];
  title?: string;
}

export function FeaturedProducts({ products, title = "Featured Products" }: FeaturedProductsProps) {
  const { addToCart } = useStore();

  const handleQuickAdd = (product: Product) => {
    if (product.colors.length > 0 && product.colors[0].sizes.length > 0) {
      const firstColor = product.colors[0];
      const firstSize = firstColor.sizes[0];
      addToCart(product, firstColor, firstSize, 1);
    }
  };

  return (
    <section className="py-12">
      <div className="container mx-auto px-4">
        <h2 className="text-3xl font-bold text-center mb-8">{title}</h2>
        
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
          {products.map((product) => (
            <ProductCard
              key={product.id}
              product={product}
              onQuickAdd={handleQuickAdd}
            />
          ))}
        </div>
      </div>
    </section>
  );
}