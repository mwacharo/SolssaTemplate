<template>
    <v-dialog v-model="dialog" max-width="900" scrollable>
        <template v-slot:activator="{ props: activatorProps }">
            <v-btn
                v-bind="activatorProps"
                color="purple"
                variant="text"
                icon="mdi-video"
                size="small"
            />
        </template>

        <template v-slot:default="{ isActive }">
            <v-card>
                <v-card-title
                    class="d-flex justify-space-between align-center bg-grey-lighten-3"
                >
                    <span class="text-h6">Call Recordings</span>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        @click="isActive.value = false"
                    />
                </v-card-title>

                <v-divider />

                <v-card-text class="pa-0" style="max-height: 70vh">
                    <v-container
                        v-if="callLogs.length === 0"
                        class="text-center py-8"
                    >
                        <v-icon size="64" color="grey-lighten-1"
                            >mdi-phone-off</v-icon
                        >
                        <p class="text-grey mt-4">
                            No call recordings available
                        </p>
                    </v-container>

                    <v-list v-else lines="three">
                        <template
                            v-for="(call, index) in callLogs"
                            :key="call.id"
                        >
                            <v-list-item class="px-4 py-3">
                                <v-card flat border>
                                    <v-card-text>
                                        <!-- Call Info Grid -->
                                        <v-row dense>
                                            <v-col cols="12" sm="6" md="4">
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    Direction
                                                </div>
                                                <div class="font-weight-medium">
                                                    <v-chip
                                                        :color="
                                                            call.direction ===
                                                            'Inbound'
                                                                ? 'blue'
                                                                : 'orange'
                                                        "
                                                        size="small"
                                                        variant="flat"
                                                    >
                                                        <v-icon start>
                                                            {{
                                                                call.direction ===
                                                                "Inbound"
                                                                    ? "mdi-phone-incoming"
                                                                    : "mdi-phone-outgoing"
                                                            }}
                                                        </v-icon>
                                                        {{ call.direction }}
                                                    </v-chip>
                                                </div>
                                            </v-col>

                                            <v-col cols="12" sm="6" md="4">
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    Status
                                                </div>
                                                <v-chip
                                                    :color="
                                                        call.status ===
                                                        'Success'
                                                            ? 'success'
                                                            : 'error'
                                                    "
                                                    size="small"
                                                    variant="flat"
                                                >
                                                    {{ call.status }}
                                                </v-chip>
                                            </v-col>

                                            <v-col cols="12" sm="6" md="4">
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    Duration
                                                </div>
                                                <div class="font-weight-medium">
                                                    {{
                                                        formatDuration(
                                                            call.durationInSeconds
                                                        )
                                                    }}
                                                </div>
                                            </v-col>

                                            <v-col cols="12" sm="6" md="4">
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    Caller
                                                </div>
                                                <div class="font-weight-medium">
                                                    {{ call.callerNumber }}
                                                </div>
                                            </v-col>

                                            <v-col cols="12" sm="6" md="4">
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    Destination
                                                </div>
                                                <div class="font-weight-medium">
                                                    {{ call.destinationNumber }}
                                                </div>
                                            </v-col>

                                            <v-col cols="12" sm="6" md="4">
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    Start Time
                                                </div>
                                                <div class="font-weight-medium">
                                                    {{
                                                        formatDateTime(
                                                            call.callStartTime
                                                        )
                                                    }}
                                                </div>
                                            </v-col>

                                            <v-col cols="12" sm="6" md="4">
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    Amount
                                                </div>
                                                <div class="font-weight-medium">
                                                    {{ call.currencyCode }}
                                                    {{ call.amount }}
                                                </div>
                                            </v-col>

                                            <v-col cols="12" sm="6" md="4">
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    Session State
                                                </div>
                                                <div class="font-weight-medium">
                                                    {{ call.callSessionState }}
                                                </div>
                                            </v-col>
                                        </v-row>

                                        <!-- Recording -->
                                        <v-divider
                                            v-if="call.recordingUrl"
                                            class="my-4"
                                        />
                                        <div v-if="call.recordingUrl">
                                            <div
                                                class="text-caption text-grey mb-2"
                                            >
                                                <v-icon size="small" start
                                                    >mdi-microphone</v-icon
                                                >
                                                Recording
                                            </div>
                                            <audio
                                                controls
                                                :src="call.recordingUrl"
                                                class="w-100"
                                                preload="metadata"
                                            />
                                        </div>

                                        <!-- Transcript -->
                                        <template
                                            v-if="call.calltranscripts?.length"
                                        >
                                            <v-divider class="my-4" />
                                            <div>
                                                <div
                                                    class="text-subtitle-2 font-weight-bold mb-2"
                                                >
                                                    <v-icon start
                                                        >mdi-text</v-icon
                                                    >
                                                    Transcript
                                                </div>
                                                <p class="text-body-2 mb-3">
                                                    {{
                                                        call.calltranscripts[0]
                                                            .transcript
                                                    }}
                                                </p>

                                                <v-card
                                                    variant="tonal"
                                                    color="grey-lighten-4"
                                                >
                                                    <v-card-text>
                                                        <v-row dense>
                                                            <v-col
                                                                cols="12"
                                                                sm="6"
                                                            >
                                                                <div
                                                                    class="text-caption text-grey"
                                                                >
                                                                    Sentiment
                                                                </div>
                                                                <div
                                                                    class="font-weight-medium"
                                                                >
                                                                    {{
                                                                        call
                                                                            .calltranscripts[0]
                                                                            .sentiment
                                                                    }}
                                                                </div>
                                                            </v-col>

                                                            <v-col
                                                                cols="12"
                                                                sm="6"
                                                            >
                                                                <div
                                                                    class="text-caption text-grey"
                                                                >
                                                                    Fulfillment
                                                                    Score
                                                                </div>
                                                                <div
                                                                    class="font-weight-medium"
                                                                >
                                                                    {{
                                                                        call
                                                                            .calltranscripts[0]
                                                                            .fulfillment_score
                                                                    }}%
                                                                </div>
                                                            </v-col>

                                                            <v-col
                                                                cols="12"
                                                                sm="6"
                                                            >
                                                                <div
                                                                    class="text-caption text-grey"
                                                                >
                                                                    CS Rating
                                                                </div>
                                                                <div
                                                                    class="font-weight-medium"
                                                                >
                                                                    <v-rating
                                                                        :model-value="
                                                                            call
                                                                                .calltranscripts[0]
                                                                                .cs_rating
                                                                        "
                                                                        density="compact"
                                                                        size="small"
                                                                        readonly
                                                                        color="amber"
                                                                    />
                                                                    {{
                                                                        call
                                                                            .calltranscripts[0]
                                                                            .cs_rating
                                                                    }}/5
                                                                </div>
                                                            </v-col>

                                                            <v-col
                                                                cols="12"
                                                                sm="6"
                                                            >
                                                                <div
                                                                    class="text-caption text-grey"
                                                                >
                                                                    Intent
                                                                </div>
                                                                <div
                                                                    class="font-weight-medium"
                                                                >
                                                                    {{
                                                                        call
                                                                            .calltranscripts[0]
                                                                            .analysis
                                                                            ?.intent ||
                                                                        "N/A"
                                                                    }}
                                                                </div>
                                                            </v-col>

                                                            <v-col cols="12">
                                                                <div
                                                                    class="text-caption text-grey mb-2"
                                                                >
                                                                    Keywords
                                                                </div>
                                                                <v-chip-group>
                                                                    <v-chip
                                                                        v-for="keyword in call
                                                                            .calltranscripts[0]
                                                                            .analysis
                                                                            ?.keywords ||
                                                                        []"
                                                                        :key="
                                                                            keyword
                                                                        "
                                                                        size="small"
                                                                        color="blue"
                                                                        variant="flat"
                                                                    >
                                                                        {{
                                                                            keyword
                                                                        }}
                                                                    </v-chip>
                                                                </v-chip-group>
                                                            </v-col>
                                                        </v-row>
                                                    </v-card-text>
                                                </v-card>
                                            </div>
                                        </template>
                                    </v-card-text>
                                </v-card>
                            </v-list-item>
                            <v-divider
                                v-if="index < callLogs.length - 1"
                                :key="`divider-${call.id}`"
                            />
                        </template>
                    </v-list>
                </v-card-text>

                <v-divider />

                <v-card-actions>
                    <v-spacer />
                    <v-btn
                        color="primary"
                        variant="flat"
                        @click="isActive.value = false"
                    >
                        Close
                    </v-btn>
                </v-card-actions>
            </v-card>
        </template>
    </v-dialog>
</template>

<script setup>
import { ref } from "vue";
import { computed } from "vue";
const dialog = ref(false);

const internalCallLogs = ref([]);
const callLogs = computed(() => internalCallLogs.value || []);

const open = (logs = []) => {
    internalCallLogs.value = logs;
    dialog.value = true;
};

const formatDuration = (seconds) => {
    if (!seconds) return "0s";
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
};

const formatDateTime = (dateTime) => {
    if (!dateTime) return "N/A";
    return new Date(dateTime).toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Expose the open method so it can be called via template ref
defineExpose({
    open,
});
</script>
