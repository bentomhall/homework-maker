/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$(document.body).ready(function(){
    retrieveCompletionRecords(null);
    $("#search").click(function(){
        var searchString = $("#search-input").val(),
            filterType = $("#search-type").val();
        filterRecords(searchString, filterType);
    });
});

function Filter(type, value) {
    this.filterType = encodeURIComponent(type);
    this.filterValue = encodeURIComponent(value);
}

function filterRecords(search, filterType) {
    var filter = new Filter(filterType, search);
    $('#completion-records').empty();
    retrieveCompletionRecords(filter);
}

function generateTableRow(record, index){
    return `<tr id="record${index}"><td>${record.assignmentName}</td><td class="student">${record.studentEmail}</td><td class='timestamp'>${record.completedOn}</td>/tr>`;
}

function generateEmptyTable(){
    return `<tr><td>No Results</td><td></td><td></td></tr>`;
}

function fillTable(data) {
    var element = $('#completion-records');
    if (data.length === 0 || "undefined" === typeof data.length) {
        element.empty();
        element.append(generateEmptyTable());
    } else {
        data.forEach(function (record, index, array) {
            element.append(generateTableRow(record, index));
        });
    }
}

function onError(error) {
    console.log(error.statusText);
    fillTable({});
}

function retrieveCompletionRecords(filterObject) {
    var baseURL = 'http://localhost:8080/api/', //https://teaching.admiralbenbo.org/api/',
        handler = fillTable,
        url;
    if (filterObject === null) {
        url = baseURL + 'completions'; //get all completion data
        console.log('filterObject is null, getting all records from '+url);
    } else {
        //values are pre-sanitized and server uses prepared statement.
        url = baseURL + `completions?type=${filterObject.filterType}&value=${filterObject.filterValue}`; //filter on server
    }
    $.get({
        url: url,
        success: handler
    }).fail(function(data){
        onError(data);
    });
}
