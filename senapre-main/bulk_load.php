<?php
require_once 'api/config/Database.php';

$data = <<<'EOD'
3389756	JONH JAIRO LOZANO BURGOS 	17653772	lozanojonh1974@gmail.com	3133943803	GESTIÓN CONTABLE Y FINANCIERA 	DIURNA 	28/09/2027	VICTOR ANDRES CAMACHO RIVERA	1138924034	camacho24a@gmail.com	3160415524
3407730	YULY VALENTINA QUILINDO CAMACHO 	1117498013	valentinaquilindocamacho@gmail.com	3026715770	GESTIÓN CONTABLE Y FINANCIERA 	DIURNA-CERRADA	10/10/2027	DIEGO  SNEYDER BONILLA URQUIJO	1117966964	diegosneyder112@gmail.com	3222838068
3407847	 YULIANA CABRERA SOTO 	1007443580	Valeria232925@gmail.com	3112898176	PRODUCCIÓN GANADERA (CESAR)	DIURNA-CERRADA	6/10/2027	LUIS ALEJANDRO FIGUEROA MORALES 	1118366707	figueroamoralesluisalejandro7@gmail.com	3229341861
3407689	JUAN CAMILO RAMOS GONZALEZ 	1117507910	ramosgonzalezjuan14@gmail.com	3107653637	ANALISIS Y DESARROLLO DE SOFTWARE	VICTOR ALFONSO RAMIREZ MORENO	1117500805	ramirezmorenovictor16@gmail.com	3107613429
3312631	JUAN SEBASTIAN PEREZ OSORIO	1013111049	Perezjs001@gmail.com 	3228887924	GESTIÓN EMPRESARIAL	ANGIE DANIELA RAMIREZ DELGADO 	1118364605	Angiedanielaramirezdelgado7@gmail.com 	3132044352
3398381	 WILLINGTON GALVIS PULIDO	17656319	willingtongalvis09@gmail.com	3212019404	PROMOTOR DE SALUD 	BLANCA IRINE CALDERON 	30505036	bg204301@gmail.com	3118292695
3398384	LYDA SHIRLEY GONZALES ORTIZ 	30509912	gonzalezortizlydashirley@gmail.com	3112320078	PROMOTOR DE SALUD 	WILLER PAPAMIJA URQUINA	17655013	willer0702@gmail.com	
3405403	NANCY TOVAR MANCHOLA 	40778440	nancytovarmanchola@gmail.com	3227113634	PISCICULTURA 	OSWALDO ALEXIS FRANCO  HERNANDEZ 	1117489273	alexisfranco204hernandez@gmail.com	323701478
ARLES FABIAN RAMIREZ 	1006459431	valentinavaron2002@gmail.com	3186303671	SERVICIOS FARMACEUTICOS 	DONCELLO 	3215046	MIXTA -TARDE (CERRADA)	28/04/2026	NO ELIGIERON VOCERO SUPLENTE
MANUEL STEEVEN HERRERA VEGA	1116914713	vegasteeven24@gmail.com	3126518322	CONTABILIZACIÓN DE OPERACIONES COMERCIALES Y FINANCIERAS	DONCELLO 	3313178	MIXTA-TARDE-CERRADA	19/05/2026	DARWIN 			3114963252
YARLEDY SERREZUELA ENDO	1117914507	flakas1208@gmail.com	3208888883	PRODUCCIÓN PECUARIA	DONCELLO 	3172855	MIXTA 	27/01/2026	NO ELIGIERON VOCERO SUPLENTE
YENCY PAREICIA ARIAS BASTOS	1006418333	yencybastos30@gmail.com	3214895011	SISTEMAS TELEINFORMATICOS 	MONONGUETE	3269402	MIXTA- TARDE- CERRADA		BRAYAN STIVEN CALVO MOTTA	1117500957	brayancalvo217@gmail.com 	3227252820
kORY YENARA BONILLA CRUZ	1051068354	korybonilla123@hotmail.com	3219068824	PROGRAMACION DE SOFWARE 	3069934	DIURNA	27/7/2025
TITO HUGO MONTIEL LOMBONA	1098637995	ska164@autlook.es	3124775889	PROGRAMACION DE SOfTWARE 	3336020	DIURNA	7/10/2026	HANNA SOFIA QUIROGA BUENAVENTURA	1077230988	olgabuenaventura93@gmail.com	3132146706
ANDREINA MORALES FERNANDEZ 	1010021128	morelesfernandez15@gmail.com	3005663986	GESTIÓN CONTABLE Y FINANCIERA 	3388629	DIURNA	9/12/2027	YERLY RODIRGUEZ RINCON	1022334170	yerlyrodrigueze@gmail.com	3222576150
EOD;

try {
    $conn = Database::getInstance()->getConnection();
    $conn->beginTransaction();

    $lines = explode("\n", $data);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, 'NOMBRE Y APELLIDOS') !== false) continue;

        // Dividir por TAB o por 2 o más espacios, ignorando vacíos
        $parts = preg_split("/\t+|\s{2,}/", $line, -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) < 2) continue;

        // Limpiar partes
        $parts = array_map(function($p) { return trim(str_replace('"', '', $p)); }, $parts);

        // Buscar la ficha (número de exactamente 7 dígitos o el rango conocido de SENA)
        $ficha = null;
        $fi = -1;
        foreach ($parts as $idx => $p) {
            $cleaned = preg_replace('/[^0-9]/', '', $p);
            if (is_numeric($cleaned) && strlen($cleaned) == 7) {
                $ficha = $cleaned;
                $fi = $idx;
                break;
            }
        }
        if (!$ficha) continue;

        // Si la ficha es el primer elemento, es Formato A
        if ($fi === 0) {
            // Ficha(0), Nombre(1), CC(2), Correo(3), Tel(4), Prog(5), Jorn(6), ...
            $nombreP = $parts[1] ?? '';
            $ccP = preg_replace('/[^0-9]/', '', $parts[2] ?? '');
            $correoP = $parts[3] ?? '';
            $celP = preg_replace('/[^0-9]/', '', $parts[4] ?? '');
            $prog = $parts[5] ?? 'POR DEFINIR';
            $jorn = $parts[6] ?? 'LECTIVA';
            $voceroS = $parts[8] ?? null;
            $ccS = isset($parts[9]) ? preg_replace('/[^0-9]/', '', $parts[9]) : null;
            $correoS = $parts[10] ?? null;
            $celS = isset($parts[11]) ? preg_replace('/[^0-9]/', '', $parts[11]) : null;
        } else {
            // Formato B: Nombre, CC, Correo, Cel, Prog, [Ficha], Jorn, ...
            $nombreP = ($fi > 0) ? $parts[0] : '';
            $ccP = ($fi > 1) ? preg_replace('/[^0-9]/', '', $parts[1]) : '';
            $correoP = ($fi > 2) ? $parts[2] : '';
            $celP = ($fi > 3) ? preg_replace('/[^0-9]/', '', $parts[3]) : '';
            $prog = ($fi > 4) ? $parts[4] : '';
            
            // Si el nombre parece ser un programa, ajustar
            if (empty($prog) && $fi > 0) $prog = $parts[0]; 

            $jorn = $parts[$fi+1] ?? 'LECTIVA';
            $voceroS = $parts[$fi+3] ?? null;
            $ccS = isset($parts[$fi+4]) ? preg_replace('/[^0-9]/', '', $parts[$fi+4]) : null;
            $correoS = $parts[$fi+5] ?? null;
            $celS = isset($parts[$fi+6]) ? preg_replace('/[^0-9]/', '', $parts[$fi+6]) : null;
        }

        // Inserción de Programa (PK: nombre_programa)
        $conn->prepare("INSERT OR IGNORE INTO programas_formacion (nombre_programa, nivel_formacion) VALUES (?, 'POR DEFINIR')")->execute([$prog]);

        // Inserción de Ficha
        $conn->prepare("INSERT OR REPLACE INTO fichas (numero_ficha, nombre_programa, jornada, estado) VALUES (?, ?, ?, 'LECTIVA')")->execute([$ficha, $prog, $jorn]);

        // Inserción de Aprendiz Principal
        if ($ccP) {
            $names = explode(' ', $nombreP, 2);
            $conn->prepare("INSERT OR REPLACE INTO aprendices (documento, tipo_identificacion, nombre, apellido, correo, celular, numero_ficha, estado) VALUES (?, 'CC', ?, ?, ?, ?, ?, 'LECTIVA')")
                 ->execute([$ccP, $names[0] ?? '', $names[1] ?? '', $correoP, $celP, $ficha]);
            
            $conn->prepare("UPDATE fichas SET vocero_principal = ? WHERE numero_ficha = ?")->execute([$ccP, $ficha]);
        }

        // Inserción de Aprendiz Suplente
        if ($ccS && $voceroS && strpos(strtolower($voceroS), 'no eligieron') === false) {
            $namesS = explode(' ', $voceroS, 2);
            $conn->prepare("INSERT OR REPLACE INTO aprendices (documento, tipo_identificacion, nombre, apellido, correo, celular, numero_ficha, estado) VALUES (?, 'CC', ?, ?, ?, ?, ?, 'LECTIVA')")
                 ->execute([$ccS, $namesS[0] ?? '', $namesS[1] ?? '', $correoS, $celS, $ficha]);

            $conn->prepare("UPDATE fichas SET vocero_suplente = ? WHERE numero_ficha = ?")->execute([$ccS, $ficha]);
        }

        echo "OK: Ficha $ficha - Programa: $prog\n";
    }

    $conn->commit();
    echo "\n=== PROCESO FINALIZADO ===\n";

} catch (Exception $e) {
    if (isset($conn)) $conn->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
