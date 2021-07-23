function blowSignal() {
    let circleBlow = document.getElementById('circleBlow');
    if (circleBlow.style.backgroundColor.toLowerCase == "green") {
        return;
    }
    circleBlow.style.backgroundColor = "green";
    setTimeout(() => { // прячем через 0.5 секунды
        circleBlow.style.backgroundColor = "transparent";
    }, 500);
}


function errorSignal() {
    let error = document.getElementById('error');
    if (error.style.backgroundColor.toLowerCase == "red") {
        return;
    }        
    error.style.backgroundColor = "red";
    setTimeout(() => { // прячем через три секунды
        error.style.backgroundColor = "transparent";
    }, 3000);
}

async function post(ctrl, mthd, vals = null, funct = null) {
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
        if (json != 'ok') {errorSignal();}
        if (funct !== null) {
            funct();
        }
    } else {
        errorSignal();
    }
}


// правый
<?php if ($running == 1) { ?> // цвет кнопки при выполнении
    document.getElementById('rightButton').style.backgroundColor = 'Thistle';
<?php } ?>

document.getElementById('rightButton').addEventListener('click', (e) => { // кнопка ручн.
    let rightButton = e.target;
    if (rightButton.style.backgroundColor.toLowerCase() == 'thistle') { // цвет - thistle, т.е. работает
        post('settings', 'change', JSON.stringify({'run' : '0'})); // отправить команду на останов
    } else { // цвет - сервый, т.е. выкл.
        document.getElementById('times_left').innerHTML = document.getElementById('qtyR').value;
        post('program', 'single');
    }      
});


function checkValue(obj) { // false - ошибка (не целое, не в диап. мин - макс ), true - ok
    if (obj.value == '') {
        return false;
    }        
    if (Number.isInteger(obj.value)) {
        return false;
    }
    if (obj.value < +obj.min || obj.value > +obj.max ) {
        return false;
    }        
    return true;
} 

let qtyR = document.getElementById('qtyR');
let qtyReventListener = function(e) { // изм.кол-ва циклов
        if (checkValue(e.target) == true) {
            e.target.dataset['preval'] = e.target.value;
            post('settings', 'change', JSON.stringify({'times' : e.target.value}));
        } else {
            e.target.value = e.target.dataset['preval'];
        }
    };
qtyR.addEventListener('change', qtyReventListener);


let delMinR = document.getElementById('delMinR');
let delMinReventListener = function(e) { // изм.задержки мин.
    let delMaxR = document.getElementById('delMaxR').value;
    if (checkValue(e.target) == true) {
        if (+e.target.value > +delMaxR) { // мин.задержка должна быть меньше или равна максимальной
            e.target.value = document.getElementById('delMaxR').value;
        }
        e.target.dataset['preval'] = e.target.value;
        post('settings', 'change', JSON.stringify({'delay_min' : e.target.value}));
    } else {
        e.target.value = e.target.dataset['preval'];                                       
    }
};    
delMinR.addEventListener('change', delMinReventListener);

let delMaxR = document.getElementById('delMaxR');
let delMaxReventListener = function(e) { // изм.задержки макс.
    let delMinR = document.getElementById('delMinR').value;
    if (checkValue(e.target) == true) {
        if (+e.target.value < +delMinR) { // макс.задержка должна быть больше или равна минимальной
            e.target.value = document.getElementById('delMinR').value;
        }
        e.target.dataset['preval'] = e.target.value;
        post('settings', 'change', JSON.stringify({'delay_max' : e.target.value}));
    } else {
        e.target.value = e.target.dataset['preval'];                                       
    }
};     
delMaxR.addEventListener('change', delMaxReventListener);


// прг.
let selectR = document.getElementById('selectR');
let selectReventListener = function(e) {
    post('settings', 'change', JSON.stringify({'sound' : e.target.value}));
};    
selectR.addEventListener('change', selectReventListener);

// громкость
let volumeL = document.getElementById('volumeL');
let volumeLeventListener = function(e) {
    let volL =  document.getElementById('volumeL').value;
    let volR =  document.getElementById('volumeR').value;
    document.getElementById('volumeLval').innerText = volL;
    post('settings', 'change', JSON.stringify({'vol_L' : volL, 'vol_R' : volR}));
};
volumeL.addEventListener('change', volumeLeventListener);

let volumeR = document.getElementById('volumeR');
let volumeReventListener = function(e) {
    let volL =  document.getElementById('volumeL').value;
    let volR =  document.getElementById('volumeR').value;
    document.getElementById('volumeRval').innerText = volR;
    post('settings', 'change', JSON.stringify({'vol_L' : volL, 'vol_R' : volR}));
};    
volumeR.addEventListener('change', volumeReventListener);

// выключение PRi
document.getElementById('shutdown').addEventListener('click', (e) => {
    post('power', 'shutdown_reboot', 'shutdown');
}); 

// перезагрузка PRi
document.getElementById('reboot').addEventListener('click', (e) => {
    post('power', 'shutdown_reboot', 'reboot');
}); 

// выход из системы (logout)
document.getElementById('logout').addEventListener('click', (e) => {
    let action = function() {
        window.location.reload();
    } 
    post('auth', 'logout', null, action);
});

// WebSocket
let host = 'wss://<?= $myIP ?>/wss';
let socket = new WebSocket(host);

socket.onopen = function(e) {
    //console.log("opened");
    //document.getElementById('root').innerHTML = e.data;
};        

socket.onclose = function(e) {
    //console.log("closed");
    //document.getElementById('root').innerHTML = e.data;
};

socket.onmessage = function(e) {
    let stats = JSON.parse(e.data);

    //console.log(stats);   
    
    document.getElementById('cpuTemp').innerHTML = 'CPU: ' + stats.cpuTemp + '℃';
    
    
    let qtyR = document.getElementById('qtyR'); // кол-во R
    if (+qtyR.value != +stats.times) {
        //console.log(document.hasFocus());
        if ((document.activeElement != qtyR) || (document.hasFocus() != true)) {
            qtyR.removeEventListener('change', qtyReventListener);
            qtyR.value = stats.times;
            qtyR.dataset['preval'] = stats.times;
            qtyR.addEventListener('change', qtyReventListener);
        }
    }    
    
    let volumeL = document.getElementById('volumeL'); // громкость L
    let volumeLval = document.getElementById('volumeLval'); // индикатор громкость L
    if (+volumeL.value != +stats.vol_L) {
        //console.log(document.hasFocus());
        if ((document.activeElement != volumeL) || (document.hasFocus() != true)) {
            volumeL.removeEventListener('change', volumeLeventListener);
            volumeL.value = stats.vol_L;
            volumeL.dataset['preval'] = stats.vol_L;
            volumeL.addEventListener('change', volumeLeventListener);
        }
        volumeLval.innerHTML = stats.vol_L;
    }     
    
    let volumeR = document.getElementById('volumeR'); // громкость R
    let volumeRval = document.getElementById('volumeRval'); // индикатор громкость R
    if (+volumeR.value != +stats.vol_R) {
        //console.log(document.hasFocus());
        if ((document.activeElement != volumeR) || (document.hasFocus() != true)) {
            volumeR.removeEventListener('change', volumeReventListener);
            volumeR.value = stats.vol_R;
            volumeR.dataset['preval'] = stats.vol_R;
            volumeR.addEventListener('change', volumeReventListener);
        }
        volumeRval.innerHTML = stats.vol_R;
    }    
    
    let selectR = document.getElementById('selectR'); // программа R
    if (+selectR.value != +stats.sound) {
        //console.log(document.hasFocus());
        if ((document.activeElement != selectR) || (document.hasFocus() != true)) {
            selectR.removeEventListener('change', selectReventListener);
            selectR.value = stats.sound;
            //selectR.dataset['preval'] = stats.delay_min;
            selectR.addEventListener('change', selectReventListener);
        }
        
    }    
    
    let delMinR = document.getElementById('delMinR'); // задержка R мин.
    if (+delMinR.value != +stats.delay_min) {
        //console.log(document.hasFocus());
        if ((document.activeElement != delMinR) || (document.hasFocus() != true)) {
            delMinR.removeEventListener('change', delMinReventListener);
            delMinR.value = stats.delay_min;
            delMinR.dataset['preval'] = stats.delay_min;
            delMinR.addEventListener('change', delMinReventListener);
        }
        
    }    
    
    let delMaxR = document.getElementById('delMaxR'); // задержка R макс
    if (+delMaxR.value != +stats.delay_max) {
        //console.log(document.hasFocus());
        if ((document.activeElement != delMaxR) || (document.hasFocus() != true)) {
            delMaxR.removeEventListener('change', delMaxReventListener);
            delMaxR.value = stats.delay_max;
            delMaxR.dataset['preval'] = stats.delay_max;
            delMaxR.addEventListener('change', delMaxReventListener);
        }
    }
    
    let times_left = document.getElementById('times_left');
    let next_in_sec = document.getElementById('next_in_sec');
    let rightButton = document.getElementById('rightButton');
    let leftButton = document.getElementById('leftButton');


    
    times_left.innerHTML = stats.times_left;
    next_in_sec.innerHTML = stats.next_in_sec;
    if (stats.running == 0) {
        rightButton.style.backgroundColor = '';
    } else if (stats.running == 1) {
        rightButton.style.backgroundColor = 'Thistle';
    }

    if (stats.blow != 0) {
        blowSignal();
    }
    //console.log(e.data);
    //socket.send("REPLY");
    
}


