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
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
            <!-- Top Stats -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div
                    class="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-zinc-900"
                >
                    <div class="text-sm text-gray-500">USD Balance</div>
                    <div class="text-2xl font-bold">
                        ${{ balance.toFixed(2) }}
                    </div>
                </div>
                <div
                    v-for="asset in assets"
                    :key="asset.symbol"
                    class="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-zinc-900"
                >
                    <div class="text-sm text-gray-500">
                        {{ asset.symbol }} Balance
                    </div>
                    <div class="text-2xl font-bold">
                        {{ parseFloat(asset.amount).toFixed(8) }}
                    </div>
                    <div class="text-xs text-gray-400">
                        Locked: {{ parseFloat(asset.locked_amount).toFixed(8) }}
                    </div>
                </div>
            </div>

            <!-- Main Exchange Area -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left: Order Form -->
                <div class="lg:col-span-1">
                    <OrderForm
                        :balance="balance"
                        :assets="assets"
                        @order-placed="refreshData"
                    />
                </div>

                <!-- Middle: Order Book -->
                <div class="lg:col-span-1">
                    <OrderBook :orders="orders" symbol="BTC" />
                </div>

                <!-- Right: My Open Orders -->
                <div
                    class="rounded-lg border border-gray-100 bg-white p-6 shadow-sm lg:col-span-1 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <h3
                        class="mb-4 text-lg font-semibold text-gray-900 dark:text-white"
                    >
                        My Open Orders
                    </h3>
                    <div class="h-64 space-y-2 overflow-y-auto">
                        <div
                            v-for="order in myOrders"
                            :key="order.id"
                            class="flex items-center justify-between rounded bg-gray-50 p-2 text-sm dark:bg-zinc-800"
                        >
                            <div>
                                <span
                                    :class="
                                        order.side === 'buy'
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    "
                                    class="font-medium uppercase"
                                    >{{ order.side }}</span
                                >
                                <span class="ml-2 font-mono">{{
                                    order.symbol
                                }}</span>
                                <div class="text-xs text-gray-400">
                                    @ ${{ parseFloat(order.price).toFixed(2) }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono">
                                    {{ parseFloat(order.amount).toFixed(8) }}
                                </div>
                                <button
                                    @click="cancelOrder(order.id)"
                                    class="mt-1 text-xs text-red-500 hover:underline"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                        <div
                            v-if="myOrders.length === 0"
                            class="py-4 text-center text-sm text-gray-400"
                        >
                            No open orders
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
