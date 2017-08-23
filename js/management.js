/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
(function(){
    this.rows = [];
$(document.body).ready(function(){
    filterRecords("", "activity", false);
});

$('#recent-activity').on('click', '.activity-element', function(){
    var assignmentName = $(this).children('.assignment-name').text();
    filterRecords(assignmentName, 'assignment', true);
});

function Filter(type, value) {
    this.filterType = encodeURIComponent(type);
    this.filterValue = encodeURIComponent(value);
}

function ActivityRow(data) {
    this.assignment = data.assignmentName;
    this.subject = data.Subject;
    this.count = data.assignmentCount;
    this.toHTML = function(index) {
        return $(`<ul class="activity-element" id="element${index}"><li class="assignment-name">${this.assignment}</li><li>${this.subject}</li><li>Completed ${this.count} times</li></ul>`);
    };
}

function DetailItem(data) {
    this.student = data.studentEmail;
    this.completedOn = data.completedOn;
    this.toHTML = function() {
        return $(`<ul class="record"><li>${this.student}</li><li>${this.completedOn}</li></ul>`);
    };
}

function filterRecords(search, filterType, isDetail=false) {
    var filter = new Filter(filterType, search);
    retrieveCompletionRecords(filter, isDetail);
}

//function generateTableRow(record, index){
//    return $(`<tr id="record${index}"><td scope="row">${record.assignmentName}</td><td>${record.studentEmail}</td><td>${record.completedOn}</td><td>${record.subjectName}</td></tr>`);
//}

function generateEmptyTable(){
    return $(`<ul class="activity-element"><li>No Results</li></ul>`);
}

function fillTable(data) {
    var element = $('#recent-modules');
    if (data.length === 0 || "undefined" === typeof data.length) {
        element.empty();
        element.append(generateEmptyTable());
    } else {
        data.forEach(function (record, index, array) {
            var item = new ActivityRow(record);
            element.append(item.toHTML(index));
        });
    }
}

function fillDetail(data) {
    var element = $('#completions');
    if (data.length === 0 || "undefined" === typeof data.length) {
        element.empty();
        element.append(generateEmptyTable());
    } else {
        data.forEach(function (record) {
            var item = new DetailItem(record);
            element.append(item.toHTML());
        });
    }
}

function onError(error) {
    console.log(error.statusText);
    fillTable({});
}

function retrieveCompletionRecords(filterObject, isDetail=false) {
    var baseURL = 'https://teaching.admiralbenbo.org/api/',
        handler = isDetail ? fillTable : fillDetail,
        url;
    if (filterObject === null) {
        url = baseURL + 'completions'; //get all completion data
    } else {
        //values are pre-sanitized and server uses prepared statement.
        url = baseURL + `completions?type=${filterObject.filterType}&value=${filterObject.filterValue}`;
    }
    $.get({url: url, success: handler}).fail(function(data){
        onError(data);
    });
}
})();
