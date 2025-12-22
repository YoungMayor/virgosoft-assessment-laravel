<script setup lang="ts">
defineProps<{
    orders: any[];
    currentUserId?: number;
    showCancel?: boolean;
}>();

const emit = defineEmits(['cancel']);
</script>

<template>
    <div class="flex-1 overflow-y-auto">
        <div
            v-if="orders.length === 0"
            class="flex h-32 items-center justify-center text-sm text-gray-400"
        >
            No orders found
        </div>

        <div v-else class="inline-block min-w-full align-middle">
            <div class="overflow-hidden rounded-lg border dark:border-zinc-800">
                <table
                    class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800"
                >
                    <thead class="bg-gray-50 dark:bg-zinc-800/50">
                        <tr>
                            <th
                                scope="col"
                                class="px-4 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Type
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Price
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Amount
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                            >
                                Total
                            </th>
                            <th
                                scope="col"
                                class="px-4 py-3 text-right text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400"
                                v-if="showCancel"
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-gray-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900"
                    >
                        <tr
                            v-for="order in orders"
                            :key="order.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-zinc-800/50"
                        >
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    :class="
                                        order.side === 'buy'
                                            ? 'bg-green-100 text-green-600 dark:bg-green-900/30'
                                            : 'bg-red-100 text-red-600 dark:bg-red-900/30'
                                    "
                                    class="rounded px-2 py-0.5 text-xs font-bold uppercase"
                                >
                                    {{ order.side }}
                                </span>
                                <span
                                    class="ml-2 font-mono text-xs text-gray-500"
                                    >{{ order.symbol }}</span
                                >
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono text-sm whitespace-nowrap text-gray-900 dark:text-gray-200"
                            >
                                {{ parseFloat(order.price).toFixed(2) }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono text-sm whitespace-nowrap text-gray-500 dark:text-gray-400"
                            >
                                {{ parseFloat(order.amount).toFixed(6) }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono text-sm whitespace-nowrap text-gray-900 dark:text-gray-200"
                            >
                                {{
                                    (
                                        parseFloat(order.price) *
                                        parseFloat(order.amount)
                                    ).toFixed(2)
                                }}
                            </td>
                            <td
                                class="px-4 py-3 text-right text-sm font-medium whitespace-nowrap"
                                v-if="showCancel"
                            >
                                <button
                                    v-if="currentUserId === order.user_id"
                                    @click="emit('cancel', order.id)"
                                    class="text-red-600 transition-colors hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                    title="Cancel Order"
                                >
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
