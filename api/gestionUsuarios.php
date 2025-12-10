<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../api/login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Gimnasio Cuerpo Sano</title>
    <link rel="stylesheet" href="../assets/css/gestionUsuarios.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Gestión de Usuarios</h1>
            <div class="user-info">
                <span>Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong></span>
                <a href="../api/logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </header>

        <div class="content">
            <!-- Sección de Perfil del Usuario Logueado -->
            <section class="profile-section">
                <h2>Mi Perfil</h2>
                <div id="userProfile" class="profile-card">
                    <div class="loading">Cargando información del usuario...</div>
                </div>
                <div class="profile-actions">
                    <button id="btnEditarPerfil" class="btn btn-primary">Editar Mi Perfil</button>
                    <button id="btnImprimirCredencial" class="btn btn-success">Imprimir Mi Credencial</button>
                </div>
            </section>

            <!-- Sección de Gestión de Todos los Usuarios (solo para admin) -->
            <section class="management-section">
                <h2>Gestión de Empleados</h2>
                
                <div class="filters">
                    <input type="text" id="searchInput" placeholder="Buscar por nombre, DNI o usuario...">
                    <select id="filterCargo">
                        <option value="">Todos los cargos</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Recepcionista">Recepcionista</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Entrenador">Entrenador</option>
                    </select>
                </div>

                <div class="table-container">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Nombre Completo</th>
                                <th>Cargo</th>
                                <th>Usuario</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="8" class="loading">Cargando usuarios...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal para Editar Usuario -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Editar Usuario</h2>
            <form id="formEditarUsuario">
                <input type="hidden" id="edit_idEmpleado" name="idEmpleado">
                
                <div class="form-group">
                    <label for="edit_nombre">Nombre:</label>
                    <input type="text" id="edit_nombre" name="nombre" required>
                </div>

                <div class="form-group">
                    <label for="edit_apellido">Apellido:</label>
                    <input type="text" id="edit_apellido" name="apellido" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="edit_telefono">Teléfono:</label>
                    <input type="text" id="edit_telefono" name="telefono">
                </div>

                <div class="form-group">
                    <label for="edit_direccion">Dirección:</label>
                    <input type="text" id="edit_direccion" name="direccion">
                </div>

                <div class="form-group">
                    <label for="edit_cargo">Cargo:</label>
                    <select id="edit_cargo" name="cargo" required>
                        <option value="Administrador">Administrador</option>
                        <option value="Recepcionista">Recepcionista</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Entrenador">Entrenador</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_password">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                    <input type="password" id="edit_password" name="password">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <button type="button" class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Confirmar Eliminación -->
    <div id="modalEliminar" class="modal">
        <div class="modal-content modal-small">
            <h2>Confirmar Eliminación</h2>
            <p>¿Está seguro que desea eliminar este usuario?</p>
            <p><strong id="deleteUserName"></strong></p>
            <div class="form-actions">
                <button id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</button>
                <button class="btn btn-secondary" onclick="cerrarModalEliminar()">Cancelar</button>
            </div>
        </div>
    </div>

    <!-- Área de impresión (oculta) -->
    <div id="printArea" style="display:none;"></div>

    <script src="../assets/js/gestionUsuarios.js"></script>
</body>
</html>