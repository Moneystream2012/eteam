<?php

class PaymentController extends Controller {

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * Фильтры
     *
     * @return array
     */
    public function filters()
    {
        return CMap::mergeArray(
            parent::filters(),
            array(
                'rights',
                'postOnly + convert'
            )
        );
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate() 
    {
        $model = new Userpayment;        

        if (isset($_POST['Userpayment'])) {
            $model->attributes = $_POST['Userpayment'];
            if ($model->save())
                $this->redirect(array('index', 'id' => $model->id));
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Process ajax request on valutables converting
     */
    public function actionConvert()
    {
        $amount = Yii::app()->request->getPost('amount');

        $converted = 0;

        if ($amount && is_numeric($amount)) {
            $converted = Yii::app()->currency->convert(
                $amount,
                date('Y-m-d'),
                Yii::app()->project->getCurrency(),
                Userpayment::getDestinationCurrency()
            );
        }

        echo Yii::app()->currency->format('0.00', $converted);
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    /**
     * Manages all models.
     */
    public function actionIndex($user_id = '') 
    {        
        $model = new Userpayment('search');
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['Userpayment']))
            $model->attributes = $_GET['Userpayment'];

        $model->processed = 0;
        if(!empty($user_id)){
            $model->user_id = $user_id;
        }
        $this->render('index', array(
            'model' => $model,
        ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Userpayment the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = Userpayment::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param Userpayment $model the model to be validated
     */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax'] === 'userpayment-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
