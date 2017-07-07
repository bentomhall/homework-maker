<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('repository.php');
require_once('logger.php');
$repository = new Repository(getCredentials());

function sendHttpResponse(int $code, string $message = "") {
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
 *  "assignmentID": 38-character UUID string matching an assignment id,
 *  "studentEmail": email string to identify a particular student
 * } 
 */

function AddCompletionRecord(Repository $repo, string $studentEmail, string $assignmentID) {
    try {
        $repo->insertCompletion($studentEmail, $assignmentID);
        debug_log("Inserted completion for student: ".$studentEmail." and assignment: ".$assignmentID);
    } catch (Exception $ex) {
        sendHttpResponse(500, "Database Error: ".$ex->getMessage());
    }
    sendHttpResponse(204);
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
    sendHttpResponse(200,json_encode($records));
}

//GET /api/assignment/$assignmentID
/*
 * Retrieve all completion records for the indicated assignment
 * assignmentIDs are 38-character UUIDs (including {})
 * Response body JSON: same as above
 */
function GetCompletionRecordsForAssignment(Repository $repo, string $assignmentID) {
    $records = $repo->getCompletionRecordsForAssignment($assignmentID);
    if ($records) {
        sendHttpResponse(200, json_encode($records));
    } else {
        sendHttpResponse(404);
    }
}

//GET /api/student/$studentEmail
/*
 * Retrieve all completion records for the indicated student
 */

function GetCompletionRecordsForStudent(Repository $repo, string $studentEmail) {
    $records = $repo->getCompletionRecordsForStudent($studentEmail);
    if ($records) {
        sendHttpResponse(200, json_encode($records));
    } else {
        sendHttpResponse(404);
    }
}

function main() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: *");
    header("Content-Type: application/json");
    global $repository;
    $method = $_SERVER['REQUEST_METHOD'];//filter_input(INPUT_SERVER, 'REQUEST_METHOD');
    switch ($method) {
        case 'GET':
            $student = filter_input(INPUT_GET, 'student', FILTER_SANITIZE_EMAIL);
            $assignment = filter_input(INPUT_GET, 'assignment', FILTER_SANITIZE_STRING);
            if ($student) {
                debug_log("GETting records for student: ".$student);
                GetCompletionRecordsForStudent($repository, $student);
                break;
            }
            elseif ($assignment) {
                debug_log("GETting records for assignment: ".$assignment);
                GetCompletionRecordsForAssignment($repository, $assignment);
                break;
            }
            else {
                debug_log("GETting all records");
                GetAllCompletionRecords($repository);
                break;
            }
        case 'POST':
            $post_data = file_get_contents('php://input');
            $data = json_decode($post_data, true);
            $isValidJSON = ValidateInput($data);
            if ($isValidJSON) {
                debug_log("POSTing record for student: ".$data["studentEmail"]." and assignment: ".$data["assignmentID"]);
                AddCompletionRecord($repository, $data['studentEmail'], $data['assignmentID']);
            } else {
                sendHttpResponse(400, "Invalid format in JSON request");
            }
            break;
        default:
            break;
    }
}

function ValidateInput(Array $data) {
    $emailMatches = preg_match('/^[a-zA-Z]*@tampaprep\.org/', $data['studentEmail']);
    $assignmentMatches = preg_match('/^[0-9A-Fa-f\-]{36}/', $data['assignmentID']);
    if (!$emailMatches) {
        debug_log("Invalid email: received ".$data['studentEmail']);
        return false;
    }
    if (!$assignmentMatches) {
        debug_log("Invalid assignment format--expected UUID, received ".$data['assignmentID']);
        return false;
    }
    return true;
}

main();