<template>
  <div class="bg-secondary py-12">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-8">
        <!-- Column 1: Logo & Social -->
        <div class="space-y-4">
          <div>
            <h3 class="text-2xl font-bold text-primary mb-2">Oneway</h3>
            <p class="text-sm text-muted-foreground">
              Your gateway to elegant fashion and premium quality clothing.
            </p>
          </div>
          <div class="flex space-x-3">
            <button class="p-2 rounded-md border border-border hover:bg-accent hover:text-accent-foreground">
              <Facebook class="h-4 w-4" />
            </button>
            <button class="p-2 rounded-md border border-border hover:bg-accent hover:text-accent-foreground">
              <Twitter class="h-4 w-4" />
            </button>
            <button class="p-2 rounded-md border border-border hover:bg-accent hover:text-accent-foreground">
              <Instagram class="h-4 w-4" />
            </button>
            <button class="p-2 rounded-md border border-border hover:bg-accent hover:text-accent-foreground">
              <Youtube class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Column 2: Useful Links -->
        <div>
          <h4 class="font-semibold mb-4">Useful Links</h4>
          <ul class="space-y-2">
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Home
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                About Us
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Products
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Contact Us
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Login / Profile
              </a>
            </li>
          </ul>
        </div>

        <!-- Column 3: Categories -->
        <div>
          <h4 class="font-semibold mb-4">Categories</h4>
          <ul class="space-y-2">
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Dresses
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Tops
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Bottoms
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Jackets
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Accessories
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Shoes
              </a>
            </li>
          </ul>
        </div>

        <!-- Column 4: Branches -->
        <div>
          <h4 class="font-semibold mb-4">Branches</h4>
          <ul class="space-y-2">
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Dubai Mall
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Abu Dhabi Branch
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Sharjah Branch
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Al Ain Branch
              </a>
            </li>
            <li>
              <a href="#" class="text-sm text-muted-foreground hover:text-primary transition-colors">
                Ras Al Khaimah
              </a>
            </li>
          </ul>
        </div>

        <!-- Column 5: Contact Info -->
        <div>
          <h4 class="font-semibold mb-4">Contact Info</h4>
          <div class="space-y-3">
            <div class="flex items-center space-x-2">
              <MapPin class="h-4 w-4 text-muted-foreground" />
              <span class="text-sm text-muted-foreground">
                United Arab Emirates
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <Phone class="h-4 w-4 text-muted-foreground" />
              <span class="text-sm text-muted-foreground">
                +971 4 123 4567
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <Phone class="h-4 w-4 text-muted-foreground" />
              <span class="text-sm text-muted-foreground">
                +971 2 765 4321
              </span>
            </div>
            <div class="flex items-center space-x-2">
              <Mail class="h-4 w-4 text-muted-foreground" />
              <span class="text-sm text-muted-foreground">
                info@oneway.com
              </span>
            </div>
          </div>
          
          <!-- Newsletter -->
          <div class="mt-6">
            <h5 class="font-medium mb-2">Subscribe to our newsletter</h5>
            <div class="flex space-x-2">
              <input
                type="email"
                placeholder="Your email"
                class="flex-1 border border-input bg-background rounded-md px-3 py-2 text-sm ring-offset-background file:border-0 placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
              />
              <button class="px-3 py-2 bg-accent hover:bg-accent/90 text-accent-foreground rounded-md text-sm">
                Subscribe
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Facebook, Twitter, Instagram, Youtube, MapPin, Phone, Mail } from 'lucide-vue-next'
</script>