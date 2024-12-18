<?php

namespace app\modules\v1\controllers;

use yii\rest\ActiveController;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use app\modules\v1\components\CustomBearerAuth;
use app\modules\v1\models\Bookings;

class BookingsController extends ActiveController
{
    public $modelClass = Bookings::class;
    /**
     * Attach Bearer Token Authentication
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // Force JSON responses
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;

        // Use Custom Bearer Authentication
        $behaviors['authenticator'] = [
            'class' => CustomBearerAuth::class,
        ];

        return $behaviors;
    }

    /**
     * Map actions to allowed HTTP methods
     */
    public function verbs()
    {
        return [
            'index' => ['GET'],
            'create' => ['POST'],
            'view' => ['GET'],
            'update' => ['PUT', 'PATCH'],
            'delete' => ['DELETE'],
        ];
    }


}
