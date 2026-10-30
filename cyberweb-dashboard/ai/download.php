<?php
require_once __DIR__.'/../auth/require_login.php';
require_once __DIR__.'/../api/db.php';
$id=filter_input(INPUT_GET,'design_id',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$id) {http_response_code(400);exit('Invalid design ID.');}
$q=$pdo->prepare('SELECT * FROM ai_generated_designs WHERE id=? AND user_id=?');
$q->execute([$id,(int)$_SESSION['user_id']]);$d=$q->fetch(PDO::FETCH_ASSOC);
if(!$d){http_response_code(404);exit('Design not found.');}
$file=__DIR__.'/../storage/ai_zips/'.basename($d['archive_name']);
if(!is_file($file)||!hash_equals($d['sha256'],hash_file('sha256',$file))){http_response_code(404);exit('ZIP unavailable or damaged.');}
$q=$pdo->prepare('INSERT INTO ai_design_downloads(design_id,project_id,user_id) VALUES(?,?,?)');
$q->execute([$id,$d['project_id'],(int)$_SESSION['user_id']]);
header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="hotel-design-'.$d['design_number'].'.zip"');
header('Content-Length: '.filesize($file));header('X-Content-Type-Options: nosniff');
readfile($file);
