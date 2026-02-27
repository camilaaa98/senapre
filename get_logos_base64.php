<?php
$sena = 'assets/img/logosena.png';
$asi = 'assets/img/asi.png';

function getBase64($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return null;
}

echo "SENA_LOGO=" . getBase64($sena) . "\n\n";
echo "ASI_LOGO=" . getBase64($asi) . "\n";
?>

