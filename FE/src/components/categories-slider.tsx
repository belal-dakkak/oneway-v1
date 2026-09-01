'use client';

import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Category } from '@/types/store';

interface CategoriesSliderProps {
  categories: Category[];
  onCategoryClick?: (category: Category) => void;
}

export function CategoriesSlider({ categories, onCategoryClick }: CategoriesSliderProps) {
  const scrollLeft = () => {
    const element = document.getElementById('categories-container');
    if (element) {
      element.scrollBy({ left: -200, behavior: 'smooth' });
    }
  };

  const scrollRight = () => {
    const element = document.getElementById('categories-container');
    if (element) {
      element.scrollBy({ left: 200, behavior: 'smooth' });
    }
  };

  return (
    <div className="relative py-8">
      <div className="container mx-auto px-4">
        <h2 className="text-2xl font-bold mb-6 text-center">Shop by Category</h2>
        
        <div className="relative">
          {/* Left scroll button */}
          <Button
            variant="outline"
            size="icon"
            className="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-background shadow-lg"
            onClick={scrollLeft}
          >
            <ChevronLeft className="h-4 w-4" />
          </Button>

          {/* Categories container */}
          <div 
            id="categories-container"
            className="flex space-x-6 overflow-x-auto scrollbar-hide px-12 py-4 scroll-smooth"
            style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
          >
            {categories.map((category) => (
              <div
                key={category.id}
                className="flex flex-col items-center min-w-[120px] cursor-pointer group"
                onClick={() => onCategoryClick?.(category)}
              >
                <div className="w-20 h-20 rounded-full bg-secondary flex items-center justify-center mb-3 group-hover:bg-accent transition-all-300 group-hover:scale-110">
                  <span className="text-3xl">{category.icon}</span>
                </div>
                <p className="text-sm font-medium text-center group-hover:text-accent-foreground transition-colors">
                  {category.name}
                </p>
              </div>
            ))}
          </div>

          {/* Right scroll button */}
          <Button
            variant="outline"
            size="icon"
            className="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-background shadow-lg"
            onClick={scrollRight}
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  );
}