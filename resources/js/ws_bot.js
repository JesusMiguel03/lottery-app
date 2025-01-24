import { exec } from "node:child_process";
import qrcode from "qrcode-terminal";
import pkg from "whatsapp-web.js";
const { Client, LocalAuth } = pkg;

const client = new Client({
    authStrategy: new LocalAuth(),
    // puppeteer: {
    //     headless: false,
    //     args: ["--no-sandbox", "--disable-setuid-sandbox"],
    // },
});

client.initialize();

client.on("qr", (qr) => {
    qrcode.generate(qr, { small: true });
});

client.once("ready", () => {
    console.log("¡Se ha establecido la conexión con whatsapp web!");
    const chatId = process.argv[2];
    const message = process.argv[3];
    console.log("Enviando mensaje");
    send_message(chatId, message);
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

client.on("message_ack", (ack) => {
    const messageStatus = ack.ack;
    const ticketId = process.argv[4].split(", ");

    if (messageStatus >= 1) {
        exec(
            `php artisan ticket_notified ${ticketId}`,
            (error, stdout, stderr) => {
                console.log("Notificación");
                const regex = /Boleto \d+ notificado/;
                if (regex.test(stdout)) {
                    console.log("Comando ejecutado");
                    return;
                }

                if (error) {
                    console.error(
                        `Error ejecutando el comando: \n ${error.message}`
                    );
                    return;
                }

                if (stderr) {
                    console.error(`Error en stderr: \n ${stderr}`);
                    return;
                }

                console.log(`Resultado del comando: \n ${stdout}`);
            }
        );
    }
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
