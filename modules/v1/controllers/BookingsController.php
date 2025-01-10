<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use yii\data\Pagination;
use yii\filters\Cors;
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


    public function actionStatistics(): array
    {
        try {
            // Get the currently authenticated website
            $website = Yii::$app->user->identity;

            if (!$website || !isset($website->id)) {
                Yii::$app->response->statusCode = 401;
                return [
                    'status' => 'error',
                    'message' => 'Authentication failed.',
                ];
            }

            $websiteId = $website->id;
            $last30Days = date('Y-m-d', strtotime('-30 days')); // Remove H:i:s to match booking_date format
            $previous30Days = date('Y-m-d', strtotime('-60 days'));

            $totalBookingsLast30Days = (int)Bookings::find()
                ->where(['website_id' => $websiteId])
                ->andWhere(['>=', 'booking_date', $last30Days])
                ->count();

            $totalBookingsPrevious30Days = (int)Bookings::find()
                ->where(['website_id' => $websiteId])
                ->andWhere(['>=', 'booking_date', $previous30Days])
                ->andWhere(['<', 'booking_date', $last30Days])
                ->count();

            // Safe percentage calculation
            $bookingChangePercentage = 0;
            if ($totalBookingsPrevious30Days > 0) {
                $difference = $totalBookingsLast30Days - $totalBookingsPrevious30Days;
                $bookingChangePercentage = round(($difference / $totalBookingsPrevious30Days) * 100, 2);
            } elseif ($totalBookingsLast30Days > 0) {
                $bookingChangePercentage = 100; // Changed from null to 100% increase
            }


            $mostSellingTime = Bookings::find()
                ->select(['HOUR(TIME(start_time)) as hour', 'COUNT(*) as count'])
                ->where(['website_id' => $websiteId])
                ->andWhere(['>=', 'booking_date', $last30Days])
                ->groupBy(['HOUR(TIME(start_time))'])
                ->orderBy(['count' => SORT_DESC])
                ->asArray()
                ->one() ?: ['hour' => null, 'count' => 0];


            $mostSellingDay = Bookings::find()
                ->select(['DAYNAME(booking_date) as day', 'COUNT(*) as count'])
                ->where(['website_id' => $websiteId])
                ->andWhere(['>=', 'booking_date', $last30Days])
                ->groupBy(['DAYNAME(booking_date)'])
                ->orderBy(['count' => SORT_DESC])
                ->asArray()
                ->one() ?: ['day' => null, 'count' => 0];


            $mostSellingDuration = Bookings::find()
                ->select(['duration_minutes as duration', 'COUNT(*) as count'])
                ->where(['website_id' => $websiteId])
                ->andWhere(['>=', 'booking_date', $last30Days])
                ->groupBy(['duration_minutes'])
                ->orderBy(['count' => SORT_DESC])
                ->asArray()
                ->one() ?: ['duration' => null, 'count' => 0];


            $mostSellingService = Bookings::find()
                ->select(['service_name', 'COUNT(*) as count'])
                ->where(['website_id' => $websiteId])
                ->andWhere(['>=', 'booking_date', $last30Days])
                ->groupBy(['service_name'])
                ->orderBy(['count' => SORT_DESC])
                ->asArray()
                ->one() ?: ['service_name' => null, 'count' => 0];

            $calculatePercentage = function($count, $total) {
                return $total > 0 ? round(($count / $total) * 100, 2) : 0;
            };


            $returnClientsData = Bookings::find()
                ->select(['customer_contact', 'customer_name', 'COUNT(*) as count'])
                ->where(['website_id' => $websiteId])
                ->andWhere(['>=', 'booking_date', $last30Days])
                ->groupBy(['customer_contact', 'customer_name'])
                ->having(['>', 'COUNT(*)', 1])
                ->asArray()
                ->all();

            $returnClientsList = array_map(function ($client) {
                return [
                    'customerContact' => $client['customer_contact'] ?? '',
                    'customerName' => $client['customer_name'] ?? '',
                    'bookings' => (int)($client['count'] ?? 0)
                ];
            }, $returnClientsData ?: []);

            return [
                'status' => 'success',
                'data' => [
                    'totalBookings' => [
                        'count' => $totalBookingsLast30Days,
                        'percentage' => $bookingChangePercentage,
                    ],
                    'mostSellingTime' => [
                        'hour' => (int)$mostSellingTime['hour'],
                        'count' => (int)$mostSellingTime['count'],
                        'percentage' => $calculatePercentage($mostSellingTime['count'], $totalBookingsLast30Days),
                    ],
                    'mostSellingDay' => [
                        'day' => $mostSellingDay['day'],
                        'count' => (int)$mostSellingDay['count'],
                        'percentage' => $calculatePercentage($mostSellingDay['count'], $totalBookingsLast30Days),
                    ],
                    'mostSellingDuration' => [
                        'durationMinutes' => (int)$mostSellingDuration['duration'],
                        'count' => (int)$mostSellingDuration['count'],
                        'percentage' => $calculatePercentage($mostSellingDuration['count'], $totalBookingsLast30Days),
                    ],
                    'mostSellingService' => [
                        'serviceName' => $mostSellingService['service_name'],
                        'count' => (int)$mostSellingService['count'],
                        'percentage' => $calculatePercentage($mostSellingService['count'], $totalBookingsLast30Days),
                    ],
                    'returnClients' => [
                        'count' => count($returnClientsList),
                        'details' => $returnClientsList,
                    ],
                ],
            ];
        } catch (\Exception $e) {
            Yii::error("Statistics calculation error: " . $e->getMessage());
            Yii::$app->response->statusCode = 500;
            return [
                'status' => 'error',
                'message' => 'An error occurred while calculating statistics.',
            ];
        }
    }
}
