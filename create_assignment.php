<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
define ('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
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
            $output = str_replace($tagToReplace, sanitize($value), $output);
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
    
    protected function sanitize($data){
        $sanitized = htmlspecialchars($data, ENT_QUOTES|ENT_HTML5);
        $allowed = ['&gt;sup&lt;' => '<sup>', '&gt;/sup&lt;' => '</sup>', '&gt;sub&lt;' => '<sub>', '&gt;/sub&lt;' => '</sub>'];
        foreach($allowed as $key => $value) {
            $sanitized = str_replace($key, $value, $sanitized);
        }
        return $sanitized
    }
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
        $output .= '<input type="radio" name="answer-entry" value="'.$i.'"/>'.$d.'</br >';
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

function update_js($file, $number_of_questions){
    $contents = "var activeQuestions = ".($number_of_questions-1).";\n";
    $contents .= file_get_contents($file);
    file_put_contents($file, $contents);
}

function copy_supporting_files($number_of_questions){
    $supporting_files = Array("correct.png", "correct_16.png", "incorrect.png", "incorrect_16.png", "validation.js", "main.css");
    foreach ($supporting_files as $file){
        copy(DOCUMENT_ROOT."/Resources/$file", "$file");
    }
    update_js("validation.js", $number_of_questions);
}

function create_zip($title, $number_of_questions){
    $zip = new ZipArchive();
    $filename = $title.".zip";
    $supporting_files = Array("correct.png", "correct_16.png", "incorrect.png", "incorrect_16.png", "validation.js", "main.css", "index.html");
    for ($i = 1; $i < $number_of_questions; $i++) {
        $supporting_files[] = "question$i.html";
    }
    if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
        exit("cannot open <$filename>\n");
    }
    foreach ($supporting_files as $file){
        $zip->addFile($file, "$file");
    }
    $zip->close();
    copy($filename, DOCUMENT_ROOT."/$filename");
    return $filename;
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
    elseif (!preg_match("^[a-zA-Z]"), $title) {
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
        else if ($q["type"] == "multiple-choice" && !count($q["prompts"]) == 0){
            $response_text = '<p class="invalid">Multiple choice questions must have at least one prompt</p>';
            $is_valid = false;
        }
    }
    if (!$is_valid) { http_response_code(400); echo $response_text;}
    return $is_valid;
        
}

$post_data = file_get_contents("php://input");
$data = json_decode($post_data,true);
if (!validate_json($data)) {
    exit(1);
}
$title = $data["title"];

if (!file_exists($title)){
    mkdir($title, 0777, true);
}
chdir($title);
$titles = Array();
$i = 1;
foreach ($data["questions"] as $qdata){
    build_question($qdata, $i);
    $i += 1;
    $titles[] = $qdata["title"];
}
build_index($title, $titles);
copy_supporting_files($i);
$output = create_zip($title, $i);
if (file_exists($output)) {
    $downloadTemplate = new Template(DOCUMENT_ROOT."/templates/download.tmpl");
    $downloadTemplate->set("url", $output);
    echo $downloadTemplate->output();
}
else {
    echo 'derp--created file not found';
}