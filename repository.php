<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'mysqli';

function getCredentials() {
    $raw = file_get_contents("config.json");
    $json = json_decode($raw);
    $creds = array();
    $creds["user"] = $json["dbUser"];
    $creds["secret"] = $json["dbSecret"];
    return $creds;
}

function log_error(string $message, string $error) {
    $log_message = $message .": ".$error;
    error_log($log_message);
    return;
}

class Repository {   
    function __construct($credentials) {
        $this->database = new mysqli("localhost", $credentials["user"], $credentials["secret"], "online_practice_module");
    }
    
    function saveAssignment($title, $uuid, $subjectId) {
        
        if (!($stmt = $this->database->prepare("INSERT INTO assignment(title, subject, uuid) VALUES(?, ?, ?, ?"))) {
            log_error("Failed to prepare statement", $this->database->error);
        }
        $stmt->bind_param("ssi", $title, $uuid, $subjectId);
        if (!($stmt->execute())) {
            log_error("Failed saving assignment", $this->database->error);
        }
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
        }
    }
    
    function getAllCompletionRecords() {
        $result = $this->database->query("SELECT * FROM completionReport");
        $output = array();
        while ($row = $result->fetch_assoc()) {
            $record = new CompletionRecord($row["studentEmail"], $row["title"], $row["completedOn"], $row["assignmentID"]);
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
            $record = new CompletionRecord($row["studentEmail"], $row["title"], $row["completedOn"], $row["assignmentID"]);
            $output[] = $record;
            }
            return $output;
        } else {
            log_error("Failed retrieving completion record", $this->database->error);
            return;
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
            return;
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

