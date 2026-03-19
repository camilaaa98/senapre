<?php
/**
 * PÁGINA DE AUTORIZACIÓN DE VOCERO PARA INSTRUCTORES
 * Formulario oficial con logos SENA/SenApre
 */

require_once __DIR__ . '/../api/config/Database.php';

// Obtener ID de la convocatoria desde la URL
$convocatoria_id = $_GET['id'] ?? $_GET['convocatoria'] ?? null;
$ficha = $_GET['ficha'] ?? null;

if (!$convocatoria_id && !$ficha) {
    die('<h1>Error: No se especificó la convocatoria o ficha</h1>');
}

$database = Database::getInstance();
$conn = $database->getConnection();

// Obtener datos de la convocatoria
if ($convocatoria_id) {
    $stmt = $conn->prepare("
        SELECT * FROM convocatorias_reunion WHERE id = :id
    ");
    $stmt->execute([':id' => $convocatoria_id]);
    $convocatoria = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$convocatoria) {
        die('<h1>Error: Convocatoria no encontrada</h1>');
    }
} else {
    // Crear convocatoria temporal si no existe
    $convocatoria = [
        'id' => 'temp_' . time(),
        'titulo' => 'Reunión de Voceros y Representantes',
        'fecha' => date('Y-m-d', strtotime('+7 days')),
        'hora' => '2:00 PM',
        'lugar' => 'Auditorio Principal - SENA Teleinformática',
        'tipo' => 'Ordinaria',
        'agenda' => json_encode([
            '1. Informes de gestión',
            '2. Situación de aprendices',
            '3. Próximas actividades',
            '4. Varios'
        ])
    ];
}

// Obtener voceros de la ficha
$voceros = [];
if ($ficha) {
    // Obtener vocero principal
    $stmt = $conn->prepare("
        SELECT a.*, f.numero_ficha, f.instructor_lider,
               'Vocero Principal' as rol_vocero
        FROM aprendices a
        JOIN fichas f ON a.numero_ficha = f.numero_ficha
        WHERE f.numero_ficha = :ficha 
        AND f.vocero_principal = a.documento
        AND a.estado = 'LECTIVA'
        LIMIT 1
    ");
    $stmt->execute([':ficha' => $ficha]);
    $principal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($principal) {
        $voceros[] = $principal;
    }
    
    // Obtener vocero suplente
    $stmt = $conn->prepare("
        SELECT a.*, f.numero_ficha, f.instructor_lider,
               'Vocero Suplente' as rol_vocero
        FROM aprendices a
        JOIN fichas f ON a.numero_ficha = f.numero_ficha
        WHERE f.numero_ficha = :ficha 
        AND f.vocero_suplente = a.documento
        AND a.estado = 'LECTIVA'
        LIMIT 1
    ");
    $stmt->execute([':ficha' => $ficha]);
    $suplente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($suplente) {
        $voceros[] = $suplente;
    }
}

// Si no hay voceros, mostrar datos de ejemplo
if (empty($voceros)) {
    $voceros = [
        [
            'id_aprendiz' => 'demo_1',
            'nombre' => 'ANA MARÍA LÓPEZ',
            'documento' => '1087654321',
            'rol_vocero' => 'Vocero Principal',
            'numero_ficha' => $ficha ?? '2559099',
            'instructor_lider' => 'CARLOS GARCÍA'
        ],
        [
            'id_aprendiz' => 'demo_2',
            'nombre' => 'LUIS FERNANDO TORRES',
            'documento' => '1098765432',
            'rol_vocero' => 'Vocero Suplente',
            'numero_ficha' => $ficha ?? '2559099',
            'instructor_lider' => 'CARLOS GARCÍA'
        ]
    ];
}

$agenda = json_decode($convocatoria['agenda'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oficio Autorización Vocero - SENA</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            line-height: 1.6;
        }
        
        .oficio-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border: 2px solid #0066cc;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
        }
        
        .logos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .logo-left {
            width: 120px;
            height: 80px;
            background: #0066cc;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            border-radius: 4px;
        }
        
        .logo-right {
            width: 120px;
            height: 80px;
            background: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            border-radius: 4px;
        }
        
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #0066cc;
            text-transform: uppercase;
            margin: 20px 0;
        }
        
        .content {
            margin-bottom: 30px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            width: 120px;
            color: #0066cc;
        }
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .info-table th,
        .info-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .info-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #0066cc;
        }
        
        .vocero-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #0066cc;
            border-radius: 0 8px 8px 0;
        }
        
        .vocero-principal {
            border-left-color: #28a745;
        }
        
        .vocero-suplente {
            border-left-color: #ffc107;
        }
        
        .vocero-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .vocero-title {
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .vocero-radio {
            margin-right: 10px;
        }
        
        .authorization-section {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .authorization-options {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .authorization-option {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .authorization-option:hover {
            background: #f8f9fa;
            border-color: #0066cc;
        }
        
        .authorization-option.selected {
            background: #e7f3ff;
            border-color: #0066cc;
        }
        
        .authorization-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .authorization-option .vocero-info {
            flex: 1;
        }
        
        .authorization-option .vocero-name {
            font-weight: bold;
            color: #333;
        }
        
        .authorization-option .vocero-details {
            font-size: 0.9em;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 80px;
        }
        
        .signature-section {
            margin-top: 50px;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            width: 300px;
            margin: 60px auto 10px;
        }
        
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #0066cc;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0052a3;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .stamp {
            border: 2px dashed #0066cc;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: none; /* Se mostrará solo después de firmar */
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            color: #0066cc;
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            transform: rotate(-15deg);
        }
        
        .stamp.visible {
            display: flex;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .oficio-container { box-shadow: none; border: 2px solid #0066cc; }
            .action-buttons { display: none; }
            .stamp { display: flex !important; }
        }
        
        @media (max-width: 768px) {
            .oficio-container {
                padding: 20px;
                margin: 10px;
            }
            
            .logos {
                flex-direction: column;
                gap: 10px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                width: auto;
                margin-bottom: 5px;
            }
            
            .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <div class="oficio-container">
        <!-- CABECERA CON LOGOS -->
        <div class="header">
            <div class="logos">
                <div class="logo-left">
                    SERVICIO NACIONAL DE APRENDIZAJE<br>SENA
                </div>
                <div class="logo-right">
                    SenApre<br>Sistema de Gestión
                </div>
            </div>
            <div class="title">
                OFICIO DE AUTORIZACIÓN DE ASISTENCIA<br>
                VOCERO DE GRUPO - REUNIÓN DE PARTICIPACIÓN
            </div>
        </div>

        <!-- CONTENIDO DEL OFICIO -->
        <div class="content">
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span><?php echo date('d') . ' de ' . date('F') . ' de ' . date('Y'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Para:</span>
                <span>Coordinación de Bienestar</span>
            </div>
            <div class="info-row">
                <span class="info-label">De:</span>
                <span id="instructor-nombre"><?php echo $voceros[0]['instructor_lider'] ?? 'Instructor Líder'; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Asunto:</span>
                <span>Autorización de Asistencia de Vocero a Reunión</span>
            </div>
            <br>
            
            <p>Reciba un cordial saludo.</p>
            
            <p>Por medio de la presente, se solicita autorización para la asistencia del vocero del grupo a la reunión programada, de acuerdo con la siguiente información:</p>
            
            <!-- TABLA DE INFORMACIÓN DE LA REUNIÓN -->
            <table class="info-table">
                <tr>
                    <th>REUNIÓN</th>
                    <td><?php echo htmlspecialchars($convocatoria['titulo']); ?></td>
                </tr>
                <tr>
                    <th>FECHA</th>
                    <td><?php echo date('d/m/Y', strtotime($convocatoria['fecha'])); ?></td>
                </tr>
                <tr>
                    <th>HORA</th>
                    <td><?php echo htmlspecialchars($convocatoria['hora']); ?></td>
                </tr>
                <tr>
                    <th>LUGAR</th>
                    <td><?php echo htmlspecialchars($convocatoria['lugar']); ?></td>
                </tr>
                <tr>
                    <th>FICHA</th>
                    <td><?php echo htmlspecialchars($voceros[0]['numero_ficha'] ?? 'N/A'); ?></td>
                </tr>
            </table>

            <!-- INFORMACIÓN DE VOCEROS -->
            <h3 style="color: #0066cc; margin-top: 30px;">VOCEROS DESIGNADOS</h3>
            
            <?php foreach ($voceros as $index => $vocero): ?>
            <div class="vocero-item <?php echo $vocero['rol_vocero'] === 'Vocero Principal' ? 'vocero-principal' : 'vocero-suplente'; ?>">
                <div class="vocero-header">
                    <h4 class="vocero-title">
                        <?php echo $vocero['rol_vocero'] === 'Vocero Principal' ? '🎭' : '🔄'; ?>
                        <?php echo htmlspecialchars($vocero['rol_vocero']); ?>
                    </h4>
                </div>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($vocero['nombre']); ?></p>
                <p><strong>Documento:</strong> <?php echo htmlspecialchars($vocero['documento']); ?></p>
                <p><strong>Estado:</strong> <span id="estado-<?php echo $index; ?>">PENDIENTE DE AUTORIZACIÓN</span></p>
            </div>
            <?php endforeach; ?>

            <!-- SECCIÓN DE AUTORIZACIÓN -->
            <div class="authorization-section">
                <h3 style="color: #0066cc; margin-top: 0;">📋 AUTORIZACIÓN REQUERIDA</h3>
                <p><strong>REGLA DE ASISTENCIA:</strong></p>
                <ul>
                    <li>✅ El <strong>Vocero Principal</strong> tiene prioridad de asistencia</li>
                    <li>🔄 El <strong>Vocero Suplente</strong> solo asiste si el Principal NO puede</li>
                    <li>⚠️ <strong>SOLO UNO</strong> de los dos puede recibir autorización</li>
                    <li>📚 El aprendiz debe estar al día con sus compromisos formativos</li>
                </ul>
                
                <form id="autorizacion-form">
                    <p><strong>DECISIÓN DEL INSTRUCTOR:</strong></p>
                    <div class="authorization-options">
                        <?php foreach ($voceros as $index => $vocero): ?>
                        <div class="authorization-option" onclick="selectVocero(<?php echo $index; ?>)">
                            <input type="radio" name="vocero_autorizado" id="vocero-<?php echo $index; ?>" 
                                   value="<?php echo htmlspecialchars($vocero['id_aprendiz']); ?>" class="vocero-radio">
                            <div class="vocero-info">
                                <div class="vocero-name"><?php echo htmlspecialchars($vocero['nombre']); ?></div>
                                <div class="vocero-details">
                                    <?php echo htmlspecialchars($vocero['rol_vocero']); ?> - 
                                    Ficha <?php echo htmlspecialchars($vocero['numero_ficha']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="observaciones">Observaciones (opcional):</label>
                        <textarea id="observaciones" name="observaciones" 
                                  placeholder="Ingrese cualquier observación relevante sobre la autorización..."></textarea>
                    </div>
                </form>
            </div>

            <!-- COMPROMISO DEL INSTRUCTOR -->
            <div style="margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <h4 style="margin: 0; color: #0066cc;">📝 COMPROMISO</h4>
                <p>Como instructor líder, confirmo que:</p>
                <ul>
                    <li>El vocero autorizado está al día con sus actividades formativas</li>
                    <li>No tiene ninguna sanción o impedimento para asistir</li>
                    <li>Se le permitirá hacer uso del tiempo necesario para la reunión</li>
                    <li>Se le apoyará en la recuperación de actividades académicas</li>
                </ul>
            </div>

            <p>Agradezco de antemano su atención y colaboración en este proceso de participación estudiantil.</p>
            
            <p>Atentamente,</p>
        </div>

        <!-- SECCIÓN DE FIRMA -->
        <div class="signature-section">
            <div class="signature-line"></div>
            <p><strong id="firma-nombre"><?php echo htmlspecialchars($voceros[0]['instructor_lider'] ?? 'INSTRUCTOR LÍDER'); ?></strong></p>
            <p>Instructor Líder</p>
            <p>Ficha <?php echo htmlspecialchars($voceros[0]['numero_ficha'] ?? 'N/A'); ?></p>
            <p>SENA - Centro de Teleinformática y Producción Industrial</p>
            
            <!-- SELLO DIGITAL -->
            <div class="stamp" id="sello-digital">
                SENA<br>TELEINFORMÁTICA<br>OFICIO<br>AUTORIZADO
            </div>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="action-buttons">
            <button type="button" class="btn btn-primary" onclick="submitAutorizacion()">
                <i class="fas fa-check"></i> Firmar y Enviar Autorización
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir Oficio
            </button>
        </div>

        <!-- PIE DE PÁGINA -->
        <div class="footer">
            <p>Servicio Nacional de Aprendizaje - SENA</p>
            <p>Centro de Teleinformática y Producción Industrial</p>
            <p>Sistema de Gestión SenApre - Oficio No. <?php echo date('Y-m-d') . '-001'; ?></p>
            <p>Generado el: <?php echo date('d/m/Y - H:i A'); ?></p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Variables globales
        let selectedVoceroIndex = null;
        const convocatoriaId = '<?php echo $convocatoria_id; ?>';
        const voceros = <?php echo json_encode($voceros); ?>;
        
        // Seleccionar vocero
        function selectVocero(index) {
            // Limpiar selección anterior
            document.querySelectorAll('.authorization-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Marcar nueva selección
            const selectedOption = document.querySelectorAll('.authorization-option')[index];
            selectedOption.classList.add('selected');
            
            // Seleccionar radio button
            document.getElementById('vocero-' + index).checked = true;
            
            // Actualizar estados
            selectedVoceroIndex = index;
            updateEstados(index);
        }
        
        // Actualizar estados de los voceros
        function updateEstados(selectedIndex) {
            voceros.forEach((vocero, index) => {
                const estadoElement = document.getElementById('estado-' + index);
                if (index === selectedIndex) {
                    estadoElement.textContent = '✅ AUTORIZADO PARA ASISTIR';
                    estadoElement.style.color = '#28a745';
                } else {
                    if (vocero.rol_vocero === 'Vocero Principal') {
                        estadoElement.textContent = '❌ NO ASISTIRÁ (AUTORIZADO SUPLENTE)';
                    } else {
                        estadoElement.textContent = '❌ NO ASISTIRÁ (EL PRINCIPAL ASISTE)';
                    }
                    estadoElement.style.color = '#dc3545';
                }
            });
        }
        
        // Enviar autorización
        async function submitAutorizacion() {
            if (selectedVoceroIndex === null) {
                showMessage('Por favor seleccione el vocero que asistirá a la reunión.', 'error');
                return;
            }
            
            const formData = new FormData(document.getElementById('autorizacion-form'));
            const voceroAutorizado = voceros[selectedVoceroIndex];
            
            const data = {
                convocatoria_id: convocatoriaId,
                instructor_id: '<?php echo $voceros[0]['instructor_lider'] ?? ''; ?>',
                vocero_autorizado_id: voceroAutorizado.id_aprendiz,
                observaciones: formData.get('observaciones') || ''
            };
            
            try {
                // Mostrar loading
                const submitBtn = document.querySelector('.btn-primary');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
                submitBtn.disabled = true;
                
                const response = await fetch('../api/notificaciones.php?action=procesarAutorizacion', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Mostrar sello digital
                    document.getElementById('sello-digital').classList.add('visible');
                    
                    // Deshabilitar formulario
                    document.querySelectorAll('.authorization-option').forEach(option => {
                        option.style.pointerEvents = 'none';
                        option.style.opacity = '0.7';
                    });
                    
                    document.getElementById('observaciones').disabled = true;
                    
                    showMessage('✅ Autorización procesada exitosamente. Se han enviado las notificaciones correspondientes.', 'success');
                    
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Autorización Completada';
                    submitBtn.style.background = '#28a745';
                    
                    // Opcional: Redirigir después de 3 segundos
                    setTimeout(() => {
                        if (confirm('¿Desea imprimir el oficio ahora?')) {
                            window.print();
                        }
                    }, 2000);
                    
                } else {
                    showMessage('Error: ' + result.error, 'error');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                showMessage('Error de conexión: ' + error.message, 'error');
                const submitBtn = document.querySelector('.btn-primary');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
        
        // Mostrar mensajes
        function showMessage(message, type = 'info') {
            // Eliminar mensajes anteriores
            const existingMessage = document.querySelector('.success-message, .error-message');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Crear nuevo mensaje
            const messageDiv = document.createElement('div');
            messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            
            // Insertar después del header
            const header = document.querySelector('.header');
            header.parentNode.insertBefore(messageDiv, header.nextSibling);
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.remove();
                }
            }, 5000);
        }
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            // Prevenir envío del formulario con Enter
            document.getElementById('autorizacion-form').addEventListener('submit', function(e) {
                e.preventDefault();
                submitAutorizacion();
            });
        });
    </script>
</body>
</html>
