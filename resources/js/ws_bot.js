import { makeWASocket, useMultiFileAuthState } from "@whiskeysockets/baileys";
import fs from "fs";
import qrcode from "qrcode-terminal";

async function run() {
    const { state, saveCreds } = await useMultiFileAuthState("./baileys_auth");
    const sock = makeWASocket({ auth: state, printQRInTerminal: false });
    let isMessageSent = false;

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
        const filePath = "./public/storage/clients.json";
        if (!fs.existsSync(filePath)) {
            console.log("Archivo clients.json no encontrado.");
            const chatId = process.argv[2];
            const message = process.argv[3];

            await sendMessage(chatId, message);
            process.exit(0);
        }

        const data = JSON.parse(fs.readFileSync(filePath, "utf8"));
        if (!data?.length) {
            console.log("No hay clientes para notificar.");
        }

        for (const client of data) {
            await sendMessage(client.chatId, client.message);
        }

        console.log("Mensajes enviados exitosamente");
        fs.unlinkSync(filePath);
        process.exit(0);
    }

    async function sendMessage(chatId, message) {
        try {
            const id = `58${chatId}@s.whatsapp.net`;
            await sock.sendMessage(id, { text: message });
            console.log("Mensaje enviado a ", chatId);
            console.log(message);
        } catch (error) {
            console.error("Error al enviar mensaje:", error);
            throw error;
        }
    }
}

run().catch((err) => {
    console.error("Unhandled error:", err);
    process.exit(1);
});
