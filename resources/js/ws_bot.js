import { makeWASocket, useMultiFileAuthState } from "@whiskeysockets/baileys";
import fs from "fs";
import path from "path";
import QRCode from "qrcode";

const STATUS_DIR = "./public/storage/";
const CLIENTS_FILE = "./public/storage/clients.json";
const LOTTERY_FILE = "./public/storage/lottery.json";
const QR_FILE = "./public/storage/qr.png";

let checkInterval = null;
let isProcessing = false;

async function run() {
    const { state, saveCreds } = await useMultiFileAuthState("./baileys_auth");
    const sock = makeWASocket({
        auth: state,
        printQRInTerminal: false,
        keepAliveIntervalMs: 10000,
    });

    sock.ev.on("connection.update", (update) => {
        const { connection, qr } = update;

        if (qr) {
            console.log("Obteniendo QR");
            QRCode.toFile(QR_FILE, qr, {
                color: {
                    dark: "#000000",
                    light: "#ffffff",
                },
            });

            console.log("QR generado en:", QR_FILE);
        }

        if (connection === "open") {
            console.log("¡Conexión establecida con WhatsApp!");
            if (fs.existsSync(QR_FILE)) {
                fs.unlinkSync(QR_FILE);
            }

            sendMessages(sock).catch(console.error);
            checkInterval = setInterval(() => {
                sendMessages(sock).catch(console.error);
            }, 5000);
        }

        if (connection === "close") {
            console.log("Desconectado. Reconectando...");
            if (checkInterval) {
                clearInterval(checkInterval);
                checkInterval = null;
            }
            run();
        }
    });

    sock.ev.on("creds.update", saveCreds);
}

async function sendMessages(sock) {
    if (isProcessing) return;
    isProcessing = true;

    try {
        if (!fs.existsSync(CLIENTS_FILE)) {
            console.log("Archivo clients.json no encontrado.");
            const chatId = process.argv[2];
            const message = process.argv[3];

            if (chatId && message) {
                await sendMessage(sock, chatId, message);
            }
            return;
        }

        const data = Object.values(
            JSON.parse(fs.readFileSync(CLIENTS_FILE, "utf8"))
        );
        if (!data?.length) {
            console.log("No hay clientes para notificar.");
            return;
        }

        let messagesSend = 0;
        for (const client of data) {
            await sendMessage(sock, client.chatId, client.message);
            messagesSend++;
        }

        await generateStatusFile(messagesSend);

        console.log("Todos los mensajes confirmados..");
        fs.unlinkSync(CLIENTS_FILE);
    } catch (err) {
        console.error("Error sending messages:", err);
    } finally {
        isProcessing = false;
    }
}

async function generateStatusFile(messagesSend) {
    const data = JSON.parse(fs.readFileSync(LOTTERY_FILE, "utf8"));
    const statusData = {
        data,
        totalMessages: messagesSend,
    };

    const finalPath = path.join(STATUS_DIR, `status.json`);
    fs.mkdirSync(STATUS_DIR, { recursive: true });
    fs.writeFileSync(finalPath, JSON.stringify(statusData));
    fs.unlinkSync(LOTTERY_FILE);
}

async function sendMessage(sock, chatId, message) {
    try {
        const id = `58${chatId}@s.whatsapp.net`;
        await sock.sendMessage(id, { text: message });
    } catch (error) {
        console.error("Error al enviar mensaje:", error);
        throw error;
    }
}

run().catch((err) => {
    console.error("Unhandled error:", err);
});
