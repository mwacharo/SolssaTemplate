<template>
    <AppLayout>
        <v-container>
            <v-btn color="primary" @click="openDialog">
                Generate Call Agent Invoice
            </v-btn>

            <!-- Dialog -->
            <v-dialog v-model="dialog" max-width="900">
                <v-card>
                    <v-card-title>Call Agent Payment</v-card-title>

                    <v-card-text>
                        <!-- Filters -->
                        <v-row>
                            <v-col cols="12" md="4">
                                <v-text-field
                                    v-model="filters.from"
                                    label="Date From"
                                    type="date"
                                />
                            </v-col>

                            <v-col cols="12" md="4">
                                <v-text-field
                                    v-model="filters.to"
                                    label="Date To"
                                    type="date"
                                />
                            </v-col>

                            <v-col cols="12" md="4">
                                <v-select
                                    v-model="filters.agent"
                                    :items="agents"
                                    item-title="name"
                                    item-value="id"
                                    label="Call Agent"
                                />
                            </v-col>
                        </v-row>

                        <!-- Orders -->
                        <v-table class="mt-4">
                            <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Delivered At</th>
                                    <th>Payment</th>
                                    <th>Commission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="order in filteredOrders"
                                    :key="order.id"
                                >
                                    <td>{{ order.order_no }}</td>
                                    <td>{{ order.delivered_at }}</td>
                                    <td>{{ order.payment_method }}</td>
                                    <td>{{ rate }}</td>
                                </tr>
                            </tbody>
                        </v-table>

                        <!-- Summary -->
                        <v-divider class="my-4" />
                        <strong>
                            Total Orders: {{ filteredOrders.length }} <br />
                            Total Commission: {{ totalCommission }}
                        </strong>
                    </v-card-text>

                    <v-card-actions>
                        <v-spacer />
                        <v-btn text @click="dialog = false">Cancel</v-btn>
                        <v-btn color="success" @click="generateInvoice">
                            Submit
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-dialog>
        </v-container>
    </AppLayout>
</template>

<script setup></script>
