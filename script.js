function displayError(comment, element, result) { // отмена изменения значения. Очистка поля результата. Цветовая индикация
    comment.style.color = 'orange';
    element.style.color = 'orange';

    result.innerText = '';
    
    setTimeout(() => {
        comment.style.color = '';
        element.style.color = '';
        element.value = element.dataset.previous;
    }, 2000);
}

function executePressed() { // вызывается при нажатии кнопки =
    console.log('execute pressed');
    let comment = document.getElementById('comment');
    let argLeft = document.getElementById('argLeft');
    let argRight = document.getElementById('argRight');
    let action = document.getElementById('action');
    let result = document.getElementById('result');
    let interimResult; // промежуточный результат до вывода на экран
    
    if ((checkValue(argLeft, comment, result) + checkValue(argRight, comment, result, (action.value == 'division') ? true : false)) != 0) {
        return; // одно из значений некорректное. Выход.
    }
    
    console.log(action.value);
    
    switch (action.value) {
        case 'plus':
            interimResult = (+argLeft.value) + (+argRight.value);
            break;
        case 'minus':
            interimResult = argLeft.value - argRight.value;
            break;
        case 'mult':
            interimResult = argLeft.value * argRight.value;
            break;
        case 'division':
            interimResult = argLeft.value / argRight.value;
            break;
    }
    
    result.innerText = Math.floor(interimResult * 1000000000) / 1000000000; // устранение неточности вычислений. См. https://learn.javascript.ru/number#неточные-вычисления.
    
}

function checkValue(element, comment, result, division = false) {
    // если введенное значение некорректное (или деление на 0), то изменение значения отменяется. Дополнительно очищается поле результата. Если корректное - предыдущее значение записывается в data-previous соответствующего element
    
    let readValue = '' + element.value; // преобразование в string
    
    // кооментарий к регулярному выражению. Допустимые значения:
    // ноль
    // дробное, где целая часть = 0: знак (опционально), ноль, точка, любое кол-во цифр, не ноль в конце
    // дробное, где целая часть != 0: знак (опционально), не ноль, любое кол-во цифр, далее опцинальный блок (целиком): точка, любое кол-во цифр, не ноль в конце
    
    console.log(readValue);
    
    if (readValue.match(/^0$|^-{0,1}(0\.)[0-9]{0,}[1-9]{1}$|^-{0,1}[1-9][0-9]{0,}(\.[0-9]{0,}[1-9]{1}){0,1}$/) == null) {
        console.log("wrong value");
        displayError(comment, element, result); // отмена изменения значения. Очистка поля результата. Цветовая индикация
        return -1; // не-ok
    }
    
    if ((division == true) && (+readValue == 0)) {
        console.log("division by 0");
        displayError(comment, element, result);
        return -1; // не-ok
    } 
    
    element.value = readValue; // во избежание отображения точки на конце. Причина: если в input в chrome (в других браузерах не тестировалось) в конце значения стоит точка (т.е. нет дробной части), то значение input возвращается без точки, регулярным выражение не "отлавливается", при этом точка в input на экране отображается. Т.е. нужно записать в value значение уже без точки  
    element.dataset.previous = readValue;
    
    return 0; // все ok
}

document.getElementById('execute').addEventListener('click', executePressed);
