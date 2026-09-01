'use client';

import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { Product, ProductColor, ProductSize, CartItem } from '@/types/store';

interface StoreState {
  // Cart state
  cart: CartItem[];
  addToCart: (product: Product, color: ProductColor, size: ProductSize, quantity?: number) => void;
  removeFromCart: (itemId: string) => void;
  updateQuantity: (itemId: string, quantity: number) => void;
  clearCart: () => void;
  getCartTotal: () => number;
  getCartCount: () => number;
  
  // Product modal state
  selectedProduct: Product | null;
  isProductModalOpen: boolean;
  openProductModal: (product: Product) => void;
  closeProductModal: () => void;
  
  // RTL state
  isRTL: boolean;
  toggleRTL: () => void;
  
  // Favorites state
  favorites: string[];
  toggleFavorite: (productId: string) => void;
  isFavorite: (productId: string) => boolean;
}

export const useStore = create<StoreState>()(
  persist(
    (set, get) => ({
      // Cart
      cart: [],
      addToCart: (product, color, size, quantity = 1) => {
        const existingItem = get().cart.find(
          item => 
            item.product.id === product.id && 
            item.color.id === color.id && 
            item.size.id === size.id
        );

        if (existingItem) {
          set(state => ({
            cart: state.cart.map(item =>
              item.id === existingItem.id
                ? { ...item, quantity: item.quantity + quantity }
                : item
            )
          }));
        } else {
          const newItem: CartItem = {
            id: `${product.id}-${color.id}-${size.id}`,
            product,
            color,
            size,
            quantity
          };
          set(state => ({ cart: [...state.cart, newItem] }));
        }
      },
      removeFromCart: (itemId) => {
        set(state => ({
          cart: state.cart.filter(item => item.id !== itemId)
        }));
      },
      updateQuantity: (itemId, quantity) => {
        if (quantity <= 0) {
          get().removeFromCart(itemId);
        } else {
          set(state => ({
            cart: state.cart.map(item =>
              item.id === itemId ? { ...item, quantity } : item
            )
          }));
        }
      },
      clearCart: () => set({ cart: [] }),
      getCartTotal: () => {
        return get().cart.reduce((total, item) => {
          const price = item.product.discountedPrice || item.product.originalPrice;
          return total + (price * item.quantity);
        }, 0);
      },
      getCartCount: () => {
        return get().cart.reduce((count, item) => count + item.quantity, 0);
      },

      // Product modal
      selectedProduct: null,
      isProductModalOpen: false,
      openProductModal: (product) => set({ 
        selectedProduct: product, 
        isProductModalOpen: true 
      }),
      closeProductModal: () => set({ 
        selectedProduct: null, 
        isProductModalOpen: false 
      }),

      // RTL
      isRTL: false,
      toggleRTL: () => set(state => ({ isRTL: !state.isRTL })),

      // Favorites
      favorites: [],
      toggleFavorite: (productId) => {
        set(state => ({
          favorites: state.favorites.includes(productId)
            ? state.favorites.filter(id => id !== productId)
            : [...state.favorites, productId]
        }));
      },
      isFavorite: (productId) => {
        return get().favorites.includes(productId);
      }
    }),
    {
      name: 'oneway-store',
      partialize: (state) => ({
        cart: state.cart,
        isRTL: state.isRTL,
        favorites: state.favorites
      })
    }
  )
);