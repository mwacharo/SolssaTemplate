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


    function startHeartbeat() {
        setInterval(() => {
            if (agentStatus.value === 'ready' || agentStatus.value === 'busy') {
                axios.post('/api/v1/agent/ping').catch(() => { });
            }
        }, 15000);
    }

    function listenForStatusUpdates() {
        Echo.private('agent.status')
            .listen('.status.updated', (e) => {
                console.log(`🔄 Agent ${e.agentId} is now ${e.status}`);
                if (e.agentId === userId.value) {
                    agentStatus.value = e.status;
                }
            });
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


    // // When the browser tab is closed or refreshed
    // window.addEventListener('beforeunload', () => {
    //     if (agentStatus.value === 'ready' || agentStatus.value === 'busy') {
    //         navigator.sendBeacon('/agent/status', JSON.stringify({ status: 'offline' }));
    //     }
    // });

    async function initializeAfricastalking() {


        startHeartbeat();
        listenForStatusUpdates();
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

            // client.on('incomingcall', (event) => {
            //     console.log("📞 Incoming call from", event.from);

            //     console.log("you clicked me");
            //     updateAgentStatus('busy');



            //     setIncomingCall({
            //         from: event.from,
            //         duration: 'Connecting...',
            //     });

            // });


            client.on('incomingcall', async (event) => {
                console.log("📞 Incoming call from", event.from);
                updateAgentStatus('busy');

                try {
                    // make sure client exists and is connected
                    client.answer();
                    console.log("✅ Auto-answered call from", event.from);
                    connectToRealtimeAI(event.from);

                } catch (err) {
                    console.error("❌ Could not auto-answer:", err);
                }
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



    // async function connectToRealtimeAI(phoneNumber) {
    //     try {
    //         // const { data: order } = await axios.get(`/api/v1/orders/recent?phone=${phoneNumber}`);

    //         // retunrs orders 


    //         const { data: order } = await axios.get(`https://app.boxleocourier.com/api/contact-search/${phoneNumber}`, {
    //             timeout: 120000,
    //         });

    //         const { data: realtimeSession } = await axios.post('/api/v1/realtime/session', {
    //             context: { phoneNumber, order }
    //         });

    //         console.log("🎙️ OpenAI Realtime Session:", realtimeSession);
    //         // later: use this to connect WebRTC audio
    //     } catch (err) {
    //         console.error("❌ Error connecting to Realtime AI:", err);
    //     }
    // }



   async function connectToRealtimeAI(phoneNumber) {
    try {
        console.log("🎧 Connecting AI for:", phoneNumber);

        // 1️⃣ Get recent order
        const { data: order } = await axios.get(
            `https://app.boxleocourier.com/api/contact-search/${phoneNumber}`, 
            { timeout: 120000 }
        );

        // 2️⃣ Create a Realtime session from your Laravel backend
        const { data: realtimeSession } = await axios.post('/api/v1/realtime/session', {
            context: { phoneNumber, order }
        });

        console.log("🎙️ OpenAI Realtime Session:", realtimeSession);

        // 3️⃣ Create the WebRTC connection for AI voice
        const pc = new RTCPeerConnection();

        // Play the AI’s voice out loud
        const audioEl = document.createElement("audio");
        audioEl.autoplay = true;
        pc.ontrack = event => {
            audioEl.srcObject = event.streams[0];
        };

        // 4️⃣ Use your computer mic for the AI input
        const localStream = await navigator.mediaDevices.getUserMedia({ audio: true });
        localStream.getTracks().forEach(track => pc.addTrack(track, localStream));

        // 5️⃣ Create SDP offer
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);

        // 6️⃣ Send offer to OpenAI
        const response = await fetch("https://api.openai.com/v1/realtime?model=gpt-realtime", {
            method: "POST",
            headers: {
                Authorization: `Bearer ${realtimeSession.client_secret.value}`,
                "Content-Type": "application/sdp"
            },
            body: offer.sdp
        });

        // 7️⃣ Set AI’s answer
        const answer = {
            type: "answer",
            sdp: await response.text(),
        };
        await pc.setRemoteDescription(answer);

        console.log("✅ Connected to OpenAI Realtime voice session");

        // 8️⃣ (Optional) AI greeting for testing
        const synth = new SpeechSynthesisUtterance("Hi there! I am Boxleo AI Assistant, ready to assist.");
        window.speechSynthesis.speak(synth);

    } catch (err) {
        console.error("❌ Error connecting to Realtime AI:", err);
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
