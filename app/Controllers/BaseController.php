<?php
/**
 * Controlador Base (BaseController)
 * 
 * Clase abstracta que proporciona funcionalidades comunes a todos
 * los controladores: seguridad de sesión, redirecciones estandarizadas
 * y renderizado desacoplado de vistas.
 */

defined('ACCESO_PERMITIDO') or exit('Acceso denegado.');

abstract class BaseController
{
    /**
     * Redirige al usuario a una ruta interna relativa a BASE_URL.
     */
    protected function redirect(string $path): void
    {
        $target = BASE_URL . '/' . ltrim($path, '/');
        header("Location: {$target}");
        exit;
    }

    /**
     * Verifica que el usuario tenga sesión activa y opcionalmente
     * valida que pertenezca a uno de los roles autorizados.
     *
     * @param array $allowedRoles Lista opcional de roles permitidos (ej: ['administrador', 'instructor'])
     */
    protected function requireAuth(array $allowedRoles = []): void
    {
        if (!isset($_SESSION['id_usuario'])) {
            $this->redirect('index.php?page=login&error=sesion_requerida');
        }

        if (!empty($allowedRoles)) {
            $userRole = strtolower($_SESSION['nombre_rol'] ?? '');
            $hasPermission = false;

            foreach ($allowedRoles as $role) {
                if (str_contains($userRole, strtolower($role))) {
                    $hasPermission = true;
                    break;
                }
            }

            if (!$hasPermission) {
                $this->redirect('index.php?page=no_autorizado');
            }
        }
    }

    /**
     * Exige que el visitante sea un invitado (sin sesión).
     * Si ya inició sesión, lo redirige a su panel según su rol.
     */
    protected function requireGuest(): void
    {
        if (isset($_SESSION['id_usuario'])) {
            $userRole = strtolower($_SESSION['nombre_rol'] ?? '');
            if (str_contains($userRole, 'admin')) {
                $this->redirect('index.php?page=fichas');
            } elseif (str_contains($userRole, 'instructor')) {
                $this->redirect('index.php?page=instructor_dashboard');
            } else {
                $this->redirect('index.php?page=aprendiz_horario');
            }
        }
    }

    /**
     * Carga y renderiza una vista inyectando los datos de forma limpia.
     * 
     * @param string $view Ruta relativa dentro de app/Views (ej: 'admin/fichas')
     * @param array $data Variables que la vista tendrá disponibles
     */
    protected function renderView(string $view, array $data = []): void
    {
        $filePath = APP_PATH . '/Views/' . ltrim($view, '/') . '.php';

        if (!file_exists($filePath)) {
            throw new RuntimeException("La vista '{$view}' no fue encontrada en {$filePath}.");
        }

        // Convierte las claves del array asociativo en variables individuales
        extract($data, EXTR_SKIP);

        require $filePath;
    }

    /**
     * Emite una respuesta en formato JSON (útil para peticiones AJAX / Fetch).
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
