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
    return $creds;
}

class Repository {
    private $database;
    function __construct($credentials) {
        $this->database = new mysqli("localhost", $credentials["user"], $credentials["secret"], "online_practice_module");
    }
    
    function saveAssignment(string $title, string $uuid, int $subjectId) {
        $stmt = $this->database->prepare("INSERT INTO assignment(title, subject, uuid) VALUES(?, ?, ?)");
        if (!($stmt)) {
            log_error("Failed to prepare statement", $this->database->error);
            throw new Exception("Failed to prepare statement: ".$this->database->error);
        }
        $stmt->bind_param("sis", $title, $subjectId, $uuid);
        if (!($stmt->execute())) {
            log_error("Failed saving assignment", $this->database->error);
            throw new Exception("Failed saving assignment: ".$this->database->error);
        }
        return true;
    }

    function getSubjectCodes() {
        $result = $this->database->query("SELECT * FROM subject");
        $subjectCodes = array();
        while ($row = $result->fetch_assoc()) {
            $subjectCodes[$row["name"]] = $row["id"];
        }
        return $subjectCodes;
    }
    
    function insertCompletion($student, $assigmentUUID) {
        $stmt = $this->database->prepare("CALL insertCompletion(?,?)");
        $stmt->bind_param("ss", $student, $assigmentUUID);
        if (!($stmt->execute())) {
            log_error("Failed saving completion record", $this->database->error);
            throw new Exception("Failed saving completion record: ".$this->database->error);
        }
        return true;
    }
    
    function getAllCompletionRecords() {
        $result = $this->database->query("SELECT * FROM completionReport");
        $output = array();
        while ($row = $result->fetch_assoc()) {
            $record = new CompletionRecord($row["student_email"], $row["title"], $row["completed_on"], $row["assignment_id"]);
            $output[] = $record;
        }
        return $output;
    }
    
    function getCompletionRecordsForAssignment(string $assignmentID) {
        $stmt = $this->database->prepare("Select * FROM completionReport WHERE assignmentID = ? ORDER BY completedOn");
        $stmt->bind_param("s", $assignmentID);
        $result = $stmt->execute();
        if ($result) {
            $output = array();
            while ($row = $result->fetch_assoc()) {
            $record = new CompletionRecord($row["studentEmail"], $row["title"], $row["completed_on"], $row["assignment_id"]);
            $output[] = $record;
            }
            return $output;
        } else {
            log_error("Failed retrieving completion record", $this->database->error);
            return false;
        }
    }
    
    function getCompletionRecordsForStudent(string $studentEmail) {
        $stmt = $this->database->prepare("Select * FROM completionReport WHERE studentEmail = ? ORDER BY completedOn");
        $stmt->bind_param("s", $studentEmail);
        $result = $stmt->execute();
        if ($result) {
            $output = array();
            while ($row = $result->fetch_assoc()) {
            $record = new CompletionRecord($row["studentEmail"], $row["title"], $row["completedOn"], $row["assignmentID"]);
            $output[] = $record;
            }
            return $output;
        } else {
            log_error("Failed retrieving completion record", $this->database->error);
            return false;
        }
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

