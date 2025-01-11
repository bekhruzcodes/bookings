<?php

namespace app\modules\v1\controllers;

use app\modules\v1\services\StatisticsService;
use app\modules\v1\traits\ResponseHelperTrait;
use Yii;
use yii\db\Exception;
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
    use ResponseHelperTrait;

    // Include the trait

    public $modelClass = Bookings::class;

    /**
     * Attach Bearer Token Authentication and CORS
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = $this->corsSettings();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;
        $behaviors['authenticator'] = ['class' => CustomBearerAuth::class];

        return $behaviors;
    }

    /**
     * CORS Settings
     */
    private function corsSettings(): array
    {
        return [
            'class' => Cors::class,
            'cors' => [
                'Origin' => ['*'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'Access-Control-Allow-Credentials' => false,
                'Access-Control-Max-Age' => 3600,
                'Access-Control-Allow-Headers' => ['Authorization', 'Content-Type'],
            ],
        ];
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
        $actions['index']['prepareDataProvider'] = fn() => $this->prepareDataProvider();
        unset($actions['create'], $actions['options']);
        return $actions;
    }

    /**
     * Prepares the data provider for the index action.
     * @throws UnauthorizedHttpException
     */
    private function prepareDataProvider(): array
    {
        $website = $this->getAuthenticatedWebsite();
        $query = Bookings::find()->andWhere(['website_id' => $website->id]);

// Optimizing pagination logic
        $pagination = $this->getPagination($query);

// Use pagination to get models
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => $pagination,
        ]);

        return [
            '_meta' => $this->getMetaData($pagination),
            '_links' => $this->getLinks($pagination),
            'data' => $dataProvider->getModels(),
        ];
    }

    /**
     * Get authenticated website.
     * @throws UnauthorizedHttpException
     */
    private function getAuthenticatedWebsite()
    {
        $website = Yii::$app->user->identity;
        if (!$website || !isset($website->id)) {
            throw new UnauthorizedHttpException('Authentication failed.');
        }
        return $website;
    }

    /**
     * Get pagination settings.
     */
    private function getPagination($query): Pagination
    {
        $totalCount = $query->count();
        return new Pagination([
            'totalCount' => $totalCount,
            'pageSize' => Yii::$app->request->get('per-page', 10),
            'pageSizeLimit' => [1, 100],
        ]);
    }

    /**
     * Get metadata for the response.
     */
    private function getMetaData(Pagination $pagination): array
    {
        return [
            'totalCount' => $pagination->totalCount,
            'pageCount' => $pagination->getPageCount(),
            'currentPage' => $pagination->getPage() + 1,
            'perPage' => $pagination->getPageSize(),
        ];
    }

    /**
     * Get links for pagination.
     */
    private function getLinks(Pagination $pagination): array
    {
        return [
            'self' => Url::to(['index', 'page' => $pagination->getPage() + 1, 'per-page' => $pagination->getPageSize()], true),
            'next' => $pagination->getPage() + 1 < $pagination->getPageCount() ? Url::to(['index', 'page' => $pagination->getPage() + 2, 'per-page' => $pagination->getPageSize()], true) : null,
            'prev' => $pagination->getPage() > 0 ? Url::to(['index', 'page' => $pagination->getPage(), 'per-page' => $pagination->getPageSize()], true) : null,
        ];
    }

    /**
     * Handle the creation action.
     * @throws UnauthorizedHttpException|Exception
     */
    public function actionCreate(): array
    {
        $website = $this->getAuthenticatedWebsite();
        $model = new Bookings();

        if ($model->load(Yii::$app->request->post(), '')) {
            $model->website_id = $website->id;
            if ($model->save()) {
                return $this->createdResponse($model);
            }
            return $this->unprocessableEntity($model);
        }

        return $this->badRequest();
    }

    /**
     * Handle statistics request.
     * @throws UnauthorizedHttpException
     */
    public function actionStatistics(): array
    {
        $website = $this->getAuthenticatedWebsite();
        $service = new StatisticsService($website->id);
        return $this->successResponse($service->getStatistics());
    }
}
