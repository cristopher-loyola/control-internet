import express from 'express';
import qrcode from 'qrcode';
import pino from 'pino';
import {
    default as makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion,
} from '@whiskeysockets/baileys';
import { Boom } from '@hapi/boom';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const AUTH_DIR = path.join(__dirname, 'auth_info');
const PORT = process.env.PORT || 3300;
// Cambia este token en produccion (variable de entorno WHATSAPP_SERVICE_TOKEN).
const API_TOKEN = process.env.WHATSAPP_SERVICE_TOKEN || 'dev-token-cambiar';

const logger = pino({ level: 'silent' });

let sock = null;
let latestQr = null;
let connectedNumber = null;
let isConnected = false;

async function startSock() {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
    const { version } = await fetchLatestBaileysVersion();

    sock = makeWASocket({
        version,
        auth: state,
        logger,
        printQRInTerminal: false,
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            latestQr = qr;
        }

        if (connection === 'open') {
            isConnected = true;
            latestQr = null;
            connectedNumber = sock.user?.id?.split(':')[0] || sock.user?.id || null;
            console.log('[whatsapp-service] Conectado como', connectedNumber);
        }

        if (connection === 'close') {
            isConnected = false;
            connectedNumber = null;
            const statusCode = lastDisconnect?.error instanceof Boom
                ? lastDisconnect.error.output?.statusCode
                : null;
            const loggedOut = statusCode === DisconnectReason.loggedOut;

            console.log('[whatsapp-service] Conexion cerrada.', loggedOut ? 'Sesion cerrada (logout).' : 'Reintentando...');

            if (!loggedOut) {
                startSock();
            }
        }
    });
}

function normalizeJid(numero) {
    const digits = String(numero).replace(/\D/g, '');
    return `${digits}@s.whatsapp.net`;
}

function requireToken(req, res, next) {
    const token = req.header('x-api-token');
    if (token !== API_TOKEN) {
        return res.status(401).json({ ok: false, message: 'Token invalido' });
    }
    next();
}

const app = express();
app.use(express.json());

app.get('/status', (req, res) => {
    res.json({
        ok: true,
        connected: isConnected,
        number: connectedNumber,
        has_qr: !!latestQr,
    });
});

app.get('/qr', async (req, res) => {
    if (isConnected) {
        return res.status(409).json({ ok: false, message: 'Ya hay una sesion vinculada. Usa /relink para cambiar de numero.' });
    }
    if (!latestQr) {
        return res.status(404).json({ ok: false, message: 'Aun no hay un QR disponible, intenta de nuevo en unos segundos.' });
    }
    const png = await qrcode.toBuffer(latestQr, { width: 320 });
    res.set('Content-Type', 'image/png');
    res.send(png);
});

app.post('/relink', requireToken, async (req, res) => {
    try {
        if (sock) {
            try { await sock.logout(); } catch (_) { /* ya pudo estar desconectado */ }
        }
        fs.rmSync(AUTH_DIR, { recursive: true, force: true });
        isConnected = false;
        connectedNumber = null;
        latestQr = null;
        await startSock();
        res.json({ ok: true, message: 'Sesion reiniciada, escanea el nuevo QR en /qr' });
    } catch (e) {
        res.status(500).json({ ok: false, message: e.message });
    }
});

app.post('/send', requireToken, async (req, res) => {
    const { to, message } = req.body || {};
    if (!to || !message) {
        return res.status(422).json({ ok: false, message: 'Faltan campos: to, message' });
    }
    if (!isConnected || !sock) {
        return res.status(503).json({ ok: false, message: 'El servicio de WhatsApp no esta conectado.' });
    }
    try {
        const jid = normalizeJid(to);
        await sock.sendMessage(jid, { text: message });
        res.json({ ok: true });
    } catch (e) {
        res.status(500).json({ ok: false, message: e.message });
    }
});

app.listen(PORT, '127.0.0.1', () => {
    console.log(`[whatsapp-service] Escuchando en http://127.0.0.1:${PORT}`);
});

startSock();
