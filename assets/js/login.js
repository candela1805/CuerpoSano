document.getElementById('loginForm').addEventListener('submit', async function (e) {
  e.preventDefault();

  const formData = new FormData(e.target);
  const mensajeDiv = document.getElementById('mensaje');

  try {
    const apiBase = window.location.pathname.includes('/pages/') ? '../api' : 'api';
    const url = `${apiBase}/login.php`;
    console.log('[login] POST', url);

    const res = await fetch(url, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData, credentials: 'same-origin' });
    const text = await res.text();
    let json;
    try { json = JSON.parse(text); } catch { json = { error: text || 'Respuesta no válida del servidor.' }; }

    if (res.ok && json && json.success) {
      window.location.href = json.redirect;
      return;
    }

    console.error('[login] status', res.status, 'body:', text);
    mensajeDiv.textContent = (json && json.error) ? json.error : `Error ${res.status}`;
    mensajeDiv.className = 'mensaje error';
    mensajeDiv.style.display = 'block';
  } catch (err) {
    console.error('Error en la solicitud de login:', err);
    mensajeDiv.textContent = 'Error de conexión con el servidor.';
    mensajeDiv.className = 'mensaje error';
    mensajeDiv.style.display = 'block';
  }
});


