<script setup lang="ts">
import OrderBook from '@/components/OrderBook.vue';
import OrderForm from '@/components/OrderForm.vue';
import { setupEcho } from '@/echo';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const balance = ref(0);
const assets = ref<any[]>([]);
const orders = ref<any[]>([]); // All orders for Book
const page = usePage();
const userId = computed(() => page.props.auth.user.id);

const myOrders = computed(() => {
    return orders.value.filter(
        (o) => o.user_id === userId.value && o.status === 'open',
    );
});

async function fetchProfile() {
    try {
        const res = await axios.get('/api/profile');
        balance.value = res.data.usd_balance;
        assets.value = res.data.assets;
    } catch (e) {
        console.error(e);
    }
}

async function fetchOrders() {
    try {
        const res = await axios.get('/api/orders?symbol=BTC');
        orders.value = res.data;
    } catch (e) {
        console.error(e);
    }
}

async function cancelOrder(id: number) {
    if (!confirm('Are you sure you want to cancel this order?')) return;
    try {
        await axios.post(`/api/orders/${id}/cancel`);
        refreshData();
    } catch (e) {
        alert('Failed to cancel order');
    }
}

function refreshData() {
    fetchProfile();
    fetchOrders();
}

onMounted(() => {
    fetchProfile();
    fetchOrders();

    setupEcho();

    // Listen for real-time updates
    if (userId.value) {
        window.Echo.private(`user.${userId.value}`).listen(
            'OrderMatched',
            (e: any) => {
                console.log('Order Matched Event:', e);
                refreshData();
            },
        );
    }
});
</script>

<template>
    <Head title="Trade" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col overflow-hidden">
            <!-- Ticker / Wallet Stats Bar -->
            <div
                class="flex shrink-0 items-center overflow-x-auto border-b border-sidebar-border bg-white px-6 py-3 shadow-sm dark:bg-zinc-900"
            >
                <div
                    class="mr-6 flex items-center border-r border-gray-100 pr-6 dark:border-zinc-800"
                >
                    <span
                        class="mr-2 text-xs font-medium tracking-wider text-gray-500 uppercase"
                        >USD Balance</span
                    >
                    <span
                        class="font-mono text-lg font-bold text-gray-900 dark:text-white"
                        >${{ balance.toFixed(2) }}</span
                    >
                </div>

                <div class="flex items-center space-x-6">
                    <div
                        v-for="asset in assets"
                        :key="asset.symbol"
                        class="flex items-center"
                    >
                        <div
                            class="mr-3 rounded-full bg-gray-100 p-1.5 dark:bg-zinc-800"
                        >
                            <!-- Simple Asset Icon Placeholder -->
                            <span
                                class="text-xs font-bold text-gray-600 dark:text-gray-300"
                                >{{ asset.symbol[0] }}</span
                            >
                        </div>
                        <div>
                            <div
                                class="text-xs font-medium text-gray-500 uppercase"
                            >
                                {{ asset.symbol }}
                            </div>
                            <div
                                class="font-mono text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                {{ parseFloat(asset.amount).toFixed(8) }}
                            </div>
                        </div>
                        <div
                            v-if="parseFloat(asset.locked_amount) > 0"
                            class="ml-3 text-xs text-gray-400"
                        >
                            <span class="block">Locked</span>
                            <span class="font-mono">{{
                                parseFloat(asset.locked_amount).toFixed(8)
                            }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div
                class="grid flex-1 grid-cols-1 overflow-hidden lg:grid-cols-12"
            >
                <!-- Left: Order Book (Market) -->
                <div
                    class="flex flex-col border-r border-gray-200 bg-gray-50/50 p-4 lg:col-span-8 dark:border-zinc-800 dark:bg-black/20"
                >
                    <OrderBook
                        :orders="orders"
                        symbol="BTC"
                        class="h-full shadow-sm"
                    />
                </div>

                <!-- Right: Actions & History -->
                <div
                    class="flex flex-col gap-4 overflow-y-auto bg-white p-4 lg:col-span-4 dark:bg-zinc-900"
                >
                    <!-- Order Form -->
                    <div class="shrink-0">
                        <OrderForm
                            :balance="balance"
                            :assets="assets"
                            @order-placed="refreshData"
                        />
                    </div>

                    <!-- My Open Orders -->
                    <div
                        class="flex flex-1 flex-col rounded-xl border border-gray-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                    >
                        <div
                            class="border-b border-gray-100 p-4 dark:border-zinc-800"
                        >
                            <h3
                                class="font-semibold text-gray-900 dark:text-white"
                            >
                                Open Orders
                            </h3>
                        </div>

                        <div class="flex-1 overflow-y-auto p-2">
                            <div
                                v-for="order in myOrders"
                                :key="order.id"
                                class="mb-2 flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-800/50"
                            >
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="
                                                order.side === 'buy'
                                                    ? 'bg-green-100 text-green-600 dark:bg-green-900/30'
                                                    : 'bg-red-100 text-red-600 dark:bg-red-900/30'
                                            "
                                            class="rounded px-1.5 py-0.5 text-xs font-bold uppercase"
                                            >{{ order.side }}</span
                                        >
                                        <span
                                            class="font-mono font-medium text-gray-900 dark:text-white"
                                            >{{ order.symbol }}</span
                                        >
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500">
                                        {{
                                            parseFloat(order.amount).toFixed(6)
                                        }}
                                        @ ${{
                                            parseFloat(order.price).toFixed(2)
                                        }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <button
                                        @click="cancelOrder(order.id)"
                                        class="rounded-md p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                        title="Cancel Order"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="16"
                                            height="16"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="lucide lucide-x"
                                        >
                                            <path d="M18 6 6 18" />
                                            <path d="m6 6 18 18" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div
                                v-if="myOrders.length === 0"
                                class="flex h-32 items-center justify-center text-sm text-gray-400"
                            >
                                No open orders
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
