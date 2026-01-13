<?php
/**
 * Sidebar Component - Menú lateral estático para todos los perfiles
 * Este archivo genera el menú lateral completo según el rol del usuario
 */

function renderSidebar($currentPage = '') {
    if (!isset($_SESSION['user_role'])) {
        return;
    }
    
    $userRole = $_SESSION['user_role'];
    $userName = $_SESSION['user_name'] ?? 'Usuario';
    
    // Configuración de roles - usando un solo color para todos los menús
    $standardGradient = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    $roleConfig = [
        'SuperAdmin' => [
            'title' => 'SuperAdmin',
            'icon' => 'fas fa-crown',
            'gradient' => $standardGradient
        ],
        'Gestor' => [
            'title' => 'Gestor', 
            'icon' => 'fas fa-user-tie',
            'gradient' => $standardGradient
        ],
        'Líder' => [
            'title' => 'Líder',
            'icon' => 'fas fa-users-cog', 
            'gradient' => $standardGradient
        ],
        'Activista' => [
            'title' => 'Activista',
            'icon' => 'fas fa-user',
            'gradient' => $standardGradient
        ]
    ];
    
    $config = $roleConfig[$userRole] ?? $roleConfig['Activista'];
    
    // Menu items por rol
    $menuItems = [];
    
    // Items comunes para todos
    $menuItems[] = [
        'url' => getDashboardUrl($userRole),
        'icon' => 'fas fa-tachometer-alt',
        'text' => 'Dashboard',
        'active' => ($currentPage === 'dashboard')
    ];
    
    // Items específicos por rol
    if (in_array($userRole, ['SuperAdmin', 'Gestor'])) {
        $menuItems[] = [
            'url' => url('admin/users.php'),
            'icon' => 'fas fa-users',
            'text' => 'Gestión de Usuarios',
            'active' => ($currentPage === 'users')
        ];
        
        $menuItems[] = [
            'url' => url('admin/pending_users.php'),
            'icon' => 'fas fa-user-clock',
            'text' => 'Usuarios Pendientes',
            'active' => ($currentPage === 'pending_users'),
            'badge' => getPendingUsersCount()
        ];
        
        // Add the new menu item for authorization
        $menuItems[] = [
            'url' => url('activities/proposals.php'),
            'icon' => 'fas fa-clipboard-check',
            'text' => 'Actividades por autorizar',
            'active' => ($currentPage === 'authorization')
        ];
        
        // Groups management - only for SuperAdmin
        if ($userRole === 'SuperAdmin') {
            $menuItems[] = [
                'url' => url('admin/groups.php'),
                'icon' => 'fas fa-users-cog',
                'text' => 'Gestión de Grupos',
                'active' => ($currentPage === 'groups')
            ];
        }
    }
    
    // Activities - texto específico por rol
    $activitiesText = 'Mis Actividades';
    if ($userRole === 'Líder') {
        $activitiesText = 'Actividades del Equipo';
    } elseif (in_array($userRole, ['SuperAdmin', 'Gestor'])) {
        $activitiesText = 'Actividades';
    }
    
    $menuItems[] = [
        'url' => url('activities/'),
        'icon' => 'fas fa-tasks',
        'text' => $activitiesText,
        'active' => ($currentPage === 'activities')
    ];
    
    // Nueva Actividad - solo para SuperAdmin y Gestor (NO para Líder ni Activista)
    if (in_array($userRole, ['SuperAdmin', 'Gestor'])) {
        $menuItems[] = [
            'url' => url('activities/create.php'),
            'icon' => 'fas fa-plus',
            'text' => 'Nueva Actividad',
            'active' => ($currentPage === 'create_activity')
        ];
    }
    
    // Tasks para activistas y líderes
    if (in_array($userRole, ['Activista', 'Líder'])) {
        $menuItems[] = [
            'url' => url('tasks/'),
            'icon' => 'fas fa-clipboard-list',
            'text' => 'Tareas',
            'active' => ($currentPage === 'tasks')
        ];
    }
    
    // Ranking - separar lógica para LÍDER
    if (in_array($userRole, ['SuperAdmin', 'Gestor'])) {
        $menuItems[] = [
            'url' => url('ranking/'),
            'icon' => 'fas fa-trophy',
            'text' => $userRole === 'SuperAdmin' ? 'Ranking' : 'Ranking General',
            'active' => ($currentPage === 'ranking')
        ];
        
        // Reset Mensual - solo para SuperAdmin
        if ($userRole === 'SuperAdmin') {
            $menuItems[] = [
                'url' => url('admin/monthly_reset.php'),
                'icon' => 'fas fa-calendar-alt',
                'text' => 'Reset Ranking Mensual',
                'active' => ($currentPage === 'monthly_reset')
            ];
        }
        
        // Reporte de Activistas - solo para SuperAdmin y Gestor
        $menuItems[] = [
            'url' => url('reports/activists.php'),
            'icon' => 'fas fa-chart-bar',
            'text' => 'Reporte de Activistas',
            'active' => ($currentPage === 'activist_report')
        ];
        
        // Informe Global de Tareas
        $menuItems[] = [
            'url' => url('reports/global-tasks.php'),
            'icon' => 'fas fa-tasks',
            'text' => 'Informe Global de Tareas',
            'active' => ($currentPage === 'reports')
        ];
        
        // Informe Global por Actividad - solo para SuperAdmin
        if ($userRole === 'SuperAdmin') {
            $menuItems[] = [
                'url' => url('reports/global_activity.php'),
                'icon' => 'fas fa-chart-pie',
                'text' => 'Informe Global por Actividad',
                'active' => ($currentPage === 'global_activity_report')
            ];
            
            $menuItems[] = [
                'url' => url('reports/best_by_group.php'),
                'icon' => 'fas fa-trophy',
                'text' => 'Mejores por Grupo',
                'active' => ($currentPage === 'best_by_group_report')
            ];
        }
    } elseif ($userRole === 'Líder') {
        // Menú específico para LÍDER - Ranking del Equipo
        $menuItems[] = [
            'url' => url('ranking/'),
            'icon' => 'fas fa-trophy',
            'text' => 'Ranking del Equipo',
            'active' => ($currentPage === 'ranking')
        ];
        
        // Reporte de Activistas - para líder (solo sus activistas)
        $menuItems[] = [
            'url' => url('reports/activists.php'),
            'icon' => 'fas fa-chart-bar',
            'text' => 'Reporte de mi Equipo',
            'active' => ($currentPage === 'activist_report')
        ];
    }
    
    // Items comunes finales
    $menuItems[] = [
        'url' => url('profile.php'),
        'icon' => 'fas fa-user',
        'text' => 'Mi Perfil',
        'active' => ($currentPage === 'profile')
    ];
    
    $menuItems[] = [
        'url' => url('logout.php'),
        'icon' => 'fas fa-sign-out-alt',
        'text' => 'Cerrar Sesión',
        'active' => false
    ];
    
    // Generar HTML con soporte responsivo
    // Botón hamburguesa para móvil
    echo '<button class="btn btn-primary d-md-none mobile-menu-toggle" id="mobileMenuToggle" type="button">';
    echo '<i class="fas fa-bars"></i>';
    echo '</button>';
    
    // Overlay para cerrar el menú al hacer clic fuera (móvil)
    echo '<div class="mobile-overlay" id="mobileOverlay"></div>';
    
    // Sidebar - oculto por defecto en móvil con clase específica
    echo '<nav class="col-md-2 d-none d-md-block sidebar mobile-sidebar-hidden" id="mobileSidebar" style="background: ' . $config['gradient'] . ';">';
    
    // Botón cerrar para móvil
    echo '<button class="btn btn-link text-white d-md-none mobile-close-btn" id="mobileCloseBtn" type="button">';
    echo '<i class="fas fa-times fa-2x"></i>';
    echo '</button>';
    
    echo '<div class="position-sticky pt-3">';
    echo '<div class="text-center text-white mb-4 mt-4">';
    echo '<h4><i class="' . $config['icon'] . ' me-2"></i>' . $config['title'] . '</h4>';
    echo '<small>' . htmlspecialchars($userName) . '</small>';
    echo '</div>';
    
    echo '<ul class="nav flex-column px-2">';
    
    foreach ($menuItems as $item) {
        $activeClass = $item['active'] ? ' active' : '';
        $isLast = $item === end($menuItems);
        $mbClass = $isLast ? '' : ' mb-2';
        
        echo '<li class="nav-item' . $mbClass . '">';
        echo '<a class="nav-link text-white' . $activeClass . '" href="' . $item['url'] . '">';
        echo '<i class="' . $item['icon'] . ' me-2"></i>' . $item['text'];
        
        if (isset($item['badge']) && $item['badge'] > 0) {
            echo '<span class="badge bg-warning text-dark ms-2">' . $item['badge'] . '</span>';
        }
        
        echo '</a>';
        echo '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
    echo '</nav>';
    
    // Script para manejar el menú móvil
    echo '<script>
    (function() {
        // Esperar a que el DOM esté listo
        document.addEventListener("DOMContentLoaded", function() {
            const mobileToggle = document.getElementById("mobileMenuToggle");
            const mobileClose = document.getElementById("mobileCloseBtn");
            const mobileSidebar = document.getElementById("mobileSidebar");
            const mobileOverlay = document.getElementById("mobileOverlay");
            
            console.log("Menú móvil inicializado", {mobileToggle, mobileClose, mobileSidebar, mobileOverlay});
            
            function closeMenu() {
                console.log("Cerrando menú");
                if (mobileSidebar) {
                    mobileSidebar.classList.remove("show");
                    mobileSidebar.classList.add("mobile-sidebar-hidden");
                    mobileSidebar.classList.add("d-none"); // Restaurar d-none de Bootstrap
                    console.log("Clases del sidebar:", mobileSidebar.className);
                }
                if (mobileOverlay) {
                    mobileOverlay.classList.remove("show");
                }
                document.body.style.overflow = "";
            }
            
            function openMenu() {
                console.log("Abriendo menú");
                if (mobileSidebar) {
                    mobileSidebar.classList.remove("mobile-sidebar-hidden");
                    mobileSidebar.classList.remove("d-none"); // Remover d-none de Bootstrap
                    mobileSidebar.classList.add("show");
                    console.log("Clases del sidebar:", mobileSidebar.className);
                }
                if (mobileOverlay) {
                    mobileOverlay.classList.add("show");
                }
                document.body.style.overflow = "hidden";
            }
            
            // Botón hamburguesa
            if (mobileToggle) {
                mobileToggle.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log("Click en botón hamburguesa");
                    openMenu();
                });
            }
            
            // Botón cerrar X
            if (mobileClose) {
                mobileClose.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log("Click en botón X");
                    closeMenu();
                });
            }
            
            // Click en overlay
            if (mobileOverlay) {
                mobileOverlay.addEventListener("click", function(e) {
                    e.preventDefault();
                    console.log("Click en overlay");
                    closeMenu();
                });
            }
            
            // Cerrar al hacer clic en enlaces
            if (mobileSidebar) {
                const links = mobileSidebar.querySelectorAll("a.nav-link");
                links.forEach(function(link) {
                    link.addEventListener("click", function() {
                        console.log("Click en enlace del menú");
                        setTimeout(closeMenu, 200);
                    });
                });
            }
        });
    })();
    </script>';
}

function getPendingUsersCount() {
    if (isset($GLOBALS['pendingUsers']) && is_array($GLOBALS['pendingUsers'])) {
        return count($GLOBALS['pendingUsers']);
    }
    return 0;
}
?>