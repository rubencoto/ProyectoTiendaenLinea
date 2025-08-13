
<?php
require_once 'conexion.php';

class DireccionesManager {
    private $conn;
    
    public function __construct() {
        $db = DatabaseConnection::getInstance();
        $this->conn = $db->getConnection();
    }
    
    /**
     * Obtener todas las direcciones de un cliente
     */
    public function obtenerDireccionesCliente($cliente_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM direcciones 
                WHERE cliente_id = ? 
                ORDER BY is_default DESC, created_at DESC
            ");
            $stmt->execute([$cliente_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener direcciones del cliente: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener dirección principal de un cliente
     */
    public function obtenerDireccionPrincipalCliente($cliente_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM direcciones 
                WHERE cliente_id = ? AND is_default = 1 
                LIMIT 1
            ");
            $stmt->execute([$cliente_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error al obtener dirección principal: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Agregar nueva dirección para cliente
     */
    public function agregarDireccionCliente($datos) {
        try {
            // Si es dirección principal, desmarcar las demás
            if ($datos['is_default']) {
                $this->desmarcarDireccionPrincipalCliente($datos['cliente_id']);
            }
            
            $stmt = $this->conn->prepare("
                INSERT INTO direcciones 
                (cliente_id, etiqueta, nombre, apellidos, telefono, codigo_postal, 
                 linea1, linea2, provincia, canton, distrito, referencia, is_default) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $datos['cliente_id'], $datos['etiqueta'], $datos['nombre'], 
                $datos['apellidos'], $datos['telefono'], $datos['codigo_postal'],
                $datos['linea1'], $datos['linea2'], $datos['provincia'], 
                $datos['canton'], $datos['distrito'], $datos['referencia'], 
                $datos['is_default'] ? 1 : 0
            ]);
            
        } catch (Exception $e) {
            error_log("Error al agregar dirección: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar dirección existente
     */
    public function actualizarDireccionCliente($id, $datos) {
        try {
            // Si es dirección principal, desmarcar las demás
            if ($datos['is_default']) {
                $this->desmarcarDireccionPrincipalCliente($datos['cliente_id']);
            }
            
            $stmt = $this->conn->prepare("
                UPDATE direcciones SET 
                    etiqueta = ?, nombre = ?, apellidos = ?, telefono = ?, 
                    codigo_postal = ?, linea1 = ?, linea2 = ?, provincia = ?, 
                    canton = ?, distrito = ?, referencia = ?, is_default = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND cliente_id = ?
            ");
            
            return $stmt->execute([
                $datos['etiqueta'], $datos['nombre'], $datos['apellidos'], $datos['telefono'],
                $datos['codigo_postal'], $datos['linea1'], $datos['linea2'], 
                $datos['provincia'], $datos['canton'], $datos['distrito'], 
                $datos['referencia'], $datos['is_default'] ? 1 : 0,
                $id, $datos['cliente_id']
            ]);
            
        } catch (Exception $e) {
            error_log("Error al actualizar dirección: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Eliminar dirección
     */
    public function eliminarDireccionCliente($id, $cliente_id) {
        try {
            $stmt = $this->conn->prepare("
                DELETE FROM direcciones 
                WHERE id = ? AND cliente_id = ?
            ");
            return $stmt->execute([$id, $cliente_id]);
        } catch (Exception $e) {
            error_log("Error al eliminar dirección: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Establecer dirección como principal
     */
    public function establecerDireccionPrincipalCliente($id, $cliente_id) {
        try {
            // Primero desmarcar todas las direcciones del cliente
            $this->desmarcarDireccionPrincipalCliente($cliente_id);
            
            // Marcar la dirección específica como principal
            $stmt = $this->conn->prepare("
                UPDATE direcciones 
                SET is_default = 1 
                WHERE id = ? AND cliente_id = ?
            ");
            return $stmt->execute([$id, $cliente_id]);
        } catch (Exception $e) {
            error_log("Error al establecer dirección principal: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desmarcar dirección principal actual
     */
    private function desmarcarDireccionPrincipalCliente($cliente_id) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE direcciones 
                SET is_default = 0 
                WHERE cliente_id = ?
            ");
            return $stmt->execute([$cliente_id]);
        } catch (Exception $e) {
            error_log("Error al desmarcar direcciones principales: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener dirección por ID (con validación de propietario)
     */
    public function obtenerDireccionPorId($id, $cliente_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM direcciones 
                WHERE id = ? AND cliente_id = ?
            ");
            $stmt->execute([$id, $cliente_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error al obtener dirección por ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Formatear dirección completa para mostrar
     */
    public function formatearDireccionCompleta($direccion) {
        if (!$direccion) return '';
        
        $partes = [];
        
        // Línea principal
        if (!empty($direccion['linea1'])) {
            $partes[] = $direccion['linea1'];
        }
        
        // Línea secundaria
        if (!empty($direccion['linea2'])) {
            $partes[] = $direccion['linea2'];
        }
        
        // Ubicación geográfica
        $ubicacion = [];
        if (!empty($direccion['distrito'])) $ubicacion[] = $direccion['distrito'];
        if (!empty($direccion['canton'])) $ubicacion[] = $direccion['canton'];
        if (!empty($direccion['provincia'])) $ubicacion[] = $direccion['provincia'];
        
        if (!empty($ubicacion)) {
            $partes[] = implode(', ', $ubicacion);
        }
        
        // Código postal
        if (!empty($direccion['codigo_postal'])) {
            $partes[] = $direccion['codigo_postal'];
        }
        
        return implode(', ', $partes);
    }
    
    /**
     * Validar datos de dirección
     */
    public function validarDatosDireccion($datos) {
        $errores = [];
        
        if (empty($datos['nombre'])) {
            $errores[] = "El nombre es obligatorio";
        }
        
        if (empty($datos['apellidos'])) {
            $errores[] = "Los apellidos son obligatorios";
        }
        
        if (empty($datos['linea1'])) {
            $errores[] = "La dirección principal es obligatoria";
        }
        
        if (empty($datos['provincia'])) {
            $errores[] = "La provincia es obligatoria";
        }
        
        if (empty($datos['canton'])) {
            $errores[] = "El cantón es obligatorio";
        }
        
        if (empty($datos['distrito'])) {
            $errores[] = "El distrito es obligatorio";
        }
        
        // Validar teléfono si se proporciona
        if (!empty($datos['telefono']) && !preg_match('/^[0-9+\-\s()]+$/', $datos['telefono'])) {
            $errores[] = "El formato del teléfono no es válido";
        }
        
        return $errores;
    }
    
    /**
     * Contar direcciones de un cliente
     */
    public function contarDireccionesCliente($cliente_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM direcciones 
                WHERE cliente_id = ?
            ");
            $stmt->execute([$cliente_id]);
            $result = $stmt->fetch();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error al contar direcciones: " . $e->getMessage());
            return 0;
        }
    }
}
?>
