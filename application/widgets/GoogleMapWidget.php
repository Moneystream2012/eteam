<?php

/* @var $cs CClientScript  */

class GoogleMapWidget extends CWidget {

    public $title = 'Map Widget';
    public $url;
    public $data = array(array('Country' => 'RU', 'Something' => 100, 'Else' => 200));
    public $width = 400;
    public $height = 300;

    public function run() {

        echo CHtml::tag('h3', array(), $this->title);
        if (!empty($this->data)) {

            $cs = Yii::app()->clientScript;
            $cs->registerCoreScript('jquery')
                    ->registerScriptFile('https://www.google.com/jsapi')
                    ->registerScript('google_load_geochart', 'google.load("visualization", "1", {packages:["geochart"]});', CClientScript::POS_HEAD);

            if (empty($this->url)) { // static data mode
                $google_psihodelic_data_format = array();

                $i = 0;
                foreach ($this->data as $label => $values) {
                    if ($i++ == 0) { // firest iteration
                        $google_psihodelic_data_format[] = array_keys($values);
                    }
                    $google_psihodelic_data_format[] = array_values($values);
                }

                $cs->registerScript(__CLASS__ . $this->id, '
                    google.setOnLoadCallback(drawChart' . $this->id . ');
                    function drawChart' . $this->id . '(){
                        data = google.visualization.arrayToDataTable(' . json_encode($google_psihodelic_data_format) . ');
                        var options = {
                            width: ' . $this->width . ',
                            height: ' . $this->height . ',
                        };
                        var chart = new google.visualization.GeoChart(document.getElementById("map_' . $this->id . '"));
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
                                width: ' . $this->width . ',
                            };
                            var chart = new google.visualization.GeoChart(document.getElementById("map_' . $this->id . '"));
                            chart.draw(data, options);
                        }
                    },"json");
                    ', CClientScript::POS_HEAD);
            }

            echo CHtml::tag('div', array('id' => 'map_' . $this->id), __CLASS__ . ' placeholder');
        }else{
            echo CHtml::tag('i', array(), 'No data');
        }
    }

}