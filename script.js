// это просто тест, попадет ли это изменение в последний PR

const comment = document.getElementById('comment');
const argLeft = document.getElementById('argLeft');
const argRight = document.getElementById('argRight');
const action = document.getElementById('action');
const result = document.getElementById('result');
const execute = document.getElementById('execute');

const debug = false;

function displayError(comment, element, result) { // отмена изменения значения при ошибке. Очистка поля результата. Цветовая индикация
    comment.style.color = 'crimson';
    element.style.color = 'crimson';

    result.innerText = '';
    
    setTimeout(() => {
        comment.style.color = '';
        element.style.color = '';
        element.value = element.dataset.previous;
    }, 2000);
}

function executePressed() { // вызывается при нажатии кнопки =
    if (debug) {console.log('execute pressed');}
    let history = document.getElementById('history');
    let interimResult; // промежуточный результат до вывода на экран
    let actionToDisplay; // action sign

    if ((checkValue(argLeft, comment, result) + checkValue(argRight, comment, result, (action.value == 'division') ? true : false)) != 0) {
        return; // одно из значений некорректное. Выход.
    }
    
    if (debug) {console.log(action.value);}
    
    switch (action.value) {
        case 'plus':
            interimResult = (+argLeft.value) + (+argRight.value);
            actionToDisplay = '+';
            break;
        case 'minus':
            interimResult = argLeft.value - argRight.value;
            actionToDisplay = '-';
            break;
        case 'mult':
            interimResult = argLeft.value * argRight.value;
            actionToDisplay = '*';
            break;
        case 'division':
            interimResult = argLeft.value / argRight.value;
            actionToDisplay = '/';
            break;
    }
    
    interimResult = Math.floor(interimResult * 1000000000) / 1000000000; // устранение неточности вычислений. См. https://learn.javascript.ru/number#неточные-вычисления.
    // См. https://learn.javascript.ru/number#неточные-вычисления.
    result.innerText = Math.floor(interimResult * 1000000000) / 1000000000; // устранение неточности вычислений. 
    addHistoryRow(+argLeft.value, +argRight.value, actionToDisplay, interimResult, history);
}

function addHistoryRow(argLeftVal, argRightVal, actionSign, interimResult, history) {
    // history row
    let historyRow = document.createElement('div');
    historyRow.className = 'historyRow';

    // history row elements
    let argLeft = document.createElement('div');
    argLeft.className = 'argsHistory';
    argLeft.innerHTML = argLeftVal;
    
    let action = document.createElement('div');
    action.className = 'actionHistory';
    action.innerHTML = actionSign;
    
    let argRight = document.createElement('div');
    argRight.className = 'argsHistory';
    argRight.innerText = argRightVal;

    let equalSign = document.createElement('div');
    equalSign.className = 'equalSignHistory';
    equalSign.innerText = '=';

    let result = document.createElement('div');
    result.className = 'resultHistory';
    result.innerText = interimResult;
    
    // add history row container to the page
    history.after(historyRow);

    // fill in history row container
    historyRow.append(argLeft);
    historyRow.append(action);
    historyRow.append(argRight);
    historyRow.append(equalSign);
    historyRow.append(result);
}

function checkValue(element, comment, result, division = false) {
    // если введенное значение некорректное (или деление на 0), то изменение значения отменяется. Дополнительно очищается поле результата. Если корректное - предыдущее значение записывается в data-previous соответствующего element
    
    let readValue = '' + element.value; // преобразование в string
    
    // кооментарий к регулярному выражению. Допустимые значения:
    // ноль
    // дробное, где целая часть = 0: знак (опционально), ноль, точка, любое кол-во цифр, не ноль в конце
    // дробное, где целая часть != 0: знак (опционально), не ноль, любое кол-во цифр, далее опцинальный блок (целиком): точка, любое кол-во цифр, не ноль в конце
    
    if (debug) {console.log(readValue);}
    
    if (readValue.match(/^0$|^-{0,1}(0\.)[0-9]{0,}[1-9]{1}$|^-{0,1}[1-9][0-9]{0,}(\.[0-9]{0,}[1-9]{1}){0,1}$/) == null) {
        if (debug) {console.log("wrong value");}
        displayError(comment, element, result); // отмена изменения значения. Очистка поля результата. Цветовая индикация
        return -1; // не-ok
    }
    
    if ((division == true) && (+readValue == 0)) {
        if (debug) {console.log("division by 0");}
        displayError(comment, element, result);
        return -1; // не-ok
    } 
    
    element.value = readValue; // во избежание отображения точки на конце. Причина: если в input в chrome (в других браузерах не тестировалось) в конце значения стоит точка (т.е. нет дробной части), то значение input возвращается без точки, регулярным выражение не "отлавливается", при этом точка в input на экране отображается. Т.е. нужно записать в value значение уже без точки  
    element.dataset.previous = readValue;
    
    return 0; // все ok
}

let fields = [argLeft, action, argRight, execute]; // поля, которые участвуют в навигации
let pointer = ''; // указатель на поле
let keys = ['ControlLeft', 'Convert', 'NonConvert']; // КОДЫ кнопок навигации между полями (Ctrl + меньше/больше)
let fk = '0'; // флаг: первая кнопка не нажата

// алгоритм обеспечивает навигацию между полями при нажатой и удерживаемой кнопки Ctrl

function keydown(e) {
    if (e.repeat == true) { return; } // в Chrome (Windows) и Chromium (Ubuntu)  e.repeat  работает по-разному!
    if ((e.target === action) && (e.code === keys[1] && fk === '1') || (e.target === action) && (e.code === keys[2]  && fk === '1')) { 
        // блокировка смены знака операции стрелками влево / вправо при нажатой первой клавише
        e.returnValue = false;
        e.cancel = true;
        if (debug) {console.log('action change cancelled'); }
        //return;
    } 
    if (debug) {console.log(e.code);}
    switch ('' + fk + e.code) {
        case '0'+keys[0]: // первая кнопка нажата и не была нажата ранее
            fk = '1';
            break;
        case '1'+keys[0]: // первая кнопка нажата и была нажата ранее
            break;        
        case '1'+keys[1]:
            if (pointer === '') { 
                pointer = 0; // первая навигация при помощи кнопок (например, после перезагрузки страницы) 
            } else if (+pointer == 0) {
                pointer = fields.length - 1;
            } else {
                pointer = +pointer - 1;
            }
            fields[pointer].focus();
            break;
        case '1'+keys[2]:          
            if (pointer  === '') { 
                pointer = 0;  // первая навигация при помощи кнопок (например, после перезагрузки страницы)
            } else if (+pointer == (fields.length - 1)) { 
                pointer = 0;
            } else {
                pointer = +pointer + 1;
            }
            fields[pointer].focus();
            break;            
    }
}

function keyup(e) {
    //if (e.repeat == true) { return; }
    switch (e.code) {
        case keys[0]:
            fk = '0';
            break;       
    }
}

function setPointer(e) { // корректировка указателя на поле при навигации, например, при помощи мыши
    fields.forEach((item, index, array) => {
        if (item == e.target) {
            pointer = index;
        } 
    });
}

// обработка нажатия/отпускания клавиш
document.addEventListener('keydown', keydown);
document.addEventListener('keyup', keyup);

// корректировка указателя на поле при навигации, например, при помощи мыши
argLeft.addEventListener('focus', setPointer);
action.addEventListener('focus', setPointer);
argRight.addEventListener('focus', setPointer);
execute.addEventListener('focus', setPointer);

execute.addEventListener('click', executePressed);