var activeQuestions = typeof(activeQuestions) === 'undefined' ? 4 : activeQuestions;
var assignmentSeed = typeof(assignmentSeed) === 'undefined' ? 'ABCD' : assignmentSeed;

document.getElementById("submit")
        .addEventListener("keyup", function(event) {
            event.preventDefault();
            if (event.keyCode === 13) {
                document.getElementById("submit").click();
            }
});
function Validator() {
    this.validateNumericWithin = function (submitted, correct, tolerance) {
        "use strict";
        if (Number(correct) === 0) {
            return Math.abs(submitted) <= tolerance;
        } else {
            return Math.abs(submitted - correct) / correct <= tolerance;
        }
    };
    
    this.validateExact = function (submitted, correct) {
        "use strict";
        return submitted.trim() === correct.trim();
    };
    
    this.validateTextContainsAny = function (submitted, requiredTerms) {
        "use strict";
        submitted = submitted.toLowerCase().replace(/^|\s+|$/g, ' ');
        requiredTerms = requiredTerms.toLowerCase().replace(/\s*,\s*/g, ',').match(/\w[^,]*/g);
        return requiredTerms.some(function (word) {
            var i = -1;
            for (var i = -1; (i = submitted.indexOf(word, i + 1)) >= 0; ) {
            // for each match, check that the character before and after the match is a non-word character
                if (/\W/.test(submitted[i - 1]) && /\W/.test(submitted[i + word.length])) return true;
            }
        });
    };

    this.validateTextContainsAll = function (submitted, requiredTerms) {
        "use strict";
        submitted = submitted.toLowerCase().replace(/^|\s+|$/g, ' ');
        requiredTerms = requiredTerms.toLowerCase().replace(/\s*,\s*/g, ',').match(/\w[^,]*/g);
        return requiredTerms.every(function (word) {
            var i = -1;
            for (var i = -1; (i = submitted.indexOf(word, i + 1)) >= 0; ) {
        // for each match, check that the character before and after the match is a non-word character
                if (/\W/.test(submitted[i - 1]) && /\W/.test(submitted[i + word.length])) return true;
            }
        });
    };

    this.validateExactCaseInsensitive = function (submitted, correct) {
        "use strict";
        return submitted.toLowerCase().trim() === correct.toLowerCase().trim();
    };
        
    this.validateMultipleSelection = function (submitted, correct) {
        "use strict";
        var isValid = false;
        submitted.sort();
        isValid = correct.every(function (value) {return submitted.indexOf(value) !== -1;});
        isValid = isValid && submitted.every(function (value) {return correct.indexOf(value) !== -1;});
        return isValid;
    };
};

function BackingStore() {
    this.storage = localStorage;

    this.getItemForIndex = function (index) {
        return this.storage.getItem(assignmentSeed + index);
    };

    this.setItemForIndex = function (index, value) {
        var key = assignmentSeed + index;
        this.storage.setItem(key, value);
    };
}

var backingStore = new BackingStore;

function markAnswer(didValidate) {
    "use strict";
    var element = document.getElementById("submit"),
        icon = document.querySelector(".validation-icon"),
        hint = document.getElementById("hint");
    if (didValidate) {
        element.innerHTML = "Correct!";
        element.className = "btn-success";
        icon.src = "correct_16.png";
        icon.style.visibility = "visible";
        
    } else {
        element.innerHTML = "Try Again!";
        element.className = "btn-danger";
        icon.src = "incorrect_16.png";
        icon.style.visibility = "visible";
        if (!hint.innerHTML) {
            hint.innerHTML = "No hint available for this problem. Check your math and check for typos in your submitted answer.";
        }
        
        hint.className = "row well show";
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
        submitted,
        validator = new Validator();
    if (isMC) {
        submittedElement = document.querySelector("input[type=\"radio\"]:checked");
        if (submittedElement === null) {
            markAnswer(false);
            backingStore.setItemForIndex(questionId, false);
            return;
        } else {
            submitted = submittedElement.value;
        }
        
    } else if (questionType === "multiple-selection") {
        rightAnswer = rightAnswer.split(' ');
        submittedElement = document.querySelectorAll("input[type=\"checkbox\"]:checked");
        if (submittedElement === null) {
            markAnswer(false);
            backingStore.setItemForIndex(questionId, false);
            return;
        } else {
            submitted = [];
            for (var item of submittedElement) {
                submitted.push(item.value);
            }
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
    } else if (questionType === "contains-any") {
        isCorrect = validator.validateTextContainsAny(submitted, rightAnswer);
    } else if (questionType === "contains-all") {
        isCorrect = validator.validateTextContainsAll(submitted, rightAnswer);
    } else if (questionType === "multiple-selection") {
        isCorrect = validator.validateMultipleSelection(submitted, rightAnswer);
    }
    markAnswer(isCorrect);
    backingStore.setItemForIndex(questionId, isCorrect);
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
    var code = document.getElementById("completion-form");
    code.className = "row well completion show";
    return;
}

function createNode(html) {
    var frag = document.createDocumentFragment(),
        temp = document.createElement('div');
    temp.innerHTML = html;
    while (temp.firstChild) {
        frag.appendChild(temp.firstChild);
    }
    return frag;
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
        isValid = backingStore.getItemForIndex(i); //1 indexed
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
    backingStore.setItemForIndex(index, "unvalidated");
}

function resetAllValidation() {
    "use strict";
    var i = 1;
    for (i; i <= activeQuestions; i += 1) {
        resetValidation(i);
    }
    location.reload(true);
}

function sendCompletion() {
    var emailElement = document.getElementById("student-email"),
        user_info = emailElement.value,
        submit_button = document.getElementById("submit-button"),
        xhr;
    if (!user_info || user_info.indexOf('@') === -1) {
        return;
    }
    xhr = new XMLHttpRequest();
    xhr.open("POST", "https://teaching.admiralbenbo.org/api/completions", true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 204) {
            submit_button.innerHTML = "Success!";
            submit_button.className = "btn-success";
        }
    };
    
    xhr.send(JSON.stringify({
        assignmentID: assignmentSeed,
        studentEmail: user_info 
    }));
}