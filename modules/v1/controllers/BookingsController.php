<?php

namespace app\modules\v1\controllers;

use app\modules\v1\components\CustomBearerAuth;
use yii\rest\Controller;
use yii\web\Response;


/**
 * Custom Bearer Token Authentication Without Yii::$app->user
 */

class BookingsController extends Controller
{
    /**
     * Attach Bearer Authentication
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
     * Example action that accesses the authenticated Website model
     */
    public function actionIndex()
    {
        /** @var CustomBearerAuth $authenticator */
        $authenticator = $this->getBehavior('authenticator');
        $website = $authenticator->website;

        return [
            'message' => 'Access granted!',
            'website_name' => $website->name,
            'website_email' => $website->email,
        ];
    }
}
