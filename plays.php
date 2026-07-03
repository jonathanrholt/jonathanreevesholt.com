<?php
// GYRE global play counter.
// GET  -> {"plays":N}
// POST -> increments by 1, returns {"plays":N}
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Cache-Control: no-store');

$file = __DIR__ . '/plays.json';

function read_plays($file){
  if(!is_file($file)) return 0;
  $d = json_decode(@file_get_contents($file), true);
  return (is_array($d) && isset($d['plays'])) ? intval($d['plays']) : 0;
}

$method = $_SERVER['REQUEST_METHOD'];
if($method === 'OPTIONS'){ http_response_code(204); exit; }

if($method === 'POST'){
  $fp = @fopen($file, 'c+');
  if($fp){
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $d = json_decode($raw, true);
    $n = (is_array($d) && isset($d['plays'])) ? intval($d['plays']) : 0;
    $n++;
    rewind($fp); ftruncate($fp, 0);
    fwrite($fp, json_encode(array('plays'=>$n))); fflush($fp);
    flock($fp, LOCK_UN); fclose($fp);
    echo json_encode(array('plays'=>$n)); exit;
  }
  echo json_encode(array('plays'=>read_plays($file))); exit;
}

echo json_encode(array('plays'=>read_plays($file)));
