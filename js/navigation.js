 function ImageData(filename, data) {
     this.name = filename;
     this.binData = data;
     this.encodedData = btoa(data);
 }
 
 $(document).on('change', ':file', function() {
        var input = $(this),
            numFiles = input.get(0).files ? input.get(0).files.length : 1,
            label = input.val().replace(/\\/g, '/').replace(/.*\//, "");
        input.trigger('fileselect', [numFiles, label]);
    });

$(document.body).ready(function(){
    sessionStorage['assignment'] = null;
    document.assignment = {};
    document.assignment.questions = [];
    window.images = {};
    sessionStorage.setItem('isEditing', false);
    $("#add-question-button").on('click', function(event) {
        editQuestion(); });
    $("#clear-question-button").click(function(event) {clearQuestion();});
    $("#delete-question-button").on('click', deleteQuestion);
    $("#toggle-editing-button").on('click', toggleEditingMode);
    $("#add-prompt-button").click(function(event) {addPrompt();});
    $(':file').on('fileselect', function(event, numFiles, label) {
        if (numFiles > 0) {
            var node = $(`<li class="list-group-item">${label}</li>`),
                reader = new FileReader();
            $('#file-list').append(node);
            reader.onload = (function(name) {return function(e) {storeImage(name, e.target.result);};})(label);
            reader.readAsDataURL($('#picker').get(0).files[0]);
        }
    });
});

function storeImage(name, data) {
    window.images[name] = data;
}

function toggleEditingMode() {
    var mode = sessionStorage.getItem('isEditing');
    if (mode === "true") { 
        sessionStorage.setItem('isEditing', false);
        $("#add-question-button").html('Add Question');
    }
    else {
        sessionStorage.setItem('isEditing', true);
        $("#add-question-button").html('Save Question');
    }
    $("#toggle-editing-button").toggleClass('on');
}

function fillDialog(index){
    var assignment = JSON.parse(localStorage.getItem("assignment")),
    question = assignment.questions[index],
    prompts;
    console.log(index);
    $("#question-title").val(question['title']);
    $("#question-text").val(question['text']);
    $("#question-answer").val(question['answer']);
    $("#question-hint").val(question['hint']);
    $("#question-type").val(question['type']);
    if (question['type'] === 'multiple-choice') {
        prompts = question['prompts'];
        $('li input').forEach(function(e, index) {
            e.val(prompts[index]);
        });
    }
    
}

function deleteQuestion() {
    var assignment = JSON.parse(localStorage.getItem('assignment')),
        index = $('tr.selected').index();
    assignment.questions.splice(index, 1);
    document.assignment = assignment;
    $('tr.selected').remove();
    renumberTableRows(index);
    storeAssigment();
}

function renumberTableRows(startingIndex) {
    $('#current-questions td:first-child').each(function(){
        var thisIndex = Number($(this).html());
        if (thisIndex > startingIndex + 1) {
            $(this).html(thisIndex - 1);
        }
    });
}

function editQuestion() {
    if (sessionStorage.getItem('isEditing') === 'true') {
        var index = $('tr.selected').index();
        addQuestion(index);
    }
    else {
        addQuestion(-1);
    }
    
}

function addTitle() {
    document.assignment['title'] = $('#assignment-title').val();
    document.assignment['subject'] = $('#subject').val();
    storeAssigment();
}

function storeAssigment(){
    localStorage.assignment = JSON.stringify(document.assignment);
}

function clearAssignment(){
    localStorage.assignment = null;
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
    return `<tr id="question${index}"><td>${index}</td><td class="qtitle">${question.title}</td><td class='qtype'>${question.type}</td></tr>`;
}

function generateMultipleChoiceInput(index) {
    var target = $('#question-prompts ol'),
        node = $(`<li class="input-group"><span class="input-group-addon"><input type="radio" name="correct-answer" value="${index}"></span><input type="text" class="form-control prompt-item"/></li>`);
    target.append(node);
}

function generateMultipleSelectionInput(index) {
    var target = $('#question-prompts ol'),
        node = $(`<li class="input-group"><span class="input-group-addon"><input type="checkbox" name="correct-answer" value="${index}"></span><input type="text" class="form-control prompt-item"/></li>`);
    target.append(node);
}

function addPrompt() {
    var index = $("#question-prompts ol li").length, //0 indexed, so this is the next index
        type = $("#question-type").val();
    if (type === "multiple-choice") {
        generateMultipleChoiceInput(index);
    } else if (type === "multiple-selection") {
        generateMultipleSelectionInput(index);
    }
}

function updateQuestionDisplay(q, index){
    $('#current-questions').append(generateTableRow(q, index));
    $("#current-questions tr").click( function(event) {
        $(this).addClass('selected').siblings().removeClass('selected');
        if (sessionStorage.getItem('isEditing') === "true") {
            fillDialog($("tr.selected").index());
        }
    });
}

function addQuestion(index) {
    var numberOfQuestions,
        prompts,
        question = {},
        questionType = $('#question-type').val(),
        index = index === 0 ? index : (index || -1),
        id = (index > -1? 'tr#question' + (index + 1) : null);
    question['title'] = $('#question-title').val();
    question['text'] = $('#question-text').val();
    question['hint'] = $('#question-hint').val();
    
    question['type'] = questionType;
    if (questionType === 'multiple-choice' || questionType === 'multiple-selection') {
        prompts = [];
        $(".prompt-item").each(function (index, element) {
            var el = $(element);
            prompts.push(el.val());
        });
        question['prompts'] = prompts;
        question['answer'] = $('input[name=correct-answer]:checked').val();
    } else {
        question['answer'] = $('#question-answer').val();
    }
    if (index !== -1){
        document.assignment.questions[index] = question;
        $(id +' td.qtitle').html(question['title']);
        $(id +' td.qtype').html(question['type']);
    }
    else {
        numberOfQuestions = document.assignment['questions'].push(question);
        updateQuestionDisplay(question, numberOfQuestions);
    }
    storeAssigment();
    clearQuestion();
    return false;
}

function createFromJson () {
    var data = $('#output-json').val(),
        json = JSON.parse(data);
    json['images'] = [];
    if (json['title'] === undefined || json['questions'].length === 0) {
        alert('Must supply valid JSON');
        return false;
    }
    else {
    var url = 'create_assignment.php',
        handler = updatePage;
    $.post(url, data, handler).fail(function(data) {onFailure(data);});
    }
}

function questionTypeChanged(){
    var isMultipleChoice = $('#question-type').val() === 'multiple-choice',
        isMultipleSelection = $('#question-type').val() === "multiple-selection";
    if (isMultipleChoice) {
        if ($('#question-prompts ol li').length === 0) {
            generateMultipleChoiceInput(0);
            generateMultipleChoiceInput(1);
        } else {
            $('#question-prompts ol').empty();
            generateMultipleChoiceInput(0);
            generateMultipleChoiceInput(1);
        }
        revealElement($('#question-prompts'));
        hideElement($(".answer-group"));
    } else if (isMultipleSelection) {
        if ($('#question-prompts ol li').length === 0) {
            generateMultipleSelectionInput(0);
            generateMultipleSelectionInput(1);
        } else {
            $('#question-prompts ol').empty();
            generateMultipleSelectionInput(0);
            generateMultipleSelectionInput(1);
        }
        revealElement($('#question-prompts'));
        hideElement($(".answer-group"));
    } else {
        hideElement($('#question-prompts'));
        revealElement($(".answer-group"));
    }
    
}

function hideElement(element) {
    if (!element.hasClass('hidden')) {
        element.addClass('hidden');
    }
}

function revealElement(element) {
    if (element.hasClass('hidden')) {
        element.removeClass('hidden');
    }
}

function updatePage(data) {
    var parent = $('#download-info'),
        child = $('#download-info .panel-body');
    parent.class += 'panel-success';
    child.html(data);
    parent.show(); 
}

function onFailure(data) {
    var element = $('#download-info');
    element.class += 'panel-failure';
    element.html("<h4>FAILED</h4>"+data["responseText"]);
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
    
    document.assignment['images'] = window.images;
    $('#output-json').append(JSON.stringify(document.assignment));
    var jsonData = JSON.stringify(document.assignment),
        url = 'create_assignment.php',
        handler = updatePage;
    console.log(document.assignment.images);
    $.post(url, jsonData, handler).fail(function(data) {console.log(data); onFailure(data);});
}