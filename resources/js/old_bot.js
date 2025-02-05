import { makeWASocket, useMultiFileAuthState } from "@whiskeysockets/baileys";
import fs from "fs";
import path from "path";
import qrcode from "qrcode-terminal";

const STATUS_DIR = "./public/storage/";
const CLIENTS_FILE = "./public/storage/clients.json";
const LOTTERY_FILE = "./public/storage/lottery.json";

async function run() {
    const { state, saveCreds } = await useMultiFileAuthState("./baileys_auth");
    const sock = makeWASocket({
        auth: state,
        printQRInTerminal: false,
        keepAliveIntervalMs: 10000,
    });
    let isMessageSent = false;

    const messageTracker = new Map(); // Track message completion

    // Add status tracking listeners
    sock.ev.on("messages.update", (update) => {
        const messageId = update.key.id;
        if (["sent", "delivered", "read"].includes(update.status)) {
            markMessageSuccess(messageId);
        }

        // if (update.status === "ERROR") {
        //     handleFailedMessage(sock, messageId);
        // } else if (["sent", "delivered", "read"].includes(update.status)) {
        //     markMessageSuccess(messageId);
        // }
    });

    sock.ev.on("connection.update", (update) => {
        const { connection, qr } = update;

        if (qr) {
            console.log("Obteniendo QR");
            qrcode.generate(qr, { small: true });
        }

        if (connection === "open" && !isMessageSent) {
            console.log("¡Conexión establecida con WhatsApp!");
            isMessageSent = true;
            sendMessages().catch((err) => {
                console.error("Error sending messages:", err);
            });
        }

        if (connection === "close") {
            console.log("Desconectado. Reconectando...");
            run();
        }
    });

    sock.ev.on("creds.update", saveCreds);

    async function sendMessages() {
        let messagesSend = 0;
        const filePath = CLIENTS_FILE;
        if (!fs.existsSync(filePath)) {
            console.log("Archivo clients.json no encontrado.");
            const chatId = process.argv[2];
            const message = process.argv[3];
            await sendMessage(chatId, message);
            return;
        }

        const data = Object.values(
            JSON.parse(fs.readFileSync(filePath, "utf8"))
        );
        if (!data?.length) {
            console.log("No hay clientes para notificar.");
            return;
        }

        for (const client of data) {
            await sendMessage(client.chatId, client.message);
            messagesSend++;
            // await trackMessage(sock, client.chatId, client.message);
        }

        // const allSucceeded = await verifyAllMessages();
        await generateStatusFile(messagesSend);

        console.log("Todos los mensajes confirmados. Eliminando archivo...");
        fs.unlinkSync(filePath);
        // if (allSucceeded) {
        //     console.log(
        //         "Todos los mensajes confirmados. Eliminando archivo..."
        //     );
        //     fs.unlinkSync(filePath);
        // } else {
        //     console.log("Algunos mensajes fallaron. Archivo conservado.");
        // }
    }

    async function generateStatusFile(messagesSend) {
        const filePath = LOTTERY_FILE;
        const data = JSON.parse(fs.readFileSync(filePath, "utf8"));

        const statusData = {
            data,
            totalMessages: messagesSend,
        };

        const finalPath = path.join(STATUS_DIR, `status.json`);

        console.log("Generando archivo resultado");
        fs.mkdirSync(STATUS_DIR, { recursive: true });
        fs.writeFileSync(finalPath, JSON.stringify(statusData));

        console.log("Eliminando archivo de lotería");
        fs.unlinkSync(LOTTERY_FILE);
    }

    // async function generateStatusFile(success) {
    //     const filePath = LOTTERY_FILE;
    //     const data = JSON.parse(fs.readFileSync(filePath, "utf8"));

    //     const statusData = {
    //         success,
    //         data,
    //         timestamp: new Date().toISOString(),
    //         totalMessages: messageTracker.size,
    //         failedMessages: [...messageTracker.values()].filter(
    //             (m) => m.status !== "success"
    //         ).length,
    //     };

    //     const finalPath = path.join(STATUS_DIR, `status.json`);

    //     console.log("Generando archivo resultado");
    //     fs.mkdirSync(STATUS_DIR, { recursive: true });
    //     fs.writeFileSync(finalPath, JSON.stringify(statusData));

    //     console.log("Eliminando archivo de lotería");
    //     fs.unlinkSync(LOTTERY_FILE);
    // }

    // async function trackMessage(sock, chatId, message) {
    //     const formattedId = `58${chatId}@s.whatsapp.net`;

    //     const existing = [...messageTracker.values()].find(
    //         (m) => m.chatId === formattedId && m.message === message
    //     );

    //     if (existing) {
    //         console.log(`Mensaje duplicado omitido para ${formattedId}`);
    //         return;
    //     }

    //     try {
    //         const id = `58${chatId}@s.whatsapp.net`;
    //         const sentMessage = await sock.sendMessage(id, { text: message });

    //         messageTracker.set(sentMessage.key.id, {
    //             chatId: formattedId,
    //             message,
    //             status: "pending",
    //             retries: 0,
    //             maxRetries: 3,
    //         });
    //     } catch (error) {
    //         console.error("Error inicial al enviar:", error);
    //         messageTracker.set(formattedId + message, {
    //             chatId: formattedId,
    //             message,
    //             status: "failed",
    //             error,
    //             retries: 0,
    //         });
    //     }
    // }

    // async function verifyAllMessages() {
    //     const maxWaitTime = 300000; // 5 minutes timeout
    //     const start = Date.now();

    //     while (Date.now() - start < maxWaitTime) {
    //         const pendingMessages = [...messageTracker.values()].filter(
    //             (m) => m.status === "pending"
    //         );

    //         if (pendingMessages.length === 0) break;

    //         await new Promise((resolve) => setTimeout(resolve, 5000));
    //         console.log(`Esperando ${pendingMessages.length} mensajes...`);
    //     }

    //     const finalStatuses = [...messageTracker.values()];
    //     return finalStatuses.every((m) => m.status === "success");
    // }

    // function markMessageSuccess(messageId) {
    //     const msg = messageTracker.get(messageId);
    //     if (msg) {
    //         msg.status = "success";
    //         console.log(`✓ Mensaje a ${msg.chatId} confirmado`);
    //     }
    // }

    // async function handleFailedMessage(sock, messageId) {
    //     const msg = messageTracker.get(messageId);
    //     if (!msg) return;

    //     if (msg.retries < msg.maxRetries) {
    //         msg.retries++;
    //         console.log(
    //             `↻ Reintentando ${msg.chatId} (intento ${msg.retries})`
    //         );

    //         try {
    //             const sentMessage = await sock.sendMessage(msg.chatId, {
    //                 text: msg.message,
    //             });

    //             messageTracker.delete(messageId);
    //             messageTracker.set(sentMessage.key.id, {
    //                 ...msg,
    //                 status: "pending",
    //                 retries: msg.retries,
    //             });
    //         } catch (error) {
    //             console.error(`Reintento fallido para ${msg.chatId}:`, error);
    //             messageTracker.set(messageId, {
    //                 ...msg,
    //                 status: "failed",
    //             });
    //         }
    //     } else {
    //         messageTracker.set(messageId, {
    //             ...msg,
    //             status: "failed",
    //         });
    //         console.log(`✗ Máximos reintentos alcanzados para ${msg.chatId}`);
    //     }
    // }

    async function sendMessage(chatId, message) {
        try {
            const id = `58${chatId}@s.whatsapp.net`;
            await sock.sendMessage(id, { text: message });
        } catch (error) {
            console.error("Error al enviar mensaje:", error);
            throw error; // Propagate error to catch in sendMessages
        }
    }
}

run().catch((err) => {
    console.error("Unhandled error:", err);
});
