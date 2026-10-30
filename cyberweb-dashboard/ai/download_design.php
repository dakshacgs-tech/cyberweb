<?php
declare(strict_types=1);
require_once __DIR__.'/../auth/require_login.php';
require_once __DIR__.'/design_functions.php';
$pid=filter_input(INPUT_GET,'project_id',FILTER_VALIDATE_INT);$did=filter_input(INPUT_GET,'design_id',FILTER_VALIDATE_INT);
if (!$pid||!$did){http_response_code(400);exit('Invalid design');}
$s=$pdo->prepare("SELECT d.design_json,d.design_number FROM ai_generated_designs d JOIN ai_generation_jobs j ON j.id=d.job_id AND j.status='completed' WHERE d.id=? AND d.project_id=? AND d.user_id=?");
$s->execute([$did,$pid,(int)$_SESSION['user_id']]);$record=$s->fetch();if(!$record){http_response_code(404);exit('Design not found');}
$d=json_decode($record['design_json'],true);
if(!is_array($d)){http_response_code(500);exit('Invalid design data');}
if(!class_exists('ZipArchive')){http_response_code(503);exit('Enable PHP zip extension in php.ini');}
$tmp=tempnam(sys_get_temp_dir(),'cw-design-');if(!$tmp){http_response_code(503);exit('Cannot create temporary ZIP');}
$z=new ZipArchive();if($z->open($tmp,ZipArchive::OVERWRITE)!==true){@unlink($tmp);http_response_code(503);exit('Cannot create ZIP');}
foreach(['html'=>'index.html','css'=>'style.css','js'=>'script.js'] as $key=>$filename){$z->addFromString($filename,(string)($d[$key]??''));}
$z->addFromString('README.txt','Cyberweb design '.$record['design_number']."\nGenerated concept; inspect and test before deploying.\n");
if(!$z->close() || !is_file($tmp)){@unlink($tmp);http_response_code(503);exit('ZIP generation failed');}
try{ai_record_download($pdo,(int)$_SESSION['user_id'],$pid,$did);}catch(Throwable $e){@unlink($tmp);error_log($e->getMessage());http_response_code(503);exit('Download logging failed');}
header('Content-Type: application/zip');header('Content-Disposition: attachment; filename="cyberweb-design-'.$did.'.zip"');header('Content-Length: '.filesize($tmp));header('Cache-Control: no-store');readfile($tmp);@unlink($tmp);
