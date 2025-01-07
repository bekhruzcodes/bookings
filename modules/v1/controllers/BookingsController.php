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
    public function behaviors(): array
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


    public function actionStatistics(): array
    {
        // Get the currently authenticated website
        $website = Yii::$app->user->identity;

        if (!$website || !isset($website->id)) {
            // Authentication failed
            Yii::$app->response->statusCode = 401;
            return [
                'status' => 'error',
                'message' => 'Authentication failed.',
            ];
        }

        $websiteId = $website->id;

        // Define date ranges
        $last30Days = date('Y-m-d H:i:s', strtotime('-30 days'));
        $previous30Days = date('Y-m-d H:i:s', strtotime('-60 days'));

        // Calculate total bookings for the last 30 days
        $totalBookingsLast30Days = Bookings::find()
            ->where(['website_id' => $websiteId])
            ->andWhere(['>=', 'start_time', $last30Days])
            ->count();

        // Calculate total bookings for the previous 30 days
        $totalBookingsPrevious30Days = Bookings::find()
            ->where(['website_id' => $websiteId])
            ->andWhere(['>=', 'start_time', $previous30Days])
            ->andWhere(['<', 'start_time', $last30Days])
            ->count();

        // Calculate percentage change correctly
        if ($totalBookingsPrevious30Days > 0) {
            $difference = $totalBookingsLast30Days - $totalBookingsPrevious30Days;
            $bookingChangePercentage = round(($difference / $totalBookingsPrevious30Days) * 100, 2);
        } else {
            $bookingChangePercentage = $totalBookingsLast30Days > 0 ? null : 0;
        }

        // Most selling time (hour)
        $mostSellingTime = Bookings::find()
            ->select(['HOUR(start_time) as hour', 'COUNT(*) as count'])
            ->where(['website_id' => $websiteId])
            ->andWhere(['>=', 'start_time', $last30Days])
            ->groupBy(['HOUR(start_time)'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->one();
        $mostSellingTime['percentage'] = round(($mostSellingTime['count'] / $totalBookingsLast30Days) * 100, 2);

        // Most selling day
        $mostSellingDay = Bookings::find()
            ->select(['DAYNAME(booking_date) as day', 'COUNT(*) as count'])
            ->where(['website_id' => $websiteId])
            ->andWhere(['>=', 'booking_date', $last30Days])
            ->groupBy(['DAYNAME(booking_date)'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->one();

        // Calculate the percentage
        $mostSellingDay['percentage'] = round(($mostSellingDay['count'] / $totalBookingsLast30Days) * 100, 2);

        // Most selling duration
        $mostSellingDuration = Bookings::find()
            ->select(['TIMESTAMPDIFF(MINUTE, start_time, end_time) as duration', 'COUNT(*) as count'])
            ->where(['website_id' => $websiteId])
            ->andWhere(['>=', 'start_time', $last30Days])
            ->groupBy(['duration'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->one();
        $mostSellingDuration['percentage'] = round(($mostSellingDuration['count'] / $totalBookingsLast30Days) * 100, 2);

        // Most selling service
        $mostSellingService = Bookings::find()
            ->select(['service_name', 'COUNT(*) as count'])
            ->where(['website_id' => $websiteId])
            ->andWhere(['>=', 'start_time', $last30Days])
            ->groupBy(['service_name'])
            ->orderBy(['count' => SORT_DESC])
            ->asArray()
            ->one();
        $mostSellingService['percentage'] = round(($mostSellingService['count'] / $totalBookingsLast30Days) * 100, 2);

        // Return clients with name
        $returnClientsData = Bookings::find()
            ->select(['customer_contact', 'customer_name', 'COUNT(*) as count'])
            ->where(['website_id' => $websiteId])
            ->andWhere(['>=', 'start_time', $last30Days])
            ->groupBy(['customer_contact', 'customer_name']) // Group by contact and name
            ->having(['>', 'COUNT(*)', 1])
            ->asArray()
            ->all();

        // Calculate total return clients
        $returnClientsNumber = count($returnClientsData);

        // Format return clients
        $returnClientsList = array_map(function ($client) {
            return [
                'customerContact' => $client['customer_contact'],
                'customerName' => $client['customer_name'], // Add the name here
                'bookings' => $client['count']
            ];
        }, $returnClientsData);

        // Build response
        return [
            'status' => 'success',
            'data' => [
                'totalBookings' => [
                    'count' => $totalBookingsLast30Days,
                    'percentage' => $bookingChangePercentage,
                ],
                'mostSellingTime' => [
                    'hour' => $mostSellingTime['hour'],
                    'count' => $mostSellingTime['count'],
                    'percentage' => $mostSellingTime['percentage'],
                ],
                'mostSellingDay' => [
                    'day' => $mostSellingDay['day'],
                    'count' => $mostSellingDay['count'],
                    'percentage' => $mostSellingDay['percentage'],
                ],
                'mostSellingDuration' => [
                    'durationMinutes' => $mostSellingDuration['duration'],
                    'count' => $mostSellingDuration['count'],
                    'percentage' => $mostSellingDuration['percentage'],
                ],
                'mostSellingService' => [
                    'serviceName' => $mostSellingService['service_name'],
                    'count' => $mostSellingService['count'],
                    'percentage' => $mostSellingService['percentage'],
                ],
                'returnClients' => [
                    'count' => $returnClientsNumber,
                    'details' => $returnClientsList,
                ],
            ],
        ];
    }

}
