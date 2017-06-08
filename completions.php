<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('repository.php');
$repository = new Repository(getCredentials());

function Respond(int $code, string $message = "") {
    http_response_code($code);
    if ($message != "") {
        echo $message;
    }
}


//POST /api/completions
/*
 * Add a completion to the database. Only 200 OK response on success.
 * JSON payload:
 * {
 *  "assignment": 38-character UUID string matching an assignment id,
 *  "student_email": email string to identify a particular student
 * } 
 */

function AddCompletionRecord(Repository $repo, string $studentEmail, string $assignmentID) {
    $repo->insertCompletion($studentEmail, $assignmentID);
    Respond(200);
    return;
}

//GET /api/completions
/* Retrieve a list of all completion records as JSON
 * Response body JSON:
 * [
 *  {
 *      "student_email": string containing the identifier supplied,
 *      "assignment_name": string containing the human-readable assignment name,
 *      "completed_on": date and time encoded with user locale* in ISO 8601 (complete date plus hours, minutes and seconds
 *      "assignment_ID": The UUID corresponding to the assignment (for future convenience calls)
 *  }
 * ]
 */
function GetAllCompletionRecords(Repository $repo) {
    $records = $repo->getAllCompletionRecords();
    respond(200,json_encode($records));
}

//GET /api/assignment/$assignmentID
/*
 * Retrieve all completion records for the indicated assignment
 * assignmentIDs are 38-character UUIDs (including {})
 * Response body JSON: same as above
 */
function GetCompletionRecordsForAssignment(Repository $repo, string $assignmentID) {
    $records = $repo->getCompletionRecordForAssignment($assignmentID);
    if ($records) {
        respond(200, json_encode($records));
    } else {
        respond(404);
    }
}

//GET /api/student/$studentEmail
/*
 * Retrieve all completion records for the indicated student
 */

function GetCompletionRecordsForStudent(Repository $repo, string $studentEmail) {
    $records = $repo->getCompletionRecordForStudent($studentEmail);
    if ($records) {
        respond(200, json_encode($records));
    } else {
        respond(404);
    }
}