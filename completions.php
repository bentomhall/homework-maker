<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once('repository.php');
require_once('logger.php');
$repository = new Repository(getCredentials());

function Respond(int $code, string $message = "") {
    http_response_code($code);
    if ($message != "") {
        echo $message;
    }
}

//POST /api/completions
/*
 * Add a completion to the database. Only 204 OK response on success.
 * JSON payload:
 * {
 *  "assignmentID": 38-character UUID string matching an assignment id,
 *  "studentEmail": email string to identify a particular student
 *  "completion": float representing completion percentage (0-100)
 * } 
 */

function AddCompletionRecord(Repository $repo, string $studentEmail, string $assignmentID, float $complete) {
    try {
        $repo->insertCompletion($studentEmail, $assignmentID, $complete);
        debug_log("Inserted completion for student: ".$studentEmail." and assignment: ".$assignmentID);
    } catch (Exception $ex) {
        Respond(500, "Database Error: ".$ex->getMessage());
    }
    Respond(204);
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
function getCompletionRecordsForAssignmentID(Repository $repo, string $assignmentID) {
    $records = $repo->getCompletionRecordsForAssignment($assignmentID);
    if ($records) {
        respond(200, json_encode($records));
    } else {
        respond(404);
    }
}

function getCompletionRecordsForAssignmentName(Repository $repo, string $assignmentName) {
    $records = $repo->getCompletionRecordsForAssignmentName($assignmentName);
    if ($records) {
        Respond(200, json_encode($records));
    } else {
        respond(404);
    }
}

//GET /api/student/$studentEmail
/*
 * Retrieve all completion records for the indicated student
 */

function getCompletionRecordsForStudent(Repository $repo, string $studentEmail) {
    $records = $repo->getCompletionRecordsForStudent($studentEmail);
    if ($records) {
        respond(200, json_encode($records));
    } else {
        respond(404);
    }
}

function filterRecords(string $type, string $filter) {
    global $repository;
    $records = false;
    debug_log("filtering on ".$type." with filter ". $filter);
    switch ($type) {
        case "student":
            $records = $repository->getCompletionRecordsForStudent($filter);
            break;
        case "assignment":
            $records = $repository->getCompletionRecordsForAssignmentName($filter);
            debug_log("records:" . var_export($records, true));
            break;
        case "subject":
            $records = $repository->getCompletionRecordsForSubject($filter);
            break;
        case "date-before":
            $records = $repository->getCompletionRecordsBeforeDate($filter);
            break;
        case "date-after":
            $records = $repository->getCompletionRecordsAfterDate($filter);
            break;
        case "activity":
            $records = $repository->getModulesWithActivity();
        default:
            break;
    }
    if ($records) {
        respond(200, json_encode($records));
    } else {
        respond(404);
    }
}

function main() {
    global $repository;
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
        case 'GET':
            $type = filter_input(INPUT_GET, 'type');//, FILTER_SANITIZE_STRING);
            $filter = filter_input(INPUT_GET, 'value');//, FILTER_SANITIZE_STRING);
            
            if (!$type) {
                GetAllCompletionRecords($repository);
            }
            else {
                filterRecords($type, $filter);
            }
            break;
        case 'POST':
            $post_data = file_get_contents('php://input');
            debug_log($post_data);
            $data = json_decode($post_data, true);
            $isValidJSON = validateInput($data);
            if ($isValidJSON) {
                if (is_null($data['completion'])) { 
                    AddCompletionRecord($repository, $data['studentEmail'], $data['assignmentID'], 100.0);
                } else {
                    AddCompletionRecord($repository, $data['studentEmail'], $data['assignmentID'], $data['completion']);
                }
            } else {
                Respond(400, "Invalid format in JSON request");
            }
            break;
        default:
            break;
    }
}

function validateInput(Array $data) {
    $emailMatches = preg_match('/^[a-zA-Z.\-]*@tampaprep\.org/', $data['studentEmail']);
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

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
main();