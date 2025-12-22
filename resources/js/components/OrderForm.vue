<script setup lang="ts">
import axios from 'axios';
import { computed, ref } from 'vue';

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

const total = computed(() => {
    const p = parseFloat(form.value.price);
    const a = parseFloat(form.value.amount);
    if (!p || !a) return 0;
    return (p * a).toFixed(2);
});

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
        form.value.price = '';
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
        class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            New Order
        </h3>

        <div
            v-if="error"
            class="mb-4 rounded bg-red-50 p-2 text-sm text-red-600"
        >
            {{ error }}
        </div>
        <div
            v-if="success"
            class="mb-4 rounded bg-green-50 p-2 text-sm text-green-600"
        >
            {{ success }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Symbol Selection (Hardcoded for MVP) -->
            <div>
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-zinc-300"
                    >Asset</label
                >
                <select
                    v-model="form.symbol"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-zinc-700 dark:bg-zinc-800"
                >
                    <option value="BTC">Bitcoin (BTC)</option>
                    <option value="ETH">Ethereum (ETH)</option>
                </select>
            </div>

            <!-- Side Toggle -->
            <div>
                <label
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-300"
                    >Side</label
                >
                <div class="flex rounded-md shadow-sm">
                    <button
                        type="button"
                        @click="form.side = 'buy'"
                        :class="{
                            'bg-green-600 text-white': form.side === 'buy',
                            'bg-gray-100 text-gray-700 dark:bg-zinc-800 dark:text-zinc-300':
                                form.side !== 'buy',
                        }"
                        class="flex-1 rounded-l-md border border-gray-300 px-4 py-2 text-sm font-medium focus:z-10 focus:ring-2 focus:ring-indigo-500 dark:border-zinc-700"
                    >
                        Buy
                    </button>
                    <button
                        type="button"
                        @click="form.side = 'sell'"
                        :class="{
                            'bg-red-600 text-white': form.side === 'sell',
                            'bg-gray-100 text-gray-700 dark:bg-zinc-800 dark:text-zinc-300':
                                form.side !== 'sell',
                        }"
                        class="flex-1 rounded-r-md border border-l-0 border-gray-300 px-4 py-2 text-sm font-medium focus:z-10 focus:ring-2 focus:ring-indigo-500 dark:border-zinc-700"
                    >
                        Sell
                    </button>
                </div>
            </div>

            <!-- Price -->
            <div>
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-zinc-300"
                    >Price (USD)</label
                >
                <input
                    v-model="form.price"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    placeholder="0.00"
                />
            </div>

            <!-- Amount -->
            <div>
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-zinc-300"
                    >Amount</label
                >
                <input
                    v-model="form.amount"
                    type="number"
                    step="0.00000001"
                    min="0"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:border-zinc-700 dark:bg-zinc-800"
                    placeholder="0.00"
                />
            </div>

            <!-- Total -->
            <div class="border-t border-gray-100 pt-2 dark:border-zinc-800">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total:</span>
                    <span class="font-medium text-gray-900 dark:text-white"
                        >${{ total }}</span
                    >
                </div>
                <div class="mt-1 flex justify-between text-xs text-gray-400">
                    <span>Avail Balance:</span>
                    <span>
                        <span v-if="form.side === 'buy'"
                            >${{ props.balance }}</span
                        >
                        <span v-else
                            >{{
                                props.assets.find(
                                    (a) => a.symbol === form.symbol,
                                )?.amount || 0
                            }}
                            {{ form.symbol }}</span
                        >
                    </span>
                </div>
                <div class="mt-1 flex justify-between text-xs text-gray-400">
                    <span>Est. Fee (1.5%):</span>
                    <span>${{ (parseFloat(total) * 0.015).toFixed(2) }}</span>
                </div>
            </div>

            <button
                type="submit"
                :disabled="loading"
                :class="{
                    'bg-green-600 hover:bg-green-700': form.side === 'buy',
                    'bg-red-600 hover:bg-red-700': form.side === 'sell',
                }"
                class="flex w-full justify-center rounded-md border border-transparent px-4 py-2 text-sm font-medium text-white shadow-sm focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
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
