<?php
error_reporting(0);
@ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once dirname(__DIR__) . '/config/Database.php';

function responder(int $code, bool $ok, string $msg, $data = null): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $conn   = Database::getInstance()->getConnection();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // ─── GET: Obtener ficha + aprendices del vocero ──────────
    if ($method === 'GET') {
        $idUsuario = trim($_GET['id_usuario'] ?? '');
        if (!$idUsuario) {
            responder(400, false, 'Se requiere id_usuario');
        }

        // 1. Buscar ficha asignada
        $stmt = $conn->prepare("
            SELECT numero_ficha, nombre_programa, estado, tipoFormacion,
                   vocero_principal, vocero_suplente
            FROM fichas
            WHERE TRIM(vocero_principal) = :id
               OR TRIM(vocero_suplente)  = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $idUsuario]);
        $ficha = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ficha) {
            responder(404, false, 'No tiene una ficha asignada. Contacte al administrador.');
        }

        $nFicha = trim($ficha['numero_ficha']);
        $tipo   = trim($ficha['vocero_principal']) === $idUsuario ? 'principal' : 'suplente';

        // 2. Obtener aprendices de esa ficha (todos los estados)
        $stmt2 = $conn->prepare("
            SELECT documento, nombre, apellido, correo, celular, estado, tipo_poblacion,
                   mujer, indigena, narp, campesino, lgbtiq, discapacidad
            FROM aprendices
            WHERE TRIM(numero_ficha) = :f
            ORDER BY apellido, nombre
        ");
        $stmt2->execute([':f' => $nFicha]);
        $aprendices = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // 3. Calcular resumen
        $resumen = [];
        foreach ($aprendices as $a) {
            $est = strtoupper(trim($a['estado'] ?? 'SIN ESTADO'));
            $resumen[$est] = ($resumen[$est] ?? 0) + 1;
        }
        arsort($resumen);

        responder(200, true, 'OK', [
            'ficha'      => $ficha,
            'tipo_vocero'=> $tipo,
            'aprendices' => $aprendices,
            'resumen'    => $resumen,
            'total'      => count($aprendices),
        ]);
    }

    // ─── PUT: Actualizar correo / celular de un aprendiz ────
    if ($method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true);
        $doc       = trim($body['documento']  ?? '');
        $correo    = trim($body['correo']     ?? '');
        $celular   = trim($body['celular']    ?? '');
        $ficha     = trim($body['numero_ficha'] ?? '');
        $poblacion = $body['poblacion'] ?? []; // Objeto con los 6 checks

        if (!$doc || !$ficha) {
            responder(400, false, 'Se requiere documento y numero_ficha');
        }

        // Solo actualizar los campos que vengan con valor
        $set    = [];
        $params = [':doc' => $doc, ':ficha' => $ficha];
        
        if ($correo !== '')  { $set[] = 'correo = :correo';   $params[':correo']  = $correo; }
        if ($celular !== '') { $set[] = 'celular = :celular'; $params[':celular'] = $celular; }

        // Agregar campos de poblacion si vienen en el payload
        $pobFields = ['mujer', 'indigena', 'narp', 'campesino', 'lgbtiq', 'discapacidad'];
        foreach ($pobFields as $pf) {
            if (isset($poblacion[$pf])) {
                $set[] = "$pf = :$pf";
                $params[":$pf"] = $poblacion[$pf] ? 1 : 0;
            }
        }

        if (empty($set)) {
            responder(400, false, 'Sin campos para actualizar');
        }

        $stmt = $conn->prepare("
            UPDATE aprendices
            SET " . implode(', ', $set) . "
            WHERE TRIM(documento) = :doc
              AND TRIM(numero_ficha) = :ficha
        ");
        $stmt->execute($params);

        if ($stmt->rowCount() > 0 || !empty($poblacion)) {
            // Incluso si no hay rowCount (los datos eran iguales), devolvemos éxito.
            responder(200, true, 'Información actualizada correctamente');
        } else {
            responder(404, false, 'Aprendiz no encontrado en esta ficha');
        }
    }

    responder(405, false, 'Método no permitido');

} catch (Exception $e) {
    responder(500, false, 'Error interno del servidor');
}
?>
