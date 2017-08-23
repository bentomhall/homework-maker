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
    $creds["apiToken"] = $json["apiKey"];
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
            PDO::ATTR_EMULATE_PREPARES => true,
        ];
        $this->database = new PDO($dsn, $user, $secret, $opt);
    }
    
    function saveAssignment(string $title, string $uuid, int $subjectId) {
        $query = "INSERT INTO assignment(title, subject, uuid) VALUES(?, ?, ?)";
        $this->insert($query, [$title, $subjectId, $uuid]);
        return;
    }
    
    function addSubject(string $name) {
        $subjectCodes = $this->getSubjectCodes();
        if (!isset($subjectCodes[$name])) {
            //subject is not already present
	    $query = "INSERT INTO subject(name) VALUES(?)";
            $this->insert($query, [$name]);
        }
        $query = "INSERT INTO subject(name) VALUES(?)";
        $this->insert($query, [$name]);
        return;
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
        $query = "CALL insertCompletion(?,?)";
        $this->insert($query, [$student, $assigmentUUID]);
        return;
    }
    
    function getAllCompletionRecords() {
        $query = $this->completionView . "WHERE 1=1";
        return $this->execute($query, null);
    }
    
    function getCompletionRecordsForAssignment(string $assignmentID) {
        $query = $this->completionView . "WHERE assignment_id = ? ORDER BY completed_on";
        return $this->execute($query, [$assignmentID]);
    }
    
    function getCompletionRecordsForStudent(string $studentEmail) {
        $query = $this->completionView . "WHERE student_email = ? ORDER BY completed_on";
        return $this->execute($query, [$studentEmail]);
    }
    
    function getCompletionRecordsForAssignmentName(string $assignmentName) {
        $query = $this->completionView . "WHERE title = ? ORDER BY completed_on";
        return $this->execute($query, [$assignmentName]);
    }
    
    function getCompletionRecordsForSubject(string $subject) {
        $query = $this->completionView . "WHERE subject_name = ? ORDER BY completed_on";
        return $this->execute($query, [$subject]);
    }
    
    function getCompletionRecordsBeforeDate(string $date) {
        $query = $this->completionView . "WHERE completed_on < ? ORDER BY completed_on";
        return $this->execute($query, [$date]);
    }
    
    function getCompletionRecordsAfterDate(string $date) {
        $query = $this->completionView . "WHERE completed_on > ? ORDER BY completed_on";
        return $this->execute($query, [$date]);
    }
    
    function getModulesWithActivity() {
        $query = <<<EOT
SELECT m.title AS Title,
COUNT(c.assignment_id) AS AssignmentCount,
    MAX(c.completed_on) AS LastCompleted,
    s.name AS Subject
FROM assignment m
JOIN completion c
    ON c.assignment_id = m.id
JOIN subject s
    ON s.id = m.subject
GROUP BY Title, Subject
ORDER BY LastCompleted DESC
EOT;
        $stmt = $this->database->prepare($query);
        if ($stmt->errorCode() != 0) {
            $this->handleStatementError($stmt->errorInfo());
        }
        $stmt->execute();
        return $this->createActivityRecord($stmt->fetchAll());
    }
    
    private function insert($query, $data) {
        $stmt = $this->database->prepare($query);
        if ($stmt->errorCode() != 0) {
            $this->handleStatementError($stmt->errorInfo());
        }
        if (is_null($data)) {
            $stmt->execute();
        } else {
            $stmt->execute($data);
        }
        return;
    }
    
    private function execute($query, $data) {
        $stmt = $this->database->prepare($query);
        if ($stmt->errorCode() != 0) {
            $this->handleStatementError($stmt->errorInfo());
        }
        if (is_null($data)) {
            $stmt->execute();
        } else {
            $stmt->execute($data);
        }
        return $this->createRecordsFromResult($stmt->fetchAll());
    }
    
    private function createRecordsFromResult($result) {
        $output = array();
        foreach ($result as $row) {
            $record = new CompletionRecord($row["student_email"], $row["title"], $row["completed_on"], $row["assignment_id"], $row["subject_name"]);
            $output[] = $record;
       	}
        return $output;
    }
    
    private function createActivityRecord($result) {
        $output = array();
        foreach ($result as $row) {
            $record = new ActivityRecord($row["Title"], $row["AssignmentCount"], $row["Subject"]);
            $output[] = $record;
       	}
        return $output;
    }
    
    private function handleStatementError($error) {
        log_error("Failed to prepare statement", $error);
        throw new Exception("Failed to prepare statement: ".$error);
    }
    
    private $completionView = <<<EOT
SELECT DISTINCT c.id AS id,
       c.student_email AS student_email,
       a.title AS title,
       c.completed_on AS completed_on,
       s.name AS subject_name,
       a.uuid AS assignment_id
FROM completion c
    LEFT JOIN assignment a ON c.assignment_id = a.id
    LEFT JOIN subject s ON a.subject = s.id
EOT;
}

class CompletionRecord {
    public $studentEmail = "";
    public $assignmentName = "";
    public $completedOn;
    public $assignmentID = "";
    public $subjectName = "";
    
    function __construct(string $email, string $assignmentName, string $completionDate, string $assignmentID, string $subject) {
        $this->studentEmail = $email;
        $this->assignmentID = $assignmentID;
        $this->assignmentName = $assignmentName;
        $this->completedOn = $completionDate;
        $this->subjectName = $subject;
    }
}

class ActivityRecord {
    public $assignmentName = "";
    public $assignmentCount = 0;
    public $subject = "";
    
    function __construct(string $name, int $count, string $subject) {
        $this->assignmentCount = $count;
        $this->assignmentName = $name;
        $this->subject = $subject;
    }
}

