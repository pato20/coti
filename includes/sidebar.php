<?php
require_once 'includes/auth.php';
verificarAuth();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4 pb-3 border-bottom border-light">
            <div class="logo-container mb-2">
                <i class="fas fa-bolt fa-2x text-warning"></i>
            </div>
            <h5 class="text-white mb-1">CyC Electric</h5>
            <small class="text-light opacity-75">Electricidad e informatica</small>
        </div>
        
        <!-- Added user info section -->
        <div class="user-info mb-4 p-3 rounded" style="background: rgba(255,255,255,0.1);">
            <div class="d-flex align-items-center">
                <div class="avatar me-2">
                    <i class="fas fa-user-circle fa-2x text-light"></i>
                </div>
                <div>
                    <div class="text-white small fw-bold"><?= htmlspecialchars($_SESSION['nombre_completo']) ?></div>
                    <div class="text-light small opacity-75"><?= ucfirst($_SESSION['rol']) ?></div>
                </div>
            </div>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'index') ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'clientes') ? 'active' : ''; ?>" href="clientes.php">
                    <i class="fas fa-users me-2"></i> Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'catalogo') ? 'active' : ''; ?>" href="catalogo.php">
                    <i class="fas fa-list m e-2"></i> Catálogo
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'cotizaciones') ? 'active' : ''; ?>" href="cotizaciones.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Cotizaciones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'ordenes') ? 'active' : ''; ?>" href="ordenes.php">
                    <i class="fas fa-clipboard-list me-2"></i> Órdenes de Trabajo
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'agenda') ? 'active' : ''; ?>" href="agenda.php">
                    <i class="fas fa-calendar-alt me-2"></i> Agenda
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'configuracion' || $current_page == 'updater') ? 'active' : ''; ?>" href="#configSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo ($current_page == 'configuracion' || $current_page == 'updater') ? 'true' : 'false'; ?>">
                    <i class="fas fa-cogs me-2"></i> Configuración
                </a>
                <div class="collapse <?php echo ($current_page == 'configuracion' || $current_page == 'updater') ? 'show' : ''; ?>" id="configSubmenu">
                    <ul class="nav flex-column ms-4">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'configuracion') ? 'active-sub' : ''; ?>" href="configuracion.php">General</a>
                        </li>
                        <?php if (is_admin()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'updater') ? 'active-sub' : ''; ?>" href="updater.php">Actualizador</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </li>
            <!-- Added reports section -->
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'reportes' || $current_page == 'reporte_detallado') ? 'active' : ''; ?>" href="#reportesSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo ($current_page == 'reportes' || $current_page == 'reporte_detallado') ? 'true' : 'false'; ?>">
                    <i class="fas fa-chart-bar me-2"></i> Reportes
                </a>
                <div class="collapse <?php echo ($current_page == 'reportes' || $current_page == 'reporte_detallado') ? 'show' : ''; ?>" id="reportesSubmenu">
                    <ul class="nav flex-column ms-4">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'reportes') ? 'active-sub' : ''; ?>" href="reportes.php">Reporte Anual</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'reporte_detallado') ? 'active-sub' : ''; ?>" href="reporte_detallado.php">Reportes Detallados</a>
                        </li>
                    </ul>
                </div>
            </li>
            <!-- Added user management for admins -->
            <?php if ($_SESSION['rol'] == 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'usuarios') ? 'active' : ''; ?>" href="usuarios.php">
                    <i class="fas fa-user-cog me-2"></i> Usuarios
                </a>
            </li>
            
            <?php endif; ?>
        </ul>
        
        <!-- Added logout button at bottom -->
        <div class="mt-auto pt-3 border-top border-light">
            <a href="logout.php" class="nav-link text-light" onclick="return confirm('¿Está seguro que desea cerrar sesión?')">
                <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
            </a>
        </div>
    </div>
    
    <style>
        .sidebar {
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 2px 8px;
            padding: 12px 16px;
        }
        .nav-link:hover {
            color: white !important;
            background-color: rgba(255,255,255,0.1) !important;
            transform: translateX(5px);
        }
        .nav-link.active {
            color: white !important;
            background-color: rgba(255,255,255,0.2) !important;
            border-left: 4px solid #f39c12;
        }
        .nav-link.active-sub {
            color: #f39c12 !important;
            font-weight: bold;
        }
        .logo-container {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</nav>

