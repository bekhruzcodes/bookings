<?php

namespace app\modules\v1\controllers;

use Yii;
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

        unset($actions['create']);
        return $actions;
    }

    public function actionCreate()
    {
        // Get the currently authenticated website
        $website = Yii::$app->user->identity;

        if ($website && isset($website->id)) {
            $model = new Bookings();

            if ($model->load(Yii::$app->request->post(), '')) {
                $model->website_id = $website->id;

                if ($model->save()) {
                    // Return success with status 201 Created
                    Yii::$app->response->statusCode = 201;
                    return [
                        'status' => 'success',
                        'data' => $model,
                    ];
                } else {
                    // Validation failed, return error with status 422 Unprocessable Entity
                    Yii::$app->response->statusCode = 422;
                    return [
                        'status' => 'error',
                        'errors' => $model->errors,
                    ];
                }
            }

            // Input data invalid, return error with status 400 Bad Request
            Yii::$app->response->statusCode = 400;
            return [
                'status' => 'error',
                'message' => 'Invalid input data.',
            ];
        } else {
            // Authentication failed, return error with status 401 Unauthorized
            Yii::$app->response->statusCode = 401;
            return [
                'status' => 'error',
                'message' => 'Authentication failed or website_id is missing.',
            ];
        }
    }


}
