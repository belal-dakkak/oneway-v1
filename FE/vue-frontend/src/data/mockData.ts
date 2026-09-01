import type { Product, Category, HeroSlide, Branch } from '@/types/store'

export const mockProducts: Product[] = [
  {
    id: '1',
    name: 'Classic Leather Jacket',
    description: 'Premium genuine leather jacket with modern fit and timeless design.',
    originalPrice: 299,
    discountedPrice: 199,
    discountPercentage: 33,
    rating: 4.5,
    reviews: 128,
    isNew: false,
    category: 'jackets',
    colors: [
      {
        id: 'c1',
        name: 'Black',
        hexCode: '#000000',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's1', size: 'S', stock: 5 },
          { id: 's2', size: 'M', stock: 8 },
          { id: 's3', size: 'L', stock: 3 },
          { id: 's4', size: 'XL', stock: 6 }
        ]
      },
      {
        id: 'c2',
        name: 'Brown',
        hexCode: '#8B4513',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's5', size: 'S', stock: 2 },
          { id: 's6', size: 'M', stock: 4 },
          { id: 's7', size: 'L', stock: 7 }
        ]
      }
    ],
    images: ['/api/placeholder/400/500', '/api/placeholder/400/500', '/api/placeholder/400/500'],
    tags: ['leather', 'classic', 'premium']
  },
  {
    id: '2',
    name: 'Silk Evening Dress',
    description: 'Elegant silk dress perfect for special occasions and evening events.',
    originalPrice: 189,
    discountedPrice: 149,
    discountPercentage: 21,
    rating: 4.8,
    reviews: 89,
    isNew: true,
    category: 'dresses',
    colors: [
      {
        id: 'c3',
        name: 'Navy Blue',
        hexCode: '#000080',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's8', size: 'XS', stock: 4 },
          { id: 's9', size: 'S', stock: 6 },
          { id: 's10', size: 'M', stock: 5 },
          { id: 's11', size: 'L', stock: 3 }
        ]
      },
      {
        id: 'c4',
        name: 'Burgundy',
        hexCode: '#800020',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's12', size: 'S', stock: 3 },
          { id: 's13', size: 'M', stock: 7 },
          { id: 's14', size: 'L', stock: 4 }
        ]
      }
    ],
    images: ['/api/placeholder/400/500', '/api/placeholder/400/500'],
    tags: ['silk', 'evening', 'elegant']
  },
  {
    id: '3',
    name: 'Premium Cotton T-Shirt',
    description: 'Comfortable and stylish cotton t-shirt for everyday wear.',
    originalPrice: 49,
    rating: 4.2,
    reviews: 256,
    isNew: false,
    category: 'tops',
    colors: [
      {
        id: 'c5',
        name: 'White',
        hexCode: '#FFFFFF',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's15', size: 'XS', stock: 10 },
          { id: 's16', size: 'S', stock: 15 },
          { id: 's17', size: 'M', stock: 20 },
          { id: 's18', size: 'L', stock: 12 },
          { id: 's19', size: 'XL', stock: 8 }
        ]
      },
      {
        id: 'c6',
        name: 'Gray',
        hexCode: '#808080',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's20', size: 'S', stock: 6 },
          { id: 's21', size: 'M', stock: 9 },
          { id: 's22', size: 'L', stock: 4 }
        ]
      }
    ],
    images: ['/api/placeholder/400/500'],
    tags: ['cotton', 'casual', 'basic']
  },
  {
    id: '4',
    name: 'Designer Handbag',
    description: 'Luxurious designer handbag crafted with premium materials.',
    originalPrice: 450,
    discountedPrice: 325,
    discountPercentage: 28,
    rating: 4.9,
    reviews: 67,
    isNew: true,
    category: 'accessories',
    colors: [
      {
        id: 'c7',
        name: 'Tan',
        hexCode: '#D2B48C',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's23', size: 'One Size', stock: 5 }
        ]
      },
      {
        id: 'c8',
        name: 'Black',
        hexCode: '#000000',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's24', size: 'One Size', stock: 3 }
        ]
      }
    ],
    images: ['/api/placeholder/400/500', '/api/placeholder/400/500', '/api/placeholder/400/500'],
    tags: ['luxury', 'designer', 'handbag']
  },
  {
    id: '5',
    name: 'Slim Fit Jeans',
    description: 'Modern slim fit jeans with comfortable stretch fabric.',
    originalPrice: 89,
    rating: 4.3,
    reviews: 194,
    isNew: false,
    category: 'bottoms',
    colors: [
      {
        id: 'c9',
        name: 'Dark Blue',
        hexCode: '#00008B',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's25', size: '28', stock: 4 },
          { id: 's26', size: '30', stock: 7 },
          { id: 's27', size: '32', stock: 6 },
          { id: 's28', size: '34', stock: 3 }
        ]
      },
      {
        id: 'c10',
        name: 'Black',
        hexCode: '#000000',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's29', size: '30', stock: 5 },
          { id: 's30', size: '32', stock: 8 },
          { id: 's31', size: '34', stock: 4 }
        ]
      }
    ],
    images: ['/api/placeholder/400/500', '/api/placeholder/400/500'],
    tags: ['jeans', 'slim-fit', 'casual']
  },
  {
    id: '6',
    name: 'Cashmere Sweater',
    description: 'Ultra-soft cashmere sweater for ultimate comfort and style.',
    originalPrice: 175,
    discountedPrice: 140,
    discountPercentage: 20,
    rating: 4.7,
    reviews: 112,
    isNew: false,
    category: 'knitwear',
    colors: [
      {
        id: 'c11',
        name: 'Camel',
        hexCode: '#C19A6B',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's32', size: 'S', stock: 3 },
          { id: 's33', size: 'M', stock: 6 },
          { id: 's34', size: 'L', stock: 4 }
        ]
      },
      {
        id: 'c12',
        name: 'Charcoal',
        hexCode: '#36454F',
        image: '/api/placeholder/400/500',
        sizes: [
          { id: 's35', size: 'S', stock: 2 },
          { id: 's36', size: 'M', stock: 5 },
          { id: 's37', size: 'L', stock: 7 }
        ]
      }
    ],
    images: ['/api/placeholder/400/500', '/api/placeholder/400/500'],
    tags: ['cashmere', 'luxury', 'sweater']
  }
]

export const mockCategories: Category[] = [
  { id: '1', name: 'Dresses', icon: '👗', image: '/api/placeholder/100/100' },
  { id: '2', name: 'Tops', icon: '👔', image: '/api/placeholder/100/100' },
  { id: '3', name: 'Bottoms', icon: '👖', image: '/api/placeholder/100/100' },
  { id: '4', name: 'Jackets', icon: '🧥', image: '/api/placeholder/100/100' },
  { id: '5', name: 'Knitwear', icon: '🧶', image: '/api/placeholder/100/100' },
  { id: '6', name: 'Accessories', icon: '👜', image: '/api/placeholder/100/100' },
  { id: '7', name: 'Shoes', icon: '👠', image: '/api/placeholder/100/100' },
  { id: '8', name: 'Sportswear', icon: '🏃', image: '/api/placeholder/100/100' }
]

export const mockHeroSlides: HeroSlide[] = [
  {
    id: '1',
    subtitle: 'New Collection',
    title: 'Spring/Summer 2024',
    description: 'Discover our latest collection with exclusive designs and premium quality',
    image: '/api/placeholder/1200/600',
    ctaText: 'Shop Now',
    ctaLink: '/products'
  },
  {
    id: '2',
    subtitle: 'Limited Offer',
    title: 'Up to 50% Off',
    description: 'Don\'t miss our amazing deals on selected items for a limited time',
    image: '/api/placeholder/1200/600',
    ctaText: 'View Deals',
    ctaLink: '/products?sale=true'
  },
  {
    id: '3',
    subtitle: 'Premium Quality',
    title: 'Designer Selection',
    description: 'Explore our curated collection of designer pieces and luxury brands',
    image: '/api/placeholder/1200/600',
    ctaText: 'Explore',
    ctaLink: '/products?designer=true'
  }
]

export const mockBranches: Branch[] = [
  {
    id: '1',
    name: 'Dubai Mall',
    address: 'Dubai Mall, Downtown Dubai, UAE',
    phone: '+971 4 123 4567',
    email: 'dubai@oneway.com'
  },
  {
    id: '2',
    name: 'Abu Dhabi Branch',
    address: 'Yas Mall, Abu Dhabi, UAE',
    phone: '+971 2 765 4321',
    email: 'abudhabi@oneway.com'
  },
  {
    id: '3',
    name: 'Sharjah Branch',
    address: 'City Centre Sharjah, UAE',
    phone: '+971 6 555 1234',
    email: 'sharjah@oneway.com'
  }
]