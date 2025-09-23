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
    
    // Generar HTML
    echo '<nav class="col-md-2 d-none d-md-block sidebar" style="background: ' . $config['gradient'] . ';">';
    echo '<div class="position-sticky pt-3">';
    echo '<div class="text-center text-white mb-4">';
    echo '<h4><i class="' . $config['icon'] . ' me-2"></i>' . $config['title'] . '</h4>';
    echo '<small>' . htmlspecialchars($userName) . '</small>';
    echo '</div>';
    
    echo '<ul class="nav flex-column">';
    
    foreach ($menuItems as $item) {
        $activeClass = $item['active'] ? ' active' : '';
        $isLast = $item === end($menuItems);
        $mbClass = $isLast ? '' : ' mb-2';
        
        echo '<li class="nav-item' . $mbClass . '">';
        echo '<a class="nav-link text-white' . $activeClass . '" href="' . $item['url'] . '">';
        echo '<i class="' . $item['icon'] . ' me-2"></i>' . $item['text'];
        
        if (isset($item['badge']) && $item['badge'] > 0) {
            echo '<span class="badge bg-warning text-dark">' . $item['badge'] . '</span>';
        }
        
        echo '</a>';
        echo '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
    echo '</nav>';
}

function getPendingUsersCount() {
    if (isset($GLOBALS['pendingUsers']) && is_array($GLOBALS['pendingUsers'])) {
        return count($GLOBALS['pendingUsers']);
    }
    return 0;
}
?>