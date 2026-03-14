<?php
$json = file_get_contents('tmp_out.json');
$data = json_decode($json, true);
// print only voceros principales info
if (isset($data['data'])) {
    foreach($data['data'] as $l) {
        if (strpos($l['tipo'], 'Vocero Principal') !== false) {
            print_r($l);
        }
    }
}
?>
