require(jquery);

function load_question(index) {
    var assignment = JSON.parse(localStorage.getItem("assignment"));
    return question = assignment.questions[index];
}

function generatePopover(question_index) {
    var question = load_question(question_index);
    
}