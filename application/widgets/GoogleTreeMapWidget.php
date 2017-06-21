<?php

/* @var $cs CClientScript  */

class GoogleTreeMapWidget extends CWidget {

    public $title = 'TreeMap Widget';
    public $url;
    public $data = array(
        'Root' => array('parent'=>null,'size'=>0,'color'=>0),
        'Something' => array('parent'=>'Root','size'=>50,'color'=>50),
        'Otherthing' => array('parent'=>'Root','size'=>50,'color'=>50),
        );
    public $width = 400;
    public $height = 300;

    public function run() {

        $cs = Yii::app()->clientScript;
        $cs->registerCoreScript('jquery')
                ->registerScriptFile('https://www.google.com/jsapi')
                ->registerScript('google_load_treemap', 'google.load("visualization", "1", {packages:["treemap"]});', CClientScript::POS_HEAD);

        if (empty($this->url)) { // static data mode

            $google_psihodelic_data_format = array(array('Name', 'Parent', 'Size', 'Color'));

            foreach ($this->data as $label => $value)
                $google_psihodelic_data_format[] = array((string)$label, $value['parent'], $value['size'], $value['color'],);

            $cs->registerScript(__CLASS__ . $this->id, '
                    google.setOnLoadCallback(drawChart' . $this->id . ');
                    function drawChart' . $this->id . '(){
                        data = google.visualization.arrayToDataTable(' . json_encode($google_psihodelic_data_format) . ');

                        // Create and draw the visualization.
                        var tree = new google.visualization.TreeMap(document.getElementById("gauge_' . $this->id . '"));
                        tree.draw(data, {
                            minColor: "#0d0",
                            midColor: "#fd0",
                            maxColor: "#f00",
                            headerHeight: 15,
                            fontColor: "black",
                            showScale: true});
                    }
                    ', CClientScript::POS_HEAD);
        } else { //Has url. Ajax mode
            $cs->registerScript(__CLASS__ . $this->id, '
                    google.setOnLoadCallback(drawChart' . $this->id . ');
                    function drawChart' . $this->id . '(){
                        jQuery.get("url",function(data){
                            data = google.visualization.arrayToDataTable(data);
                        }
                    },"json");
                    ', CClientScript::POS_HEAD);
        }


        echo CHtml::tag('h3', array(), $this->title);
        echo CHtml::tag('div', array('id' => 'gauge_' . $this->id, 'style'=>'width: '.$this->width.'px; height: '.$this->height.'px;'), __CLASS__ . ' placeholder');
    }

}