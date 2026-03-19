<?php
/**
 * Script de Importación de Aprendices desde Excel
 * Lee archivos .xls de la carpeta fichas/ e importa/actualiza en la base de datos
 * 
 * REQUERIMIENTOS:
 * - PhpSpreadsheet instalado (composer require phpoffice/phpspreadsheet)
 * - Permisos de escritura en logs/
 * 
 * MAPEO DE ESTADOS:
 * - "EN FORMACION" → "LECTIVA"
 * - "INDUCCION" → "LECTIVA"
 * - No encontrados → "PRODUCTIVA"
 * - "Cancelado/Cancelada" → "CANCELADO"
 * - "Retirado/Retiro" → "RETIRADO"
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/Database.php';

// Verificar si PhpSpreadsheet está instalado
$phpspreadsheetAvailable = false;
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    $phpspreadsheetAvailable = class_exists('PhpOffice\PhpSpreadsheet\IOFactory');
} catch (Exception $e) {
    // No está instalado
}

if (!$phpspreadsheetAvailable) {
    echo json_encode([
        'success' => false,
        'message' => 'PhpSpreadsheet no está instalado. Ejecute: composer require phpoffice/phpspreadsheet',
        'instruction' => 'En Render, agregue al build command: composer install'
    ]);
    exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportadorAprendices {
    private $conn;
    private $fichasDir;
    private $logs = [];
    
    // FICHAS A OMITIR COMPLETAMENTE
    private $fichasOmitir = ['2995479'];
    
    // FICHAS QUE SOLO ACTUALIZAN ESTADO (no tocar otros datos)
    private $fichasSoloEstado = ['3236691'];
    
    private $stats = [
        'fichas_procesadas' => 0,
        'aprendices_creados' => 0,
        'aprendices_actualizados' => 0,
        'fichas_creadas' => 0,
        'fichas_actualizadas' => 0,
        'errores' => 0
    ];

    // Documentos procesados en esta corrida para no marcarlos como PRODUCTIVA
    private $processedDocs = [];

    public function __construct($conn) {
        $this->conn = $conn;
        $this->fichasDir = __DIR__ . '/../fichas/';
    }

    /**
     * Mapear estado del Excel al sistema SenApre
     */
    private function mapearEstado($estadoExcel) {
        $estado = strtoupper(trim($estadoExcel));
        
        // Estados que van a LECTIVA
        if (in_array($estado, ['EN FORMACION', 'EN FORMACIÓN', 'INDUCCION', 'INDUCCIÓN', 'LECTIVA'])) {
            return 'LECTIVA';
        }
        
        // Estados cancelados
        if (in_array($estado, ['CANCELADO', 'CANCELADA'])) {
            return 'CANCELADO';
        }
        
        // Estados retirados
        if (in_array($estado, ['RETIRADO', 'RETIRO', 'RETIRADA'])) {
            return 'RETIRADO';
        }
        
        // Estados finalizados
        if (in_array($estado, ['FINALIZADO', 'FINALIZADA', 'CERTIFICADO'])) {
            return 'FINALIZADO';
        }
        
        // Por defecto: PRODUCTIVA (si no está en el Excel)
        return 'LECTIVA'; // Los que están en Excel van a LECTIVA por defecto
    }

    /**
     * Extraer número de ficha del nombre del archivo
     */
    private function extraerNumeroFicha($filename) {
        // Patrón: "Reporte de Aprendices Ficha XXXXXXX.xls"
        if (preg_match('/Ficha\s+(\d+)/', $filename, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Leer archivo Excel
     */
    private function leerExcel($filepath) {
        try {
            $reader = IOFactory::createReader('Xls');
            $spreadsheet = $reader->load($filepath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            return $rows;
        } catch (Exception $e) {
            $this->log("Error leyendo Excel: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Buscar o crear ficha
     */
    private function procesarFicha($numeroFicha, $datosFicha = []) {
        try {
            // Verificar si ficha existe
            $stmt = $this->conn->prepare("SELECT numero_ficha, programa_id FROM fichas WHERE numero_ficha = :numero");
            $stmt->execute([':numero' => $numeroFicha]);
            $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ficha) {
                // Actualizar ficha si es necesario
                $this->stats['fichas_actualizadas']++;
                $this->log("Ficha $numeroFicha actualizada", 'info');
                return $ficha;
            } else {
                // Crear nueva ficha
                $stmt = $this->conn->prepare("
                    INSERT INTO fichas (numero_ficha, nombre_programa, nivel_formacion, estado, fecha_inicio, fecha_fin)
                    VALUES (:numero, :programa, :nivel, 'LECTIVA', :fecha_inicio, :fecha_fin)
                ");
                
                $stmt->execute([
                    ':numero' => $numeroFicha,
                    ':programa' => $datosFicha['programa'] ?? 'SIN PROGRAMA',
                    ':nivel' => $datosFicha['nivel'] ?? 'TECNICO',
                    ':fecha_inicio' => $datosFicha['fecha_inicio'] ?? date('Y-m-d'),
                    ':fecha_fin' => $datosFicha['fecha_fin'] ?? date('Y-m-d', strtotime('+1 year'))
                ]);
                
                $this->stats['fichas_creadas']++;
                $this->log("Ficha $numeroFicha creada", 'success');
                
                return ['numero_ficha' => $numeroFicha, 'programa_id' => null];
            }
        } catch (Exception $e) {
            $this->stats['errores']++;
            $this->log("Error procesando ficha $numeroFicha: " . $e->getMessage(), 'error');
            return null;
        }
    }

    /**
     * Buscar o crear aprendiz
     */
    private function procesarAprendiz($datos, $numeroFicha) {
        try {
            $documento = $datos['documento'];
            
            // Verificar si aprendiz existe
            $stmt = $this->conn->prepare("SELECT * FROM aprendices WHERE documento = :documento");
            $stmt->execute([':documento' => $documento]);
            $aprendiz = $stmt->fetch(PDO::FETCH_ASSOC);

            // Preparar datos
            $nombre = $datos['nombres'] ?? '';
            $apellido = $datos['apellidos'] ?? '';
            $email = $datos['email'] ?? '';
            $telefono = $datos['telefono'] ?? '';
            $estado = $this->mapearEstado($datos['estado'] ?? 'LECTIVA');

            if ($aprendiz) {
                // VERIFICAR SI EL APRENDIZ YA TIENE DATOS COMPLETOS (no modificar si vocero ya actualizó)
                $tieneDatosCompletos = !empty($aprendiz['nombre']) && 
                                       !empty($aprendiz['apellido']) && 
                                       !empty($aprendiz['correo']) && 
                                       !empty($aprendiz['celular']);
                
                if ($tieneDatosCompletos && !in_array($numeroFicha, $this->fichasSoloEstado)) {
                    // Solo actualizar estado y ficha, preservar datos del vocero
                    $stmt = $this->conn->prepare("
                        UPDATE aprendices SET
                            estado = :estado,
                            numero_ficha = :ficha,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE documento = :documento
                    ");
                    
                    $stmt->execute([
                        ':estado' => $estado,
                        ':ficha' => $numeroFicha,
                        ':documento' => $documento
                    ]);
                    
                    $this->stats['aprendices_actualizados']++;
                    $this->log("Aprendiz $documento - Solo estado/ficha actualizado (datos preservados de vocero)", 'info');
                }
                // VERIFICAR SI LA FICHA SOLO ACTUALIZA ESTADO
                else if (in_array($numeroFicha, $this->fichasSoloEstado)) {
                    // Solo actualizar estado (el vocero ya actualizó otros datos)
                    $stmt = $this->conn->prepare("
                        UPDATE aprendices SET
                            estado = :estado,
                            numero_ficha = :ficha,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE documento = :documento
                    ");
                    
                    $stmt->execute([
                        ':estado' => $estado,
                        ':ficha' => $numeroFicha,
                        ':documento' => $documento
                    ]);
                    
                    $this->stats['aprendices_actualizados']++;
                    $this->log("Aprendiz $documento - SOLO ESTADO actualizado a: $estado (ficha en modo solo-estado)", 'info');
                } else {
                    // Actualizar aprendiz completo (normal)
                    $stmt = $this->conn->prepare("
                        UPDATE aprendices SET
                            nombre = :nombre,
                            apellido = :apellido,
                            correo = :email,
                            celular = :telefono,
                            estado = :estado,
                            numero_ficha = :ficha,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE documento = :documento
                    ");
                    
                    $stmt->execute([
                        ':nombre' => $nombre,
                        ':apellido' => $apellido,
                        ':email' => $email,
                        ':telefono' => $telefono,
                        ':estado' => $estado,
                        ':ficha' => $numeroFicha,
                        ':documento' => $documento
                    ]);
                    
                    $this->stats['aprendices_actualizados']++;
                    $this->log("Aprendiz actualizado: $documento - $nombre $apellido", 'info');
                }
                
            } else {
                // Crear nuevo aprendiz
                $stmt = $this->conn->prepare("
                    INSERT INTO aprendices (
                        documento, nombre, apellido, tipo_documento,
                        correo, celular, estado, numero_ficha,
                        created_at, updated_at
                    ) VALUES (
                        :documento, :nombre, :apellido, 'CC',
                        :email, :telefono, :estado, :ficha,
                        CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )
                ");
                
                $stmt->execute([
                    ':documento' => $documento,
                    ':nombre' => $nombre,
                    ':apellido' => $apellido,
                    ':email' => $email,
                    ':telefono' => $telefono,
                    ':estado' => $estado,
                    ':ficha' => $numeroFicha
                ]);
                
                $this->stats['aprendices_creados']++;
                $this->log("Aprendiz creado: $documento - $nombre $apellido", 'success');
            }

            // Registrar como procesado para que no se marque como PRODUCTIVA
            $this->processedDocs[] = $documento;

        } catch (Exception $e) {
            $this->stats['errores']++;
            $this->log("Error procesando aprendiz {$datos['documento']}: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Procesar un archivo Excel
     */
    private function procesarArchivo($filepath) {
        $filename = basename($filepath);
        $numeroFicha = $this->extraerNumeroFicha($filename);
        
        if (!$numeroFicha) {
            $this->log("No se pudo extraer número de ficha de: $filename", 'error');
            return;
        }
        
        // VERIFICAR SI LA FICHA DEBE SER OMITIDA
        if (in_array($numeroFicha, $this->fichasOmitir)) {
            $this->log("Ficha $numeroFicha OMITIDA (en lista de exclusión)", 'warning');
            return;
        }
        
        $this->log("Procesando ficha: $numeroFicha", 'info');
        
        // Procesar/crear ficha
        $ficha = $this->procesarFicha($numeroFicha);
        if (!$ficha) {
            return;
        }
        
        // Leer Excel
        $rows = $this->leerExcel($filepath);
        if (!$rows || count($rows) < 2) {
            $this->log("Archivo vacío o sin datos: $filename", 'warning');
            return;
        }
        
        // Detectar columnas (primera fila es header)
        $headers = array_map('strtoupper', array_map('trim', $rows[0]));
        
        // Mapeo flexible de columnas
        $colDocumento = $this->buscarColumna($headers, ['DOCUMENTO', 'NÚMERO DOCUMENTO', 'NUMERO DOCUMENTO', 'NRO DOCUMENTO']);
        $colNombres = $this->buscarColumna($headers, ['NOMBRES', 'NOMBRE', 'NOMBRE COMPLETO']);
        $colApellidos = $this->buscarColumna($headers, ['APELLIDOS', 'APELLIDO']);
        $colEmail = $this->buscarColumna($headers, ['CORREO', 'EMAIL', 'CORREO ELECTRONICO', 'CORREO ELECTRÓNICO', 'E-MAIL']);
        $colTelefono = $this->buscarColumna($headers, ['TELEFONO', 'TELÉFONO', 'CELULAR', 'MOVIL', 'MÓVIL', 'CONTACTO']);
        $colEstado = $this->buscarColumna($headers, ['ESTADO', 'ESTADO APRENDIZ', 'SITUACION', 'SITUACIÓN']);
        
        if ($colDocumento === null) {
            $this->log("No se encontró columna de documento en: $filename", 'error');
            return;
        }
        
        // Procesar filas de datos (desde la segunda fila)
        $aprendicesProcesados = 0;
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Verificar si la fila tiene datos
            if (empty($row[$colDocumento])) {
                continue;
            }
            
            $datos = [
                'documento' => trim($row[$colDocumento]),
                'nombres' => $colNombres !== null ? ($row[$colNombres] ?? '') : '',
                'apellidos' => $colApellidos !== null ? ($row[$colApellidos] ?? '') : '',
                'email' => $colEmail !== null ? ($row[$colEmail] ?? '') : '',
                'telefono' => $colTelefono !== null ? ($row[$colTelefono] ?? '') : '',
                'estado' => $colEstado !== null ? ($row[$colEstado] ?? 'LECTIVA') : 'LECTIVA'
            ];
            
            $this->procesarAprendiz($datos, $numeroFicha);
            $aprendicesProcesados++;
        }
        
        $this->stats['fichas_procesadas']++;
        $this->log("Ficha $numeroFicha procesada: $aprendicesProcesados aprendices", 'success');
    }

    /**
     * Buscar índice de columna por posibles nombres
     */
    private function buscarColumna($headers, $posiblesNombres) {
        foreach ($posiblesNombres as $nombre) {
            $index = array_search(strtoupper($nombre), $headers);
            if ($index !== false) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Marcar aprendices no encontrados en Excel como PRODUCTIVA
     */
    private function marcarProductivos() {
        try {
            if (empty($this->processedDocs)) {
                $this->log("No se procesaron aprendices en Excel, cancelando marcado de PRODUCTIVA por seguridad.", 'warning');
                return;
            }

            $this->log("Marcando aprendices no encontrados en Excel (que eran LECTIVA) como PRODUCTIVA...", 'info');
            
            // Cantidad de aprendices procesados
            $totalProc = count($this->processedDocs);
            $this->log("Aprendices en Excel: $totalProc", 'info');

            // Lógica final: Todo el que sea LECTIVA pero NO esté en processedDocs -> PRODUCTIVA
            // Usamos una consulta con NOT IN. Si son muchos (>5000), se recomienda procesar por lotes o usar tabla temporal.
            
            $placeholders = implode(',', array_fill(0, count($this->processedDocs), '?'));
            $sql = "UPDATE aprendices SET estado = 'PRODUCTIVA' WHERE estado = 'LECTIVA' AND documento NOT IN ($placeholders)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($this->processedDocs);
            $actualizados = $stmt->rowCount();
            
            if ($actualizados > 0) {
                $this->log("$actualizados aprendices pasaron de LECTIVA a PRODUCTIVA (no estaban en Excel)", 'warning');
            } else {
                $this->log("No se encontraron aprendices para pasar a PRODUCTIVA", 'info');
            }
            
        } catch (Exception $e) {
            $this->log("Error marcando como PRODUCTIVA: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Ejecutar importación completa
     */
    public function ejecutar() {
        try {
            $this->log("=== INICIANDO IMPORTACIÓN ===", 'info');
            $this->log("Directorio: " . $this->fichasDir, 'info');
            
            // Verificar directorio
            if (!is_dir($this->fichasDir)) {
                throw new Exception("Directorio no encontrado: " . $this->fichasDir);
            }
            
            // Obtener archivos Excel
            $archivos = glob($this->fichasDir . '*.xls');
            
            if (empty($archivos)) {
                throw new Exception("No se encontraron archivos .xls en: " . $this->fichasDir);
            }
            
            $this->log("Archivos encontrados: " . count($archivos), 'info');
            
            // Procesar cada archivo
            foreach ($archivos as $archivo) {
                $this->procesarArchivo($archivo);
            }
            
            // Marcar como PRODUCTIVA los no encontrados
            $this->marcarProductivos();
            
            $this->log("=== IMPORTACIÓN COMPLETADA ===", 'success');
            
            return [
                'success' => true,
                'stats' => $this->stats,
                'logs' => $this->logs
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $this->stats,
                'logs' => $this->logs
            ];
        }
    }

    /**
     * Agregar entrada al log
     */
    private function log($mensaje, $tipo = 'info') {
        $this->logs[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => $tipo,
            'mensaje' => $mensaje
        ];
        
        // También mostrar en tiempo real
        echo "$tipo: $mensaje\n";
        flush();
    }
}

// Ejecutar si se llama directamente
if (php_sapi_name() === 'cli' || isset($_GET['ejecutar'])) {
    try {
        $database = Database::getInstance();
        $conn = $database->getConnection();
        
        $importador = new ImportadorAprendices($conn);
        $resultado = $importador->ejecutar();
        
        header('Content-Type: application/json');
        echo json_encode($resultado, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    // Mostrar instrucciones
    echo json_encode([
        'success' => false,
        'message' => 'Acceso no válido',
        'instrucciones' => [
            '1. Instalar PhpSpreadsheet: composer require phpoffice/phpspreadsheet',
            '2. Colocar archivos Excel en: fichas/',
            '3. Ejecutar: GET api/importar-excel.php?ejecutar=1',
            '4. O por CLI: php api/importar-excel.php'
        ]
    ]);
}
?>
