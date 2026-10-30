<?php
declare(strict_types=1);
require_once __DIR__.'/../auth/require_login.php';
require_once __DIR__.'/design_functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD']!=='POST' || !valid_csrf()) {http_response_code(403);echo json_encode(['error'=>'Invalid request']);exit;}
try {
 $projectId=filter_var($_POST['project_id']??null,FILTER_VALIDATE_INT);
 $facts=json_decode((string)($_POST['hotel_information_json']??''),true,64,JSON_THROW_ON_ERROR);
 if (!$projectId || !is_array($facts)) throw new InvalidArgumentException('Invalid hotel data');
 $request=ai_start_job($pdo,(int)$_SESSION['user_id'],$projectId,$facts);
 // IMPORTANT: This returns a pending request; no AI generation has occurred.
 echo json_encode(['request_id'=>$request['request_id'],'status'=>'pending'],JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException|JsonException $e) {http_response_code(422);echo json_encode(['error'=>$e->getMessage()]);}
catch (DomainException $e){http_response_code(404);echo json_encode(['error'=>$e->getMessage()]);}
catch (Throwable $e){error_log($e->getMessage());http_response_code(500);echo json_encode(['error'=>'Unable to queue design request']);}
