<?php
// GYRE global high score — tiny file-backed endpoint.
// GET  -> {"score":N,"name":"..."}
// POST score,name -> updates only if higher; returns the current record + "updated":bool
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Cache-Control: no-store');

$file = __DIR__ . '/highscore.json';

function current_hs($file){
  if(!is_file($file)) return array('score'=>0, 'name'=>'—');
  $d = json_decode(@file_get_contents($file), true);
  if(!is_array($d) || !isset($d['score'])) return array('score'=>0, 'name'=>'—');
  return array('score'=>intval($d['score']), 'name'=> isset($d['name']) ? (string)$d['name'] : '—');
}

$method = $_SERVER['REQUEST_METHOD'];
if($method === 'OPTIONS'){ http_response_code(204); exit; }

if($method === 'POST'){
  $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
  $name  = isset($_POST['name'])  ? (string)$_POST['name'] : '';
  // sanitize name: letters/numbers/space/basic punctuation, single spaces, max 12 chars
  $name = preg_replace('/[^A-Za-z0-9 _.\-]/', '', $name);
  $name = trim(preg_replace('/\s+/', ' ', $name));
  if(strlen($name) > 12) $name = substr($name, 0, 12);
  if($name === '') $name = 'ANON';
  if($score < 0) $score = 0;
  if($score > 100000) $score = 100000; // sane cap against garbage submissions

  $fp = @fopen($file, 'c+');
  if($fp){
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $d = json_decode($raw, true);
    $cur     = (is_array($d) && isset($d['score'])) ? intval($d['score']) : 0;
    $curName = (is_array($d) && isset($d['name']))  ? (string)$d['name']  : '—';
    if($score > $cur){
      rewind($fp); ftruncate($fp, 0);
      fwrite($fp, json_encode(array('score'=>$score, 'name'=>$name, 'ts'=>time())));
      fflush($fp);
      $out = array('score'=>$score, 'name'=>$name, 'updated'=>true);
    } else {
      $out = array('score'=>$cur, 'name'=>$curName, 'updated'=>false);
    }
    flock($fp, LOCK_UN); fclose($fp);
    echo json_encode($out); exit;
  }
  $hs = current_hs($file);
  echo json_encode(array('score'=>$hs['score'], 'name'=>$hs['name'], 'updated'=>false, 'error'=>'store')); exit;
}

echo json_encode(current_hs($file));
