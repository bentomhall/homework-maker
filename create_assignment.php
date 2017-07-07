<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
define ('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

require_once "repository.php";

function sendHttpResponse(int $code, string $message = "") {
    http_response_code($code);
    if ($message != "") {
        echo $message;
    }
}

class AssignmentOutput {
    protected $title;
    protected $escaped_title;
    protected $numQuestions = 1;
    protected $questionTitles = array();
    protected $fileContents = array();
    protected $supporting_files = Array("correct_16.png", "incorrect_16.png", "main.css", "bootstrap.min.css");
    protected $resourceDirectory = DOCUMENT_ROOT . '/Resources/';
    protected $outputDirectory = DOCUMENT_ROOT . '/downloads/';
    public function __construct(string $title) {
        $this->title = $title;
        $this->escaped_title = str_replace(' ', '', $title); //for filenames
    }
    
    public function addFile(string $name, string $contents) {
        $this->fileContents[$name] = $contents;
        return;
    }
    
    private function updateJs($UUID){
        $contents = "var activeQuestions = ".($this->numQuestions).";\n";
        $contents .= "var assignmentSeed = \"".$UUID."\";\n";
        $contents .= file_get_contents($this->resourceDirectory . "validation.js");
        $this->addFile('validation.js', $contents);
    }
    
    public function addIndex(){
        $template = new Template(DOCUMENT_ROOT."/templates/index.tmpl");
        $template->set("title", $this->title);
        $template->set("questions", get_question_templates($this->questionTitles));
        $template->set("r", rand(1000,9999));
        $filename = "index.html";
        $this->addFile($filename, $template->output());
    }
    
    public function addQuestion($question_data){
        if ($question_data["type"] == "multiple-choice"){
            $template = new Template(DOCUMENT_ROOT."/templates/multiple_choice.tmpl");
            $template->set("prompts", $this->buildPrompts($question_data["prompts"], false));
        } 
        else if ($question_data["type"] == "multiple-selection") {
            $template = new Template(DOCUMENT_ROOT."/templates/multiple_selection.tmpl");
            $template->set("prompts", $this->buildPrompts($question_data["prompts"], true));
        }
        else {
            $template = new Template(DOCUMENT_ROOT."/templates/question.tmpl");
        }
        $template->set("id", $this->numQuestions);
        foreach ($question_data as $key => $value) {
            if ($key != "prompts") {
                $template->set($key, $value);
            }
        }
        $filename = "question".$this->numQuestions.".html";
        $this->numQuestions += 1;
        $this->addFile($filename, $template->output());
        $this->questionTitles[] = $question_data['title'];
    }
    
    private function addSupportingFiles($UUID){
        foreach ($this->supporting_files as $file) {
            $contents = file_get_contents($this->resourceDirectory . $file);
            $this->addFile($file, $contents);
        }
        $this->updateJs($UUID);
    }
    
    public function addImage(string $name, $data) {
        $payload = base64_decode(explode(',', $data)[1]);
        $this->addFile($name, $payload);
    }
    
    public function createZip(string $UUID){
        $zip = new ZipArchive();
        $filename = $this->outputDirectory . $this->escaped_title.".zip";
        $index = 0;
        while ($zip->open($filename, ZipArchive::CREATE)==ZipArchive::ER_EXISTS) {
            //keep trying to increment the number until it succeeds.
            $filename = $this->outputDirectory . $this->escaped_title.$index.".zip";
            $index += 1;
        }
        $this->addSupportingFiles($UUID);
        foreach ($this->fileContents as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();
        return basename($filename);
    }
    
    private function buildPrompts($data, $isSelection = false){
        $output = "";
        $i = 0;
        foreach ($data as $d){
            if ($isSelection) {
                $output .= '<input type="checkbox" class="checkbox-inline" name="answer-entry" value="'.$i.'"/>'.sanitize($d).'</br >';
            } else {
                $output .= '<input type="radio" class="checkbox-inline" name="answer-entry" value="'.$i.'"/>'.sanitize($d).'</br >';
            }
            $i += 1;
        }
        return $output;
    }
}

class Template {
    protected $file;
    protected $values = array();
    public function __construct($file) {
        $this->file = $file;
    }

    public function set($key, $value) {
        $this->values[$key] = $value;
    }

    public function output() {
        if (!file_exists($this->file)) {
            return "Error Loading File ($this->file).";
        }
        $output = file_get_contents($this->file);
        foreach ($this->values as $key => $value) {
            $tagToReplace = "[@$key]";
            if ($tagToReplace == "[@prompts]" || $tagToReplace == "[@questions]") {
                //these components are built out of already sanitized values but contain raw html themselves that should not be escaped.
                $output = str_replace($tagToReplace, $value, $output);
            } 
            else {
                $output = str_replace($tagToReplace, sanitize($value), $output);
            }
        }
        return $output;
    }

    static public function merge($templates, $separator = "\n") {
        $output = "";
 
        foreach ($templates as $template) {
            $content = (get_class($template) !== "Template")
                ? "Error, incorrect type - expected Template."
                : $template->output();
        $output .= $content . $separator;
    }
 
    return $output;
    }
}

function sanitize($data){
        $sanitized = htmlspecialchars($data, ENT_QUOTES);
        $patterns = Array('/\^(.?)\^/',
                     '/_(.?)_/',
                     '/\[LIST\](.*)\[\/LIST\]/',
                     '/\[B\](.*)\[\/B\]/',
                     '/\[_]/',
                     '/\[IMG\](.*)\[\/IMG\]/');
        $replacements = Array('<sup>${1}</sup>', 
                              '<sub>${1}</sub>', 
                              '<ul>${1}</ul>', 
                              '<b>${1}</b>', 
                              '____________',
                              '<img src="${1}" class="image-md" height="300" alt="${1}">');
        $innerPattern = '/\[\*\](.*?)\[\/\*\]/';
        $innerReplacement = '<li>${1}</li>';
        //do <li> replacement first, then match outer pattern
        $sanitized = preg_replace($innerPattern, $innerReplacement, $sanitized);
        $sanitized = preg_replace($patterns, $replacements, $sanitized);
        return $sanitized;
    }

function get_question_templates(Array $items){
    $i = 1;
    $templates = Array();
    foreach ($items as $item){
        $t = new Template(DOCUMENT_ROOT."/templates/question_list.tmpl");
        $t->set("url", "question$i.html");
        $t->set("q", "$item");
        $templates[] = $t;
        $i += 1;
    }
    return Template::merge($templates);
}

function getGUID(){
    if (function_exists('com_create_guid')){
        return trim(com_create_guid(), '{}');
    }else{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(bin2hex(openssl_random_pseudo_bytes(16)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }
}

function hasValueForKey($array, $key){
    return isset($array[$key]);
}

function validateJson($json){
    $isValidJson = true;
    $responseText = "";
    if (!isset($json['title'])) {
        $responseText = '<p class="invalid">Must supply title</p>';
        $isValidJson = false;
    }
    elseif (!preg_match('/^[A-Za-z].*/', $json['title'])) {
        error_log($json['title']);
        $responseText = '<p class="invalid">Invalid assignment title. Titles must begin with an alphabetic character</p>';
        $isValidJson = false;
    }   
    elseif (!(isset($json["questions"]) && count($json["questions"]))) {
        $responseText = '<p class="invalid">Must supply questions</p>';
        $isValidJson = false;
    }
    foreach ($json["questions"] as $q){
        if (!isset($q["title"]) || !isset($q["text"]) || !isset($q["answer"]) || !isset($q["hint"])){
            $responseText = '<p class="invalid">Question specification invalid for question with title '.htmlspecialchars($q["title"]).'</p>';
            $isValidJson = false;
        }
        else if ($q["type"] == "multiple-choice" && count($q["prompts"]) == 0){
            $responseText = '<p class="invalid">Multiple choice question titled "'.$q['title']. '" must have at least one prompt</p>';
            $isValidJson = false;
        }
    }
    if (!$isValidJson) {
        sendHttpResponse(400, $responseText);
    }
    return $isValidJson;
        
}

function saveAssignment(Repository $repo, $title, $subject, $uuid) {
    $subjectIDs = $repo->getSubjectCodes();
    if (key_exists($subject, $subjectIDs)) {
        if (!($repo->saveAssignment($title, $uuid, $subjectIDs[$subject]))) {
            sendHttpResponse(500, "Internal Server Error");
        }
    } else {
        sendHttpResponse(400, "Invalid request body--subject not found");
    }
}

$post_data = file_get_contents("php://input");
$data = json_decode($post_data,true);
if (!validateJson($data)) {
    exit(1);
}
$title = $data["title"];
$outputData = new AssignmentOutput($title);
foreach ($data["questions"] as $qdata){
    $outputData->addQuestion($qdata);
}
foreach ($data['images'] as $name => $image) {
    $outputData->addImage($name, $image);
}
$outputData->addIndex();
$uuid = getGUID();
$credentials = getCredentials();
$repo = new Repository($credentials);
try {
    saveAssignment($repo, $title, $data["subject"], $uuid);
} catch (Exception $ex) {
    sendHttpResponse(400, "Your assignment was not saved: ".$ex->getMessage());
    exit(1);
}

$output = $outputData->createZip($uuid);
$downloadTemplate = new Template(DOCUMENT_ROOT."/templates/download.tmpl");
$downloadTemplate->set("url", "downloads/download.php?name=$output");
sendHttpResponse(200, $downloadTemplate->output());
$date = date('M/d/Y h:i');
file_put_contents('usage_log.log', "Processed $title on $date", FILE_APPEND);
