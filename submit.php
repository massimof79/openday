<?php
// submit.php
// Riceve POST con: nome, cognome, data_nascita, scuola, consenso
// Salva in CSV con intestazione (se file nuovo). Utilizza fputcsv per corretta escaping.

header('Content-Type: text/html; charset=utf-8');

// --- configurazione ---
$dir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$file = $dir . DIRECTORY_SEPARATOR . 'registrazioni_open_day.csv';

if(!is_dir($dir)){
    if(!mkdir($dir, 0750, true)){
        die('Impossibile creare la cartella dati sul server.');
    }
}

function sanitize_text($s){
    $s = trim($s);
    $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s);
    return $s;
}

$nome = isset($_POST['nome']) ? sanitize_text($_POST['nome']) : '';
$cognome = isset($_POST['cognome']) ? sanitize_text($_POST['cognome']) : '';
$data_nascita = isset($_POST['data_nascita']) ? sanitize_text($_POST['data_nascita']) : '';
$scuola = isset($_POST['scuola']) ? sanitize_text($_POST['scuola']) : '';
$consenso = isset($_POST['consenso']) && ($_POST['consenso'] === '1' || $_POST['consenso'] === 'on') ? '1' : '0';

$errors = [];
if($nome === '') $errors[] = 'Nome mancante.';
if($cognome === '') $errors[] = 'Cognome mancante.';
if($data_nascita === '') $errors[] = 'Data di nascita mancante.';
else{
    $d = DateTime::createFromFormat('Y-m-d', $data_nascita);
    if(!$d || $d->format('Y-m-d') !== $data_nascita) $errors[] = 'Formato data non valido. Usa AAAA-MM-GG.';
    else if($d > new DateTime()) $errors[] = 'Data di nascita non può essere futura.';
}
if($scuola === '') $errors[] = 'Scuola di provenienza mancante.';
if($consenso !== '1') $errors[] = 'Consenso al trattamento dei dati richiesto.';

if(count($errors) > 0){
    echo '<p>Errore: ' . implode(' ', $errors) . '</p>';
    exit;
}

$row = [
    (new DateTime())->format(DateTime::ATOM),
    $nome,
    $cognome,
    $data_nascita,
    $scuola,
    $consenso
];

$handle = fopen($file, 'a');
if(!$handle){
    die('Impossibile aprire il file dati sul server.');
}

if(filesize($file) === 0){
    $header = ['inserimento_iso8601','nome','cognome','data_nascita','scuola','consenso'];
    fputcsv($handle, $header);
}

if(flock($handle, LOCK_EX)){
    fputcsv($handle, $row);
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    echo '<p>Registrazione salvata con successo.</p>';
    exit;
} else {
    fclose($handle);
    echo '<p>Impossibile ottenere lock sul file. Riprovare.</p>';
    exit;
}

