async function post(ctrl, mthd, vals = null) {
    let response = await fetch('#', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: ('c='+encodeURIComponent(ctrl)+'&a='+encodeURIComponent(mthd)+'&v='+encodeURIComponent(vals))
    });

    if (response.ok) { // если HTTP-статус в диапазоне 200-299
        // получаем тело ответа
        let json = await response.text();
        console.log(json);       
        processResponse(json);
    } else {
        //errorSignal();
    }
}

function processResponse(response) {
    let loginFrame = document.getElementById('loginFrame');
    if (response == 'nok') {
        loginFrame.style.color = 'red';
        loginFrame.innerText = 'Неверные имя пользователя или пароль';
    } else if (response == 'nok3') {
        loginFrame.style.color = 'red';
        loginFrame.innerText = 'Превышено максимальное кол-во неудачных попыток входа. Следующая попытка будет возможна через 15 минут.';        
    } else if (response == 'nok4') {
        loginFrame.style.color = 'red';
        loginFrame.innerText = 'Период блокировки попыток входа еще не истек.';        
    } 
    
    setTimeout(() => { // редирект через 1 секунду
        window.location.reload();
    }, 1000);
}

document.getElementById('submit').addEventListener('click', (e) => { // кнопка OK
    let userName = document.getElementById('user').value;
    let pwd = document.getElementById('pwd').value;    
    post('auth', 'login', JSON.stringify({'userName' : userName, 'pwd' : pwd}));      
});