<script setup lang="ts">
import OrderForm from '@/components/OrderForm.vue';
import OrderTabs from '@/components/OrderTabs.vue';
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

            <!-- Main Content - Centered Single Column -->
            <div
                class="flex-1 overflow-y-auto bg-gray-50/50 p-4 dark:bg-black/20"
            >
                <div class="mx-auto max-w-3xl space-y-4">
                    <!-- Place Order Card -->
                    <OrderForm
                        :balance="balance"
                        :assets="assets"
                        @order-placed="refreshData"
                    />

                    <!-- Order Tabs (Your Orders / All Orders) -->
                    <div class="h-[500px]">
                        <OrderTabs
                            :orders="orders"
                            :current-user-id="userId"
                            @cancel-order="cancelOrder"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
