<script setup lang="ts">
import { computed, ref } from 'vue';
import OrderList from './OrderList.vue';

const props = defineProps<{
    orders: any[];
    currentUserId: number;
}>();

const emit = defineEmits(['cancel-order']);

const activeTab = ref<'my_orders' | 'all_orders'>('my_orders');

const filteredOrders = computed(() => {
    if (activeTab.value === 'my_orders') {
        return props.orders.filter((o) => o.user_id === props.currentUserId);
    }
    return props.orders;
});
</script>

<template>
    <div
        class="flex h-full flex-col overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
    >
        <!-- Tabs Header -->
        <div class="flex border-b border-gray-100 dark:border-zinc-800">
            <button
                @click="activeTab = 'my_orders'"
                class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-medium transition-colors duration-200"
                :class="
                    activeTab === 'my_orders'
                        ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                "
            >
                My Open Orders
            </button>
            <button
                @click="activeTab = 'all_orders'"
                class="flex-1 border-b-2 px-4 py-3 text-center text-sm font-medium transition-colors duration-200"
                :class="
                    activeTab === 'all_orders'
                        ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                "
            >
                All Open Orders
            </button>
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col overflow-hidden p-4">
            <OrderList
                :orders="filteredOrders"
                :current-user-id="currentUserId"
                :show-cancel="true"
                @cancel="(id) => emit('cancel-order', id)"
            />
        </div>
    </div>
</template>
