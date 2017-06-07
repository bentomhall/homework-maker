<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('repository.php');
$repository = new Repository(getCredentials());

function Respond(int $code, string $message = "") {
    
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
//stub
function GetAllCompletionRecords(Repository $repo) {
    $records = $repo->getAllCompletionRecords();
    echo json_encode($records);
}

//GET /api/assignment/$assignmentID
/*
 * Retrieve all completion records for the indicated assignment
 * assignmentIDs are 38-character UUIDs (including {})
 * Response body JSON: same as above
 */
//stub
function GetCompletionRecordForAssignment(Repository $repo, string $assignmentID) {
    
}

//GET /api/student/$studentEmail
/*
 * Retrieve all completion records for the indicated student
 */