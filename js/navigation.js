$(document.body).ready(function(){
    var storedAssignment = localStorage['assignment'];
    if (!storedAssignment){
        document.assignment = {};
        document.assignment.questions = [];
    }
    else {
        document.assignment = JSON.parse(storedAssignment);
    }
});
function addTitle() {
    document.assignment['title'] = $('#assignment-title').val();
    storeAssigment();
}

function storeAssigment(){
    localStorage.assigment = JSON.stringify(document.assignment);
}

function clearAssignment(){
    localStorage.assigment = null;
    clearQuestion();
}

function clearQuestion(){
    var elements = ['#question-type', '#question-title', '#question-answer', '#question-text', '#question-hint'],
        prompts = $('li input');
    elements.forEach(function(e) {
        if (e !== '#question-type') {
            $(e).val('');
        }
        else {
            $(e).val('exact-case');
        }
    });
    prompts.val('');
    questionTypeChanged();
}

function generateTableRow(question, index){
    return `<tr><td>${index}</td><td>${question.title}</td><td>${question.type}</td></tr>`
}

function updateQuestionDisplay(q, index){
    $('#current-questions').append(generateTableRow(q, index));
}

function addQuestion() {
    var numberOfQuestions,
        prompts,
        question = {},
        questionType = $('#question-type').val();
    question['title'] = $('#question-title').val();
    question['text'] = $('#question-text').val();
    question['hint'] = $('#question-hint').val();
    question['answer'] = $('#question-answer').val();
    question['type'] = questionType;
    if (questionType === 'multiple-choice') {
        prompts = []
        $(".prompt-item").each(function (index, element) {
            var el = $(element);
            prompts.push(el.val());
        });
        question['prompts'] = prompts;
    }
    numberOfQuestions = document.assignment['questions'].push(question);
    updateQuestionDisplay(question, numberOfQuestions);
    storeAssigment();
    clearQuestion();
    console.log(document.assignment);
    return false;
}

function questionTypeChanged(){
    var isMultipleChoice = $('#question-type').val() === 'multiple-choice';
    $("#question-prompts").toggleClass('visible', isMultipleChoice);
}

function updatePage(data) {
    if 
    console.log(data);
    var element = $('#download-info');
    element.append(data);
    element.show(); 
}

function makeRequest() {
    addTitle();
    if (document.assignment === undefined) {
        console.log('Assignment is undefined');
    }
    if (document.assignment['title'] === undefined || document.assignment['questions'].length === 0){
        return false;
    }
    $('#output-json').append(JSON.stringify(document.assignment));
    var jsonData = JSON.stringify(document.assignment),
        url = 'create_assignment.php',
        handler = updatePage;
    $.post(url, jsonData, handler).fail(function(data) {alert(data);});
}