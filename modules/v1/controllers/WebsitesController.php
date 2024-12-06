<?php

namespace app\modules\v1\controllers;

class WebsitesController extends \yii\rest\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
