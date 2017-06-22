/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$(document.body).ready(function(){
    retrieveCompletionRecords(null);
});


function generateTableRow(record, index){
    return `<tr id="record${index}"><td>${record.assignmentName}</td><td class="student">${record.studentEmail}</td><td class='timestamp'>${record.completedOn}</td>/tr>`;
}

function generateEmptyTable(){
    return `<tr><td>No Results</td><td></td><td></td></tr>`;
}

function fillTable(data) {
    console.log(data);
    if (data.length === 0) {
        $('#completion-records').append(generateEmptyTable());
    } else {
        data.forEach(function (record, index, array) {
            $('#completion-records').append(generateTableRow(record, index));
        });
    }
}

function retrieveCompletionRecords(filterObject) {
    var baseURL = 'https://teaching.admiralbenbo.org/api/',
        handler = fillTable,
        url;
    if (filterObject === null) {
        url = baseURL + 'completions';
        $.get(url, handler).fail(function(data){console.log(data);});
    }
}
