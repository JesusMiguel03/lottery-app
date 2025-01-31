import fs from "fs";
import qrcode from "qrcode-terminal";
import pkg from "whatsapp-web.js";
const { Client, LocalAuth } = pkg;

const client = new Client({
    authStrategy: new LocalAuth(),
});

client.initialize();

client.on("qr", (qr) => {
    console.log("Obteniendo QR");
    qrcode.generate(qr, { small: true });
});

client.once("ready", () => {
    console.log("¡Se ha establecido la conexión con whatsapp web!");
    const chatId = process.argv[2];
    const message = process.argv[3];

    console.log("Iniciando envio de mensajes");
    if (chatId == null && message == null) {
        const data = JSON.parse(
            fs.readFileSync("./public/storage/clients.json", "utf8")
        );

        for (const client of data) {
            const { chatId, message } = client;
            send_message(chatId, message);
        }

        console.log("Todos los mensajes fueron enviados exitosamente");
    } else {
        console.log("Enviando mensaje");
        send_message(chatId, message);
    }
});

client.on("authenticated", () => {
    console.log("Sesión iniciada");
});

client.on("disconnected", (reason) => {
    console.log("Se desconectó: ", reason);
});

client.on("auth_failure", (message) => {
    console.log("Error al autenticar:", message);
});

async function send_message(chatId, message) {
    try {
        console.time("Tiempo transcurrido");
        const chat = await client.getChatById("58" + chatId + "@c.us");
        await chat.sendMessage(message);

        await new Promise((resolve) => setTimeout(resolve, 6000));

        console.timeEnd("Tiempo transcurrido");
        process.exit(0);
    } catch (error) {
        console.error("Hubo un error al enviar el mensaje", error);
        process.exit(1);
    }
}
