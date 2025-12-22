# Limit-Order Exchange Mini Engine

## Project Overview

This project is a technical assessment to build a simplified Limit-Order Exchange Mini Engine. The system allows users to place buy and sell limit orders for crypto assets (e.g., BTC, ETH). It features a matching engine that executes trades when order prices cross, handles atomic balance updates, manages commissions, and broadcasts real-time updates to connected clients.

## Technology Stack

- **Backend**: Laravel (PHP 8.3+)
- **Frontend**: Vue.js (Composition API) with Tailwind CSS
- **Database**: MySQL / PostgreSQL
- **Real-time**: Pusher via Laravel Broadcasting
- **Testing**: Pest

## Key Features

- **User Wallet**: Management of USD balance and Crypto Assets (Available vs. Locked).
- **Order Management**: Create and Cancel Limit Orders.
- **Matching Engine**:
  - Validates balance/asset availability.
  - Matches BUY enters against SELL orders (and vice versa) based on price limits.
  - Executes full matches (no partial matches required for this MVP).
  - Calculates and deducts a 1.5% commission.
- **Real-time Updates**: Instant UI updates for order status and balance changes using WebSocket events.
