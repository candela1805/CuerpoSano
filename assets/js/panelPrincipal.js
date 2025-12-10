document.addEventListener('DOMContentLoaded', () => {
  const cerrarSesion = document.getElementById('cerrarSesion');

  cerrarSesion.addEventListener('click', (e) => {
    e.preventDefault();
    if (confirm('¿Deseas cerrar la sesión actual?')) {
      window.location.href = 'logout.php';
    }
  });
});
