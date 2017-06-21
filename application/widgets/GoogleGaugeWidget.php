<?php

/* @var $cs CClientScript  */

class GoogleGaugeWidget extends CWidget {

    public $title = 'Gauge Widget';
    public $url;
    public $data = array('CPU' => 55);
    public $width = 400;
    public $height;
    public $gaugesInRow = 2;

    public function run() {

        $cs = Yii::app()->clientScript;
        $cs->registerCoreScript('jquery')
                ->registerScriptFile('https://www.google.com/jsapi')
                ->registerScript('google_load_gauge', 'google.load("visualization", "1", {packages:["gauge"]});', CClientScript::POS_HEAD);

        if (empty($this->url)) { // static data mode
            if (empty($this->height)) {
                $this->height = (((count($this->data) - 1) / $this->gaugesInRow) + 1) * $this->width / $this->gaugesInRow;
            }

            $google_psihodelic_data_format = array(array('Label', 'Value'));

            foreach ($this->data as $label => $value)
                $google_psihodelic_data_format[] = array($label, $value);

            $cs->registerScript(__CLASS__ . $this->id, '
                    google.setOnLoadCallback(drawChart' . $this->id . ');
                    function drawChart' . $this->id . '(){
                        data = google.visualization.arrayToDataTable(' . json_encode($google_psihodelic_data_format) . ');
                        var options = {
                            title: "' . $this->title . '",
                            width: ' . $this->width . ', height: ' . $this->height . ',
                            redFrom: 90, redTo: 100,
                            yellowFrom:75, yellowTo: 90,
                            minorTicks: 5
                        };
                        var chart = new google.visualization.Gauge(document.getElementById("gauge_' . $this->id . '"));
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
                                width: ' . $this->width . ', height: ' . $this->height . ',
                                redFrom: 90, redTo: 100,
                                yellowFrom:75, yellowTo: 90,
                                minorTicks: 5
                            };
                            var chart = new google.visualization.Gauge(document.getElementById("gauge_' . $this->id . '"));
                            chart.draw(data, options);
                        }
                    },"json");
                    ', CClientScript::POS_HEAD);
        }


        echo CHtml::tag('h3', array(), $this->title);
        echo CHtml::tag('div', array('id' => 'gauge_' . $this->id), __CLASS__ . ' placeholder');
    }

}