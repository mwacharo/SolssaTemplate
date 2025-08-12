// stores/webrtc.js
import { defineStore } from 'pinia';
import Africastalking from 'africastalking-client';
import { usePage } from '@inertiajs/vue3';

import { computed, ref } from 'vue';

export const useWebRTCStore = defineStore('webrtc', () => {



    const agentStatus = ref('offline');

    const page = usePage();

    // Debug: Log the page props to inspect user values
    // console.debug("Page props:", JSON.stringify(page.props));

    const userToken = computed(() => {
        const token = page.props.auth?.user?.token;
        // console.debug("✅ Computed userToken:", token);
        return token;
    });

    const userId = computed(() => {
        const id = page.props.auth?.user?.id;
        // console.debug("✅ Computed userId:", id);
        return id;
    });


    const afClient = ref(null);
    const incomingCallDialog = ref(false);
    // const incomingCall = ref({ from: '', duration: '' });
    const incomingCall = ref(null);



    const connection_active = ref(false);


    const connectionStatusColor = computed(() => {
        return connection_active.value ? 'success' : 'error'; // green if ready
    });

    const connectionStatusText = computed(() => {
        return connection_active.value ? 'Online' : 'Offline';
    });



    function setIncomingCall(data) {
        incomingCall.value = data;
        incomingCallDialog.value = true;
    }

    function closeIncomingCallDialog() {
        incomingCallDialog.value = false;
        incomingCall.value = null;
    }


    async function waitForToken() {
        return new Promise((resolve) => {
            const check = setInterval(() => {
                if (userToken.value) {
                    clearInterval(check);
                    resolve();
                }
            }, 300);
        });
    }


    async function updateAgentStatus(status) {
    try {
        agentStatus.value = status;
       await axios.post('/api/v1/agent/status', { status });
        console.log(`✅ Agent status updated to ${status}`);
    } catch (error) {
        console.error("❌ Failed to update agent status:", error);
    }
}

    async function initializeAfricastalking() {
        if (!userToken.value) {
            console.warn("Waiting for token...");
            await waitForToken();
        }

        if (afClient.value) {
            console.log("WebRTC client already initialized.");
            return;
        }

        const params = {
            sounds: {
                // dialing: 'https://support.solssa.com/storage/ringtones/office_phone.mp3',
                ringing: 'https://support.solssa.com/storage/ringtones/office_phone.mp3',
            },
        };

        try {
            const client = new Africastalking.Client(userToken.value, params);
            afClient.value = client;

            console.log("✅ WebRTC client initialized for user:", userId.value);

            client.on('ready', () => {
                connection_active.value = true;
                console.log("🎧 WebRTC client ready.");
                    updateAgentStatus('ready');
            });

            client.on('error', (err) => {
                console.error("❌ WebRTC error:", err);
            });

            client.on('closed', () => {
                connection_active.value = false;
                console.warn("🔌 WebRTC connection closed.");
                    updateAgentStatus('closed');

            });

            client.on('incomingcall', (event) => {
                console.log("📞 Incoming call from", event.from);

                console.log("you clicked me");
                    updateAgentStatus('busy');

                setIncomingCall({
                    from: event.from,
                    duration: 'Connecting...',
                });
            
            });

            client.on('hangup', (event) => {
                console.log("☎️ Call hung up:", event.reason);
                    updateAgentStatus('ready');

                incomingCallDialog.value = false;
                connection_active.value = false;
            });

        } catch (error) {
            console.error("⚠️ Failed to initialize WebRTC:", error);
        }
    }

    return {
        afClient,
        userId,
        userToken,
        incomingCallDialog,
        incomingCall,
        connection_active,
        connectionStatusColor,
        connectionStatusText,
        incomingCall,
        incomingCallDialog,
        setIncomingCall,
        closeIncomingCallDialog,
        initializeAfricastalking,
        updateAgentStatus,

    };
});
