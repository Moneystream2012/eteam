<?php

class AjaxBStatWidget extends BaseStatWidget {
    public $rows;
    public $columns;
    private $_total;
    public $renderTotal = true;
    public $totalMask;
    public $allowHourDetail = false;
    public $allowYesterdayPercent = false;
    public $params;
    public function run() {
        if ($this->allowHourDetail) {
            $params='';
            if(!empty($this->params)){
                $params = implode('//' , $this->params);
            }
            Yii::app()->clientScript->registerScript('StatWidgetHourDetail', "
            function hour_detail(ts){

                $('.hour_details_'+ts).remove();

                $('.hour_id_'+ts).after('<tr id=\"hour_details_'+ts+'\"><td colspan=\"6\">Loading <img src=\"/images/loading.gif\"/></td></tr>');
                $.get('" . CHtml::normalizeUrl(array('HourDetail')) . "?ts='+ts+'&params=".$params."',function(res){
                    $('#hour_details_'+ts).after(res);
                    $('#hour_details_'+ts).remove();
                    });
            }
            function hour_remove(ts){
                $('.hour_details_'+ts).remove();
            }
            ", CClientScript::POS_HEAD);
        }
        Yii::app()->clientScript->registerScript('StatWidget', "
            $(document).ready(function(){
                jQuery('.removePercent').click(function(e){
                    e.preventDefault();
                    if(jQuery(this).hasClass('visible')){
                        jQuery(this).removeClass('visible');
                        jQuery(this).text('" . Yii::t('messages', 'Показать проценты') . "');
                        jQuery('.statPercent').hide();
                    }else{
                        jQuery(this).addClass('visible');
                        jQuery('.statPercent').show();
                        jQuery(this).text('" . Yii::t('messages', 'Убрать проценты') . "');
                    }
                });
            });
            var dt_loaded = {};
            function toggle_hours(dt , params){
                if(dt_loaded[dt]){
                    var i=0;
                    $('.hours_'+dt).toggle('slow'); 
                    while(i<24){
                        $('.banners_'+dt+'_'+('0'+i).slice(-2)).hide();
                        i++;
                    }                    
                }else{
                    $('#hours_'+dt).remove();

                    $('#date_'+dt).after('<tr id=\"hours_'+dt+'\"><td colspan=\"4\">Loading <img src=\"/images/loading.gif\"/></td></tr>');
                    $.get('" . CHtml::normalizeUrl(array('DateDetail')) . "?dt='+dt+'&params='+params,function(res){
                        $('#hours_'+dt).after(res);
                        $('#hours_'+dt).remove();
                        dt_loaded[dt]=true;
                        $('.hours_'+dt).toggle('slow');
                        if(jQuery('.removePercent').hasClass('visible') == false){
                            jQuery('.statPercent').css('display','none');
                        }
                        });
                }
            }
            
            function toggle_banners(dt,hr, params){
                hr=hr.substr(0,2);
                if(dt_loaded[dt+'_'+hr]){
                    $('.banners_'+dt+'_'+hr).toggle('slow');
                }else{
                    $('#banners_'+dt+'_'+hr).remove();

                    $('#hour_'+dt+'_'+hr).after('<tr id=\"banners_'+dt+'_'+hr+'\"><td colspan=\"4\">Loading <img src=\"/images/loading.gif\"/></td></tr>');
                    $.get('" . CHtml::normalizeUrl(array('HourDetail')) . "?dt='+dt+'&hr='+hr+'&params='+params,function(res){
                        $('#banners_'+dt+'_'+hr).after(res);
                        $('#banners_'+dt+'_'+hr).remove();
                        dt_loaded[dt+'_'+hr]=true;
                        $('.banners_'+dt+'_'+hr).toggle('slow');
                        if(jQuery('.removePercent').hasClass('visible') == false){
                            jQuery('.statPercent').css('display','none');
                        }
                        });
                }
            }

", CClientScript::POS_HEAD);

        echo '<div class="grid-view"><table class="items" id="' . $this->id . '">';
        if (!empty($this->columns)) {

            $this->columns = array('dt' => Yii::t('messages', 'Дата')) + $this->columns;
            $this->renderHead();

            if (!empty($this->rows)) {
                $this->renderBody();
            } else {
                $this->renderEmpty(count($this->columns));
            }
        } else {
            $this->renderEmpty();
        }
        echo '</table></div>';
        if($this->allowYesterdayPercent)
            echo Chtml::link(Yii::t('messages', 'Убрать проценты') , '#' , array('class' => 'removePercent visible'));
    }

    private function renderBody() {
        $count = 1;
        $length = '';
        if(!$this->renderTotal){
            $length = count($this->rows);
        }
        echo '<tbody>';
        foreach ($this->rows as $row) {
            echo '<tr class="odd" id="date_' . $row['dt'] . '">';
            $params='';
            if(!empty($this->params)){
                $params = '\' , \'' . implode(',' , $this->params);
            }
            foreach ($this->columns as $key => $name) {
                if ($key == 'dt') {
                    echo '<td><nobr><a href="javascript:void(0);" onclick="toggle_hours(\'' . $row['dt'] . $params . '\')">' . $row['dt'] . '</a></nobr></td>';
                } else {
                    if ($this->allowYesterdayPercent) {
                        $color = ($row[$key . '_percent'] >= 0) ? 'green' : 'red';
                        $percent = '<div class="statPercent st-' . $color . '">' .
                            Yii::app()->numberFormatter->format('0.00', $row[$key . '_percent']) .
                            '</div>';
                    } else {
                        $percent = '';
                    }
                    $value = isset($row[$key]) ? $row[$key] : null;
                    if ($value !== null) {
                        $value =
                            isset($this->columnFormats[$key]['convertCurrency'])
                            && $this->columnFormats[$key]['convertCurrency']
                            && is_numeric($value)
                            ? Yii::app()->currency->convert($value, $row['dt'])
                            : $value;
                        $displayValue =
                            isset($this->columnFormats[$key]['numberFormat'])
                            && $this->columnFormats[$key]['numberFormat']
                            && is_numeric($value)
                            ? Yii::app()->numberFormatter->format($this->columnFormats[$key]['numberFormat'], $value)
                            : $value;
                        echo '<td>' . $displayValue . $percent . '</td>';
                    } else {
                        echo '<td>&nbsp;</td>';
                    }
                    $this->_total[$key] += $value;
                }
            }
            if(!$this->renderTotal){
                $count++;
            }
            echo '</tr>';
        }
        
        $this->_total['CTR']=0.00;
        $this->_total['CPC']=0.00;
        if($this->_total['banner_show']>0 && $this->_total['banner_click_unique']>0){
            $this->_total['CTR']=round(($this->_total['banner_click_unique']/$this->_total['banner_show'])*100,2);        
        }
        if($this->_total['advertizer_price']>0 && $this->_total['banner_click_unique']>0){
            $this->_total['CPC']=round(($this->_total['advertizer_price']/$this->_total['banner_click_unique']),2);        
        }
        $this->renderTotal();

        echo '</tbody>';
    }
    
    private function renderTotal() {
        if($this->renderTotal){
            array_shift($this->columns);
            echo '<tr class="odd">';
            echo '<td>'. Yii::t('messages','Итого') .':</td>';
            foreach ($this->columns as $key => $name) {
                $val = 'N/A';
                if (empty($this->totalMask[$key])) {
                    $val = $this->_total[$key];
                } elseif ($this->totalMask[$key] == 'straight') { // rollback /2 division
                    $val = $this->_total[$key];
                } elseif ($this->totalMask[$key] == 'average') {
                    $val = $this->_total[$key] / count($this->rows);
                }elseif ($this->totalMask[$key] == 'N/A'){
                    $val = 'N/A';
                } else {
                    $multiplier = empty($this->totalMask[$key]['multiplier']) ? 100 : $this->totalMask[$key]['multiplier'];
                    $val = empty($this->_total[$this->totalMask[$key]['bottom']]) ? '∞' : $this->_total[$this->totalMask[$key]['top']] / $this->_total[$this->totalMask[$key]['bottom']] * $multiplier;
                }
                $val =
                    isset($this->columnFormats[$key]['numberFormat'])
                    && $this->columnFormats[$key]['numberFormat']
                    && is_numeric($val)
                    ? Yii::app()->numberFormatter->format($this->columnFormats[$key]['numberFormat'], $val)
                    : $val;
                echo '<td>' . $val . '</td>';
            }
            echo '</tr>';
        }
    }

    private function renderEmpty($n = 0) {
        echo '<tbody><tr>';
        echo '<td ' . (empty($n) ? '' : 'colspan="' . $n . '"') . '>' . Yii::t('messages', 'Нет результатов') . '</td>';
        echo '</tr></tbody>';
    }

    private function renderHead() {
        echo '<thead><tr>';
        foreach ($this->columns as $key => $col) {
            echo '<th>' . $col . '</th>';
            $this->_total[$key] = 0;
        }
        echo '</tr></thead>';
    }

}