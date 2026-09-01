'use client';

import { useEffect } from 'react';
import { Header } from '@/components/header';
import { HeroBanner } from '@/components/hero-banner';
import { CategoriesSlider } from '@/components/categories-slider';
import { FeaturedProducts } from '@/components/featured-products';
import { Sitemap } from '@/components/sitemap';
import { Footer } from '@/components/footer';
import { useStore } from '@/lib/store';
import { mockProducts, mockCategories, mockHeroSlides } from '@/lib/mock-data';

export default function Home() {
  const { isRTL } = useStore();

  useEffect(() => {
    // Apply RTL class to body when RTL is enabled
    if (isRTL) {
      document.body.classList.add('rtl');
      document.body.classList.remove('ltr');
    } else {
      document.body.classList.add('ltr');
      document.body.classList.remove('rtl');
    }
  }, [isRTL]);

  const handleCategoryClick = (category: any) => {
    // Navigate to category page (placeholder)
    console.log('Navigate to category:', category.name);
  };

  return (
    <div className={`min-h-screen ${isRTL ? 'rtl' : 'ltr'}`}>
      <Header />
      
      <main>
        {/* Hero Banner */}
        <HeroBanner slides={mockHeroSlides} />

        {/* Categories Slider */}
        <CategoriesSlider 
          categories={mockCategories}
          onCategoryClick={handleCategoryClick}
        />

        {/* Featured Products */}
        <FeaturedProducts products={mockProducts} />

        {/* Additional sections can be added here */}
        <section className="py-12 bg-secondary">
          <div className="container mx-auto px-4 text-center">
            <h2 className="text-3xl font-bold mb-4">Why Choose Oneway?</h2>
            <p className="text-muted-foreground max-w-2xl mx-auto mb-8">
              Discover premium quality fashion with our carefully curated collection. 
              From timeless classics to modern trends, we bring you the best in contemporary style.
            </p>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
              <div className="text-center">
                <div className="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                  <span className="text-2xl">🚚</span>
                </div>
                <h3 className="font-semibold mb-2">Free Shipping</h3>
                <p className="text-sm text-muted-foreground">On orders over $100</p>
              </div>
              <div className="text-center">
                <div className="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                  <span className="text-2xl">🔄</span>
                </div>
                <h3 className="font-semibold mb-2">Easy Returns</h3>
                <p className="text-sm text-muted-foreground">30-day return policy</p>
              </div>
              <div className="text-center">
                <div className="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                  <span className="text-2xl">🎁</span>
                </div>
                <h3 className="font-semibold mb-2">Member Benefits</h3>
                <p className="text-sm text-muted-foreground">Exclusive offers and rewards</p>
              </div>
            </div>
          </div>
        </section>
      </main>

      {/* Sitemap */}
      <Sitemap />

      {/* Footer */}
      <Footer />
    </div>
  );
}