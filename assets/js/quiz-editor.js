(() => {
    const initial = window.bootUpInitial;
    const questions = Array.isArray(initial.questions) ? initial.questions : [];
    const quiz = initial.quiz;
    const list = document.getElementById('questions-list');
    const count = document.getElementById('question-count');
    const message = document.getElementById('editor-message');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const make = (tag, className, text) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined) element.textContent = text;
        return element;
    };

    function field(labelText, control, className = '') {
        const label = make('label', className);
        label.append(document.createTextNode(labelText), control);
        return label;
    }

    function input(type, value, className = '') {
        const element = document.createElement('input');
        element.type = type;
        element.value = value ?? '';
        if (className) element.className = className;
        return element;
    }

    function render() {
        list.replaceChildren();
        count.textContent = `${questions.length} ${questions.length === 1 ? 'question' : 'questions'}`;
        questions.forEach((question, index) => {
            const card = make('article', 'question-card');
            const top = make('div', 'question-card-top');
            const title = make('div', 'question-card-title');
            title.append(make('span', 'question-index', String(index + 1).padStart(2, '0')), make('strong', '', `Question ${index + 1}`));
            const typeSelect = document.createElement('select');
            typeSelect.setAttribute('aria-label', `Question ${index + 1} type`);
            [
                ['multiple_choice', 'Multiple choice'],
                ['true_false', 'True or false'],
                ['fill_blank', 'Fill in the blank'],
            ].forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = question.question_type === value;
                typeSelect.append(option);
            });
            typeSelect.addEventListener('change', () => {
                question.question_type = typeSelect.value;
                if (question.question_type === 'multiple_choice' && !question.options?.length) {
                    question.options = [
                        { text: '', correct: true }, { text: '', correct: false },
                        { text: '', correct: false }, { text: '', correct: false },
                    ];
                }
                if (question.question_type === 'true_false' && !question.answer) question.answer = 'true';
                if (question.question_type === 'fill_blank' && !question.accepted_answers?.length) question.accepted_answers = [''];
                render();
            });
            const remove = make('button', 'icon-button', 'Remove');
            remove.type = 'button';
            remove.addEventListener('click', () => {
                questions.splice(index, 1);
                render();
            });
            top.append(title, field('Format', typeSelect, 'question-type-field'), remove);
            card.append(top);

            const text = document.createElement('textarea');
            text.rows = 2;
            text.maxLength = 1000;
            text.placeholder = 'Write your question here…';
            text.value = question.question_text || '';
            text.addEventListener('input', () => { question.question_text = text.value; });
            card.append(field('Question', text, 'question-prompt'));

            const answers = make('div', 'answer-editor');
            if (question.question_type === 'multiple_choice') {
                const answerTitle = make('div', 'answer-heading');
                answerTitle.append(make('strong', '', 'Answer choices'), make('span', 'muted small-text', 'Select the correct answer'));
                answers.append(answerTitle);
                (question.options || []).forEach((choice, choiceIndex) => {
                    const row = make('div', 'choice-row');
                    const correct = input('radio', '');
                    correct.name = `correct-${index}`;
                    correct.checked = Boolean(choice.correct);
                    correct.setAttribute('aria-label', `Mark choice ${choiceIndex + 1} correct`);
                    correct.addEventListener('change', () => {
                        question.options.forEach((item, i) => { item.correct = i === choiceIndex; });
                        render();
                    });
                    const choiceInput = input('text', choice.text, 'choice-input');
                    choiceInput.maxLength = 250;
                    choiceInput.placeholder = `Answer ${String.fromCharCode(65 + choiceIndex)}`;
                    choiceInput.addEventListener('input', () => { choice.text = choiceInput.value; });
                    row.append(correct, make('span', 'choice-letter', String.fromCharCode(65 + choiceIndex)), choiceInput);
                    if (question.options.length > 2) {
                        const removeChoice = make('button', 'choice-remove', '×');
                        removeChoice.type = 'button';
                        removeChoice.setAttribute('aria-label', `Remove answer ${choiceIndex + 1}`);
                        removeChoice.addEventListener('click', () => {
                            question.options.splice(choiceIndex, 1);
                            if (!question.options.some(item => item.correct)) question.options[0].correct = true;
                            render();
                        });
                        row.append(removeChoice);
                    }
                    answers.append(row);
                });
                if (question.options.length < 4) {
                    const addChoice = make('button', 'add-choice', '+ Add answer choice');
                    addChoice.type = 'button';
                    addChoice.addEventListener('click', () => {
                        question.options.push({ text: '', correct: false });
                        render();
                    });
                    answers.append(addChoice);
                }
            } else if (question.question_type === 'true_false') {
                const answer = document.createElement('select');
                [['true', 'True'], ['false', 'False']].forEach(([value, label]) => {
                    const option = document.createElement('option');
                    option.value = value;
                    option.textContent = label;
                    option.selected = question.answer === value;
                    answer.append(option);
                });
                answer.addEventListener('change', () => { question.answer = answer.value; });
                answers.append(field('Correct answer', answer));
            } else {
                const accepted = document.createElement('textarea');
                accepted.rows = 3;
                accepted.placeholder = 'Enter accepted answers, one per line';
                accepted.value = (question.accepted_answers || []).join('\n');
                accepted.addEventListener('input', () => {
                    question.accepted_answers = accepted.value.split(/\r?\n/);
                });
                answers.append(field('Accepted answers', accepted));
                const caseLabel = make('label', 'toggle-row compact-toggle');
                const caseBox = input('checkbox', '');
                caseBox.checked = Boolean(question.case_sensitive);
                caseBox.addEventListener('change', () => { question.case_sensitive = caseBox.checked; });
                caseLabel.append(caseBox, make('span', '', 'Answers are case-sensitive'));
                answers.append(caseLabel);
            }
            card.append(answers);

            const settings = make('div', 'question-settings');
            const timer = input('number', question.timer_seconds ?? 0);
            timer.min = '0';
            timer.max = '300';
            timer.step = '1';
            timer.addEventListener('input', () => { question.timer_seconds = timer.value === '' ? '' : Number(timer.value); });
            const points = input('number', question.points ?? initial.quiz.base_points);
            points.min = '0';
            points.max = '100000';
            points.step = '50';
            points.addEventListener('input', () => { question.points = points.value === '' ? '' : Number(points.value); });
            const timerField = field('Timer override', timer, 'setting-field');
            timerField.append(make('small', '', '0 = use quiz default'));
            settings.append(
                timerField,
                field('Points', points, 'setting-field'),
            );
            const explanation = document.createElement('textarea');
            explanation.rows = 2;
            explanation.maxLength = 2000;
            explanation.placeholder = 'Optional: explain why the answer is correct';
            explanation.value = question.explanation || '';
            explanation.addEventListener('input', () => { question.explanation = explanation.value; });
            settings.append(field('Answer explanation', explanation, 'explanation-field'));
            card.append(settings);
            list.append(card);
        });
    }

    function showMessage(text, isError) {
        message.textContent = text;
        message.className = `notice ${isError ? 'notice-error' : 'notice-success'}`;
        message.hidden = false;
        message.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function collectQuiz() {
        return {
            id: quiz.id,
            title: document.getElementById('quiz-title').value,
            description: document.getElementById('quiz-description').value,
            visibility: document.getElementById('quiz-visibility').value,
            status: document.getElementById('quiz-status').value,
            default_timer: Number(document.getElementById('quiz-default-timer').value),
            base_points: Number(document.getElementById('quiz-base-points').value),
            randomize_questions: document.getElementById('randomize-questions').checked,
            randomize_answers: document.getElementById('randomize-answers').checked,
            show_leaderboard: document.getElementById('show-leaderboard').checked,
        };
    }

    async function save() {
        const buttons = [document.getElementById('save-quiz'), document.getElementById('save-quiz-bottom')];
        buttons.forEach(button => { button.disabled = true; button.textContent = 'Saving…'; });
        try {
            const response = await fetch('/BootUp/api/save-quiz.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ quiz: collectQuiz(), questions }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'The quiz could not be saved.');
            quiz.id = result.id;
            showMessage('Quiz saved. Your question formats, answers, and settings are all up to date.', false);
            if (!new URLSearchParams(location.search).has('id')) {
                history.replaceState(null, '', `/BootUp/admin/quiz-edit.php?id=${result.id}`);
                document.title = 'Edit quiz — BootUp';
            }
        } catch (error) {
            showMessage(error.message || 'The quiz could not be saved. Check your connection and try again.', true);
        } finally {
            buttons.forEach((button, index) => {
                button.disabled = false;
                button.textContent = index === 0 ? 'Save quiz' : 'Save quiz';
            });
        }
    }

    document.getElementById('add-question').addEventListener('click', () => {
        questions.push({
            question_text: '',
            question_type: 'multiple_choice',
            options: [
                { text: '', correct: true }, { text: '', correct: false },
                { text: '', correct: false }, { text: '', correct: false },
            ],
            accepted_answers: [],
            answer: 'true',
            case_sensitive: false,
            explanation: '',
            timer_seconds: 0,
            points: Number(document.getElementById('quiz-base-points').value),
        });
        render();
        list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
    document.getElementById('save-quiz').addEventListener('click', save);
    document.getElementById('save-quiz-bottom').addEventListener('click', save);
    const deleteButton = document.getElementById('delete-quiz');
    if (deleteButton) {
        deleteButton.addEventListener('click', async () => {
            if (!window.confirm('Delete this quiz and all of its questions? This cannot be undone.')) return;
            deleteButton.disabled = true;
            try {
                const response = await fetch('/BootUp/api/delete-quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ id: quiz.id }),
                });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'The quiz could not be deleted.');
                location.href = '/BootUp/admin/quizzes.php';
            } catch (error) {
                showMessage(error.message || 'The quiz could not be deleted.', true);
                deleteButton.disabled = false;
            }
        });
    }

    render();
})();
