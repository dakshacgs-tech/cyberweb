<?php
declare(strict_types=1);
/** PHP-only orchestration. An authenticated external MCP host must execute requests. */
function ai_prompt(): string {
    $text = file_get_contents(__DIR__ . '/system_prompt.txt');
    if ($text === false) throw new RuntimeException('AI system prompt not found');
    return $text;
}
function ai_input(array $project, array $submitted): array {
    // Only explicitly supplied data. Logo URI must be a server-managed asset reference.
    $allowed = ['hotel_name','description','address','phone','email','website','amenities','rooms',
      'facilities','policies','faq','gallery','logo_asset','logo_palette','booking_url'];
    $facts = [];
    foreach ($allowed as $key) {
        if (!array_key_exists($key,$submitted)) continue;
        $value = $submitted[$key];
        if (!is_string($value) && !is_array($value)) continue;
        $facts[$key] = $value;
    }
    return ['project_id'=>(int)$project['id'], 'project_name'=>(string)$project['name'],
        'property_name'=>(string)$project['property_name'], 'location'=>(string)$project['location'],
        'submitted_facts'=>$facts];
}
function ai_start_job(PDO $pdo, int $userId, int $projectId, array $submitted): array {
    $stmt=$pdo->prepare('SELECT id,name,property_name,location FROM projects WHERE id=? AND user_id=?');
    $stmt->execute([$projectId,$userId]); $project=$stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project) throw new DomainException('Project not found or access denied');
    $input=ai_input($project,$submitted);
    if (empty($input['submitted_facts'])) throw new InvalidArgumentException('Submit hotel details before generating designs');
    $requestId=bin2hex(random_bytes(16)); $prompt=ai_prompt();
    $stmt=$pdo->prepare('INSERT INTO ai_generation_jobs (project_id,user_id,request_id,input_json,system_prompt) VALUES (?,?,?,?,?)');
    $stmt->execute([$projectId,$userId,$requestId,json_encode($input,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),$prompt]);
    return ['request_id'=>$requestId,'status'=>'pending','instructions'=>$prompt,'input'=>$input];
}
function ai_complete_job(PDO $pdo, string $requestId, array $result, int $userId): void {
    // Call ONLY from a trusted authenticated MCP bridge, never directly from browser POST.
    $designs=$result['designs']??null;
    if (!is_array($designs) || count($designs)!==4) throw new InvalidArgumentException('Exactly four designs required');
    $numbers=[];
    foreach ($designs as $d) {
        if (!is_array($d) || !isset($d['design_number'],$d['style_name'],$d['palette'],$d['sections'],$d['html'],$d['css'],$d['js']) ||
            !in_array($d['design_number'],[1,2,3,4],true) || !is_string($d['style_name']) ||
            !is_array($d['palette']) || !is_array($d['sections']) || !is_string($d['html']) || !is_string($d['css']) || !is_string($d['js']))
            throw new InvalidArgumentException('Invalid design payload');
        $numbers[]=$d['design_number'];
    }
    sort($numbers); if ($numbers!==[1,2,3,4]) throw new InvalidArgumentException('Design numbers must be unique 1-4');
    $pdo->beginTransaction();
    try {
        $s=$pdo->prepare('SELECT * FROM ai_generation_jobs WHERE request_id=? AND user_id=? FOR UPDATE');
        $s->execute([$requestId,$userId]); $job=$s->fetch(PDO::FETCH_ASSOC);
        if (!$job || $job['status']!=='pending') throw new DomainException('Pending job not found');
        $insert=$pdo->prepare('INSERT INTO ai_generated_designs (job_id,project_id,user_id,design_number,style_name,design_json) VALUES (?,?,?,?,?,?)');
        foreach ($designs as $d) {
            $insert->execute([(int)$job['id'],(int)$job['project_id'],$userId,(int)$d['design_number'],mb_substr($d['style_name'],0,60),json_encode($d,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
        }
        $pdo->prepare("UPDATE ai_generation_jobs SET status='completed',completed_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$job['id']]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}
function ai_select_design(PDO $pdo, int $userId, int $projectId, int $designId): void {
    $pdo->beginTransaction();
    try {
        $own=$pdo->prepare('SELECT id FROM projects WHERE id=? AND user_id=? FOR UPDATE');
        $own->execute([$projectId,$userId]);
        if (!$own->fetchColumn()) throw new DomainException('Project not found');
        $s=$pdo->prepare("SELECT d.id,d.style_name FROM ai_generated_designs d JOIN ai_generation_jobs j ON j.id=d.job_id WHERE d.id=? AND d.project_id=? AND d.user_id=? AND j.status='completed'");
        $s->execute([$designId,$projectId,$userId]); $design=$s->fetch(PDO::FETCH_ASSOC);
        if (!$design) throw new DomainException('Design not found');
        $pdo->prepare('UPDATE ai_generated_designs SET selected_at=NULL WHERE project_id=? AND user_id=?')->execute([$projectId,$userId]);
        $pdo->prepare('UPDATE ai_generated_designs SET selected_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$designId]);
        $pdo->prepare("UPDATE projects SET status='in_progress' WHERE id=? AND user_id=?")->execute([$projectId,$userId]);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
}
function ai_record_download(PDO $pdo,int $userId,int $projectId,int $designId): void {
    // Call after ZIP generation succeeds and just before a successful file response.
    $s=$pdo->prepare('SELECT id FROM ai_generated_designs WHERE id=? AND project_id=? AND user_id=?');
    $s->execute([$designId,$projectId,$userId]);
    if (!$s->fetchColumn()) throw new DomainException('Design not found');
    $s=$pdo->prepare('INSERT INTO ai_design_downloads (design_id,project_id,user_id) VALUES (?,?,?)');
    $s->execute([$designId,$projectId,$userId]);
}
