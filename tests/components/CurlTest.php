<?php

Yii::import('application.extensions.curl.Curl');
/**
 * Class CurlTest created for mock Curl component in our system
 */
class CurlTest extends Curl implements IApplicationComponent
{
    /**
     * Interface function
     *
     * @return bool
     */
    public function getIsInitialized()
    {
        return false;
    }
}