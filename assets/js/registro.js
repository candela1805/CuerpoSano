document.getElementById('registroForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const mensajeDiv = document.getElementById('mensaje');

    // Validar que las contraseñas coincidan
    if (formData.get('password') !== formData.get('confirmar')) {
        mensajeDiv.textContent = 'Las contraseñas no coinciden.';
        mensajeDiv.className = 'mensaje error';
        mensajeDiv.style.display = 'block';
        return;
    }

    try {
        const response = await fetch('../api/registro.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (response.ok && result.success) {
            mensajeDiv.innerHTML = result.message || 'Registro exitoso. <a href="login.html">Iniciá sesión</a>';
            mensajeDiv.className = 'mensaje exito';
            form.reset();
        } else {
            mensajeDiv.textContent = result.error || 'Ocurrió un error desconocido.';
            mensajeDiv.className = 'mensaje error';
        }
        mensajeDiv.style.display = 'block';

    } catch (error) {
        console.error('Error en la solicitud:', error);
        mensajeDiv.textContent = 'Error de conexión con el servidor.';
        mensajeDiv.className = 'mensaje error';
        mensajeDiv.style.display = 'block';
    }
});

