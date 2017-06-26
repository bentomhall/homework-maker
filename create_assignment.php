<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
define ('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

require_once "repository.php";

function Respond(int $code, string $message = "") {
    http_response_code($code);
    if ($message != "") {
        echo $message;
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
                     '/\[_]/');
        $replacements = Array('<sup>${1}</sup>', '<sub>${1}</sub>', '<ul>${1}</ul>', '<b>${1}</b>', '____________');
        $inner_pattern = '/\[\*\](.*?)\[\/\*\]/';
        $inner_replacement = '<li>${1}</li>';
        //do <li> replacement first, then match outer pattern
        $sanitized = preg_replace($inner_pattern, $inner_replacement, $sanitized);
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

function build_prompts($data){
    $output = "";
    $i = 0;
    foreach ($data as $d){
        $output .= '<input type="radio" name="answer-entry" value="'.$i.'"/>'.sanitize($d).'</br >';
        $i += 1;
    }
    return $output;
}

function build_index($title, $question_titles){
    $template = new Template(DOCUMENT_ROOT."/templates/index.tmpl");
    $template->set("title", $title);
    $template->set("questions", get_question_templates($question_titles));
    $template->set("r", rand(1000,9999));
    $filename = "index.html";
    file_put_contents($filename, $template->output());
}

function build_question($question_data, $question_number){
    if ($question_data["type"] == "multiple-choice"){
        $template = new Template(DOCUMENT_ROOT."/templates/multiple_choice.tmpl");
        $template->set("prompts", build_prompts($question_data["prompts"]));
    }
    else {
        $template = new Template(DOCUMENT_ROOT."/templates/question.tmpl");
    }
    $template->set("id", $question_number);
    foreach ($question_data as $key => $value) {
        if ($key != "prompts") {
            $template->set($key, $value);
        }
    }
    $filename = "question".$question_number.".html";
    file_put_contents($filename, $template->output());
    return $question_data["title"];
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

function update_js($file, $number_of_questions, $UUID){
    $contents = "var activeQuestions = ".($number_of_questions-1).";\n";
    $contents .= "var assignmentSeed = ".$UUID.";\n";
    $contents .= file_get_contents($file);
    file_put_contents($file, $contents);
}

function copy_supporting_files($number_of_questions, $UUID){
    $supporting_files = Array("correct.png", "correct_16.png", "incorrect.png", "incorrect_16.png", "validation.js", "main.css", "bootstrap.min.css");
    foreach ($supporting_files as $file){
        copy(DOCUMENT_ROOT."/Resources/$file", "$file");
    }
    update_js("validation.js", $number_of_questions, $UUID);
}

function create_zip($title, $number_of_questions, $images){
    $zip = new ZipArchive();
    $supporting_files = Array("correct.png", "correct_16.png", "incorrect.png", "incorrect_16.png", "validation.js", "main.css", "index.html");
    for ($i = 1; $i < $number_of_questions; $i++) {
        $supporting_files[] = "question$i.html";
    }
    $filename = preg_replace('/tmp$/', '', $title).".zip";

    if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
        exit("cannot open <$filename>\n");
    }
    foreach ($supporting_files as $file){
        $zip->addFile($file, "$file");
    }
    if (count($images) > 0) {
        foreach ($images as $image => $data){
            $payload = explode(',', $data)[1];
            $zip->addFromString("$image", base64_decode($payload));
        }
    }
    $zip->close();
    copy($filename, DOCUMENT_ROOT."/downloads/$filename");
    foreach ($supporting_files as $file) {
        unlink($file);
    }
    unlink($filename);
    return basename($filename);
}

function has_value_for_key($array, $key){
    return isset($array[$key]);
}

function validate_json($json){
    $is_valid = true;
    $response_text = "";
    if (!isset($json['title'])) {
        $response_text = '<p class="invalid">Must supply title</p>';
        $is_valid = false;
    }
    elseif (!preg_match('/^[A-Za-z].*/', $json['title'])) {
        error_log($json['title']);
        $response_text = '<p class="invalid">Invalid assignment title. Titles must begin with an alphabetic character</p>';
        $is_valid = false;
    }   
    elseif (!(isset($json["questions"]) && count($json["questions"]))) {
        $response_text = '<p class="invalid">Must supply questions</p>';
        $is_valid = false;
    }
    foreach ($json["questions"] as $q){
        if (!isset($q["title"]) || !isset($q["text"]) || !isset($q["answer"]) || !isset($q["hint"])){
            $response_text = '<p class="invalid">Question specification invalid for question with title '.htmlspecialchars($q["title"]).'</p>';
            $is_valid = false;
        }
        else if ($q["type"] == "multiple-choice" && count($q["prompts"]) == 0){
            $response_text = '<p class="invalid">Multiple choice question titled "'.$q['title']. '" must have at least one prompt</p>';
            $is_valid = false;
        }
    }
    if (!$is_valid) {
        Respond(400, $response_text);
    }
    return $is_valid;
        
}

function saveAssignment(Repository $repo, $title, $subject, $uuid) {
    $subjectIDs = $repo->getSubjectCodes();
    if (key_exists($subject, $subjectIDs)) {
        if (!($repo->saveAssignment($title, $uuid, $subjectIDs[$subject]))) {
            Respond(500, "Internal Server Error");
        }
    } else {
        Respond(400, "Invalid request body--subject not found");
    }
}

$post_data = file_get_contents("php://input");
$data = json_decode($post_data,true);
if (!validate_json($data)) {
    exit(1);
}
$title = $data["title"];
$escaped_title = str_replace(' ', '', $title)."tmp"; //stupid spaces
if (!file_exists($escaped_title)){
    mkdir($escaped_title, 0744, true);
}
chdir($escaped_title);
$titles = Array();
$i = 1;
foreach ($data["questions"] as $qdata){
    build_question($qdata, $i);
    $i += 1;
    $titles[] = $qdata["title"];
}
$images = $data['images'];
build_index($title, $titles);
$uuid = getGUID();
$credentials = getCredentials();
$repo = new Repository($credentials);
try {
    saveAssignment($repo, $title, $data["subject"], $uuid);
} catch (Exception $ex) {
    http_response_code(400);
    echo "Your assignment was not saved: ".$ex->getMessage();
    die();
}

copy_supporting_files($i, $uuid);
$output = create_zip($escaped_title, $i, $images);
$downloadTemplate = new Template(DOCUMENT_ROOT."/templates/download.tmpl");
$downloadTemplate->set("url", "downloads/download.php?name=$output");
echo $downloadTemplate->output();
$date = date('M/d/Y h:i');
file_put_contents('usage_log.log', "Processed $title on $date", FILE_APPEND);
