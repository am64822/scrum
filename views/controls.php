<div class='row first'>  
    <div class='circleBlow' id='circleBlow'></div>
    <div class='circleError' id='error'></div>
    <input type="checkbox" id='chbx' style='display:none' <?php if($no_mute == 1) {echo ('checked');} ?>>
    <div id='cpuTemp'></div>
</div>
<div class='row second'>  
    <input type="number" id="qtyR" min="1" max="1000" value="<?= $times ?>" data-preVal="<?= $times ?>" class='blowsNumber'>        

    <input type="range" min="0" max="100" id='volumeL' value='<?= $vol_L ?>' data-preVal="<?= $vol_L ?>" step='5' class='soundVolume'>
    <div id='volumeLval' class='soundVolumeIndicator'><?= $vol_L ?></div>
    <select class="select" id='selectR'>
        <option value="1" <?php if($sound == 1) {echo ('selected');} ?>>Перф.дл.</option>
        <option value="2" <?php if($sound == 2) {echo ('selected');} ?>>Перф.кор.</option>
        <option value="3" <?php if($sound == 3) {echo ('selected');} ?>>Стук.дл.</option>
        <option value="4" <?php if($sound == 4) {echo ('selected');} ?>>Стук.кор.</option>
        <option value="5" <?php if($sound == 5) {echo ('selected');} ?>>Стук.3</option>
        <option value="7" <?php if($sound == 7) {echo ('selected');} ?>>Лай од.</option>
        <option value="8" <?php if($sound == 8) {echo ('selected');} ?>>Лай од.сер.</option>
        <option value="10" <?php if($sound == 10) {echo ('selected');} ?>>Лай сер.</option>
        <option value="20" <?php if($sound == 20) {echo ('selected');} ?>>Ремонт</option>
        <option value="15" <?php if($sound == 15) {echo ('selected');} ?>>Голос</option>
        <option value="30" <?php if($sound == 30) {echo ('selected');} ?>>Унитаз</option>
    </select>


</div>
<div class='row third'>
    <div class='info_div_left' id='times_left'>0</div>       
    <input type="range" min="0" max="100" id='volumeR' value='<?= $vol_R ?>' data-preVal="<?= $vol_R ?>" step='5' class='soundVolume'>
    <div id='volumeRval' class='soundVolumeIndicator'><?= $vol_R ?></div>
    <button type='button' id='leftButton' class='soundButton' disabled></button>
    <div class="separator"></div>
    <div class='info_div_left' id='next_in_sec'>0</div>
    <div class='delays_lable_left'>Пауза, секунд&nbsp;</div>
    <input type="number" id="delMinR" min="0" max="9999" value="<?= $delay_min ?>" data-preVal="<?= $delay_min ?>" class='delays'><div>-</div>
    <input type="number" id="delMaxR" min="1" max="9999" value="<?= $delay_max ?>" data-preVal="<?= $delay_max ?>" class='delays'>
    <div class="separator"></div>
    <button type='button' id='rightButton' class='soundButton ml-a max_w_1024_mtb-a'>Прав.+Лев.</button>
</div>       
<div class='row last'>  
    <button type='button' id='shutdown'>Выкл.RPi</button>
    <button type='button' id='reboot'>Перезагр.RPi</button>
    <div class="separator"></div>
    <button type='button' id='monitor' style="background-color: <?php if ($monitor == 0) {echo ("''");} elseif ($monitor == 1)  {echo ('Thistle');} ?>">Монитор</button>
    <button type='button' id='logout' class='ml-a'>Выйти</button>
    <div class="separator_bottom"></div>
</div>





