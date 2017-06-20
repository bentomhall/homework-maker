<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('logger.php');

function getCredentials() {
    $raw = file_get_contents(__DIR__ . "/config.json");
    $json = json_decode($raw, true);
    $creds = array();
    $creds["user"] = $json["dbUser"];
    $creds["secret"] = $json["dbSecret"];
    $creds["database"] = $json["database"];
    return $creds;
}

class Repository {
    private $database;
    function __construct($credentials) {
        //$this->database = new mysqli("localhost", $credentials["user"], $credentials["secret"], $credentials["database"]);
        $dbname = $credentials["database"];
        $user = $credentials["user"];
        $secret = $credentials["secret"];
        $dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8";
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $this->database = new PDO($dsn, $user, $secret, $opt);
    }
    
    function saveAssignment(string $title, string $uuid, int $subjectId) {
        $stmt = $this->database->prepare("INSERT INTO assignment(title, subject, uuid) VALUES(?, ?, ?)");
        if (!($stmt)) {
            log_error("Failed to prepare statement", $this->database->error);
            throw new Exception("Failed to prepare statement: ".$this->database->error);
        }
        $stmt->execute([$title, $uuid, $subjectId]);
        return true;
    }

    function getSubjectCodes() {
        $result = $this->database->query("SELECT * FROM subject");
        $subjectCodes = array();
        foreach ($result as $row) {
            $subjectCodes[$row["name"]] = $row["id"];
        }
        return $subjectCodes;
    }
    
    function insertCompletion($student, $assigmentUUID) {
        $stmt = $this->database->prepare("CALL insertCompletion(?,?)");
        $stmt->execute([$student, $assigmentUUID]);
        return;
    }
    
    function getAllCompletionRecords() {
        $result = $this->database->query("SELECT * FROM completionreport");
        $output = array();
        foreach ($result as $row) {
            $record = new CompletionRecord($row["student_email"], $row["title"], $row["completed_on"], $row["assignment_id"]);
            $output[] = $record;
        }
        return $output;
    }
    
    function getCompletionRecordsForAssignment(string $assignmentID) {
        $stmt = $this->database->prepare("Select * FROM completionreport WHERE assignment_id = ? ORDER BY completed_on");
        $stmt->execute([$assignmentID]);
        $result = $stmt->fetchAll();
        $output = array();
        foreach ($result as $row) {
            $record = new CompletionRecord($row["student_email"], $row["title"], $row["completed_on"], $row["assignment_id"]);
            $output[] = $record;
       	}
        return $output;
    }
    
    function getCompletionRecordsForStudent(string $studentEmail) {
        $stmt = $this->database->prepare("Select * FROM completionreport WHERE student_email = ? ORDER BY completed_on");
        $stmt->execute([$studentEmail]);
        $result = $stmt->fetchAll();
        $output = array();
        foreach ($result as $row) {
            $record = new CompletionRecord($row["student_email"], $row["title"], $row["completed_on"], $row["assignment_id"]);
            $output[] = $record;
       	}
        return $output;
    }
    
}

class CompletionRecord {
    public $studentEmail = "";
    public $assignmentName = "";
    public $completedOn;
    public $assignmentID = "";
    
    function __construct(string $email, string $assignmentName, string $completionDate, string $assignmentID) {
        $this->studentEmail = $email;
        $this->assignmentID = $assignmentID;
        $this->assignmentName = $assignmentName;
        $this->completedOn = $completionDate;
    }
}

