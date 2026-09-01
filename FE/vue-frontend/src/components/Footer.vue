<template>
  <footer class="bg-background border-t">
    <div class="container mx-auto px-4 py-6">
      <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
        <!-- Copyright -->
        <div class="text-sm text-muted-foreground">
          © 2024 Oneway. All rights reserved.
        </div>

        <!-- Payment methods -->
        <div class="flex items-center space-x-4">
          <span class="text-sm text-muted-foreground">Payment methods:</span>
          <div class="flex space-x-3">
            <div class="w-8 h-8 bg-muted rounded flex items-center justify-center" title="Visa">
              <CreditCard class="h-4 w-4" />
            </div>
            <div class="w-8 h-8 bg-muted rounded flex items-center justify-center" title="MasterCard">
              <CreditCard class="h-4 w-4" />
            </div>
            <div class="w-8 h-8 bg-muted rounded flex items-center justify-center" title="PayPal">
              <Wallet class="h-4 w-4" />
            </div>
            <div class="w-8 h-8 bg-muted rounded flex items-center justify-center" title="Apple Pay">
              <Smartphone class="h-4 w-4" />
            </div>
            <div class="w-8 h-8 bg-muted rounded flex items-center justify-center" title="Cash on Delivery">
              <Banknote class="h-4 w-4" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { CreditCard, Wallet, Smartphone, Banknote } from 'lucide-vue-next'
</script>