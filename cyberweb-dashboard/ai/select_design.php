<?php
declare(strict_types=1);
require_once __DIR__.'/../auth/require_login.php';
require_once __DIR__.'/design_functions.php';
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD']!=='POST' || !valid_csrf()){http_response_code(403);echo json_encode(['error'=>'Invalid request']);exit;}
try {
 $p=filter_var($_POST['project_id']??null,FILTER_VALIDATE_INT);
 $d=filter_var($_POST['design_id']??null,FILTER_VALIDATE_INT);
 if (!$p || !$d) throw new InvalidArgumentException('Invalid selection');
 ai_select_design($pdo,(int)$_SESSION['user_id'],$p,$d);
 echo json_encode(['selected'=>true]);
} catch (DomainException $e){http_response_code(404);echo json_encode(['error'=>$e->getMessage()]);}
catch (Throwable $e){error_log($e->getMessage());http_response_code(500);echo json_encode(['error'=>'Unable to select design']);}
