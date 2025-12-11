<template>
    <!-- Message Details Dialog -->
    <v-dialog v-model="dialog" max-width="800" persistent>
        <v-card class="conversation-dialog">
            <!-- Header with contact info -->
            <v-card-title class="conversation-header pa-4">
                <div class="d-flex align-center w-100">
                    <v-avatar color="primary" size="40" class="mr-3">
                        <v-icon color="white">mdi-account</v-icon>
                    </v-avatar>
                    <div class="flex-grow-1">
                        <div class="text-h6 mb-0">{{ contactName }}</div>
                        <div class="text-caption text-grey">
                            {{ contactPhone }}
                        </div>
                    </div>
                    <v-chip
                        :color="getStatusColor()"
                        small
                        outlined
                        class="mr-2"
                    >
                        {{ getConnectionStatus() }}
                    </v-chip>
                    <v-btn icon @click="closeDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </div>
            </v-card-title>

            <v-divider />

            <!-- Messages Container -->
            <v-card-text class="pa-0">
                <v-progress-linear
                    v-if="store.loading.conversation"
                    indeterminate
                    color="primary"
                />

                <div v-else class="messages-container" ref="messagesContainer">
                    <div
                        v-if="
                            Array.isArray(conversation) && conversation.length
                        "
                        class="pa-4"
                    >
                        <div
                            v-for="msg in conversation"
                            :key="msg.id"
                            class="message-wrapper mb-3"
                            :class="getMessageAlignment(msg)"
                        >
                            <!-- Message Bubble -->
                            <div
                                class="message-bubble elevation-1"
                                :class="getMessageBubbleClass(msg)"
                            >
                                <!-- Sender name for incoming messages -->
                                <div
                                    v-if="isIncomingMessage(msg)"
                                    class="message-sender text-caption font-weight-medium mb-1"
                                >
                                    {{ msg.sender_name || contactName }}
                                </div>

                                <!-- Message content -->
                                <div class="message-content">
                                    {{ msg.content }}
                                </div>

                                <!-- Media attachments -->
                                <div
                                    v-if="msg.image_url"
                                    class="message-media mt-2"
                                >
                                    <v-img
                                        :src="msg.image_url"
                                        max-height="200"
                                        max-width="300"
                                        contain
                                        class="rounded"
                                        @click="openImagePreview(msg.image_url)"
                                        style="cursor: pointer"
                                    />
                                </div>

                                <div
                                    v-if="msg.audio_url"
                                    class="message-media mt-2"
                                >
                                    <div class="audio-container">
                                        <v-btn
                                            icon
                                            small
                                            @click="
                                                toggleAudioWithRef(
                                                    msg.id,
                                                    $refs
                                                )
                                            "
                                            class="mr-2"
                                        >
                                            <v-icon>{{
                                                audioStates[msg.id]?.playing
                                                    ? "mdi-pause"
                                                    : "mdi-play"
                                            }}</v-icon>
                                        </v-btn>
                                        <audio
                                            :ref="`audio-${msg.id}`"
                                            :src="msg.audio_url"
                                            @ended="onAudioEnded(msg.id)"
                                            style="display: none"
                                        ></audio>
                                        <span class="text-caption"
                                            >Voice message</span
                                        >
                                    </div>
                                </div>

                                <!-- Message timestamp and status -->
                                <div
                                    class="message-meta d-flex align-center justify-end mt-1"
                                >
                                    <span class="text-caption text-grey mr-2">
                                        {{
                                            formatTimestamp(
                                                msg.created_at || msg.timestamp
                                            )
                                        }}
                                    </span>
                                    <v-icon
                                        v-if="isOutgoingMessage(msg)"
                                        :color="
                                            getStatusIconColor(
                                                msg.message_status
                                            )
                                        "
                                        size="14"
                                    >
                                        {{ getStatusIcon(msg.message_status) }}
                                    </v-icon>
                                    <!-- Retry button for failed messages -->
                                    <v-btn
                                        v-if="msg.message_status === 'failed'"
                                        icon
                                        x-small
                                        @click="retryFailedMessage(msg.id)"
                                        class="ml-1"
                                    >
                                        <v-icon size="12">mdi-refresh</v-icon>
                                    </v-btn>
                                </div>
                            </div>
                        </div>

                        <!-- Typing indicator -->
                        <div
                            v-if="typingIndicator"
                            class="typing-indicator mb-3"
                        >
                            <div class="message-bubble incoming">
                                <div class="typing-dots">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty state -->
                    <div v-else class="empty-state pa-8 text-center">
                        <v-icon size="64" color="grey lighten-2"
                            >mdi-message-outline</v-icon
                        >
                        <div class="text-h6 mt-4 text-grey">
                            No messages yet
                        </div>
                        <div class="text-caption text-grey">
                            Start the conversation below
                        </div>
                    </div>
                </div>
            </v-card-text>

            <!-- Reply Input Section -->
            <v-divider />
            <v-card-text class="reply-section pa-4">
                <!-- Attachment Preview -->
                <div v-if="hasAttachment" class="attachment-preview mb-3">
                    <v-chip
                        v-if="attachment.image && attachment.image.length"
                        close
                        @click:close="attachment.image = null"
                        color="blue lighten-4"
                        class="mr-2"
                    >
                        <v-icon left small>mdi-image</v-icon>
                        {{ attachment.image[0]?.name }}
                    </v-chip>
                    <v-chip
                        v-if="attachment.audio && attachment.audio.length"
                        close
                        @click:close="attachment.audio = null"
                        color="green lighten-4"
                    >
                        <v-icon left small>mdi-microphone</v-icon>
                        {{ attachment.audio[0]?.name }}
                    </v-chip>
                </div>

                <!-- select template -->

                <v-select
                    v-model="store.selectedTemplate"
                    :items="store.allTemplates"
                    item-title="name"
                    item-value="id"
                    label="Select Template"
                    return-object
                    :disabled="store.loading.templates"
                    :loading="store.loading.templates"
                    @update:model-value="store.onTemplateSelect"
                    class="mt-3"
                    clearable
                />

                <!-- Message Input -->
                <div class="message-input-container">
                    <v-textarea
                        v-model="store.messageText"
                        label="Type your message..."
                        rows="2"
                        auto-grow
                        outlined
                        dense
                        hide-details
                        class="message-input"
                        :disabled="connectionStatus !== 'online'"
                        @keydown.enter.exact.prevent="sendMessage"
                        @keydown.enter.shift.exact="addNewLine"
                    />

                    <!-- Input Actions -->
                    <div class="input-actions d-flex align-center mt-2">
                        <div class="d-flex align-center flex-grow-1">
                            <!-- Emoji Button -->
                            <v-btn
                                icon
                                small
                                @click="showEmojiPicker = !showEmojiPicker"
                                class="mr-2"
                                :disabled="connectionStatus !== 'online'"
                            >
                                <v-icon>mdi-emoticon-happy-outline</v-icon>
                            </v-btn>

                            <!-- Attachment Buttons -->
                            <v-menu offset-y>
                                <template v-slot:activator="{ on, attrs }">
                                    <v-btn
                                        icon
                                        small
                                        v-bind="attrs"
                                        v-on="on"
                                        class="mr-2"
                                        :disabled="
                                            connectionStatus !== 'online'
                                        "
                                    >
                                        <v-icon>mdi-attachment</v-icon>
                                    </v-btn>
                                </template>
                                <v-list dense>
                                    <v-list-item
                                        @click="$refs.imageInput.click()"
                                    >
                                        <v-list-item-icon>
                                            <v-icon>mdi-image</v-icon>
                                        </v-list-item-icon>
                                        <v-list-item-content>
                                            <v-list-item-title
                                                >Image</v-list-item-title
                                            >
                                        </v-list-item-content>
                                    </v-list-item>
                                    <v-list-item
                                        @click="$refs.audioInput.click()"
                                    >
                                        <v-list-item-icon>
                                            <v-icon>mdi-microphone</v-icon>
                                        </v-list-item-icon>
                                        <v-list-item-content>
                                            <v-list-item-title
                                                >Voice Note</v-list-item-title
                                            >
                                        </v-list-item-content>
                                    </v-list-item>
                                </v-list>
                            </v-menu>

                            <!-- Hidden file inputs -->
                            <input
                                ref="imageInput"
                                type="file"
                                accept="image/*"
                                @change="handleImageUploadEvent"
                                style="display: none"
                            />
                            <input
                                ref="audioInput"
                                type="file"
                                accept="audio/*"
                                @change="handleAudioUploadEvent"
                                style="display: none"
                            />

                            <!-- Character count -->
                            <span
                                v-if="replyMessage.length > 0"
                                class="text-caption text-grey ml-2"
                            >
                                {{ replyMessage.length }}
                            </span>
                        </div>

                        <!-- Send Button -->
                        <v-btn
                            color="primary"
                            @click="sendMessage"
                            class="ml-2"
                            :loading="sending"
                        >
                            <v-icon>mdi-send</v-icon>
                        </v-btn>
                    </div>

                    <!-- Emoji Picker -->
                    <v-expand-transition>
                        <div
                            v-if="showEmojiPicker"
                            class="emoji-picker mt-3 pa-3"
                        >
                            <div class="emoji-grid">
                                <v-btn
                                    v-for="emoji in commonEmojis"
                                    :key="emoji"
                                    text
                                    x-small
                                    @click="appendEmoji(emoji)"
                                    class="emoji-btn"
                                >
                                    {{ emoji }}
                                </v-btn>
                            </div>
                        </div>
                    </v-expand-transition>
                </div>
            </v-card-text>
        </v-card>

        <!-- Image Preview Dialog -->
        <v-dialog v-model="imagePreviewDialog" max-width="90vw">
            <v-card>
                <v-card-title class="pa-2">
                    <v-spacer />
                    <v-btn icon @click="closeImagePreview">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-card-title>
                <v-card-text class="pa-2">
                    <v-img :src="previewImageUrl" contain max-height="80vh" />
                </v-card-text>
            </v-card>
        </v-dialog>
    </v-dialog>
</template>

<script setup>
import { computed } from "vue";
import { nextTick, onMounted, watch, toRefs, ref } from "vue";
import { useConversationStore } from "@/stores/useConversationStore";
import { useWhatsAppStore } from "@/stores/whatsappStore";

import { useAuthStore } from "@/stores/auth";

const auth = useAuthStore();
const userId = computed(() => auth.user?.id);

const store = useWhatsAppStore();

const conversationStore = useConversationStore();
const {
    dialog,
    conversation,
    contactName,
    contactPhone,
    connectionStatus,
    replyMessage,
    attachment,
    imagePreviewDialog,
    previewImageUrl,
    typingIndicator,
    audioStates,
    sending,
} = toRefs(conversationStore);

const messagesContainer = ref(null);

const messagesContainerRef = ref(null);

const showEmojiPicker = ref(false);

const onTemplateSelect = (selectedTemplate) =>
    store.onTemplateSelect(selectedTemplate);
const commonEmojis = [
    "😀",
    "😂",
    "😍",
    "😭",
    "😡",
    "👍",
    "🙏",
    "🔥",
    "🎉",
    "💯",
];

const getConnectionStatus = () => {
    switch (connectionStatus.value) {
        case "online":
            return "Online";
        case "offline":
            return "Offline";
        case "connecting":
            return "Connecting...";
        default:
            return "Unknown";
    }
};

const getStatusColor = () => {
    switch (connectionStatus.value) {
        case "online":
            return "success";
        case "offline":
            return "grey";
        case "connecting":
            return "warning";
        default:
            return "grey";
    }
};

const isIncomingMessage = (msg) => msg.from !== "system";
const isOutgoingMessage = (msg) =>
    msg.from === "system" || msg.direction === "outgoing";

// const isIncomingMessage = (msg) => msg.direction === 'incoming' || msg.from !== 'system'
// const isOutgoingMessage = (msg) => msg.direction === 'outgoing' || msg.from === 'system'
const getMessageAlignment = (msg) =>
    isOutgoingMessage(msg) ? "outgoing" : "incoming";
const getMessageBubbleClass = (msg) =>
    isOutgoingMessage(msg) ? "outgoing" : "incoming";

const getStatusIcon = (status) => {
    switch (status) {
        case "sent":
            return "mdi-check";
        case "delivered":
            return "mdi-check-all";
        case "read":
            return "mdi-check-all";
        case "failed":
            return "mdi-alert-circle";
        case "sending":
            return "mdi-clock-outline";
        default:
            return "mdi-clock-outline";
    }
};

const getStatusIconColor = (status) => {
    switch (status) {
        case "read":
            return "blue";
        case "delivered":
            return "grey";
        case "sent":
            return "grey";
        case "failed":
            return "red";
        case "sending":
            return "orange";
        default:
            return "grey";
    }
};

const formatTimestamp = (timestamp) => {
    if (!timestamp) return "-";
    const date = new Date(timestamp);
    const now = new Date();
    const diffInHours = (now - date) / (1000 * 60 * 60);

    if (diffInHours < 24) {
        return date.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit",
        });
    } else if (diffInHours < 168) {
        return date.toLocaleDateString([], {
            weekday: "short",
            hour: "2-digit",
            minute: "2-digit",
        });
    } else {
        return date.toLocaleDateString([], { month: "short", day: "numeric" });
    }
};

const addNewLine = () => {
    replyMessage.value += "\n";
};

const handleImageUploadEvent = (event) => {
    const file = event.target.files[0];
    if (file) conversationStore.handleImageUpload(file);
};

const handleAudioUploadEvent = (event) => {
    const file = event.target.files[0];
    if (file) conversationStore.handleAudioUpload(file);
};

const toggleAudioWithRef = (messageId, refs) => {
    let audioRef = refs[`audio-${messageId}`];
    if (Array.isArray(audioRef)) audioRef = audioRef[0];
    if (audioRef) {
        conversationStore.toggleAudio(messageId, audioRef);
    } else {
        console.warn(`Audio element not found for messageId: ${messageId}`);
    }
};

// const scrollToBottom = (refs) => {
//   const container = refs.messagesContainer
//   if (container) {
//     nextTick(() => {
//       container.scrollTop = container.scrollHeight
//     })
//   }
// }

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainerRef.value) {
            messagesContainerRef.value.scrollTop =
                messagesContainerRef.value.scrollHeight;
        }
    });
};

const closeDialog = () => {
    dialog.value = false;
};

const sendMessage = () => store.sendMessage(userId.value);

const appendEmoji = (emoji) => {
    replyMessage.value += emoji;
};

const closeImagePreview = () => {
    imagePreviewDialog.value = false;
};

const retryFailedMessage = (id) => {
    conversationStore.retryFailedMessage(id);
};

const canSendMessage = computed(
    () =>
        replyMessage.value.trim().length > 0 ||
        attachment.value?.image ||
        attachment.value?.audio
);

onMounted(() => {
    scrollToBottom();
    store.initialize();
});

watch(
    conversation,
    () => {
        scrollToBottom();
    },
    { deep: true }
);

watch(dialog, (val) => {
    if (val) nextTick(() => scrollToBottom());
});
</script>

<style scoped>
.conversation-dialog {
    height: 90vh;
    display: flex;
    flex-direction: column;
    background: #ece5dd; /* WhatsApp-like background */
}

/* HEADER */
.conversation-header {
    background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
    color: white;
}

/* MAIN CONTAINER */
.messages-container {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #ece5dd;
    background-image: url("https://i.ibb.co/vz8msV1/chat-bg-light.png");
    background-size: cover;
}

/* MESSAGE WRAPPER */
.message-wrapper {
    display: flex;
    margin-bottom: 8px;
    animation: slideInFromBottom 0.3s ease-out;
}

.message-wrapper.outgoing {
    justify-content: flex-end;
}

.message-wrapper.incoming {
    justify-content: flex-start;
}

/* MESSAGE BUBBLE */
.message-bubble {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 18px;
    word-wrap: break-word;
    position: relative;
    line-height: 1.4;
    font-size: 0.95rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
}

.message-bubble.outgoing {
    background: #dcf8c6; /* light green */
    color: #000;
    border-bottom-right-radius: 4px;
}

.message-bubble.incoming {
    background: #fff;
    color: #000;
    border-bottom-left-radius: 4px;
}

/* MESSAGE META (timestamp + icon) */
.message-meta {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    font-size: 11px;
    margin-top: 4px;
    color: #777;
}

.message-sender {
    font-size: 11px;
    color: #666;
    margin-bottom: 4px;
}

/* REPLY SECTION */
.reply-section {
    background: #f0f0f0;
    border-top: 1px solid #ddd;
}

.message-input-container {
    display: flex;
    flex-direction: column;
}

.attachment-preview {
    border: 1px dashed #ccc;
    border-radius: 8px;
    padding: 6px;
    background: #fafafa;
}

/* EMOJI PICKER */
.emoji-picker {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.emoji-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(36px, 1fr));
    gap: 4px;
}

.emoji-btn {
    min-width: 36px !important;
    font-size: 18px;
    transition: transform 0.1s ease;
}

.emoji-btn:hover {
    transform: scale(1.2);
}

/* TYPING INDICATOR */
.typing-indicator {
    display: flex;
    justify-content: flex-start;
}

.typing-dots {
    display: flex;
    gap: 4px;
    padding: 8px 12px;
}

.typing-dots span {
    width: 6px;
    height: 6px;
    background-color: #999;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out both;
}

.typing-dots span:nth-child(1) {
    animation-delay: -0.32s;
}
.typing-dots span:nth-child(2) {
    animation-delay: -0.16s;
}

@keyframes typing {
    0%,
    80%,
    100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

/* ANIMATION */
@keyframes slideInFromBottom {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* MOBILE */
@media (max-width: 600px) {
    .conversation-dialog {
        height: 100vh;
        border-radius: 0;
    }
    .message-bubble {
        max-width: 85%;
    }
}
</style>
