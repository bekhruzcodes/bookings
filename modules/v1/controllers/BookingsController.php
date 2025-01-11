<?php

namespace app\modules\v1\controllers;

use app\modules\v1\services\StatisticsService;
use Yii;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\filters\Cors;
use yii\helpers\Url;
use app\modules\v1\components\CustomBearerAuth;
use app\modules\v1\models\Bookings;
use yii\web\UnauthorizedHttpException;

class BookingsController extends ActiveController
{
    public $modelClass = Bookings::class;

    /**
     * Attach Bearer Token Authentication
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Add CORS filter
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'], // Allow all origins
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Allow-Credentials' => false, // Set to true if cookies are needed
                'Access-Control-Max-Age' => 3600, // Cache preflight request for 1 hour
                'Access-Control-Allow-Headers' => ['Authorization', 'Content-Type'],
            ],
        ];
        
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
    public function verbs(): array
    {
        return [
            'index' => ['GET'],
            'create' => ['POST'],
            'view' => ['GET'],
            'update' => ['PUT'],
            'delete' => ['DELETE'],
        ];
    }


    /**
     * Customize the data provider for the index action to include _meta and _links
     */
    public function actions(): array
    {
        $actions = parent::actions();

        // Override the data provider for index
        $actions['index']['prepareDataProvider'] = function () {
            $website = Yii::$app->user->identity;

            if (!$website || !isset($website->id)) {
                // Authentication failed
                throw new \yii\web\UnauthorizedHttpException('Authentication failed.');
            }

            $websiteId = $website->id;

            $query = Bookings::find()->andWhere(['website_id' => $websiteId]);

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
        unset($actions['options']);
        return $actions;
    }

    public function actionCreate(): array
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


    public function actionOptions(): array
    {
        Yii::$app->response->statusCode = 204;  // Indicating "No Content"
        Yii::$app->response->headers->add('Allow', 'GET, POST, PUT, DELETE, OPTIONS');  // Define allowed methods
        return [];  // Returning an empty array for body, as per convention
    }


    /**
     * @throws UnauthorizedHttpException
     */
    public function actionStatistics(): array
    {
        $website = Yii::$app->user->identity;

        if (!$website || !isset($website->id)) {
            Yii::$app->response->statusCode = 401;
            return [
                'status' => 'error',
                'message' => 'Authentication failed.',
            ];
        }

        $service = new StatisticsService($website->id);

        return [
            'status' => 'success',
            'data' => $service->getStatistics(),
        ];
    }

}
