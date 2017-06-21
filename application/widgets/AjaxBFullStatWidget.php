<?php

class AjaxBFullStatWidget extends BaseStatWidget {
    public $rows;
    public $columns;
    private $_total;
    public $renderTotal = true;
    public $totalMask;
    public $allowHourDetail = false;
    public $allowYesterdayPercent = false;
    public $params;
    public function run() {

        

        echo '<div class="grid-view"><table class="items" id="' . $this->id . '">';
        if (!empty($this->columns)) {

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
    }

    private function renderBody() {
        $count = 1;
        $length = '';
        if(!$this->renderTotal){
            $length = count($this->rows);
        }
        echo '<tbody>';
        foreach ($this->rows as $row) {
            //echo '<tr class="odd" id="banner_' . $row['b_name'] . '_ '. $row['company_id'] . '">';
            $params='';
            if(!empty($this->params)){
                $params = '\' , \'' . implode(',' , $this->params);
            }
            foreach ($this->columns as $key => $name) {
                if ($key == 'b_name' || $key == 'company_id') {
                    //echo '<td><nobr>' . $row[$key] . '</nobr></td>';
                }elseif($key=='company_name'){
                    //echo '<td><a href="'.Yii::app()->getBaseUrl(true).'/ru/administration/bCompany/view/'.$row['company_id'].'">'. $row['company_name'] .'</a></td>';;
                } elseif($key=='paused') {
                    if($row['paused']==0 ) {
                        //echo '<td align="center"><img title="Pause" class="play" id="img_'.$row['banner_id'].'" src="/images/pause.png" alt="Pause" onClick="ChangeState('.$row['banner_id'].')"></td>';
                    }
                    else {
                        //echo '<td align="center"><img title="Play" class="pause" id="img_'.$row['banner_id'].'" src="/images/play.png" alt="Play" onClick="ChangeState('.$row['banner_id'].')"></td>';
                    }
                } else {
                    $value =
                        isset($this->columnFormats[$key]['convertCurrency'])
                        && $this->columnFormats[$key]['convertCurrency']
                        && is_numeric($row[$key])
                        ? Yii::app()->currency->convert($row[$key], date('Y-m-d'))
                        : $row[$key];
                    //echo '<td>' . $row[$key] . '</td>';
                    $this->_total[$key] += $value;                
                }
            }
            if(!$this->renderTotal){
                $count++;
            }
            //if($row['paused']) echo '';
            //else echo '';
            //echo '</tr>';
        }
        $this->_total['CTR']=0.00;
        $this->_total['CPC']=0.00;
        if($this->_total['banner_show']>0 && $this->_total['banner_click_unique']>0){
            $this->_total['CTR']=($this->_total['banner_click_unique']/$this->_total['banner_show']) * 100;        
        }
        if($this->_total['advertizer_price']>0 && $this->_total['banner_click_unique']>0){
            $this->_total['CPC']=$this->_total['advertizer_price']/$this->_total['banner_click_unique'];        
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