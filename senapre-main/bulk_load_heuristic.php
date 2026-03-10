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
GESTIÓN CONTABLE Y FINANCIERA	3388626	DIURNA	9/12/2027
GESTIÓN CONTABLE Y FINANCIERA (OLGA )	3388627	DIURNA	9/12/2027
GESTIÓN CONTABLE Y FINANCIERA (MARY LUZ ) no	3388628	DIURNA	9/12/2027
ANDREINA MORALES FERNANDEZ 	1010021128	morelesfernandez15@gmail.com	3005663986	GESTIÓN CONTABLE Y FINANCIERA 	3388629	DIURNA	9/12/2027	YERLY RODIRGUEZ RINCON	1022334170	yerlyrodrigueze@gmail.com	3222576150
EOD;

try {
    $conn = Database::getInstance()->getConnection();
    $conn->beginTransaction();

    $lines = explode("\n", $data);

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, 'NOMBRE Y APELLIDOS') !== false || strpos($line, 'CEDULA DE CIUDADANÍA') !== false) continue;

        // Extraer todos los números de 7+ dígitos
        preg_match_all('/\b\d{7,10}\b/', $line, $numbers);
        $numbers = $numbers[0];

        // Extraer correos
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $line, $emails);
        $emails = $emails[0];

        // Buscar Ficha (7 dígitos)
        $ficha = null;
        foreach ($numbers as $n) {
            if (strlen($n) == 7 && $n >= 3000000) {
                $ficha = $n;
                break;
            }
        }
        if (!$ficha) continue;

        // Buscar Cédulas (8-10 dígitos)
        $ccP = null;
        $ccS = null;
        foreach ($numbers as $n) {
            if ($n != $ficha && (strlen($n) >= 8 && strlen($n) <= 10)) {
                if (!$ccP) $ccP = $n;
                else if (!$ccS) $ccS = $n;
            }
        }

        // Buscar Nombres y Programa
        // Dividir por delimitadores grandes para separar bloques de texto
        $textParts = preg_split("/\t+|\s{2,}/", $line, -1, PREG_SPLIT_NO_EMPTY);
        $textParts = array_map('trim', $textParts);
        
        $nombreP = "";
        $prog = "POR DEFINIR";
        $jorn = "LECTIVA";
        $voceroS = "";

        // Heurística de asignación
        foreach ($textParts as $tp) {
            if (preg_match('/[a-zA-Z]{5,}/', $tp)) {
                if (strpos($tp, 'GESTIÓN') !== false || strpos($tp, 'SOFTWARE') !== false || strpos($tp, 'SISTEMAS') !== false || strpos($tp, 'PRODUCCIÓN') !== false || strpos($tp, 'SERVICIOS') !== false) {
                    $prog = $tp;
                } else if (strpos($tp, 'DIURNA') !== false || strpos($tp, 'MIXTA') !== false || strpos($tp, 'NOCTURNA') !== false) {
                    $jorn = $tp;
                } else if (empty($nombreP)) {
                    $nombreP = $tp;
                } else if (empty($voceroS) && $tp != $prog && $tp != $jorn) {
                    $voceroS = $tp;
                }
            }
        }

        // Inserción de Programa
        $conn->prepare("INSERT OR IGNORE INTO programas_formacion (nombre_programa, nivel_formacion) VALUES (?, 'POR DEFINIR')")->execute([$prog]);

        // Inserción de Ficha
        $conn->prepare("INSERT OR REPLACE INTO fichas (numero_ficha, nombre_programa, jornada, estado) VALUES (?, ?, ?, 'LECTIVA')")->execute([$ficha, $prog, $jorn]);

        // Inserción de Aprendiz Principal
        if ($ccP && !empty($nombreP)) {
            $names = explode(' ', $nombreP, 2);
            $conn->prepare("INSERT OR REPLACE INTO aprendices (documento, tipo_identificacion, nombre, apellido, correo, celular, numero_ficha, estado) VALUES (?, 'CC', ?, ?, ?, ?, ?, 'LECTIVA')")
                 ->execute([$ccP, $names[0] ?? '', $names[1] ?? '', $emails[0] ?? null, null, $ficha]);
            
            $conn->prepare("UPDATE fichas SET vocero_principal = ? WHERE numero_ficha = ?")->execute([$ccP, $ficha]);
        }

        // Inserción de Aprendiz Suplente
        if ($ccS && !empty($voceroS) && strpos(strtolower($voceroS), 'no eligieron') === false) {
            $namesS = explode(' ', $voceroS, 2);
            $conn->prepare("INSERT OR REPLACE INTO aprendices (documento, tipo_identificacion, nombre, apellido, correo, celular, numero_ficha, estado) VALUES (?, 'CC', ?, ?, ?, ?, ?, 'LECTIVA')")
                 ->execute([$ccS, $namesS[0] ?? '', $namesS[1] ?? '', $emails[1] ?? null, null, $ficha]);

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
