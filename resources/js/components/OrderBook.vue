<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    orders: any[];
    symbol: string;
}>();

const asks = computed(() => {
    return props.orders
        .filter((o) => o.side === 'sell')
        .sort((a, b) => parseFloat(a.price) - parseFloat(b.price))
        .slice(0, 15); // Show top 15
});

const bids = computed(() => {
    return props.orders
        .filter((o) => o.side === 'buy')
        .sort((a, b) => parseFloat(b.price) - parseFloat(a.price))
        .slice(0, 15); // Show top 15
});

// Calculate max volume for depth visualization
const maxVolume = computed(() => {
    const all = [...asks.value, ...bids.value];
    if (!all.length) return 1;
    return Math.max(...all.map((o) => parseFloat(o.amount)));
});

const spread = computed(() => {
    if (asks.value.length === 0 || bids.value.length === 0) return null;
    const lowestAsk = parseFloat(asks.value[0].price);
    const highestBid = parseFloat(bids.value[0].price);
    return (lowestAsk - highestBid).toFixed(2);
});
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="border-b border-gray-100 p-4 dark:border-zinc-800">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                Order Book <span class="text-gray-400">({{ symbol }})</span>
            </h3>
        </div>

        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Header -->
            <div
                class="grid grid-cols-3 gap-2 px-4 py-2 text-xs font-medium tracking-wider text-gray-400 uppercase"
            >
                <div class="text-left">Price</div>
                <div class="text-right">Amount</div>
                <div class="text-right">Total</div>
            </div>

            <!-- List -->
            <div class="flex-1 overflow-y-auto font-mono text-xs">
                <!-- Asks (Sells) -->
                <div class="flex flex-col-reverse">
                    <!-- Flex reverse to stack lowest ask at bottom -->
                    <div
                        v-for="order in asks"
                        :key="order.id"
                        class="relative grid cursor-pointer grid-cols-3 gap-2 px-4 py-1 hover:bg-gray-50 dark:hover:bg-zinc-800"
                    >
                        <!-- Depth Bar -->
                        <div
                            class="absolute inset-y-0 right-0 bg-red-100/50 dark:bg-red-900/20"
                            :style="{
                                width:
                                    (parseFloat(order.amount) / maxVolume) *
                                        100 +
                                    '%',
                            }"
                        ></div>

                        <div class="relative z-10 text-red-500">
                            {{ parseFloat(order.price).toFixed(2) }}
                        </div>
                        <div
                            class="relative z-10 text-right text-gray-600 dark:text-gray-400"
                        >
                            {{ parseFloat(order.amount).toFixed(6) }}
                        </div>
                        <div
                            class="relative z-10 text-right text-gray-400 dark:text-gray-500"
                        >
                            {{
                                (
                                    parseFloat(order.price) *
                                    parseFloat(order.amount)
                                ).toFixed(2)
                            }}
                        </div>
                    </div>
                </div>

                <!-- Spread -->
                <div
                    class="sticky top-0 z-20 my-1 flex items-center justify-center border-y border-gray-100 py-1.5 text-center text-xs font-medium text-gray-500 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <span v-if="spread">Spread: {{ spread }}</span>
                    <span v-else class="text-gray-400">-</span>
                </div>

                <!-- Bids (Buys) -->
                <div>
                    <div
                        v-for="order in bids"
                        :key="order.id"
                        class="relative grid cursor-pointer grid-cols-3 gap-2 px-4 py-1 hover:bg-gray-50 dark:hover:bg-zinc-800"
                    >
                        <!-- Depth Bar -->
                        <div
                            class="absolute inset-y-0 right-0 bg-green-100/50 dark:bg-green-900/20"
                            :style="{
                                width:
                                    (parseFloat(order.amount) / maxVolume) *
                                        100 +
                                    '%',
                            }"
                        ></div>

                        <div class="relative z-10 text-green-500">
                            {{ parseFloat(order.price).toFixed(2) }}
                        </div>
                        <div
                            class="relative z-10 text-right text-gray-600 dark:text-gray-400"
                        >
                            {{ parseFloat(order.amount).toFixed(6) }}
                        </div>
                        <div
                            class="relative z-10 text-right text-gray-400 dark:text-gray-500"
                        >
                            {{
                                (
                                    parseFloat(order.price) *
                                    parseFloat(order.amount)
                                ).toFixed(2)
                            }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
