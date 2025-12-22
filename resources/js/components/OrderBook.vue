<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    orders: any[];
    symbol: string;
}>();

const asks = computed(() => {
    return props.orders
        .filter((o) => o.side === 'sell')
        .sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
});

const bids = computed(() => {
    return props.orders
        .filter((o) => o.side === 'buy')
        .sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
});
</script>

<template>
    <div
        class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            Order Book ({{ symbol }})
        </h3>

        <div
            class="mb-2 grid grid-cols-2 gap-4 text-xs font-medium tracking-wider text-gray-500 uppercase"
        >
            <div>Price (USD)</div>
            <div class="text-right">Amount</div>
        </div>

        <div class="h-64 space-y-1 overflow-y-auto">
            <!-- Asks (Sells) - Red -->
            <div
                v-for="order in asks"
                :key="order.id"
                class="grid cursor-pointer grid-cols-2 gap-4 rounded p-1 text-sm transition-colors hover:bg-gray-50 dark:hover:bg-zinc-800"
            >
                <div class="text-red-500">
                    {{ parseFloat(order.price).toFixed(2) }}
                </div>
                <div class="text-right text-gray-700 dark:text-gray-300">
                    {{ parseFloat(order.amount).toFixed(8) }}
                </div>
            </div>

            <div
                v-if="asks.length === 0"
                class="py-2 text-center text-xs text-gray-400"
            >
                No asks
            </div>

            <div
                class="my-2 border-t border-b border-gray-100 py-2 text-center text-xs text-gray-400 dark:border-zinc-800"
            >
                Spread
            </div>

            <!-- Bids (Buys) - Green -->
            <div
                v-for="order in bids"
                :key="order.id"
                class="grid cursor-pointer grid-cols-2 gap-4 rounded p-1 text-sm transition-colors hover:bg-gray-50 dark:hover:bg-zinc-800"
            >
                <div class="text-green-500">
                    {{ parseFloat(order.price).toFixed(2) }}
                </div>
                <div class="text-right text-gray-700 dark:text-gray-300">
                    {{ parseFloat(order.amount).toFixed(8) }}
                </div>
            </div>
            <div
                v-if="bids.length === 0"
                class="py-2 text-center text-xs text-gray-400"
            >
                No bids
            </div>
        </div>
    </div>
</template>
