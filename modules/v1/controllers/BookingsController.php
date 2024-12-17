<?php

namespace app\modules\v1\controllers;

class BookingsController extends \yii\rest\Controller
{
    public function actionIndex()
    {
        return ["name" => "Rock"];
    }

}
