export interface Product {
  id: string;
  name: string;
  description: string;
  originalPrice: number;
  discountedPrice?: number;
  discountPercentage?: number;
  rating: number;
  reviews: number;
  isNew?: boolean;
  category: string;
  colors: ProductColor[];
  images: string[];
  tags?: string[];
}

export interface ProductColor {
  id: string;
  name: string;
  hexCode: string;
  image: string;
  sizes: ProductSize[];
}

export interface ProductSize {
  id: string;
  size: string;
  stock: number;
  price?: number;
}

export interface CartItem {
  id: string;
  product: Product;
  color: ProductColor;
  size: ProductSize;
  quantity: number;
}

export interface Category {
  id: string;
  name: string;
  icon: string;
  image?: string;
}

export interface HeroSlide {
  id: string;
  subtitle: string;
  title: string;
  description: string;
  image: string;
  ctaText: string;
  ctaLink: string;
}

export interface Branch {
  id: string;
  name: string;
  address: string;
  phone: string;
  email: string;
}

export interface User {
  id: string;
  name: string;
  email: string;
  phone?: string;
  avatar?: string;
}