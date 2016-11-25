var activeQuestions = 1;
var validator = {};

validator.validateExact = function (submitted, correct) {
    "use strict";
    return submitted.trim() === correct.trim();
};
validator.validateNumericWithin = function (submitted, correct, tolerance) {
    "use strict";
    if (Number(correct) === 0) {
        return Math.abs(submitted) <= tolerance;
    } else {
        return Math.abs(submitted - correct) / correct <= tolerance;
    }
};
validator.validateTextContainsAny = function (submitted, requiredTerms) {
    "use strict";
    var output = [],
        input = submitted.split(', ');
    input.forEach(function (x) {output.push(requiredTerms.indexOf(x)); });
    return output.some(function (x) {return x !== -1; });
};

validator.validateTextContainsAll = function (submitted, requiredTerms) {
    "use strict";
    var output = [],
        input = requiredTerms.toLowerCase().split(', ');
    input.forEach(function (x) {
        output.push(submitted.toLowerCase().indexOf(x));
        return output.every(function (x) { return x !== -1; });
    });
};

validator.validateExactCaseInsensitive = function (submitted, correct) {
    "use strict";
    return submitted.toLowerCase().trim() === correct.toLowerCase().trim();
};

function markAnswer(didValidate) {
    "use strict";
    var element = document.getElementById("submit"),
        icon = document.querySelector(".validation-icon"),
        hint = document.getElementById("hint");
    if (didValidate) {
        element.innerHTML = "Correct!";
        element.className = "correct";
        icon.src = "correct_16.png";
        icon.style.visibility = "visible";
        
    } else {
        element.innerHTML = "Try Again!";
        element.className = "incorrect";
        icon.src = "incorrect_16.png";
        icon.style.visibility = "visible";
        if (!hint.innerHTML) {
            hint.innerHTML = "No hint available for this problem. Check your math and check for typos in your submitted answer.";
        }
        
        hint.style.visibility = "visible";
    }
}

function validateAnswer(isMC) {
    "use strict";
    var rightAnswer = document.getElementById("answer").innerHTML,
        questionType = document.getElementById("question-type").innerHTML,
        submittedElement,
        questionId = document.getElementById("question-id").innerHTML,
        tolerance = 0.02,
        isCorrect = false,
        submitted;
    if (isMC) {
        submittedElement = document.querySelector("input[type=\"radio\"]:checked");
        if (submittedElement === null) {
            markAnswer(false);
            sessionStorage.setItem(questionId, false);
            return;
        } else {
            submitted = submittedElement.value;
        }
    } else {
        submitted = document.getElementById("answer-entry").value;
    }
    
    if (!submitted) {
        return;
    }
    if (questionType === "numeric-within") {
        isCorrect = validator.validateNumericWithin(submitted, rightAnswer, tolerance);
    } else if (questionType === "exact") {
        isCorrect = validator.validateExact(submitted, rightAnswer);
    } else if (questionType === "exact-case") {
        isCorrect = validator.validateExactCaseInsensitive(submitted, rightAnswer);
    } else if (questionType === "containsAny") {
        isCorrect = validator.validateTextContainsAny(submitted, rightAnswer);
    } else if (questionType === "containsAll") {
        isCorrect = validator.validateTextContainsAll(submitted, rightAnswer);
    }
    markAnswer(isCorrect);
    sessionStorage.setItem(questionId, isCorrect);
}

function nextPage() {
    "use strict";
    var questionId = Number(document.getElementById("question-id").innerHTML);
    if (questionId < activeQuestions) {
        window.location = "./question" + (questionId + 1) + ".html";
    } else {
        window.location = "./index.html";
    }
}

function homePage() {
    "use strict";
    window.location = "./index.html";
}

function toggleCompletionCode() {
    "use strict";
    var code = document.getElementById("completion-code");
    code.style.visibility = "visible";
    return;
}

function validateHomePageLinks() {
    "use strict";
    var links = document.querySelectorAll(".question"),
        i = 1,
        link,
        isValid,
        validCount = 0;
    for (i; i <= activeQuestions; i += 1) {
        link = links[i - 1]; //0 indexed
        isValid = sessionStorage.getItem(i); //1 indexed
        if (isValid === "true") {
            link.className = "question correct";
            validCount += 1;
        } else if (isValid === null || isValid === "unvalidated") {
            link.className = "question unchecked";
        } else {
            link.className = "question incorrect";
        }
    }
    if (validCount === activeQuestions) {
        toggleCompletionCode();
    }
}



function resetValidation(index) {
    "use strict";
    sessionStorage.setItem(index, "unvalidated");
}

function resetAllValidation() {
    "use strict";
    var i = 1;
    for (i; i <= activeQuestions; i += 1) {
        resetValidation(i);
    }
    location.reload(true);
}