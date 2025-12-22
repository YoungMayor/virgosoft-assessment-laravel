# Task Breakdown & Implementation Plan

This document outlines the step-by-step approach to building the Limit-Order Exchange Mini Engine.

## Phase 1: Foundation & Database Setup

**Goal**: Establish the data structure and core models.

- **Database Migration**:
  - Update `users` table to include `balance` (decimal).
  - Create `assets` table: `user_id`, `symbol`, `amount`, `locked_amount`.
  - Create `orders` table: `user_id`, `symbol`, `side`, `price`, `amount`, `status`.
  - Create `trades` table (Optional/Bonus): Record executed matches.
- **Model Configuration**:
  - Define relationships (User hasMany Assets/Orders).
  - specific casts (decimal) for financial precision.

## Phase 2: Core API & Banking Logic

**Goal**: Enable users to view balances and manage potential orders.

- **Profile Endpoint**: `GET /api/profile` returning USD and Asset balances.
- **Order Read Endpoint**: `GET /api/orders` to fetch open orders (filtered by symbol).
- **Validation Logic**: Ensure users cannot withdraw/trade more than they own.

## Phase 3: Order Placement & Management

**Goal**: Allow users to create and cancel orders.

- **Create Order**: `POST /api/orders`
  - **Buy Logic**: Check `users.balance` >= `total_cost`. Deduct balance, create Order (Open).
  - **Sell Logic**: Check `assets.amount` >= `amount`. Move to `locked_amount`, create Order (Open).
- **Cancel Order**: `POST /api/orders/{id}/cancel`
  - Revert funds/assets from locked state to available state.
  - Mark order as Cancelled.

## Phase 4: The Matching Engine

**Goal**: Execute trades when conditions are met.

- **Matching Logic**:
  - Runs synchronously on Order Creation or via Job.
  - **Buy Match**: Find first SELL where `sell.price <= buy.price`.
  - **Sell Match**: Find first BUY where `buy.price >= sell.price`.
- **Execution**:
  - **Full Match Only** (per requirements).
  - **Commission**: Calculate 1.5% fee on matched USD volume.
  - **Atomic Updates**: Update User Balance and Asset Balances in a transaction.
  - **Status Update**: Mark matched orders as `filled`.

## Phase 5: Real-Time Integration

**Goal**: Push updates to the frontend instantly.

- **Configuration**: Setup Pusher (or Reverb) in `broadcasting.php`.
- **Events**: Create `OrderMatched` event.
- **Broadcasting**: Dispatch event on successful trade execution to `private-user.{id}` channels.

## Phase 6: Frontend Implementation (Vue.js + Tailwind)

**Goal**: Build the user interface.

- **Layout**: Main dashboard layout.
- **Order Form**:
  - Inputs: Symbol, Side, Price, Amount.
  - Validation & Submission.
- **Dashboard Components**:
  - Wallet Overview (Balances).
  - Order Book / Active Orders list.
  - Past Orders history.
- **Real-time Listener**:
  - Subscribe to private channel.
  - Update local state (Balances, Order Lists) upon receiving `OrderMatched`.

## Phase 7: Testing & Quality Assurance

**Goal**: Ensure reliability and correctness.

- **Unit/Feature Tests**:
  - Test Balance deduction/locking.
  - Test Matching Engine logic (Exact price, Better price).
  - Test Commission calculations.
  - Test Concurrency/Race conditions (using `DB::lockForUpdate`).
- **Manual Verification**: End-to-end user flow test.
