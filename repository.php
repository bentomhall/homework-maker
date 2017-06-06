<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once 'mysqli';

class Repository {   
    function __construct($credentials) {
        $this->database = new mysqli("localhost", $credentials["user"], $credentials["secret"], "online_practice_module");
    }
    
    function saveAssignment($title, $uuid, $subjectId) {
        
        if (!($stmt = $this->database->prepare("INSERT INTO assignment(title, subject, uuid) VALUES(?, ?, ?, ?"))) {
            echo "Prepare failed: (" . $this->database->errno . ")" . $this->database->error;
        }
        $stmt->bind_param("ssi", $title, $uuid, $subjectId);
        if (!($stmt->execute())) {
            echo "Failed saving assignment: (" . $this->database->errno . ")" . $this->database->error;
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
            echo "Failed saving assignment: (" . $this->database->errno . ")" . $this->database->error;
        }
    }
    
}

