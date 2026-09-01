'use client';

import { useState } from 'react';
import { Search, ShoppingCart, User, Menu, X, Heart, Globe } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useStore } from '@/lib/store';

interface HeaderProps {
  onMenuToggle?: () => void;
}

export function Header({ onMenuToggle }: HeaderProps) {
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const { cart, getCartCount, isRTL, toggleRTL } = useStore();

  return (
    <header className="sticky top-0 z-50 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 border-b">
      <div className="container mx-auto px-4">
        {/* Top bar */}
        <div className="flex items-center justify-between h-16">
          {/* Menu button (mobile) */}
          <Button
            variant="ghost"
            size="icon"
            className="md:hidden"
            onClick={onMenuToggle}
          >
            <Menu className="h-5 w-5" />
          </Button>

          {/* Logo */}
          <div className="flex items-center">
            <h1 className="text-2xl font-bold text-primary">Oneway</h1>
          </div>

          {/* Search bar (desktop) */}
          <div className="hidden md:flex flex-1 max-w-md mx-8">
            <div className="relative w-full">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search products..."
                className="pl-10 pr-4"
              />
            </div>
          </div>

          {/* Actions */}
          <div className="flex items-center space-x-2">
            {/* Search button (mobile) */}
            <Button
              variant="ghost"
              size="icon"
              className="md:hidden"
              onClick={() => setIsSearchOpen(!isSearchOpen)}
            >
              <Search className="h-5 w-5" />
            </Button>

            {/* RTL Toggle */}
            <Button
              variant="ghost"
              size="icon"
              onClick={toggleRTL}
              title={isRTL ? "Switch to LTR" : "Switch to RTL"}
            >
              <Globe className="h-5 w-5" />
            </Button>

            {/* Favorites */}
            <Button variant="ghost" size="icon">
              <Heart className="h-5 w-5" />
            </Button>

            {/* Cart */}
            <Button variant="ghost" size="icon" className="relative" onClick={() => window.location.href = '/cart'}>
              <ShoppingCart className="h-5 w-5" />
              {getCartCount() > 0 && (
                <Badge className="absolute -top-2 -right-2 h-5 w-5 rounded-full p-0 flex items-center justify-center text-xs">
                  {getCartCount()}
                </Badge>
              )}
            </Button>

            {/* User */}
            <Button variant="ghost" size="icon">
              <User className="h-5 w-5" />
            </Button>
          </div>
        </div>

        {/* Mobile search */}
        {isSearchOpen && (
          <div className="md:hidden py-3 border-t">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search products..."
                className="pl-10 pr-4"
                autoFocus
              />
            </div>
          </div>
        )}

        {/* Navigation */}
        <nav className="hidden md:flex items-center space-x-8 py-4 border-t">
          <a href="/" className="text-sm font-medium hover:text-primary transition-colors">
            Home
          </a>
          <a href="/products" className="text-sm font-medium hover:text-primary transition-colors">
            Products
          </a>
          <a href="#categories" className="text-sm font-medium hover:text-primary transition-colors">
            Categories
          </a>
          <a href="#about" className="text-sm font-medium hover:text-primary transition-colors">
            About Us
          </a>
          <a href="#contact" className="text-sm font-medium hover:text-primary transition-colors">
            Contact
          </a>
          <a href="/products?sale=true" className="text-sm font-medium text-red-600 hover:text-red-700 transition-colors">
            Sale
          </a>
        </nav>
      </div>
    </header>
  );
}