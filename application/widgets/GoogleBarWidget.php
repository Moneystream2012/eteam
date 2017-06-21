<?php

/* @var $cs CClientScript  */

class GoogleBarWidget extends CWidget {

    public $title = 'Bar Widget';
    public $url;
    public $data = array(
        'AAA' => array('oil' => 300, 'gas' => 200),
        'BBB' => array('oil' => 320, 'gas' => 210),
        'CCC' => array('oil' => 260, 'gas' => 140),
    );
    public $width = 400;
    public $height;
    public $vAxis = 'xAxis';
    public $hAxis;

    public function run() {

        if (!empty($this->data)) {
            $cs = Yii::app()->clientScript;
            $cs->registerCoreScript('jquery')
                    ->registerScriptFile('https://www.google.com/jsapi')
                    ->registerScript('google_load_corechart', 'google.load("visualization", "1", {packages:["corechart"]});', CClientScript::POS_HEAD);

            if (empty($this->height)) {
                $this->height = $this->width * 0.5;
            }

            if (empty($this->url)) { // static data mode
                $google_psihodelic_data_format = array();

                $i = 0;
                foreach ($this->data as $label => $values) {
                    if ($i++ == 0) { // firest iteration
                        $google_psihodelic_data_format[] = array_merge(array($this->vAxis), array_keys($values));
                    }
                    $google_psihodelic_data_format[] = array_merge(array($label), array_values($values));
                }

                $cs->registerScript(__CLASS__ . $this->id, '
                    google.setOnLoadCallback(drawChart' . $this->id . ');
                    function drawChart' . $this->id . '(){
                        data = google.visualization.arrayToDataTable(' . json_encode($google_psihodelic_data_format) . ');
                        var options = {
                            title: "' . $this->title . '",
                            vAxis: {title: "' . $this->vAxis . '"},  hAxis: {title: "' . $this->hAxis . '"}
                        };
                        var chart = new google.visualization.BarChart(document.getElementById("bar_' . $this->id . '"));
                        chart.draw(data, options);
                    }
                    ', CClientScript::POS_HEAD);
            } else { //Has url. Ajax mode
                $cs->registerScript(__CLASS__ . $this->id, '
                    google.setOnLoadCallback(drawChart' . $this->id . ');
                    function drawChart' . $this->id . '(){
                        jQuery.get("url",function(data){
                            data = google.visualization.arrayToDataTable(data);
                            var options = {
                                title: "' . $this->title . '",
                                vAxis: {title: "' . $this->vAxis . '"},  hAxis: {title: "' . $this->hAxis . '"}
                            };
                            var chart = new google.visualization.BarChart(document.getElementById("bar_' . $this->id . '"));
                            chart.draw(data, options);
                        }
                    },"json");
                    ', CClientScript::POS_HEAD);
            }


            echo CHtml::tag('div', array('id' => 'bar_' . $this->id, 'style' => 'width: ' . $this->width . 'px; height: ' . $this->height . 'px;'), __CLASS__ . ' placeholder');
        }
    }

}