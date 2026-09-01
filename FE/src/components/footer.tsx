'use client';

import { CreditCard, Smartphone, Wallet, Banknote } from 'lucide-react';

export function Footer() {
  return (
    <footer className="bg-background border-t">
      <div className="container mx-auto px-4 py-6">
        <div className="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
          {/* Copyright */}
          <div className="text-sm text-muted-foreground">
            © 2024 Oneway. All rights reserved.
          </div>

          {/* Payment methods */}
          <div className="flex items-center space-x-4">
            <span className="text-sm text-muted-foreground">Payment methods:</span>
            <div className="flex space-x-3">
              <div className="w-8 h-8 bg-muted rounded flex items-center justify-center" title="Visa">
                <CreditCard className="h-4 w-4" />
              </div>
              <div className="w-8 h-8 bg-muted rounded flex items-center justify-center" title="MasterCard">
                <CreditCard className="h-4 w-4" />
              </div>
              <div className="w-8 h-8 bg-muted rounded flex items-center justify-center" title="PayPal">
                <Wallet className="h-4 w-4" />
              </div>
              <div className="w-8 h-8 bg-muted rounded flex items-center justify-center" title="Apple Pay">
                <Smartphone className="h-4 w-4" />
              </div>
              <div className="w-8 h-8 bg-muted rounded flex items-center justify-center" title="Cash on Delivery">
                <Banknote className="h-4 w-4" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}