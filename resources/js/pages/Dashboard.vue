    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Top Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
               <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border shadow-sm">
                   <div class="text-sm text-gray-500">USD Balance</div>
                   <div class="text-2xl font-bold">${{ balance.toFixed(2) }}</div>
               </div>
               <div v-for="asset in assets" :key="asset.symbol" class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border shadow-sm">
                   <div class="text-sm text-gray-500">{{ asset.symbol }} Balance</div>
                   <div class="text-2xl font-bold">{{ parseFloat(asset.amount).toFixed(8) }}</div>
                   <div class="text-xs text-gray-400">Locked: {{ parseFloat(asset.locked_amount).toFixed(8) }}</div>
               </div>
            </div>

            <!-- Main Exchange Area -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: Order Form -->
                <div class="lg:col-span-1">
                    <OrderForm :balance="balance" :assets="assets" @order-placed="refreshData" />
                </div>

                <!-- Middle: Order Book -->
                <div class="lg:col-span-1">
                    <OrderBook :orders="orders" symbol="BTC" />
                </div>

                <!-- Right: My Open Orders -->
                <div class="lg:col-span-1 bg-white dark:bg-zinc-900 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-zinc-800">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">My Open Orders</h3>
                    <div class="space-y-2 h-64 overflow-y-auto">
                        <div v-for="order in myOrders" :key="order.id" class="flex justify-between items-center text-sm p-2 bg-gray-50 dark:bg-zinc-800 rounded">
                            <div>
                                <span :class="order.side === 'buy' ? 'text-green-600' : 'text-red-600'" class="font-medium uppercase">{{ order.side }}</span>
                                <span class="ml-2 font-mono">{{ order.symbol }}</span>
                                <div class="text-xs text-gray-400">@ ${{ parseFloat(order.price).toFixed(2) }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono">{{ parseFloat(order.amount).toFixed(8) }}</div>
                                <button @click="cancelOrder(order.id)" class="text-red-500 text-xs hover:underline mt-1">Cancel</button>
                            </div>
                        </div>
                        <div v-if="myOrders.length === 0" class="text-center text-gray-400 text-sm py-4">No open orders</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import OrderForm from '@/components/OrderForm.vue';
import OrderBook from '@/components/OrderBook.vue';
import { setupEcho } from '@/echo';

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
    return orders.value.filter(o => o.user_id === userId.value && o.status === 'open');
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
        window.Echo.private(`user.${userId.value}`)
            .listen('OrderMatched', (e: any) => {
                console.log('Order Matched Event:', e);
                refreshData();
                // Could be optimized to patch data instead of full refresh,
                // but requirement says "update balance, asset and order list instantly".
                // Full fetch ensures consistency.
            });
    }
});
</script>
