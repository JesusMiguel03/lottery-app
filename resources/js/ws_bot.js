import qrcode from "qrcode-terminal";
import pkg from "whatsapp-web.js";
const { Client, LocalAuth } = pkg;

const client = new Client({
    authStrategy: new LocalAuth(),
});

client.once("ready", () => {
    console.log("¡Se ha establecido la conexión con whatsapp web!");
    const chatId = process.argv[2];
    const message = process.argv[3];
    send_message(chatId, message);
});

client.on("qr", (qr) => {
    qrcode.generate(qr, { small: true });
});

client.initialize();

async function send_message(chatId, message) {
    try {
        const chat = await client.getChatById("58" + chatId + "@c.us");
        await chat.sendMessage(message);

        await new Promise((resolve) => setTimeout(resolve, 5000));

        process.exit(0);
    } catch (error) {
        console.error("Hubo un error al enviar el mensaje", error);
        process.exit(1);
    }
}
