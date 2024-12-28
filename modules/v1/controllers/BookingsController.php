<?php

namespace app\modules\v1\controllers;

use yii\rest\ActiveController;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\helpers\Url;
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

    /**
     * Customize the data provider for the index action to include _meta and _links
     */
    public function actions()
    {
        $actions = parent::actions();

        // Override the data provider for index
        $actions['index']['prepareDataProvider'] = function () {
            $query = Bookings::find();

            // Pagination (automatically handles `per-page` and `page` from query parameters)
            $pagination = new Pagination([
                'totalCount' => $query->count(),
                'pageSize' => \Yii::$app->request->get('per-page', 10), // Default to 10 if not provided
                'pageSizeLimit' => [1, 100], // Set limits for `per-page`
            ]);

            // Data provider
            $dataProvider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => $pagination,
            ]);

            $models = $dataProvider->getModels();

            // Build response
            return [
                '_meta' => [
                    'totalCount' => $pagination->totalCount,
                    'pageCount' => $pagination->getPageCount(),
                    'currentPage' => $pagination->getPage() + 1,
                    'perPage' => $pagination->getPageSize(),
                ],
                '_links' => [
                    'self' => Url::to(['index', 'page' => $pagination->getPage() + 1, 'per-page' => $pagination->getPageSize()], true),
                    'next' => $pagination->getPage() + 1 < $pagination->getPageCount() ? Url::to(['index', 'page' => $pagination->getPage() + 2, 'per-page' => $pagination->getPageSize()], true) : null,
                    'prev' => $pagination->getPage() > 0 ? Url::to(['index', 'page' => $pagination->getPage(), 'per-page' => $pagination->getPageSize()], true) : null,
                ],
                'data' => $models,
            ];
        };

        return $actions;
    }
}
