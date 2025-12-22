<script setup lang="ts">
import axios from 'axios';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    balance: number;
    assets: any[];
}>();

const emit = defineEmits(['order-placed']);

const form = ref({
    symbol: 'BTC',
    side: 'buy',
    price: '',
    amount: '',
});

const loading = ref(false);
const error = ref('');
const success = ref('');

// Computed Available Balance based on Side
const availableBalance = computed(() => {
    if (form.value.side === 'buy') {
        return props.balance;
    } else {
        const asset = props.assets.find((a) => a.symbol === form.value.symbol);
        return asset ? parseFloat(asset.amount) : 0;
    }
});

const total = computed(() => {
    const p = parseFloat(form.value.price);
    const a = parseFloat(form.value.amount);
    if (!p || !a) return 0;
    return (p * a).toFixed(2);
});

// Watch for side change to reset amounts if needed or just clear error
watch(
    () => form.value.side,
    () => {
        error.value = '';
        success.value = '';
    },
);

function setPercentage(percent: number) {
    if (!form.value.price) {
        // If price is not set, we can't calculate amount for BUY (since we need Total Cost <= Balance)
        // For SELL, we can calculate simply based on asset amount.
        if (form.value.side === 'sell') {
            form.value.amount = (availableBalance.value * percent).toFixed(8);
        } else {
            error.value = 'Please set a price first to calculate Buy amount.';
        }
        return;
    }

    if (form.value.side === 'buy') {
        // Buy: Total Cost = Price * Amount <= Balance
        // Amount = Balance / Price
        // Adjusted for safe margin? No, just raw calculation.
        const maxAmount = availableBalance.value / parseFloat(form.value.price);
        form.value.amount = (maxAmount * percent).toFixed(8);
    } else {
        // Sell: Amount <= Asset Balance
        form.value.amount = (availableBalance.value * percent).toFixed(8);
    }
}

async function submit() {
    loading.value = true;
    error.value = '';
    success.value = '';

    try {
        const payload = {
            symbol: form.value.symbol,
            side: form.value.side,
            price: form.value.price,
            amount: form.value.amount,
        };

        const response = await axios.post('/api/orders', payload);
        emit('order-placed', response.data);
        success.value = 'Order placed successfully!';

        // Reset form but keep symbol
        // form.value.price = ''; // Keep price for rapid trading? Usually better to keep.
        form.value.amount = '';
    } catch (e: any) {
        error.value = e.response?.data?.message || 'Failed to place order.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div
        class="h-full rounded-xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
        <div class="mb-5 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">
                Place Order
            </h3>
            <div class="text-xs text-gray-400">
                Avail:
                <span
                    class="font-mono font-medium text-gray-700 dark:text-gray-200"
                >
                    {{
                        form.side === 'buy'
                            ? '$' + availableBalance.toFixed(2)
                            : availableBalance.toFixed(8) + ' ' + form.symbol
                    }}
                </span>
            </div>
        </div>

        <div
            v-if="error"
            class="mb-4 rounded-md bg-red-50 p-2 text-xs text-red-600"
        >
            {{ error }}
        </div>
        <div
            v-if="success"
            class="mb-4 rounded-md bg-green-50 p-2 text-xs text-green-600"
        >
            {{ success }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Buy/Sell Tabs -->
            <div class="flex rounded-lg bg-gray-100 p-1 dark:bg-zinc-800">
                <button
                    type="button"
                    @click="form.side = 'buy'"
                    class="flex-1 rounded-md py-1.5 text-sm font-medium transition-all"
                    :class="{
                        'bg-green-600 text-white shadow-sm':
                            form.side === 'buy',
                        'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200':
                            form.side !== 'buy',
                    }"
                >
                    Buy
                </button>
                <button
                    type="button"
                    @click="form.side = 'sell'"
                    class="flex-1 rounded-md py-1.5 text-sm font-medium transition-all"
                    :class="{
                        'bg-red-600 text-white shadow-sm': form.side === 'sell',
                        'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200':
                            form.side !== 'sell',
                    }"
                >
                    Sell
                </button>
            </div>

            <!-- Inputs -->
            <div class="space-y-4">
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-zinc-400"
                        >Asset</label
                    >
                    <select
                        v-model="form.symbol"
                        class="block w-full rounded-lg border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                    >
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                    </select>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-zinc-400"
                        >Price (USD)</label
                    >
                    <div class="relative rounded-md shadow-sm">
                        <input
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        />
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                        >
                            <span class="text-gray-400 sm:text-sm">$</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-gray-500 dark:text-zinc-400"
                        >Amount</label
                    >
                    <div class="relative rounded-md shadow-sm">
                        <input
                            v-model="form.amount"
                            type="number"
                            step="0.00000001"
                            min="0"
                            placeholder="0.00000000"
                            class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                        />
                        <div
                            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                        >
                            <span class="text-gray-400 sm:text-sm">{{
                                form.symbol
                            }}</span>
                        </div>
                    </div>
                    <!-- Percentage Buttons -->
                    <div class="mt-2 grid grid-cols-4 gap-2">
                        <button
                            v-for="pct in [0.25, 0.5, 0.75, 1]"
                            :key="pct"
                            type="button"
                            @click="setPercentage(pct)"
                            class="rounded border border-gray-200 bg-white py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-gray-300 dark:hover:bg-zinc-700"
                        >
                            {{ pct * 100 }}%
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-zinc-800/50">
                <div class="flex justify-between">
                    <span class="text-gray-500">Est. Total</span>
                    <span class="font-semibold text-gray-900 dark:text-white"
                        >${{ total }}</span
                    >
                </div>
                <div class="mt-1 flex justify-between text-xs text-gray-400">
                    <span>Fee (1.5%)</span>
                    <span>${{ (parseFloat(total) * 0.015).toFixed(2) }}</span>
                </div>
            </div>

            <button
                type="submit"
                :disabled="loading"
                :class="{
                    'bg-green-600 hover:bg-green-700 focus:ring-green-500':
                        form.side === 'buy',
                    'bg-red-600 hover:bg-red-700 focus:ring-red-500':
                        form.side === 'sell',
                }"
                class="w-full rounded-lg py-2.5 text-sm font-bold text-white shadow-sm transition-all focus:ring-2 focus:ring-offset-2 disabled:opacity-50"
            >
                <span v-if="loading">Processing...</span>
                <span v-else>{{
                    form.side === 'buy'
                        ? 'Buy ' + form.symbol
                        : 'Sell ' + form.symbol
                }}</span>
            </button>
        </form>
    </div>
</template>
